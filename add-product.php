<?php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['product_name'])) {
    echo json_encode(['success' => false, 'message' => 'Product name is required']);
    exit;
}

$product_name = trim($data['product_name']);
$version      = trim($data['version'] ?? '1.0');

if ($product_name === '') {
    echo json_encode(['success' => false, 'message' => 'Product name cannot be empty']);
    exit;
}

try {
    // Check for duplicate
    $check = $pdo->prepare("SELECT product_id FROM products WHERE product_name = ? AND version = ?");
    $check->execute([$product_name, $version]);
    if ($existing = $check->fetch()) {
        echo json_encode([
            'success'      => true,
            'product_id'   => $existing['product_id'],
            'product_name' => $product_name,
            'version'      => $version,
            'label'        => "$product_name v$version",
            'duplicate'    => true
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO products (product_name, version) VALUES (?, ?)");
    $stmt->execute([$product_name, $version]);
    $newId = $pdo->lastInsertId();

    echo json_encode([
        'success'      => true,
        'product_id'   => $newId,
        'product_name' => $product_name,
        'version'      => $version,
        'label'        => "$product_name v$version",
        'duplicate'    => false
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
