<?php
require_once 'log_helpers.php';
require_once __DIR__ . '/../../config/db.php';

define('FILE_STORAGE', '/var/www/loan_system_files');
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

function generate_files($response_info) {
    global $SCRIPT_NAME;
    log_message("Generating files...", $SCRIPT_NAME);

    $tmp = explode(":", $response_info[1]);
    $files = json_decode($tmp[1]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        log_message("ERROR: Failed to decode file list. JSON error: " . json_last_error_msg(), $SCRIPT_NAME);
        return [];
    }

    if (empty($files)) {
        log_message("[query_files] No files returned by API", $SCRIPT_NAME);
    } else {
        log_message("[INFO]: Number of files received: " . count($files), $SCRIPT_NAME);
    }

    log_message("[INFO]: Files received: " . print_r($files, true), $SCRIPT_NAME);

    log_message("[INFO]: Starting file download process...", $SCRIPT_NAME);

    return $files;
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

function get_doctype_from_filename($doctype) {
    // Remove trailing underscore + number
    $doctype = preg_replace('/_\d+$/', '', $doctype);

    // Remove trailing 's' (case-insensitive)
    $doctype = preg_replace('/s$/i', '', $doctype);

    // Replace underscores inside the name with spaces
    $doctype = str_replace('_', ' ', $doctype);

    return $doctype;
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

function get_mysql_ts($raw_ts) {
    // Remove the file extension if present
    $raw_ts = pathinfo($raw_ts, PATHINFO_FILENAME);

    // Validate input
    if (!$raw_ts) {
        return null;
    }

    // Use regex to ensure correct format: YYYYMMDD_HH_MM_SS
    if (!preg_match('/^(\d{8})_(\d{2})_(\d{2})_(\d{2})$/', $raw_ts, $matches)) {
        return null; // invalid format
    }

    $date_part = $matches[1]; // YYYYMMDD
    $hour = $matches[2];
    $minute = $matches[3];
    $second = $matches[4];

    // Build MySQL TIMESTAMP string
    $mysql_ts = substr($date_part, 0, 4) . '-' . substr($date_part, 4, 2) . '-' . substr($date_part, 6, 2)
                . ' ' . $hour . ':' . $minute . ':' . $second;

    return $mysql_ts;
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

function mime_type_check($content) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = finfo_buffer($finfo, $content);
    finfo_close($finfo);

    return $mimetype === "application/pdf";
}
