<?php
require_once __DIR__ . '/helpers/api_helpers.php';
$SCRIPT_NAME = basename(__FILE__);

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = http_build_query([
    'username' => $username,
    'password' => $password
]);

$api_response = api_call('create_session', $data);

if ($api_response[0] === "Status: OK") {
    db_save_session($api_response[2]);
} else {
    log_message("Failed to create session. $api_response[2]", $SCRIPT_NAME);
    if ($api_response[1] === "MSG: Previous Session Found" || $api_response[1] === "MSG: SID not found") {
        api_call('clear_session', $data);

        log_message("[RETRY] Retrying create_session...", $SCRIPT_NAME);
        $api_response = api_call('create_session', $data);
        db_save_session($api_response[2]);
    }
}

