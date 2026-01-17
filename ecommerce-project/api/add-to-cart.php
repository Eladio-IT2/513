<?php
/**
 * API endpoint to add product to cart
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

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

// Add to cart
add_to_cart($productId, $quantity);

echo json_encode([
    'success' => true,
    'message' => 'Added to your cart!',
    'cart_total' => cart_totals()['items']
]);

