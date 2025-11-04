<?php
require_once __DIR__ . '../helpers/api_helpers.php';
require_once __DIR__ . '../helpers/file_helpers.php';
require_once __DIR__ . '../config/db.php';
require_once __DIR__ . '../helpers/log_helpers.php';

$dblink = get_dblink();

$result = $dblink->query("SELECT * FROM documents WHERE status='pending'");

while ($file = $result->fetch_assoc()) {
    $data = "sid={$file['sid']}&uid={$file['username']}&fid={$file['fid']}";
    $contents = api_call('request_file', $data);
    write_file($file['loan_id'], $file['filename'], $contents);
    $dblink->query("UPDATE documents SET status='downloaded' WHERE id={$file['id']}");
}
