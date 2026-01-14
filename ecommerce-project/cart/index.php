<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token('cart_actions', $token)) {
        $errors[] = 'Invalid cart action. Please refresh and try again.';
    } else {
        $productId = (int) ($_POST['product_id'] ?? 0);

        if ($action === 'update') {
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
            update_cart_quantity($productId, $quantity);
            $messages[] = 'Cart updated.';
        }

        if ($action === 'remove') {
            remove_from_cart($productId);
            $messages[] = 'Item removed from your cart.';
        }

        if ($action === 'clear') {
            clear_cart();
            $messages[] = 'Cart cleared.';
        }
    }
}

$cartItems = get_cart_items();
$totals = cart_totals();
?>

<section class="container page-header">
    <h1>Your Shopping Cart</h1>
    <p class="breadcrumbs">Home / Cart</p>
</section>

<section class="container cart-page">
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endforeach; ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endforeach; ?>

    <?php if (empty($cartItems)): ?>
        <div class="content-card">
            <p>Your cart is currently empty. Explore our <a href="<?php echo site_url('products/index.php'); ?>">craft catalog</a> to add handmade goods.</p>
        </div>
    <?php else: ?>
        <div class="two-column">
            <div>
                <div class="content-card">
                    <table class="cart-table">
                        <thead>
                        <tr>
                            <th>Craft</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?php echo htmlspecialchars(media_url($item['image_url'])); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='<?php echo asset('images/logo.png'); ?>'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <span class="pill">Artisan crafted</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="price-cell">$<?php echo number_format((float) $item['price'], 2); ?></td>
                                <td>
                                    <form method="post" class="quantity-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('cart_actions'); ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                        <input type="number" min="1" value="<?php echo (int) $item['quantity']; ?>" name="quantity">
                                        <button class="btn btn-outline" type="submit">Update</button>
                                    </form>
                                </td>
                                <td class="total-cell">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="action-cell">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('cart_actions'); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                        <button class="btn btn-outline" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" style="margin-top:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('cart_actions'); ?>">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-outline">Clear Cart</button>
                </form>
            </div>

            <aside class="cart-summary">
                <h2>Order Summary</h2>
                <div class="cart-summary__row">
                    <span>Items</span>
                    <span><?php echo $totals['items']; ?></span>
                </div>
                <div class="cart-summary__row cart-summary__total">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format((float) $totals['subtotal'], 2); ?></span>
                </div>
                <p style="font-size:0.9rem; color:var(--color-muted);">Orders are confirmed by artisans via email or phone. Payment is settled offline when the order is fulfilled.</p>
                <a class="btn btn-primary" style="width:100%; text-align:center; justify-content:center;" href="checkout.php">Proceed to Checkout</a>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

