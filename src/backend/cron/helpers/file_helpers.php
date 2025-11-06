<?php
require_once 'log_helpers.php';
require_once __DIR__ . '/../../config/db.php';

define('FILE_STORAGE', '/var/www/loan_system_files');
$SCRIPT_NAME = basename(__FILE__);


function write_file_to_db($loan_id, $filename, $contents) {
    global $SCRIPT_NAME;
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

    log_message("[INFO]: Starting metadata saving process...", $SCRIPT_NAME);

    return $files;
}

function save_doctype_if_new($dblink, $docname) {
    global $SCRIPT_NAME;

    // Normalize the doctype: remove trailing "_{number}" if present
    $doctype = get_doctype_from_filename($docname);

    if (!$doctype) {
        log_message("[ERROR] Could not determine doctype for filename: $docname", $SCRIPT_NAME);
        return null;
    }

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

        $result = $select_stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Document type already exists
            return $row['doctype_id'];
        }

    } finally {
        if (isset($select_stmt) && $select_stmt instanceof mysqli_stmt) {
            $select_stmt->close();
        }
    }

    // Insert new doctype if new 
    $insert_query = "INSERT INTO document_types (doctype) VALUES (?)";
    $insert_stmt = $dblink->prepare($insert_query);
    if (!$insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        $insert_stmt->bind_param("s", $doctype);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR][doctypes] Failed to insert $doctype: " . $insert_stmt->error, $SCRIPT_NAME);
            return null;
        }

        log_message("[doctypes] Added new doctype: $doctype", $SCRIPT_NAME);
        return $insert_stmt->insert_id;

    } finally {
        if (isset($insert_stmt) && $insert_stmt instanceof mysqli_stmt) {
            $insert_stmt->close();
        }
    }
}

function extract_file_parts($file) {
    global $SCRIPT_NAME;
    // Extract loan number, document type, and timestamp
    $file_parts = explode("-", $file);

    // Validate filename format and type
    if (count($file_parts) !== 3 || !str_ends_with($file, "pdf")) {
        log_message("Skipping invalid filename: $file", $SCRIPT_NAME);
    }

    return $file_parts;

}

function get_doctype_from_filename($docname) {
    // Remove trailing underscore + number
    $docname = preg_replace('/_\d+$/', '', $docname);

    // Remove trailing 's' (case-insensitive)
    $docname = preg_replace('/s$/i', '', $docname);

    // Replace underscores inside the name with spaces
    $docname = str_replace('_', ' ', $docname);

    return $docname;
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
