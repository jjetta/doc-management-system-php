<?php
require_once '../helpers/api_helpers.php';

echo "testing close session script";

$sid = get_latest_session_id();
$data = "sid=$sid";

api_call('close_session', $data);
expire_session($sid);

