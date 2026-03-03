<?php
echo "<h2>Debug Information</h2>";
echo "<hr>";

echo "<h3>File Locations:</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h3>Checking if get-tickets.php exists:</h3>";
$getTicketsPath = __DIR__ . '/get-tickets.php';
echo "Path: " . $getTicketsPath . " - ";
if (file_exists($getTicketsPath)) {
    echo "<span style='color:green'>FOUND ✓</span><br>";
} else {
    echo "<span style='color:red'>NOT FOUND ✗</span><br>";
}

echo "<h3>Test Links:</h3>";
echo "<a href='/tickets/get-tickets.php?id=1' target='_blank'>Test /tickets/get-tickets.php?id=1</a><br>";
echo "<a href='get-tickets.php?id=1' target='_blank'>Test get-tickets.php?id=1</a><br>";

echo "<h3>PHP Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
?>