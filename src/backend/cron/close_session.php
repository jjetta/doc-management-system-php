<?php
require_once '../helpers/api_helpers.php';

echo "[CRON]: starting close_session script";

$sid = get_latest_session_id();
$data = "sid=$sid";

api_call('close_session', $data);
log_message("Session $sid closed");

echo "[CRON]: close_session finished";
