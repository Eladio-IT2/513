<?php
/**
 * Public API endpoint to get all products as JSON
 * Endpoint: /api/products.php or /api/products.json
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/functions.php';

// Get all products (no limit for API)
$products = get_products(null); // null means no limit

// Ensure all image URLs are properly formatted
foreach ($products as &$product) {
    if (isset($product['image_url'])) {
        $product['image_url'] = media_url($product['image_url']);
    }
}

// Return products data as JSON
echo json_encode([
    'success' => true,
    'products' => $products,
    'count' => count($products)
]);
?>
