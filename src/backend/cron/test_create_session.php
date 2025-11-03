<?php
require_once '../helpers/api_helpers.php';

echo 'testing create session script';

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = "username=$username&password=$password";

$info = api_call('create_session', $data);

if ($info[0] === "Status: OK") {
    save_session($info[2]);
} else {
    log_message("[TEST] Failed to create session. $info[2]");
    if ($info[1] === "MSG: Previous Session Found"){
        api_call('clear_session', $data);

        log_message("Retrying create_session...");
        $retry_info = api_call('create_session', $data);
        save_session($retry_info[2]);
    }
}

