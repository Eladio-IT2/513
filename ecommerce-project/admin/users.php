<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Redirect to admin login if not logged in or not admin
if (!is_logged_in() || !is_admin()) {
    redirect('admin/login.php?redirect=admin/users.php');
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin_user', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid submission. Please try again.';
    } else {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = sanitize($_POST['role'] ?? 'customer');

        // If this looks like an external subscriber (we use negative IDs for fluentcrm rows),
        // import or find a matching local user by email and then set role.
        if ($userId <= 0) {
            $extId = abs($userId);
            $fc = fluentcrm_get_connection();
            $subscriber = null;
            if ($fc) {
                $stmt = $fc->prepare('SELECT first_name, last_name, email, phone FROM wp5x_fc_subscribers WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('i', $extId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $subscriber = $res->fetch_assoc() ?: null;
                    $stmt->close();
                }
            }

            if (!$subscriber) {
                $errors[] = 'External subscriber not found. Cannot change role.';
            } else {
                $email = trim($subscriber['email'] ?? '');
                $name = trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? ''));
                $phone = trim($subscriber['phone'] ?? '');

                if ($email === '') {
                    $errors[] = 'External subscriber has no email address; cannot import.';
                } else {
                    $conn = db();
                    // Check if a local user already exists with this email
                    $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    if ($checkStmt) {
                        $checkStmt->bind_param('s', $email);
                        $checkStmt->execute();
                        $cres = $checkStmt->get_result();
                        $existing = $cres->fetch_assoc() ?: null;
                        $checkStmt->close();
                    } else {
                        $existing = null;
                    }

                    if ($existing && !empty($existing['id'])) {
                        $localId = (int) $existing['id'];
                    } else {
                        // Create a local user record (generate a random password hash)
                        $randomPassword = bin2hex(random_bytes(8));
                        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
                        $insert = $conn->prepare('INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)');
                        if ($insert) {
                            $defaultRole = 'customer';
                            $insert->bind_param('sssss', $name, $email, $passwordHash, $defaultRole, $phone);
                            if (!$insert->execute()) {
                                $errors[] = 'Failed to import external subscriber: ' . $insert->error;
                                $insert->close();
                            } else {
                                $localId = (int) $insert->insert_id;
                                $insert->close();
                            }
                        } else {
                            $errors[] = 'Failed to import external subscriber: ' . $conn->error;
                        }
                    }

                    // If we have a local user id, update their role
                    if (empty($errors) && isset($localId)) {
                        if (update_user_role($localId, $role)) {
                            $successMessage = 'External user imported and role updated.';
                        } else {
                            $errors[] = 'Imported user created but failed to set role.';
                        }
                    }
                }
            }
        } else {
            if (update_user_role($userId, $role)) {
                $successMessage = 'Role updated.';
            } else {
                $errors[] = 'Unable to update role.';
            }
        }
    }
}

$users = get_all_users();

// Order users so administrators appear first, then others.
// Use a sequential display index starting at 1 (admin = 1).
$adminUsers = [];
$otherUsers = [];
foreach ($users as $u) {
    if (($u['role'] ?? '') === 'admin') {
        $adminUsers[] = $u;
    } else {
        $otherUsers[] = $u;
    }
}
$orderedUsers = array_merge($adminUsers, $otherUsers);
$displayIndex = 1;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Heritage Craft Marketplace</title>
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        <a href="<?php echo site_url('admin/orders.php'); ?>"><i class="fa-solid fa-file-invoice"></i>Orders</a>
        <a class="active" href="<?php echo site_url('admin/users.php'); ?>"><i class="fa-solid fa-users"></i>Users</a>
        <a class="logout-link" href="<?php echo site_url('auth/logout.php'); ?>"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
    </nav>
</aside>

<section class="admin-content">
    <header class="admin-topbar">
        <div>
            <h1>User Directory</h1>
            <p>Review artisan onboarding, upgrade trusted partners to admin, and coordinate with advisors.</p>
        </div>
    </header>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <h2>All Users</h2>
        <?php if (empty($users)): ?>
            <p>No users registered yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orderedUsers as $user): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars((string) ($displayIndex++)); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php
                                $roleLabel = ($user['role'] ?? 'customer');
                                if ($roleLabel === 'admin') {
                                    $displayRole = 'Administrator';
                                } else {
                                    $displayRole = ucfirst($roleLabel);
                                }
                                echo htmlspecialchars($displayRole);
                                if (($user['source'] ?? 'local') !== 'local') {
                                    echo ' <small style="opacity:.7;">(external)</small>';
                                }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></td>
                        <td><?php echo !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : '—'; ?></td>
                        <td><a class="btn-admin" style="background:rgba(34,197,94,0.2); color:#bbf7d0;" href="mailto:<?php echo htmlspecialchars($user['email']); ?>">Email</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
</body>
</html>

