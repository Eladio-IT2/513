<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$redirectTo = sanitize($_GET['redirect'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('login_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif ($phone === '') {
            $errors[] = 'Phone number is required.';
        } else {
            // Login requires both email and phone
            $result = login_user($email, $phone);
            if ($result['success']) {
                // Use relative path for redirect
                // If redirectTo is a relative path from root (e.g., 'forum.php'), go up one level first
                $redirectPath = $redirectTo ?: '../index.php';
                if (strpos($redirectPath, '../') !== 0 && strpos($redirectPath, '/') !== 0) {
                    // It's a root-level file, go up one level from auth/ directory
                    $redirectPath = '../' . $redirectPath;
                }
                header('Location: ' . $redirectPath);
                exit;
            }
            $errors[] = $result['error'] ?? 'Unable to log in. Please check your email and phone number.';
        }
    }
}
?>

<section class="container page-header">
    <h1>Sign In</h1>
    <p class="breadcrumbs">Home / Sign In</p>
</section>

<section class="container" style="max-width:480px;">
    <div class="content-card">
        <h2>Welcome back</h2>
        <p>Access your artisan dashboard, manage your profile, and track orders.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('login_form'); ?>">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTo); ?>">
            <div>
                <label for="email">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
            <div>
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    name="phone"
                    id="phone"
                    placeholder="Enter your phone number"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
        </form>

        <p style="margin-top:1rem; text-align:center;">New here? <a href="http://eladio.wuaze.com/eladio/wordpress/subscribe/">Create an account</a></p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

