<?php
function log_message($message) {
    $log_file = '/var/log/loan_system/loan_system.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

