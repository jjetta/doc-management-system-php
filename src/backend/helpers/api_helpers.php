<?php
require_once __DIR__ . '/../config/db.php';
require_once 'log_helpers.php';

$api_url = 'https://cs4743.professorvaladez.com/api/';

function api_call($endpoint, $data) {
    global $api_url;
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

    return json_decode($response, true);
}

function save_session($sid, $username) {
    global $mysqli;
    $stmt = $mysqli->prepare("INSERT INTO api_sessions (sid, username) VALUES (?, ?)");
    $stmt->bind_param("ss", $sid, $username);
    if (!$stmt->execute()) {
        log_message("DB ERROR: Failed to save session $sid");
    } else {
        log_message("Session created: $sid");
    }
}

function get_latest_session_id() {
    global $mysqli;
    $result = $mysqli->query("SELECT sid FROM api_sessions ORDER BY created_at DESC LIMIT 1");
    $row = $result->fetch_assoc();
    return $row['sid'] ?? null;
}
?>

