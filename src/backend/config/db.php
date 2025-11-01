<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'webuser';
$pass = getenv('DB_PASS') ?: '7f//]ra2vDw(b8HD';
$db   = getenv('DB_NAME') ?: 'document_management';

// Create a MySQLi connection
$dblink = new mysqli($host, $user, $pass, $db);

// Check connection
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Optional: set charset
$mysqli->set_charset("utf8mb4");

