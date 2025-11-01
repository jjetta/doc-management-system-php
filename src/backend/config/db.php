<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'webuser';
$pass = getenv('DB_PASS') ?: '7f//]ra2vDw(b8HD';
$db   = getenv('DB_NAME') ?: 'document_management';

// Create a MySQLi connection
$dblink = new mysqli($host, $user, $pass, $db);

// Check connection
if ($dblink->connect_errno) {
    echo "Failed to connect to MySQL: " . $dblink->connect_error;
    throw new RuntimeException("Database connection error. Check logs for details.");
}


