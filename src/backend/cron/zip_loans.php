<?php
require_once __DIR__ . '../helpers/file_helpers.php';
require_once __DIR__ . '../config/db.php';

$dblink = get_dblink();

$query = "SELECT DISTINCT loan_id FROM documents WHERE status='downloaded' AND zipped=0";
$stmt = $dblink->prepare($query);
if (!stmt) {
    log_message("[DB ERROR] Failed to prepare SELECT statement - " . $dblink->error);
    exit;
}
if (!stmt->execute()) {
    log_message("[DB ERROR] Failed to execute SELECT statement - " . $dblink->error);
}
$loans = $stmt->get_result();

while ($loan = $loans->fetch_assoc()) {
    zip_loan($loan['loan_id']);
    $dblink->query("UPDATE documents SET zipped=1 WHERE loan_id={$loan['loan_id']}");
}

