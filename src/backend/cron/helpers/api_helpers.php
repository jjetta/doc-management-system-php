<?php
require_once __DIR__ . '/../../config/db.php';
require_once 'log_helpers.php';

$SCRIPT_NAME = basename(__FILE__);

$dblink = get_dblink();
$api_url = 'https://cs4743.professorvaladez.com/api/';

function api_call($endpoint, $data) {
    global $api_url;
    global $SCRIPT_NAME;

    log_message("Calling endpoint: " . $api_url . $endpoint, $SCRIPT_NAME);

    $ch = curl_init($api_url . $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($data)
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        log_message("CURL ERROR: " . curl_error($ch), $SCRIPT_NAME);
    }
    curl_close($ch);

    $response_info = json_decode($response);

    log_message("[API RESPONSE]", $SCRIPT_NAME);
    log_message($response_info[0], $SCRIPT_NAME);
    log_message($response_info[1], $SCRIPT_NAME);
    log_message($response_info[2], $SCRIPT_NAME);
    echo "------------------------------------------------\n";

    return $response_info;
}

function save_session($sid) {
    global $dblink;
    global $SCRIPT_NAME;

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

function expire_session($sid) {
    global $dblink;
    global $SCRIPT_NAME;

    $stmt = $dblink->prepare("UPDATE api_sessions SET expired_at = NOW() WHERE session_id = ?");
    if (!$stmt) {
        log_message("[DB ERROR] Failed to prepare update statement - " . $dblink->error, $SCRIPT_NAME);
        return false;
    }

    try {
        $stmt->bind_param("s", $sid);
        if (!$stmt->execute()) {
            log_message("[DB ERROR] Failed to update session $sid expiry - " . $stmt->error, $SCRIPT_NAME);
            return false;
        } else {
            log_message("Session closed/expired: $sid", $SCRIPT_NAME);
            return true;
        }
    } finally {
        $stmt->close();
    }

}

function get_latest_session_id() {
    global $dblink;
    global $SCRIPT_NAME;

    log_message("Fetching lastest session id...", $SCRIPT_NAME);

    $result = $dblink->query("SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1");

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
