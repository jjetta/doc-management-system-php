<?php
require_once '../helpers/api_helpers.php';

$sid = get_latest_session_id();
$data = "sid=$sid";
api_call('close_session', $data);
log_message("Session $sid closed");
?>

