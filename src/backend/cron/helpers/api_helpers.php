<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/log_helpers.php';
require_once __DIR__ . '/db_helpers.php';

define('API_URL', 'https://cs4743.professorvaladez.com/api/');

$script_name = basename(__FILE__);

$dblink = get_dblink();

function api_call($endpoint, $data, $octet = false) {
    log_message("Calling endpoint: " . API_URL . $endpoint);

    $ch = curl_init(API_URL . $endpoint);
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
        log_message("CURL ERROR: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($octet) { // Not decoding because this would be raw pdf data
        return $response;
    }

    $response_info = json_decode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_message("Invalid JSON response: " . json_last_error_msg());
        return false;
    }

    log_message("[API RESPONSE]");
    foreach ($response_info as $info) {
        log_message($info);
    }
    echo str_repeat("-", 50) . "\n";

    return $response_info;
}

function reconnect($dblink) {

    $username = getenv('API_USER');
    $password = getenv('API_PASS');

    $data = http_build_query([
        'username' => $username,
        'password' => $password
    ]);

    //clear session
    log_message("[RECONNECT] Clearing session...");
    $clear_session_response = api_call('clear_session', $data);

    //verify status ok
    list($status, $msg, $session_id) = $clear_session_response;
    if ($clear_session_response[0] === 'Status: OK') {
        $session_id = $clear_session_response[2];
        db_save_session($dblink, $session_id);
    }
    //maybe return a bool? or the new session id?
}

