<?php
require_once __DIR__ . '../helpers/api_helpers.php';
require_once __DIR__ . '../helpers/file_helpers.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '../helpers/log_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$sid = get_latest_session_id();
$username = getenv('API_USER');

$dblink = get_dblink();

// get the top 100 docs from the db whose status is pending and store them in an array
$pending_docs = get_pending_docs($dblink);

foreach ($pending_docs as $document_id => $filename) {
    $data = "sid=$sid&uid=$username&fid=$filename";
    $content = api_call('request_file', $data);

    if (mime_type_check($content)) {
        write_file_to_db($dblink, $document_id, $content);
    } else {
        log_message("Invalid file type. MIME type is not pdf.", $SCRIPT_NAME);
    }
}
