<?php
require_once __DIR__ . '/helpers/api_helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/log_helpers.php';
require_once __DIR__ . '/helpers/file_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$files = require_once '../cron/query_files.php';

$dblink = get_dblink();

foreach ($files as $file) {

}

log_message("[query_files] Completed document queueing process.", $SCRIPT_NAME);

