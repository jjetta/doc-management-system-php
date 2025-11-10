<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/log_helpers.php';
require_once __DIR__ . '/helpers/file_helpers.php';
require_once __DIR__ . '/helpers/db_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$dblink = get_dblink();

$sid = get_latest_session_id2($dblink);
$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = http_build_query([
    'uid' => $username,
    'sid' => $sid
]);

$query_files_response = api_call('query_files', $data);

// retry in the event our sid got kicked or a timeout
if (!$query_files_response || $query_files_response[1] === "MSG: SID not found") {
    log_message("[RETRY] Getting a new session_id...", $SCRIPT_NAME);

    $retry_data = http_build_query([
        'username' => $username,
        'password' => $password
    ]);
    api_call('clear_session', $retry_data);

    log_message("[RETRY] Retrying create_session...", $SCRIPT_NAME);
    $create_session_response = api_call('create_session', $retry_data);
    db_save_session($create_session_response[2]);

    $sid = get_latest_session_id2();
    $data = http_build_query([
        'uid' => $username,
        'sid' => $sid
    ]);

    $query_files_response = api_call('query_files', $data);
}

// if there's no files, just exit
if ($query_files_response[1] === "MSG: No new files found" || $query_files_response[1] === "MSG: []") {
    log_message("[INFO] No files to query. Moving along.", $SCRIPT_NAME);
    exit(0);
}

if ($query_files_response[0] === "Status: OK") {
    $files = generate_files($query_files_response);
} else {
    log_message("[ERROR] API returned unexpected status or format.", $SCRIPT_NAME);
    exit(1);
}

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
    save_file_metadata($dblink, $file_parts, $loan_id, $doctype_id);
}
log_message("[INFO] Processing complete.", $SCRIPT_NAME );
log_message("----------------------------------------------", $SCRIPT_NAME);

return $files;
