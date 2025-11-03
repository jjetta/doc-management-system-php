<?php
require_once __DIR__ . '/../config/db.php';
require_once 'log_helpers.php';

$dblink = get_dblink();

$api_url = 'https://cs4743.professorvaladez.com/api/';

function api_call($endpoint, $data) {
    global $api_url;

    log_message("Calling endpoint: " . $api_url . $endpoint);

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
        log_message("CURL ERROR: " . curl_error($ch));
    }
    curl_close($ch);

    $info = json_decode($response);

    log_message("[API RESPONSE]");
    log_message($info[0]);
    log_message($info[1]);
    log_message($info[2]);
    log_message("------------------------------------------------");

    return json_decode($response, true);
}

function save_session($sid) {
    global $dblink;
    $stmt = $dblink->prepare("INSERT INTO api_sessions (session_id) VALUES (?)");
    $stmt->bind_param("s", $sid);
    if (!$stmt->execute()) {
        log_message("DB ERROR: Failed to save session $sid - " . $stmt->error);
    } else {
        log_message("Session saved: $sid");
    }
}

function expire_session($sid) {
    global $dblink;
    $stmt = $dblink->prepare("UPDATE api_sessions SET expired_at = NOW() WHERE session_id = ?");
    $stmt->bind_param("s", $sid);
    if (!$stmt->execute()) {
        log_message("DB ERROR: Failed to update session $sid expiry - " . $stmt->error);
    } else {
        log_message("Session closed/expired: $sid");
    }
}

function get_latest_session_id() {
    global $dblink;
    log_message("Fetching lastest session id...");

    $result = $dblink->query("SELECT session_id FROM api_sessions ORDER BY created_at DESC LIMIT 1");

    if (!$result) {
        log_message("[DB ERROR]: Failed to execute query. - " . $dblink->error);
        return null;
    }

    $row = $result->fetch_assoc();

    if ($row && isset($row['session_id'])) {
        $sid = $row['session_id'];
        log_message("Latest session ID found: $sid");
        return $sid;
    } else {
        log_message("No sessions found in api_sessions table.");
        return null;
    }

    return $row['session_id'];
}
