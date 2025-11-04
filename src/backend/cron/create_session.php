<?php
require_once __DIR__ . '/helpers/api_helpers.php';
$SCRIPT_NAME = basename(__FILE__);
echo "[DEBUG] create_session.php is running\n";

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = "username=$username&password=$password";

$info = api_call('create_session', $data);

if ($info[0] === "Status: OK") {
    save_session($info[2]);
} else {
    log_message("Failed to create session. $info[2]", $SCRIPT_NAME);
    if ($info[1] === "MSG: Previous Session Found"){
        api_call('clear_session', $data);

        log_message("Retrying create_session...", $SCRIPT_NAME);
        $retry_info = api_call('create_session', $data);
        save_session($retry_info[2]);
    }
}

