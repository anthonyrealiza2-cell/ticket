<?php
require 'database.php';
$stmt = $pdo->query('SELECT client_id, company_name FROM clients');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $r) {
    echo $r['client_id'] . " | '" . $r['company_name'] . "'\n";
}
?>
