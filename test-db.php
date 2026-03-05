<?php
require_once 'database.php';

header('Content-Type: text/plain');

echo "Testing database connection...\n\n";

try {
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful\n\n";
    
    // Check tables
    $tables = ['clients', 'tickets', 'products', 'concerns', 'technical_staff'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "✅ Table '$table' exists - $count records\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>