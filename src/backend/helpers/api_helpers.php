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
        echo "CURL ERROR: " . curl_error($ch));
    }
    curl_close($ch);

    echo "API CALL SUCCESSFUL: " . var_dump($response);

    return json_decode($response, true);
}

function save_session($sid) {
    global $dblink;
    $stmt = $dblink->prepare("INSERT INTO api_sessions (sid) VALUES (?)");
    $stmt->bind_param("s", $sid);
    if (!$stmt->execute()) {
        log_message("DB ERROR: Failed to save session $sid");
    } else {
        log_message("Session created: $sid");
    }
}

function get_latest_session_id() {
    global $dblink;
    $result = $dblink->query("SELECT sid FROM api_sessions ORDER BY created_at DESC LIMIT 1");
    $row = $result->fetch_assoc();
    return $row['sid'] ?? null;
}
?>

