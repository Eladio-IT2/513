<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

require_login('../auth/login.php?redirect=cart/checkout.php');

$cartItems = get_cart_items();
$totals = cart_totals();

if (empty($cartItems)) {
    redirect('index.php');
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('checkout_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid submission. Please refresh and try again.';
    } else {
        $user = current_user();
        $result = create_order((int) $user['id'], $_POST);
        if ($result['success']) {
            // Redirect to confirmation page
            $orderId = (int) $result['order_id'];
            header('Location: confirmation.php?order_id=' . $orderId);
            exit;
        } else {
            $errors = $result['errors'] ?? ['Unable to place order.'];
        }
    }
}
?>

<section class="container page-header">
    <h1>Checkout</h1>
    <p class="breadcrumbs">Home / Cart / Checkout</p>
</section>

<section class="container two-column">
    <div class="content-card">
        <h2>Delivery Details</h2>
        <p>Provide contact information so artisans can confirm your order. Payments and delivery are arranged offline.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endforeach; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <form method="post" class="two-column checkout-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('checkout_form'); ?>">
            <div>
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? current_user()['name']); ?>" required>
            </div>
            <div>
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
            </div>
            <div style="grid-column:1/-1;">
                <label for="address">Delivery Address</label>
                <textarea name="address" id="address" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            <div style="grid-column:1/-1;">
                <label for="notes">Order Notes (optional)</label>
                <textarea name="notes" id="notes" placeholder="Share preferred delivery times or customization requests."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Order</button>
        </form>
    </div>

    <aside class="cart-summary">
        <h2>Order Summary</h2>
        <?php if (!empty($cartItems)): ?>
            <ul style="list-style:none; padding:0; margin:0 0 1rem 0;">
                <?php foreach ($cartItems as $item): ?>
                    <li style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int) $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="cart-summary__row cart-summary__total">
                <span>Total</span>
                <span>$<?php echo number_format((float) $totals['subtotal'], 2); ?></span>
            </div>
            <p style="font-size:0.9rem; color:var(--color-muted);">After submitting, you will receive an email or call from the artisan to confirm pickup or delivery arrangements.</p>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </aside>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

