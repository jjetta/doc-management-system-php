<?php
require_once '../helpers/api_helpers.php';
require_once '../helpers/file_helpers.php';
require_once '../config/db.php';
require_once '../helpers/log_helpers.php';

echo "[CRON]: starting download_files script";

$dblink = get_dblink();

$result = $dblink->query("SELECT * FROM documents WHERE status='pending'");

while ($file = $result->fetch_assoc()) {
    $data = "sid={$file['sid']}&uid={$file['username']}&fid={$file['fid']}";
    $contents = api_call('request_file', $data);
    write_file($file['loan_id'], $file['filename'], $contents);
    $dblink->query("UPDATE documents SET status='downloaded' WHERE id={$file['id']}");
}
echo "[CRON]: download_files script finished";
