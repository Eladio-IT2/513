<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

require_login('auth/login.php?redirect=user/orders.php');

$user = current_user();
$orders = get_user_orders((int) $user['id']);
?>

<section class="container page-header">
    <h1>My Orders</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Orders</p>
</section>

<section class="container content-card">
    <h2>Order History</h2>
    <p>Track artisan confirmations and delivery progress. Status updates are reflected once artisans confirm orders via the admin dashboard.</p>

    <?php if (empty($orders)): ?>
        <p>You have no orders yet. Browse the <a href="<?php echo site_url('products/index.php'); ?>">craft catalog</a> to get started.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Items</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <?php $items = get_order_items((int) $order['id']); ?>
                <tr>
                    <td>#<?php echo (int) $order['id']; ?></td>
                    <td><?php echo date('F j, Y', strtotime($order['created_at'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                    <td>$<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                    <td>
                        <ul style="margin:0; padding-left:1.2rem;">
                            <?php foreach ($items as $item): ?>
                                <li><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int) $item['quantity']; ?> ($<?php echo number_format($item['unit_price'], 2); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

