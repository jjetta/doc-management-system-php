<?php
require_once __DIR__ . '/../helpers/api_helpers.php';

$sid = get_latest_session_id2();
$data = http_build_query([
    'sid' => $sid
]);

api_call('close_session', $data);
db_close_session($sid);

