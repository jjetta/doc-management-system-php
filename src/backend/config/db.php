<?php
function get_dblink(): mysqli {
    static $dblink = null;

    if ($dblink === null) {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = base64_decode(getenv('DB_PASS'));
        $db   = getenv('DB_NAME');

        $dblink = new mysqli($host, $user, $pass, $db);

        // Check connection
        if ($dblink->connect_errno) {
            error_log("Failed to connect to MySQL: " . $dblink->connect_error);
            throw new RuntimeException("Database connection error. Check logs for details.");
        }
    }

    return $dblink;
}

