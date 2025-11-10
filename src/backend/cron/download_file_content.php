<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/helpers/file_helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/log_helpers.php';
require_once __DIR__ . '/helpers/db_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$dblink = get_dblink();

$sid = get_latest_session_id2($dblink);
$username = getenv('API_USER');


// get the top 100 docs from the db whose status is pending and 
// store them in an associative array where document_id => filename
$pending_docs = get_pending_docs($dblink);

if (empty($pending_docs)) {
    log_message("No documents are pending for download. All good.", $SCRIPT_NAME);
    exit(0);
}

log_message("Downloading files...", $SCRIPT_NAME);
log_message("--------------------------------------------", $SCRIPT_NAME);
foreach ($pending_docs as $document_id => $filename) {
    $data = http_build_query([
        'sid' => $sid,
        'uid' => $username,
        'fid' => $filename
    ]);

    $content = api_call('request_file', $data, true);

    if (!$content) {
        log_message("[WARN] File content not received.", $SCRIPT_NAME);
        continue;
    }

    $mime_type = get_mime_type($content);
    if ($mime_type !== "application/pdf") {
        log_message("[WARN] Invalid file type: $mime_type", $SCRIPT_NAME);
        continue;
    }

    write_file_to_db($dblink, $document_id, $content);
}
