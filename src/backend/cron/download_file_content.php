<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/helpers/file_helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/log_helpers.php';
require_once __DIR__ . '/helpers/db_helpers.php';

$script_name = basename(__FILE__);

$dblink = get_dblink();

$sid = get_latest_session_id2($dblink);
$username = getenv('API_USER');


// get the top 100 docs from the db whose status is pending or failed and 
// store them in an associative array where document_id => filename
$pending_docs = get_pending_docs($dblink);

if (empty($pending_docs)) {
    log_message("No documents are pending for download. All good.");
    exit(0);
}

log_message("Downloading files...");
log_message("--------------------------------------------");
foreach ($pending_docs as $document_id => $filename) {
    $data = http_build_query([
        'sid' => $sid,
        'uid' => $username,
        'fid' => $filename
    ]);

    $content = api_call('request_file', $data, true);

    if (!$content) {
        log_message("[WARN] File content not received.");
        fail_doc_status($dblink, $document_id);
        continue;
    }

    $mime_type = get_mime_type($content);
    if ($mime_type !== "application/pdf") {
        log_message("[WARN] Invalid MIME type: $mime_type");
        fail_doc_status($dblink, $document_id);
        continue;
    }

    write_file_to_db($dblink, $document_id, $content);
}
