<?php
require_once __DIR__ . '/../helpers/api_helpers.php';
require_once '../config/db.php';

$dblink = get_dblink();

$sid = get_latest_session_id2($dblink);
$data = http_build_query([
    'sid' => $sid
]);

api_call('close_session', $data);
db_close_session($sid);

