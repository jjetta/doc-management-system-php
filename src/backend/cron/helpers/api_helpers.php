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

function db_save_session($sid) {
    global $dblink;
    global $SCRIPT_NAME;

    log_message("Saving session...", $SCRIPT_NAME);

    $stmt = $dblink->prepare("INSERT INTO api_sessions (session_id) VALUES (?)");
    if (!$stmt) {
        log_message("[DB ERROR] Failed to prepare statement - " . $dblink->error, $SCRIPT_NAME);
        return false;
    }

    try {
        $stmt->bind_param("s", $sid);
        if (!$stmt->execute()) {
            log_message("[DB ERROR] Failed to save session $sid - " . $stmt->error, $SCRIPT_NAME);
            return false;
        } else {
            log_message("Session saved: $sid", $SCRIPT_NAME);
            return true;
        }
    } finally {
        $stmt->close();
    }
}

function db_close_session($sid) {
    global $dblink;
    global $SCRIPT_NAME;

    log_message("Updating session status in db...", $SCRIPT_NAME);

    $update_query = "UPDATE api_sessions SET closed_at = NOW() WHERE session_id = ?";
    $update_stmt = $dblink->prepare($update_query);
    if (!$update_stmt) {
        log_message("[DB ERROR] Failed to prepare update statement - " . $dblink->error, $SCRIPT_NAME);
        return false;
    }

    try {
        $update_stmt->bind_param("s", $sid);
        if (!$update_stmt->execute()) {
            log_message("[DB ERROR] Failed to update session $sid close - " . $update_stmt->error, $SCRIPT_NAME);
            return false;
        } else {
            log_message("Session closed: $sid", $SCRIPT_NAME);
            return true;
        }
    } finally {
        $update_stmt->close();
    }

}

function get_latest_session_id() {
    global $dblink;
    global $SCRIPT_NAME;

    log_message("Fetching lastest session id...", $SCRIPT_NAME);

    $query = "SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1";
    $result = $dblink->query($query);

    if (!$result) {
        log_message("[DB ERROR] Failed to execute query. - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    $sid = null;
    $row = $result->fetch_assoc();
    if ($row && isset($row['session_id'])) {
        $sid = $row['session_id'];
        log_message("Latest session ID found: $sid", $SCRIPT_NAME);
    } else {
        log_message("No sessions found in api_sessions table.", $SCRIPT_NAME);
    }

    $result->close();
    return $sid;
}

function get_latest_session_id2() {
    global $dblink;
    global $SCRIPT_NAME;

    log_message("Fetching latest session id...", $SCRIPT_NAME);

    $select_query = "SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1";
    $select_stmt = $dblink->prepare($select_query);
    if (!$select_stmt) {
        log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error, $SCRIPT_NAME);
        return null;
    }

    try {
        if (!$select_stmt->execute()) {
            log_message("[DB ERROR] Failed to execute SELECT statement - " . $dblink->error, $SCRIPT_NAME);
            return null;
        }

        $latest_session_id = null;
        $select_stmt->bind_result($latest_session_id);
        if (!$select_stmt->fetch()) {
            log_message("No sessions found in api_sessions table.", $SCRIPT_NAME);
            return null;
        }

        log_message("Latest session ID found: $latest_session_id", $SCRIPT_NAME);
        return $latest_session_id;
    } finally {
        $select_stmt->close();
    }
}
