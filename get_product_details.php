<?php
session_start();
include('db.php');

header('Content-Type: application/json');

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing Product ID']);
    exit();
}

$product_id = intval($_GET['id']);

// Fetch Product Main Info
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

// Fetch Features
$stmt = $conn->prepare("SELECT * FROM product_features WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$features = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Gallery
$stmt = $conn->prepare("SELECT * FROM product_gallery WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$gallery = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Specifications
$stmt = $conn->prepare("SELECT * FROM product_specifications WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$specifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Variants
$stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'product' => $product,
    'features' => $features,
    'gallery' => $gallery,
    'specifications' => $specifications,
    'variants' => $variants
]);