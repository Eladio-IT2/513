<?php
declare(strict_types=1);
/**
 * Lightweight register proxy.
 * This file redirects users to the external subscription page so registration
 * happens on the external WordPress site while preserving a local /auth/register.php route.
 */

$target = 'http://eladio.wuaze.com/eladio/wordpress/subscribe/';

if (!headers_sent()) {
    header('Location: ' . $target, true, 302);
    exit;
}

// Fallback HTML if headers already sent
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Redirecting…</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; padding: 3rem; text-align: center; background: #fff; color: #222; }
        a { color: #0b66ff; text-decoration: none; }
    </style>
</head>
<body>
    <p>Redirecting to the registration page. If you are not redirected automatically, <a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">click here to continue</a>.</p>
</body>
</html>


