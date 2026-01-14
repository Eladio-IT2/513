<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

require_login('auth/login.php?redirect=cart/payment.php');

$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId === 0) {
    header('Location: index.php');
    exit;
}

$order = get_order_by_id($orderId);
$user = current_user();

// Verify order belongs to current user
if (!$order || ($order['user_id'] && (int) $order['user_id'] !== (int) $user['id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('payment_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid submission. Please refresh and try again.';
    } else {
        $paymentMethod = sanitize($_POST['payment_method'] ?? '');
        
        if ($paymentMethod === '') {
            $errors[] = 'Please select a payment method.';
        } else {
            // Simulate payment processing
            // In a real system, this would integrate with payment gateway
            
            // Update order status to 'paid'
            $conn = db();
            $sql = 'UPDATE wp_orders SET status = ? WHERE order_id = ?';
            $stmt = $conn->prepare($sql);
            $status = 'paid';
            $stmt->bind_param('si', $status, $orderId);
            
            if ($stmt->execute()) {
                $stmt->close();
                // Clear cart after successful payment
                clear_cart();
                $success = true;
            } else {
                $errors[] = 'Payment processing failed. Please try again.';
            }
        }
    }
}
?>

<section class="container page-header">
    <h1>Payment</h1>
    <p class="breadcrumbs">Home / Cart / Checkout / Confirmation / Payment</p>
</section>

<section class="container" style="max-width:600px;">
    <div class="content-card">
        <?php if ($success): ?>
            <div style="text-align:center; margin-bottom:2rem;">
                <div style="width:80px; height:80px; background:#d4edda; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h2 style="color:#28a745; margin-bottom:0.5rem;">Payment Successful!</h2>
                <p style="color:var(--color-muted); margin-bottom:2rem;">Your order #<?php echo htmlspecialchars((string) $orderId); ?> has been paid successfully.</p>
                <div style="background:#f8f9fa; padding:1.5rem; border-radius:var(--radius-medium); margin-bottom:2rem;">
                    <p style="margin:0.5rem 0;"><strong>Order Total:</strong> $<?php echo number_format((float) $order['total_amount'], 2); ?></p>
                    <p style="margin:0.5rem 0;"><strong>Payment Method:</strong> <?php echo htmlspecialchars($_POST['payment_method'] ?? 'Simulated Payment'); ?></p>
                </div>
                <a href="index.php" class="btn btn-primary" style="display:inline-block; padding:1rem 2rem;">
                    Return to Cart
                </a>
            </div>
        <?php else: ?>
            <h2 style="margin-bottom:1rem;">Payment Simulation</h2>
            <p style="color:var(--color-muted); margin-bottom:2rem;">This is a simulated payment page for demonstration purposes.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>

            <div style="background:#f8f9fa; padding:1.5rem; border-radius:var(--radius-medium); margin-bottom:2rem;">
                <h3 style="margin-top:0; margin-bottom:1rem;">Order Summary</h3>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                    <span style="color:var(--color-muted);">Order ID:</span>
                    <strong>#<?php echo htmlspecialchars((string) $orderId); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                    <span style="color:var(--color-muted);">Total Amount:</span>
                    <strong style="font-size:1.2rem; color:var(--color-primary-dark);">$<?php echo number_format((float) $order['total_amount'], 2); ?></strong>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('payment_form'); ?>">
                
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.75rem; font-weight:600;">Payment Method</label>
                    <div style="display:grid; gap:0.75rem;">
                        <label style="display:flex; align-items:center; padding:1rem; border:2px solid rgba(0,0,0,0.1); border-radius:var(--radius-medium); cursor:pointer; transition:all 0.2s;">
                            <input type="radio" name="payment_method" value="credit_card" style="margin-right:0.75rem;" required>
                            <span>Credit Card (Simulated)</span>
                        </label>
                        <label style="display:flex; align-items:center; padding:1rem; border:2px solid rgba(0,0,0,0.1); border-radius:var(--radius-medium); cursor:pointer; transition:all 0.2s;">
                            <input type="radio" name="payment_method" value="paypal" style="margin-right:0.75rem;">
                            <span>PayPal (Simulated)</span>
                        </label>
                        <label style="display:flex; align-items:center; padding:1rem; border:2px solid rgba(0,0,0,0.1); border-radius:var(--radius-medium); cursor:pointer; transition:all 0.2s;">
                            <input type="radio" name="payment_method" value="bank_transfer" style="margin-right:0.75rem;">
                            <span>Bank Transfer (Simulated)</span>
                        </label>
                    </div>
                </div>

                <div style="background:#fff3cd; padding:1rem; border-radius:var(--radius-medium); margin-bottom:1.5rem; border:1px solid #ffc107;">
                    <p style="margin:0; font-size:0.9rem; color:#856404;">
                        <strong>Note:</strong> This is a simulation. No actual payment will be processed. Click "Complete Payment" to simulate a successful transaction.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem; font-size:1.1rem;">
                    Complete Payment
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

