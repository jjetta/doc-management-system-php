<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/helpers/db_helpers.php';
require_once __DIR__ . '/../config/db.php';

$script_name = basename(__FILE__);

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = http_build_query([
    'username' => $username,
    'password' => $password
]);

$dblink = get_dblink();

$api_response = api_call('create_session', $data);

if ($api_response[0] !== "Status: OK") {
    log_message("Failed to create session. $api_response[2]");

    if ($api_response[1] === "MSG: Previous Session Found" || $api_response[1] === "MSG: SID not found") {
        api_call('clear_session', $data);

        log_message("[RETRY] Retrying create_session...");
        $api_response = api_call('create_session', $data);
    }
}

// Happy path at the bottom
if ($api_response[0] === "Status: OK") {
    db_save_session($dblink, $api_response[2]);
} else {
    log_message("[FATAL] Session creation ultimately failed.");
}

echo str_repeat("-", 100) . "\n";
