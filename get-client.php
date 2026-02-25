<?php
header('Content-Type: application/json');
require_once 'database.php';

if(isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$_GET['id']]);
    $client = $stmt->fetch();
    
    if($client) {
        echo json_encode($client);
    } else {
        echo json_encode(['error' => 'Client not found']);
    }
}
?>