<?php
/**
 * API endpoint to get product details as JSON
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

$productId = (int) ($_GET['id'] ?? 0);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product = get_product($productId);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Ensure image URL is properly formatted
if (isset($product['image_url'])) {
    $product['image_url'] = media_url($product['image_url']);
}

// Return product data as JSON
echo json_encode([
    'success' => true,
    'product' => $product
]);

