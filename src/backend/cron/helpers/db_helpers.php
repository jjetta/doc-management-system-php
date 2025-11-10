<?php
require_once 'log_helpers.php';
require_once __DIR__ . '/../../config/db.php';

$SCRIPT_NAME = basename(__FILE__);

function write_file_to_db($dblink, $document_id, $content) {
    global $SCRIPT_NAME;

    $clean_content = addslashes($content); //escape special characters; prep data for SQL insertion
    $size = strlen($clean_content);

    $insert_query = "INSERT INTO document_contents (document_id, content, file_size) VALUES (?, ?, ?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR][write_file_to_db] Failed to prepare INSERT statement - " . $dblink->error, $SCRIPT_NAME);
    }

    try {
        $insert_stmt->bind_param("isi", $document_id, $clean_content, $size);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][write_file_to_db] Failed to execute INSERT statement - " . $dblink->error, $SCRIPT_NAME);
        }

        log_message("[write_file_to_db] File successfully written to database for document #$document_id.", $SCRIPT_NAME);
    } finally {
        $insert_stmt->close();
    }
}

function save_doctype_if_new($dblink, $doctype) {
    global $SCRIPT_NAME;

    // Normalize the doctype: remove trailing "_{number}" if present
    $doctype = get_doctype_from_filename($doctype);

    // Check if the doctype exists
    $select_query = "SELECT doctype_id FROM document_types WHERE doctype = ?";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        $select_stmt->bind_param("s", $doctype);
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR] Failed to execute SELECT statement - " . $select_stmt->error, $SCRIPT_NAME);
            return null;
        }

        $doctype_id = null;
        $select_stmt->bind_result($doctype_id);
        if ($select_stmt->fetch()) {
            return $doctype_id;
        }
    } finally {
        $select_stmt->close();
    }

    // Insert new doctype
    $insert_stmt = $dblink->prepare("INSERT INTO document_types (doctype) VALUES (?)");
    if (!$insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        $insert_stmt->bind_param("s", $doctype);
        if ($insert_stmt->execute()) {
            $new_id = $dblink->insert_id;
            log_message("[doctypes] Added new doctype: $doctype", $SCRIPT_NAME);
            return new_id;
        } else {
            log_message("[DB ERROR][doctypes] Failed to insert $doctype: " . $insert_stmt->error, $SCRIPT_NAME);
            return null;
        }
    } finally {
        $insert_stmt->close();
    }
}

function save_file_metadata($dblink, $file_parts, $loan_id, $doctype_id) {
    global $SCRIPT_NAME;

    $mysql_ts = get_mysql_ts($file_parts[2]);

    $insert_query = "INSERT INTO documents (loan_id, doctype_id, uploaded_at, file_name) VALUES (?, ?, ?, ?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT statement - " . $dblink->error, $SCRIPT_NAME);
    }

    try {
        $insert_stmt->bind_param("iiss", $loan_id, $doctype_id, $mysql_ts, $file_parts[1]);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][save_file_metadata] Failed to execute INSERT - " . $dblink->error, $SCRIPT_NAME);
        }

        log_message("[save_file_metadata] Metadata saved for $file_parts[0]-$file_parts[1]-$file_parts[2]", $SCRIPT_NAME);
    } finally {
        if (isset($insert_stmt) && $insert_stmt instanceof mysqli_stmt) {
            $insert_stmt->close();
        }
    }
}

function ensure_loan_exists($dblink, $loan_number) {
    global $SCRIPT_NAME;

    // Check if the loan already exists
    $select_query = "SELECT loan_id FROM loans WHERE loan_number = ?";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        $select_stmt->bind_param("s", $loan_number);
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to execute SELECT statement - " . $dblink->error, $SCRIPT_NAME);
            return null;
        }

        $result = $select_stmt->get_result();
        if (!$result) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to get result - " . $dblink->error, $SCRIPT_NAME);
        }

        if ($row = $result->fetch_assoc()) {
            return $row['loan_id'];
        }

    } finally {
        if (isset($select_stmt) && $select_stmt instanceof mysqli_stmt) {
            $select_stmt->close();
        }
    }

    // Insert the loan if not found
    $insert_query = "INSERT INTO loans (loan_number) VALUES (?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        $insert_stmt->bind_param("s", $loan_number);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to execute INSERT -  " . $dblink->error, $SCRIPT_NAME);
            return null;
        }

        log_message("[INFO] New loan inserted: $loan_number", $SCRIPT_NAME);
        return $insert_stmt->insert_id;

    } finally {
        if (isset($insert_stmt) && $insert_stmt instanceof mysqli_stmt) {
            $insert_stmt->close();
        }
    }
}

