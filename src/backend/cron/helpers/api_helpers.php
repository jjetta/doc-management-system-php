<?php
require_once __DIR__ . '/../../config/db.php';
require_once 'log_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$dblink = get_dblink();
$api_url = 'https://cs4743.professorvaladez.com/api/';

function api_call($endpoint, $data, $binary = false) {
    global $api_url;
    global $SCRIPT_NAME;

    log_message("Calling endpoint: " . $api_url . $endpoint, $SCRIPT_NAME);

    $ch = curl_init($api_url . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        log_message("CURL ERROR: " . curl_error($ch), $SCRIPT_NAME);
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($binary) { // Not decoding because this would be raw pdf data
        return $response;
    }

    $response_info = json_decode($response);

    if (json_last_error() !== JSON_ERROR_NONE) {
        log_message("Invalid JSON response: " . json_last_error_msg(), $SCRIPT_NAME);
        return false;
    }

    log_message("[API RESPONSE]", $SCRIPT_NAME);
    log_message($response_info[0], $SCRIPT_NAME);
    log_message($response_info[1], $SCRIPT_NAME);
    log_message($response_info[2], $SCRIPT_NAME);
    echo "------------------------------------------------\n";

    return $response_info;
}

