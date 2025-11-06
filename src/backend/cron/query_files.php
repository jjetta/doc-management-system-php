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

if ($api_response[1] == "MSG: No new files found" || $api_response[1] == "MSG: []") {
    log_message("[INFO] No files to query. Moving along.", $SCRIPT_NAME);
    exit(0);
}

$files = generate_files($api_response);

$dblink = get_dblink();

log_message("[INFO] Processing loan ids and document_types...", $SCRIPT_NAME);
foreach ($files as $file) {

    $file_parts = explode("-", $file);

    // Validate filename format and type
    if (count($file_parts) !== 3 || !str_ends_with($file, "pdf")) {
        log_message("Skipping invalid filename: $file", $SCRIPT_NAME);
        continue;
    }

    list($loan_number, $docname, $timestamp) = $file_parts;

    // Update document_types table
    $docname = $file_parts[1];
    $doctype_id = save_doctype_if_new($dblink, $docname);

    // Update loans table
    $loan_number = $file_parts[0];
    $loan_id = ensure_loan_exists($dblink, $loan_number);
    if ($loan_id === null) {
        log_message("[ERROR] Could not ensure loan exists for $loan_number", $SCRIPT_NAME);
    }

    // Update documents table with file metadata
    log_message("Saving file metadata... ", $SCRIPT_NAME);
    save_file_metadata($dblink, $file_parts, $loan_id, $doctype_id);
}
log_message("[INFO] Processing complete.", $SCRIPT_NAME );
log_message("----------------------------------------------", $SCRIPT_NAME);

return $files;
