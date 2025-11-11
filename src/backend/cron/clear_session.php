<?php
require_once __DIR__ . '/../helpers/api_helpers.php';
require_once '../config/db.php';

$dblink = get_dblink();

$username = getenv('API_USER');
$password = getenv('API_PASS');
$data = http_build_query([
    'username' => $username,
    'password' => $password
]);

api_call('clear_session', $data);
