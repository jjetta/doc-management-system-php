<?php
define('CRON_LOG', '/var/log/loan_system/cron.log');

function log_message($message, $script_name) {
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp][$script_name] $message\n";
    file_put_contents(CRON_LOG, $formatted,  FILE_APPEND);
}

