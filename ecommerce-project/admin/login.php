<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_admin()) {
    header('Location: ' . site_url('admin/index.php'));
    exit;
}

$errors = [];
$redirectTo = sanitize($_GET['redirect'] ?? 'admin/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin_login', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif ($password === '') {
            $errors[] = 'Password is required.';
        } else {
            $result = login_admin($email, $password);
            if ($result['success']) {
                // Use site_url to get correct path
                $redirectPath = site_url($redirectTo);
                header('Location: ' . $redirectPath);
                exit;
            } else {
                $errors[] = $result['error'] ?? 'Invalid email or password.';
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Heritage Craft Marketplace</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .admin-login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            padding: 2rem;
        }
        .admin-login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 420px;
            width: 100%;
        }
        .admin-login-card h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .admin-login-card .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }
        .admin-login-card .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-login-card .logo i {
            font-size: 3rem;
            color: #d4a574;
        }
    </style>
</head>
<body>
<div class="admin-login-page">
    <div class="admin-login-card">
        <div class="logo">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1>Admin Login</h1>
        <p class="subtitle">Enter your credentials to access the admin panel</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('admin_login'); ?>">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTo); ?>">
            
            <div>
                <label for="email">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="admin@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                    autofocus
                >
            </div>
            
            <div>
                <label for="password">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                <i class="fa-solid fa-lock"></i> Sign In
            </button>
        </form>

        <p style="margin-top:2rem; text-align:center; font-size:0.9rem; color:#666;">
            <a href="<?php echo site_url('index.php'); ?>">← Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>

