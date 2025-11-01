<?php
require_once '../helpers/api_helpers.php';
require_once '../config/db.php';
require_once '../helpers/log_helpers.php';

$sid = get_latest_session_id();
$username = getenv('API_USER');

$data = "uid=$username&sid=$sid";
$info = api_call('query_files', $data);

if (empty($info['files'])) {
    log_message("[query_files] No files returned by API");
    exit;
}

foreach ($info['files'] as $file) {
    $filename = $file['filename'];

    // Extract loan number (before the first hyphen)
    $parts = explode('-', $filename, 3);
    if (count($parts) < 2) {
        log_message("[query_files] Skipping invalid filename format: $filename");
        continue;
    }

    $loan_number = $parts[0];

    // Query for loan_id using the loan_number
    $stmt = $mysqli->prepare("SELECT id FROM loans WHERE loan_number = ?");
    $stmt->bind_param("s", $loan_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $loan_id = $row['id'];

        // Insert the document record
        $insert = $mysqli->prepare("INSERT INTO documents (loan_id, filename, status) VALUES (?, ?, 'pending')");
        $insert->bind_param("is", $loan_id, $filename);
        $insert->execute();

        log_message("[query_files] Document queued: $filename (Loan ID $loan_id)");
    } else {
        log_message("[query_files] Loan number not found: $loan_number (File: $filename)");
    }

    $stmt->close();
}

log_message("[query_files] Completed document queueing process.");
?>

