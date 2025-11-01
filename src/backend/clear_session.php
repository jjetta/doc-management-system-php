<?php
require 'routes.php';

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = "username=$username&password=$password";
api_call('clear_session', $data);
?>
