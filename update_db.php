<?php
require 'database.php';
try {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN is_viewed TINYINT(1) DEFAULT 0");
    echo "Column is_viewed added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
