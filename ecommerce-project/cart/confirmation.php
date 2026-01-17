<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

require_login('auth/login.php?redirect=cart/confirmation.php');

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

$orderItems = get_order_items($orderId);
?>

<section class="container page-header">
    <h1>Order Confirmation</h1>
    <p class="breadcrumbs">Home / Cart / Checkout / Confirmation</p>
</section>

<section class="container" style="max-width:800px;">
    <div class="content-card">
        <div style="text-align:center; margin-bottom:2rem;">
            <div style="width:80px; height:80px; background:#d4edda; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h2 style="color:#28a745; margin-bottom:0.5rem;">Order Placed Successfully!</h2>
            <p style="color:var(--color-muted);">Your order #<?php echo htmlspecialchars((string) $orderId); ?> has been received.</p>
        </div>

        <div style="background:#f8f9fa; padding:1.5rem; border-radius:var(--radius-medium); margin-bottom:2rem;">
            <h3 style="margin-top:0; margin-bottom:1rem;">Order Details</h3>
            <div style="display:grid; gap:0.75rem;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--color-muted);">Order ID:</span>
                    <strong>#<?php echo htmlspecialchars((string) $orderId); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--color-muted);">Order Date:</span>
                    <strong><?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($order['order_date']))); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--color-muted);">Customer Email:</span>
                    <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--color-muted);">Status:</span>
                    <span style="padding:0.25rem 0.75rem; background:rgba(215,168,110,0.2); border-radius:20px; font-size:0.9rem; text-transform:capitalize;">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="margin-bottom:2rem;">
            <h3 style="margin-bottom:1rem;">Order Items</h3>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:rgba(140,74,51,0.1);">
                        <th style="padding:0.75rem; text-align:left;">Product</th>
                        <th style="padding:0.75rem; text-align:right;">Quantity</th>
                        <th style="padding:0.75rem; text-align:right;">Price</th>
                        <th style="padding:0.75rem; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr style="border-bottom:1px solid rgba(0,0,0,0.08);">
                            <td style="padding:0.75rem;"><?php echo htmlspecialchars($item['name'] ?? 'Unknown Product'); ?></td>
                            <td style="padding:0.75rem; text-align:right;"><?php echo (int) ($item['quantity'] ?? 1); ?></td>
                            <td style="padding:0.75rem; text-align:right;">$<?php echo number_format((float) ($item['unit_price'] ?? 0), 2); ?></td>
                            <td style="padding:0.75rem; text-align:right; font-weight:600;">
                                $<?php echo number_format((float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1), 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="padding:0.75rem; text-align:right; font-weight:700;">Total Amount:</td>
                        <td style="padding:0.75rem; text-align:right; font-weight:700; font-size:1.1rem; color:var(--color-primary-dark);">
                            $<?php echo number_format((float) $order['total_amount'], 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($order['full_name']) || !empty($order['address'])): ?>
            <div style="background:#f8f9fa; padding:1.5rem; border-radius:var(--radius-medium); margin-bottom:2rem;">
                <h3 style="margin-top:0; margin-bottom:1rem;">Delivery Information</h3>
                <?php if (!empty($order['full_name'])): ?>
                    <p style="margin:0.5rem 0;"><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                <?php endif; ?>
                <?php if (!empty($order['phone'])): ?>
                    <p style="margin:0.5rem 0;"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <?php endif; ?>
                <?php if (!empty($order['address'])): ?>
                    <p style="margin:0.5rem 0;"><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align:center; padding-top:2rem; border-top:2px solid rgba(0,0,0,0.1);">
            <a href="payment.php?order_id=<?php echo $orderId; ?>" class="btn btn-primary" style="display:inline-block; padding:1rem 2rem; font-size:1.1rem;">
                Proceed to Payment
            </a>
            <p style="margin-top:1rem; color:var(--color-muted); font-size:0.9rem;">
                <a href="../index.php">Continue Shopping</a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

