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

$dblink = get_dblink();

log_message("[INFO] Inserting loan ids into database...", $SCRIPT_NAME);
foreach ($files as $file) {

    $file_parts = explode("-", $file);
    $loan_number = $file_parts[0];

    $loan_id = ensure_loan_exists($dblink, $loan_number);
    if ($loan_id === null) {
        log_message("[ERROR] Could not ensure loan exists for $loan_number", $SCRIPT_NAME);
        continue;
    }
}
log_message("[INFO] Loan number processing complete.", $SCRIPT_NAME );
log_message("----------------------------------------------", $SCRIPT_NAME);

