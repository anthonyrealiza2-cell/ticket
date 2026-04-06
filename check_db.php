<?php
require 'c:/xampp/htdocs/ticket/database.php';
$stmt = $pdo->query('DESCRIBE tickets');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
