<?php
// Simple heartbeat to keep session and InfinityFree security cookie alive
ob_start();
header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Keep session alive but close it immediately to release lock
session_write_close();

while (ob_get_level() > 0) {
    ob_end_clean();
}
echo json_encode(['status' => 'alive', 'time' => time()]);
exit;
?>
