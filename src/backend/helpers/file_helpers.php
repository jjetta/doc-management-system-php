<?php
require_once 'log_helpers.php';

define('FILE_STORAGE', '/var/www/loan_system_files');

function write_file($loan_id, $filename, $contents)
{
    $loan_dir = FILE_STORAGE . "/$loan_id";

    if (!is_dir($loan_dir)) {
        mkdir($loan_dir, 0750, true);
    }

    $file_path = "$loan_dir/$filename";
    file_put_contents($file_path, $contents);

    log_message("File written: $file_path");
}

function zip_loan($loan_id)
{
    $loan_dir = FILE_STORAGE . "/$loan_id";
    $zip_file = FILE_STORAGE . "/$loan_id.zip";

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        foreach (glob("$loan_dir/*.pdf") as $f) {
            $zip->addFile($f, basename($f));
        }
        $zip->close();
        log_message("Loan $loan_id zipped to $zip_file");
    } else {
        log_message("ERROR: Could not create zip for loan $loan_id");
    }
}

function generate_files($info)
{
    log_message("Generating files...");

    $tmp = explode(":", $info[1]);
    $files = json_decode($tmp[1]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        log_message("ERROR: Failed to decode file list. JSON error: " . json_last_error_msg());
        return [];
    }

    log_message("INFO: Number of files received: " . count($files));

    log_message("INFO: Files received: " . print_r($files, true));

    log_message("INFO: Starting file download process...");

    return $files;
}
