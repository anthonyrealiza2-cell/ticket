<?php
echo "<h2>Path Testing</h2>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

echo "<h3>File Existence Check:</h3>";
$files = [
    'database.php',
    'import-tickets.php',
    'download-template.php',
    'vendor/autoload.php',
    'pages/tickets.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file - Found<br>";
    } else {
        echo "❌ $file - Not Found<br>";
    }
}

echo "<h3>Path Suggestions:</h3>";
echo "If files are in root: Use 'download-template.php?type=tickets'<br>";
echo "If in pages folder: Use '../download-template.php?type=tickets'<br>";
?>