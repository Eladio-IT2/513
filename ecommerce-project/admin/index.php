<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Redirect to admin login if not logged in or not admin
if (!is_logged_in() || !is_admin()) {
    redirect('admin/login.php?redirect=admin/index.php');
}

$stats = order_statistics();
$orders = array_slice(get_all_orders(), 0, 5);
$users = array_slice(get_all_users(), 0, 5);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Heritage Craft Marketplace</title>
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
        <a class="active" href="<?php echo site_url('admin/index.php'); ?>"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <a href="<?php echo site_url('admin/products.php'); ?>"><i class="fa-solid fa-store"></i>Products</a>
        <a href="<?php echo site_url('admin/orders.php'); ?>"><i class="fa-solid fa-file-invoice"></i>Orders</a>
        <a href="<?php echo site_url('admin/users.php'); ?>"><i class="fa-solid fa-users"></i>Users</a>
        <a class="logout-link" href="<?php echo site_url('auth/logout.php'); ?>"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
    </nav>
</aside>

<section class="admin-content">
    <header class="admin-topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Monitor artisan activity, order submissions, and community engagement.</p>
        </div>
        <div class="admin-topbar__actions">
            <span>Welcome, <?php echo htmlspecialchars(current_user()['name']); ?></span>
        </div>
    </header>

    <div class="admin-grid admin-grid-4">
        <div class="admin-card metric-card">
            <h3>Total Users</h3>
            <strong><?php echo (int) $stats['users']; ?></strong>
        </div>
        <div class="admin-card metric-card">
            <h3>Published Products</h3>
            <strong><?php echo (int) $stats['products']; ?></strong>
        </div>
        <div class="admin-card metric-card">
            <h3>Orders Submitted</h3>
            <strong><?php echo (int) $stats['orders']; ?></strong>
        </div>
        <div class="admin-card metric-card">
            <h3>Confirmed Revenue</h3>
            <strong>$<?php echo number_format((float) $stats['revenue'], 2); ?></strong>
        </div>
    </div>

    <div class="admin-grid">
        <div class="admin-card">
            <h2>Recent Orders</h2>
            <?php if (empty($orders)): ?>
                <p>No orders submitted yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo (int) $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?> <br><small><?php echo htmlspecialchars($order['email']); ?></small></td>
                            <td>$<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h2>New Users</h2>
            <?php if (empty($users)): ?>
                <p>No users registered yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>
</body>
</html>

