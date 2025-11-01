<?php
require_once '../helpers/api_helpers.php';
require_once '../helpers/file_helpers.php';
require_once '../config/db.php';
require_once '../helpers/log_helpers.php';

$result = $mysqli->query("SELECT * FROM documents WHERE status='pending'");
while ($file = $result->fetch_assoc()) {
    $data = "sid={$file['sid']}&uid={$file['username']}&fid={$file['fid']}";
    $contents = api_call('request_file', $data);
    write_file($file['loan_id'], $file['filename'], $contents);
    $mysqli->query("UPDATE documents SET status='downloaded' WHERE id={$file['id']}");
}
?>