function get_pending_docs($dblink) {
    global $SCRIPT_NAME;

    $select_query = "
        SELECT d.document_id, l.loan_number, d.file_name, d.uploaded_at
        FROM documents d
        JOIN loans l ON d.loan_id = l.loan_id
        WHERE d.status = 'pending'
        ORDER BY d.document_id
        LIMIT 100
    ";

    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR][get_pending_docs] Failed to execute SELECT statement - " . $dblink->error, $SCRIPT_NAME);
            return null;
        }

        $result = $select_stmt->get_result();
        if (!$result) {
            log_message("[DB ERROR] Failed to get result - " . $dblink->error, $SCRIPT_NAME);
        }

        $pending_docs = [];
        while ($row = $result->fetch_assoc()) {
            $uploaded_at = date('Ymd_H_i_s', strtotime($row['uploaded_at']));
            $filename = "{$row['loan_number']}-{$row['file_name']}-{$uploaded_at}.pdf";
            $pending_docs[$row['document_id']] = $filename ;
        }

        return $pending_docs;

    } finally {
        $select_stmt->close();
    }
}

function fail_file_status($dblink, $document_id) {
    global $SCRIPT_NAME;

    $update_query = "UPDATE documents SET file_status = 'failed' WHERE document_id = ?";
    $update_stmt = $dblink->prepare($update_query);
    if (!$update_stmt) {
        log_message("[DB ERROR] Failed to prepare UPDATE - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }
}

function db_save_session($dblink, $sid) {
    global $SCRIPT_NAME;

    log_message("Saving session...", $SCRIPT_NAME);

    $stmt = $dblink->prepare("INSERT INTO api_sessions (session_id) VALUES (?)");
    if (!$stmt) {
        log_message("[DB ERROR] Failed to prepare statement - " . $dblink->error, $SCRIPT_NAME);
        return false;
    }

    try {
        $stmt->bind_param("s", $sid);
        if (!$stmt->execute()) {
            log_message("[DB ERROR] Failed to save session $sid - " . $stmt->error, $SCRIPT_NAME);
            return false;
        } else {
            log_message("Session saved: $sid", $SCRIPT_NAME);
            return true;
        }
    } finally {
        $stmt->close();
    }
}

function db_close_session($dblink, $sid) {
    global $SCRIPT_NAME;

    log_message("Updating session status in db...", $SCRIPT_NAME);

    $update_query = "UPDATE api_sessions SET closed_at = NOW() WHERE session_id = ?";
    $update_stmt = $dblink->prepare($update_query);
    if (!$update_stmt) {
        log_message("[DB ERROR] Failed to prepare update statement - " . $dblink->error, $SCRIPT_NAME);
        return false;
    }

    try {
        $update_stmt->bind_param("s", $sid);
        if (!$update_stmt->execute()) {
            log_message("[DB ERROR] Failed to update session $sid close - " . $update_stmt->error, $SCRIPT_NAME);
            return false;
        } else {
            log_message("Session closed: $sid", $SCRIPT_NAME);
            return true;
        }
    } finally {
        $update_stmt->close();
    }

}

function get_latest_session_id2($dblink) {
    global $SCRIPT_NAME;

    log_message("Fetching latest session id...", $SCRIPT_NAME);

    $select_query = "SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR] Failed to execute SELECT statement - " . $dblink->error, $SCRIPT_NAME);
            return null;
        }

        $latest_session_id = null;
        $select_stmt->bind_result($latest_session_id);
        if (!$select_stmt->fetch()) {
            log_message("No sessions found in api_sessions table.", $SCRIPT_NAME);
            return null;
        }

        log_message("Latest session ID found: $latest_session_id", $SCRIPT_NAME);
        return $latest_session_id;
    } finally {
        $select_stmt->close();
    }
}
