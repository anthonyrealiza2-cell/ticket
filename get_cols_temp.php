<?php
require_once 'database.php';

$stmt = $pdo->query("DESCRIBE tickets");
$tickets_cols = [];
while ($row = $stmt->fetch()) {
    $tickets_cols[] = $row['Field'];
}

$stmt = $pdo->query("DESCRIBE tickets_archive");
$archive_cols = [];
while ($row = $stmt->fetch()) {
    $archive_cols[] = $row['Field'];
}

echo "TICKETS: " . implode(", ", $tickets_cols) . "\n";
echo "ARCHIVE: " . implode(", ", $archive_cols) . "\n";
?>
