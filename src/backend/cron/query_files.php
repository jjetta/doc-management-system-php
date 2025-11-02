<?php
require_once '../helpers/api_helpers.php';
require_once '../config/db.php';
require_once '../helpers/log_helpers.php';

$sid = get_latest_session_id();
$username = getenv('API_USER');

$data = "uid=$username&sid=$sid";
$info = api_call('query_files', $data);
$files = generate_files($info);

if (empty($files)) {
    log_message("[query_files] No files returned by API");
    exit;
}

$dblink = get_dblink();

foreach ($files as $file) {

    // Extract loan number, document type, and timestamp
    list($loan_number, $doctype, $timestamp) = explode("-", $file);
    if (!str_ends_with($file, ".pdf")) { // might need extra checks to verify that it's a pdf
        log_message("[query_files] Skipping invalid filename format: $file");
        continue;
    }

    // Query for loan_id using the loan_number
    $stmt = $dblink->prepare("SELECT id FROM loans WHERE loan_number = ?");
    $stmt->bind_param("s", $loan_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $loan_id = $row['id'];

        // Insert the document record
        $insert = $dblink->prepare("INSERT INTO documents (loan_id, file_name, status) VALUES (?, ?, 'pending')");
        $insert->bind_param("is", $loan_id, $file);
        $insert->execute();

        log_message("[query_files] Document queued for download: $file (Loan ID: $loan_id)");
    } else {
        log_message("[query_files] Loan number not found: $loan_number (File: $file)");
    }

    $stmt->close();
}

log_message("[query_files] Completed document queueing process.");
