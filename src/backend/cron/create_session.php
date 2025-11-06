<?php
require_once __DIR__ . '/helpers/api_helpers.php';
$SCRIPT_NAME = basename(__FILE__);

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = "username=$username&password=$password";

$api_response = api_call('create_session', $data);

if ($api_response[0] === "Status: OK") {
    save_session($api_response[2]);
} else {
    log_message("Failed to create session. $api_response[2]", $SCRIPT_NAME);
    if ($api_response[1] === "MSG: Previous Session Found"){
        api_call('clear_session', $data);

        log_message("Retrying create_session...", $SCRIPT_NAME);
        $retry_api_response = api_call('create_session', $data);
        save_session($retry_api_response[2]);
    }
}

