<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

start_session();
$authUser = current_user();
$cartTotals = cart_totals();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Heritage Craft Marketplace connects local artisans with regional customers through a simple e-commerce experience.">
    <title>Heritage Craft Marketplace</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="<?php echo asset('js/main.js'); ?>" defer></script>
</head>
<?php
// Add a simple body class when the current script is the site root index.php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$bodyClass = '';

// Map common pages to body classes so we can target header/footer per page
$pageClassMap = [
    'index.php' => 'home',
    'about.php' => 'about',
    'contact-list.php' => 'customer-list',
    'recruitment.php' => 'recruitment',
    'support.php' => 'support',
    'forum.php' => 'forum',
    'login.php' => 'signin', // auth/login.php or admin/login.php handled by dirname check below
    'register.php' => 'register-page',
];

// If script is in auth/ or admin/, use basename only for mapping
$baseName = basename($currentScript);
if (isset($pageClassMap[$baseName])) {
    $bodyClass = $pageClassMap[$baseName];
} else {
    // For files referenced with directories (e.g., auth/login.php or products/index.php)
    $scriptFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (isset($pageClassMap[$scriptFile])) {
        $bodyClass = $pageClassMap[$scriptFile];
    } else {
        // Detect if we're inside the products directory (e.g., /products/index.php)
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($scriptPath, '/products/') !== false) {
            $bodyClass = 'products';
        }
    }
}
?>
<body<?php echo $bodyClass ? ' class="' . $bodyClass . '"' : ''; ?>>
<header class="site-header">
    <div class="container header-inner">
        <div class="header-top">
            <a class="logo" href="<?php echo site_url('index.php'); ?>">
                <img src="<?php echo asset('images/logo.png'); ?>" alt="Heritage Craft Marketplace Logo">
                <span>Heritage Craft Marketplace</span>
            </a>
        </div>
        <div class="header-bottom">
            <button class="nav-toggle" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <nav class="main-nav" aria-label="Primary navigation">
                <ul>
                    <li><a href="<?php echo site_url('index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo site_url('about.php'); ?>">About Us</a></li>
                    <li><a href="<?php echo site_url('products/index.php'); ?>">Products</a></li>
                    <li><a href="<?php echo site_url('contact-list.php'); ?>">Customer List</a></li>
                    <li><a href="<?php echo site_url('recruitment.php'); ?>">Recruitment</a></li>
                    <li><a href="<?php echo site_url('support.php'); ?>">Support</a></li>
                    <li><a href="<?php echo site_url('forum.php'); ?>">Forum</a></li>
                    <li><a href="<?php echo site_url('cart/index.php'); ?>">Cart (<?php echo $cartTotals['items']; ?>)</a></li>
                    <?php if ($authUser): ?>
                        <li class="nav-dropdown">
                            <button class="nav-dropdown__trigger" aria-haspopup="true" aria-expanded="false">
                                <i class="fa-solid fa-user-circle"></i>
                                <?php echo htmlspecialchars($authUser['name']); ?>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <ul class="nav-dropdown__menu">
                                <li><a href="<?php echo site_url('user/orders.php'); ?>">Orders</a></li>
                                <?php if (is_admin()): ?>
                                    <li><a href="<?php echo site_url('admin/index.php'); ?>">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><a href="<?php echo site_url('auth/logout.php'); ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo site_url('auth/login.php'); ?>">Sign In</a></li>
                        <li><a href="http://eladio.wuaze.com/eladio/wordpress/subscribe/">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php if (!is_logged_in()): ?>
                <div class="admin-login-link">
                    <a href="<?php echo site_url('admin/login.php'); ?>">Admin Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">

