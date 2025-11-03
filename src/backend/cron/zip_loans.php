<?php
require_once '../helpers/file_helpers.php';
require_once '../config/db.php';

echo "[CRON]: starting zip_loans script";

$dblink = get_dblink();

$loans = $dblink->query("SELECT DISTINCT loan_id FROM documents WHERE status='downloaded' AND zipped=0");

while ($loan = $loans->fetch_assoc()) {
    zip_loan($loan['loan_id']);
    $dblink->query("UPDATE documents SET zipped=1 WHERE loan_id={$loan['loan_id']}");
}

echo "[CRON]: zip_loans script finished";
