<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$search = sanitize($_GET['q'] ?? '');
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    if (!validate_csrf_token('add_to_cart', $_POST['csrf_token'] ?? null)) {
        $feedback = 'Unable to add to cart. Please refresh the page.';
    } else {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        add_to_cart($productId, $quantity);
        $feedback = 'Item added to your cart!';
    }
}

$products = get_products(null, $search ?: null);
// Image mapping is now handled automatically by get_products() from products.json
?>

<section class="container page-header">
    <h1>Products</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Products</p>
</section>

<section class="container">
    <div class="filter-bar">
        <form method="get" class="search-controls" aria-label="Search crafts">
            <label class="sr-only" for="search">Search crafts</label>
            <div class="search-input-wrap">
                <input class="search-input" type="search" name="q" id="search" placeholder="Search by craft, artisan, or story..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn search-btn" type="submit">Search</button>
            </div>
            <?php if ($search): ?>
                <a class="btn btn-outline" href="<?php echo site_url('products/index.php'); ?>">Clear</a>
            <?php endif; ?>
        </form>
        <div class="total-badge" aria-hidden="true">Total crafts: <?php echo count($products); ?></div>
    </div>

    <?php if ($feedback): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($feedback); ?></div>
    <?php endif; ?>

    <div class="grid grid-3">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <img src="<?php echo htmlspecialchars(media_url($product['image_url'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo media_url('images/logo.png'); ?>'; this.onerror=null;">
                <div class="product-card__body">
                    <h3 class="product-card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars(substr($product['description'], 0, 140))); ?>...</p>
                    <div class="product-card__actions">
                        <span class="product-card__price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                        <div class="product-card__controls">
                            <button class="btn btn-outline" onclick="openProductModal(<?php echo (int) $product['id']; ?>); return false;">Details</button>
                            <form method="post" style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('add_to_cart'); ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                <label for="qty-<?php echo (int) $product['id']; ?>" class="sr-only">Quantity</label>
                                <input class="quantity-input" type="number" id="qty-<?php echo (int) $product['id']; ?>" name="quantity" value="1" min="1" aria-label="Quantity">
                                <button type="submit" class="btn btn-primary">Add</button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <p>No crafts match your search yet. Try another keyword or check back later.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

