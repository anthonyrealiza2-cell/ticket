<?php
header('Content-Type: application/json');
require_once 'database.php';

if(isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
    $stmt->execute([$_GET['id']]);
    $tech = $stmt->fetch();
    
    if($tech) {
        echo json_encode($tech);
    } else {
        echo json_encode(['error' => 'Technical staff not found']);
    }
}
?>