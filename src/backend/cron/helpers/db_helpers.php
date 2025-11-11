<?php
require_once 'log_helpers.php';
require_once __DIR__ . '/../../config/db.php';

$script_name = basename(__FILE__);

function write_file_to_db($dblink, $document_id, $content) {
    $size = strlen($content);

    $insert_query = "INSERT INTO document_contents (document_id, content, size) VALUES (?, ?, ?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR][write_file_to_db] Failed to prepare INSERT statement - " . $dblink->error);
        return false;
    }

    try {
        $null = null;
        $insert_stmt->bind_param("ibi", $document_id, $null, $size);
        $insert_stmt->send_long_data(1, $content);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][write_file_to_db] Failed to execute INSERT statement - " . $dblink->error);
            return false;
        }

        log_message("[write_file_to_db] Document #$document_id successfully written to database.");
        return true;
    } finally {
        $insert_stmt->close();
    }
}

function get_or_create($dblink, $doctype) {

    // Normalize the doctype: remove trailing "_{number}" if present
    $doctype = get_doctype_from_filename($doctype);

    // Check if the doctype exists
    $select_query = "SELECT doctype_id FROM document_types WHERE doctype = ?";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR][get_or_create] Failed to prepare SELECT statement - " . $dblink->error);
        return null;
    }

    try {
        $select_stmt->bind_param("s", $doctype);
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR][get_or_create] Failed to execute SELECT statement - " . $select_stmt->error);
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
        log_message("[DB ERROR][get_or_create] Failed to prepare INSERT statement - " . $dblink->error);
        return null;
    }

    try {
        $insert_stmt->bind_param("s", $doctype);
        if ($insert_stmt->execute()) {
            $new_id = $dblink->insert_id;
            log_message("[get_or_create] Added new doctype: $doctype");
            return $new_id;
        } else {
            log_message("[DB ERROR][get_or_create] Failed to insert $doctype: " . $insert_stmt->error);
            return null;
        }
    } finally {
        $insert_stmt->close();
    }
}

function save_file_metadata($dblink, $file_parts, $loan_id, $doctype_id) {

    $mysql_ts = get_mysql_ts($file_parts[2]);

    $insert_query = "INSERT INTO documents (loan_id, doctype_id, uploaded_at, file_name) VALUES (?, ?, ?, ?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR][save_file_metadata] Failed to prepare INSERT statement - " . $dblink->error);
    }

    try {
        $insert_stmt->bind_param("iiss", $loan_id, $doctype_id, $mysql_ts, $file_parts[1]);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][save_file_metadata] Failed to execute INSERT - " . $dblink->error);
        }

        log_message("[save_file_metadata] Metadata saved for $file_parts[0]-$file_parts[1]-$file_parts[2]");
    } finally {
            $insert_stmt->close();
    }
}

function ensure_loan_exists($dblink, $loan_number) {

    // Check if the loan already exists
    $select_query = "SELECT loan_id FROM loans WHERE loan_number = ?";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR][ensure_loan_exists] Failed to prepare SELECT statement - " . $dblink->error);
        return null;
    }

    try {
        $select_stmt->bind_param("s", $loan_number);
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to execute SELECT statement - " . $dblink->error);
            return null;
        }

        $result = $select_stmt->get_result();
        if (!$result) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to get result - " . $dblink->error);
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
        log_message("[DB ERROR] Failed to prepare INSERT - " . $dblink->error);
        return null;
    }

    try {
        $insert_stmt->bind_param("s", $loan_number);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][ensure_loan_exists] Failed to execute INSERT -  " . $dblink->error);
            return null;
        }

        log_message("[INFO] New loan inserted: $loan_number");
        return $insert_stmt->insert_id;

    } finally {
        if (isset($insert_stmt) && $insert_stmt instanceof mysqli_stmt) {
            $insert_stmt->close();
        }
    }
}

function get_pending_docs($dblink) {

    $select_query = "
        SELECT d.document_id, l.loan_number, d.file_name, d.uploaded_at
        FROM documents d
        JOIN loans l ON d.loan_id = l.loan_id
        JOIN document_statuses s ON d.document_id = s.document_id
        WHERE s.status IN ('pending', 'failed')
        ORDER BY d.document_id
        LIMIT 100
    ";

    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR][get_pending_docs] Failed to prepare SELECT - " . $dblink->error);
        return null;
    }

    try {
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR][get_pending_docs] Failed to execute SELECT statement - " . $dblink->error);
            return null;
        }

        $result = $select_stmt->get_result();
        if (!$result) {
            log_message("[DB ERROR][get_pending_docs] Failed to get result - " . $dblink->error);
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

function mark_as_failed($dblink, $document_id) {

    $update_query = "UPDATE document_statuses SET status = 'failed' WHERE document_id = ?";
    $update_stmt = $dblink->prepare($update_query);
    if (!$update_stmt) {
        log_message("[DB ERROR][fail_file_status] Failed to prepare UPDATE statement - " . $dblink->error);
    }

    try {
        $update_stmt->bind_param("i", $document_id);
        if (!$update_stmt->execute()) {
            log_message("[DB ERROR][fail_file_status] Failed to execute UPDATE statement - " . $dblink->error);
        }
    } finally {
        $update_stmt->close();
    }
}

function db_save_session($dblink, $sid) {

    log_message("Saving session...");

    $stmt = $dblink->prepare("INSERT INTO api_sessions (session_id) VALUES (?)");
    if (!$stmt) {
        log_message("[DB ERROR][db_save_session] Failed to prepare statement - " . $dblink->error);
        return false;
    }

    try {
        $stmt->bind_param("s", $sid);
        if (!$stmt->execute()) {
            log_message("[DB ERROR][db_save_session] Failed to save session $sid - " . $stmt->error);
            return false;
        } else {
            log_message("Session saved: $sid");
            return true;
        }
    } finally {
        $stmt->close();
    }
}

function db_close_session($dblink, $sid) {

    log_message("Updating session status in db...");

    $update_query = "UPDATE api_sessions SET closed_at = NOW() WHERE session_id = ?";
    $update_stmt = $dblink->prepare($update_query);
    if (!$update_stmt) {
        log_message("[DB ERROR][db_close_session] Failed to prepare update statement - " . $dblink->error);
        return false;
    }

    try {
        $update_stmt->bind_param("s", $sid);
        if (!$update_stmt->execute()) {
            log_message("[DB ERROR][db_close_session] Failed to update session $sid close - " . $update_stmt->error);
            return false;
        } else {
            log_message("Session closed: $sid");
            return true;
        }
    } finally {
        $update_stmt->close();
    }

}

function get_session($dblink) {

    log_message("Fetching latest session id...");

    $select_query = "SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR][get_session] Failed to prepare SELECT statement - " . $dblink->error);
        return null;
    }

    try {
       if (!$select_stmt->execute()) {
            log_message("[DB ERROR][get_session] Failed to execute SELECT statement - " . $dblink->error);
            return null;
        }

        $latest_session_id = null;
        $select_stmt->bind_result($latest_session_id);
        if (!$select_stmt->fetch()) {
            log_message("[get_session] No sessions found in api_sessions table.");
            return null;
        }

        log_message("[get_session] Latest session ID found: $latest_session_id");
        return $latest_session_id;
    } finally {
        $select_stmt->close();
    }
}
