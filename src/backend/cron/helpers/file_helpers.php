<?php
require_once 'log_helpers.php';

define('FILE_STORAGE', '/var/www/loan_system_files');
$SCRIPT_NAME = basename(__FILE__);

function write_file($loan_id, $filename, $contents) {
    global $SCRIPT_NAME;
    $loan_dir = FILE_STORAGE . "/$loan_id";

    if (!is_dir($loan_dir)) {
        mkdir($loan_dir, 0750, true);
    }

    $file_path = "$loan_dir/$filename";
    file_put_contents($file_path, $contents);

    log_message("File written: $file_path", $SCRIPT_NAME);
}

function zip_loan($loan_id) {
    global $SCRIPT_NAME;
    $loan_dir = FILE_STORAGE . "/$loan_id";
    $zip_file = FILE_STORAGE . "/$loan_id.zip";

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        foreach (glob("$loan_dir/*.pdf") as $f) {
            $zip->addFile($f, basename($f));
        }
        $zip->close();
        log_message("Loan $loan_id zipped to $zip_file", $SCRIPT_NAME);
    } else {
        log_message("ERROR: Could not create zip for loan $loan_id", $SCRIPT_NAME);
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
    $clean_doctype = preg_replace('/_\d+$/', '', $doctype);

    // Check if the doctype exists
    $select_stmt = $dblink->prepare("SELECT doctype_id FROM document_types WHERE doctype = ?");
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error, $SCRIPT_NAME);
        return;
    }

    try {
        $select_stmt->bind_param("s", $clean_doctype);
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR] Failed to execute SELECT statement - " . $select_stmt->error, $SCRIPT_NAME);
            return;
        }

        $select_stmt->store_result();
        $exists = $select_stmt->num_rows > 0;
    } finally {
        $select_stmt->close();
    }

    if ($exists) {
        log_message("[doctypes] Doctype already exists: $clean_doctype", $SCRIPT_NAME);
        return;
    }

    // Insert new doctype
    $insert_stmt = $dblink->prepare("INSERT INTO document_types (doctype) VALUES (?)");
    if (!$insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT statement - " . $dblink->error, $SCRIPT_NAME);
        return;
    }

    try {
        $insert_stmt->bind_param("s", $clean_doctype);
        if ($insert_stmt->execute()) {
            log_message("[doctypes] Added new doctype: $clean_doctype", $SCRIPT_NAME);
        } else {
            log_message("[DB ERROR][doctypes] Failed to insert $clean_doctype: " . $insert_stmt->error, $SCRIPT_NAME);
        }
    } finally {
        $insert_stmt->close();
    }
}

