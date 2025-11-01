<?php
require_once '../helpers/api_helpers.php';

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = "username=$username&password=$password";

$info = api_call('create_session', $data);

if ($info[0] === "Status: OK") {
    log_message("Session $info[2] successfully created.")
    save_session($info[2]);
} else {
    log_message("Failed to create session. $info[2]");
    if ($info[1] === "MSG: Previous Session Found"){
        api_call('clear_session', $data);
        api_call('create_session', $data);
    }
}
?>

