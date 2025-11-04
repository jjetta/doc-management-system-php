<?php
require_once __DIR__ . '../helpers/api_helpers.php';

$sid = get_latest_session_id();
$data = "sid=$sid";

api_call('close_session', $data);
expire_session($sid);

