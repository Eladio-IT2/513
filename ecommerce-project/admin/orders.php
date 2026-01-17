<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Redirect to admin login if not logged in or not admin
if (!is_logged_in() || !is_admin()) {
    redirect('admin/login.php?redirect=admin/orders.php');
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin_order', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request. Please refresh the page.';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'pending');
        if (update_order_status($orderId, $status)) {
            $successMessage = 'Order status updated.';
        } else {
            $errors[] = 'Unable to update order status.';
        }
    }
}

$orders = get_all_orders();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — Heritage Craft Marketplace</title>
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="<?php echo asset('js/admin.js'); ?>" defer></script>
</head>
<body class="admin-layout">
<aside class="admin-sidebar">
    <a class="admin-sidebar__logo" href="<?php echo site_url('index.php'); ?>">
        <i class="fa-solid fa-feather"></i>
        Heritage Admin
    </a>
    <nav class="admin-sidebar__nav">
        <a href="<?php echo site_url('admin/index.php'); ?>"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <a href="<?php echo site_url('admin/products.php'); ?>"><i class="fa-solid fa-store"></i>Products</a>
        <a class="active" href="<?php echo site_url('admin/orders.php'); ?>"><i class="fa-solid fa-file-invoice"></i>Orders</a>
        <a href="<?php echo site_url('admin/users.php'); ?>"><i class="fa-solid fa-users"></i>Users</a>
        <a class="logout-link" href="<?php echo site_url('auth/logout.php'); ?>"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
    </nav>
</aside>

<section class="admin-content">
    <header class="admin-topbar">
        <div>
            <h1>Order Management</h1>
            <p>Review customer submissions, confirm artisan fulfilment, and keep the community informed.</p>
        </div>
    </header>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <h2>All Orders</h2>
        <?php if (empty($orders)): ?>
            <p>No orders have been placed yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php $items = get_order_items((int) $order['order_id']); ?>
                    <tr>
                        <td>#<?php echo (int) $order['order_id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?><br>
                            <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                        </td>
                        <td>$<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                        <td>
                            <form method="post" class="js-order-status-form">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('admin_order'); ?>">
                                <input type="hidden" name="order_id" value="<?php echo (int) $order['order_id']; ?>">
                                <select name="status">
                                    <?php foreach (['pending', 'confirmed', 'completed', 'cancelled', 'paid'] as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($order['status'] ?? 'pending') === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <ul style="margin:0; padding-left:1rem;">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <li><?php echo htmlspecialchars($item['name'] ?? 'Unknown Product'); ?> × <?php echo (int) ($item['quantity'] ?? 1); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>No items found</li>
                                <?php endif; ?>
                            </ul>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($order['order_date'] ?? 'now')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
</body>
</html>

