<?php
require_once 'database.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM technical_staff");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
