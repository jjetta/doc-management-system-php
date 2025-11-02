<?php
require_once __DIR__ . '/helpers/api_helpers.php';

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = "username=$username&password=$password";
api_call('clear_session', $data);
