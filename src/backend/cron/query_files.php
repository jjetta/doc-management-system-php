<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/log_helpers.php';
require_once __DIR__ . '/helpers/file_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$sid = get_latest_session_id();
$username = getenv('API_USER');

$data = "uid=$username&sid=$sid";
$api_response = api_call('query_files', $data);
$files = generate_files($api_response);

if (empty($files)) {
    exit;
}

$dblink = get_dblink();

foreach ($files as $file) {

    // Extract loan number, document type, and timestamp
    $file_parts = explode("-", $file);

    // Validate filename format and type
    if (count($file_parts) !== 3 || !str_ends_with($file, "pdf")) {
        log_message("Skipping invalid filename: $file", $SCRIPT_NAME);
        continue;
    }

    list($loan_number, $doctype, $timestamp) = $file_parts;

    // Query for loan_id using the loan_number; needed when inserting metadata into documents table
    $query = "SELECT loan_id FROM loans WHERE loan_number = ?";
    $stmt = $dblink->prepare($query);
    if (!$stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement for $loan_number - " . $dblink->error, $SCRIPT_NAME);
        continue;
    }

    try {
        $stmt->bind_param("s", $loan_number);
        if (!$stmt->execute()) {
            log_message("[DB ERROR] Failed to execute SELECT for $loan_number - " . $dblink->error, $SCRIPT_NAME);
            continue;
        }
        $result = $stmt->get_result();
        $row = result->fetch_assoc();
        $result->close();

        if (!$row || !isset($row['id'])) {
            log_message("[query_files] Loan number not found: $loan_number (File: $file)", $SCRIPT_NAME);
            continue;
        }

        $loan_id = $row['loan_id'];

    } finally {
        $stmt->close();
    }

    // Update document_types table
    save_doctype_if_new($dblink, $doctype);

    // update document metadata in database
    $query = "INSERT INTO documents (loan_id, file_name, status) VALUES (?, ?, pending)";
    $insert_stmt = $dblink->prepare($query);
    if (!insert_stmt) {
        log_message("[DB ERROR] Failed to prepare INSERT statement for file $file - " . $insert_stmt->error, $SCRIPT_NAME);
        continue;
    }

    try {
        $insert_stmt->bind_param("is", $loan_id, $file);
        if (!$insert_stmt->execute()) {
            log_message("[DB ERROR]: Failed to insert document $file - " . $insert_stmt->error, $SCRIPT_NAME);
        } else {
            log_message("[query_files] Document queued for download: $file (Loan ID: $loan_id)", $SCRIPT_NAME);
        }
    } finally {
        $insert_stmt->close();
    }

}

log_message("[query_files] Completed document queueing process.", $SCRIPT_NAME);

