<?php
require_once __DIR__ . '/helpers/api_helpers.php';

echo "[CRON]: starting clear_session script";

$username = getenv('API_USER');
$password = getenv('API_PASS');

$data = "username=$username&password=$password";
api_call('clear_session', $data);

echo "[CRON]: clear_session finished";
