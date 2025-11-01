<?php
require 'helpers.php';

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = "username=$username&password=$password";

$info = api_call('create_session', $data);

if ($info[0] == "Status: OK") {
    $session_id = $info[2];
    
}
?>
