<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Path helpers
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

    if ($projectRoot && $documentRoot && strpos($projectRoot, $documentRoot) === 0) {
        $relative = trim(substr($projectRoot, strlen($documentRoot)), '/');
        $base = $relative === '' ? '' : '/' . $relative;
    } else {
        $base = '';
    }

    return $base;
}

function site_url(string $path = ''): string
{
    // Use base_path to determine project root, similar to asset()
    $base = base_path();
    $trimmed = ltrim($path, '/');
    
    // Get current script directory
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    $scriptDir = trim(str_replace('\\', '/', $scriptDir), '/');
    $scriptDir = ltrim($scriptDir, '/');

    if ($trimmed === '') {
        // Return path to index.php
        if ($base === '') {
            if ($scriptDir === '' || $scriptDir === '.') {
                return 'index.php';
            }
            $depth = substr_count($scriptDir, '/') + 1;
            return str_repeat('../', $depth) . 'index.php';
        }
        
        // We're in a subdirectory like /ecommerce-project
        $baseTrimmed = trim($base, '/');
        if ($scriptDir === $baseTrimmed) {
            return 'index.php';
        }
        if (strpos($scriptDir, $baseTrimmed . '/') === 0) {
            $relativePath = substr($scriptDir, strlen($baseTrimmed) + 1);
            $depth = substr_count($relativePath, '/') + 1;
            return str_repeat('../', $depth) . 'index.php';
    }
        return 'index.php';
    }
    
    // Calculate path to target file
    // Parse target path to get its directory
    $targetDir = dirname($trimmed);
    $targetDir = trim($targetDir, '/');
    if ($targetDir === '.' || $targetDir === '') {
        $targetDir = '';
    }
    
    if ($base === '') {
        // At document root
        if ($scriptDir === '' || $scriptDir === '.') {
            return $trimmed;
        }
        
        // Check if target is in same directory as current script
        if ($targetDir === $scriptDir) {
            // Same directory, just return filename
            return basename($trimmed);
        }
        
        // Check if target is in a subdirectory of current script
        if ($targetDir !== '' && strpos($targetDir, $scriptDir . '/') === 0) {
            // Target is in subdirectory, return relative path
            return substr($targetDir, strlen($scriptDir) + 1) . '/' . basename($trimmed);
        }
        
        // Need to go up to common ancestor
        $depth = substr_count($scriptDir, '/') + 1;
        return str_repeat('../', $depth) . $trimmed;
    }
    
    // We're in a subdirectory like /ecommerce-project
    $baseTrimmed = trim($base, '/');
    
    // If script is at project root level
    if ($scriptDir === $baseTrimmed) {
        return $trimmed;
    }
    
    // If script is in subdirectory of project root
    if (strpos($scriptDir, $baseTrimmed . '/') === 0) {
        $scriptRelativePath = substr($scriptDir, strlen($baseTrimmed) + 1);
        $scriptDepth = substr_count($scriptRelativePath, '/') + 1;
        
        // Check if target is in same directory as current script
        if ($targetDir !== '' && strpos($targetDir, $baseTrimmed . '/') === 0) {
            $targetRelativePath = substr($targetDir, strlen($baseTrimmed) + 1);
            if ($targetRelativePath === $scriptRelativePath) {
                // Same directory, just return filename
                return basename($trimmed);
            }
            // Check if target is in subdirectory of current script
            if (strpos($targetRelativePath, $scriptRelativePath . '/') === 0) {
                return substr($targetRelativePath, strlen($scriptRelativePath) + 1) . '/' . basename($trimmed);
            }
        }
        
        return str_repeat('../', $scriptDepth) . $trimmed;
    }
    
    // Fallback
    if ($scriptDir === '' || $scriptDir === '.') {
        return $trimmed;
    }
    
    // Check if target is in same directory
    if ($targetDir === $scriptDir) {
        return basename($trimmed);
    }
    
    $depth = substr_count($scriptDir, '/');
    if ($depth === 0) {
        return $trimmed;
    }
    
    return str_repeat('../', $depth) . $trimmed;
}

function asset(string $path): string
{
    // Use base_path to determine the project root
    // Assets are always at project_root/assets/
    $base = base_path();
    $assetPath = 'assets/' . ltrim($path, '/');
    
    // Get current script directory
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    $scriptDir = trim(str_replace('\\', '/', $scriptDir), '/');
    
    // Remove leading slash if present
    $scriptDir = ltrim($scriptDir, '/');
    
    // If base is empty, we're at document root
    if ($base === '') {
        // If script is at root, use relative path
        if ($scriptDir === '' || $scriptDir === '.') {
            return $assetPath;
        }
        // Script is in subdirectory, go up
        $depth = substr_count($scriptDir, '/') + 1;
        return str_repeat('../', $depth) . $assetPath;
    }
    
    // We're in a subdirectory like /ecommerce-project
    $baseTrimmed = trim($base, '/');
    
    // If script is at project root level (e.g., /ecommerce-project/index.php)
    if ($scriptDir === $baseTrimmed) {
        return $assetPath;
    }
    
    // If script is in subdirectory of project root (e.g., /ecommerce-project/admin/index.php)
    if (strpos($scriptDir, $baseTrimmed . '/') === 0) {
        $relativePath = substr($scriptDir, strlen($baseTrimmed) + 1);
        $depth = substr_count($relativePath, '/') + 1;
        return str_repeat('../', $depth) . $assetPath;
    }
    
    // Fallback: if script directory doesn't match base, calculate from depth
    if ($scriptDir === '' || $scriptDir === '.') {
        return $assetPath;
    }
    
    // Count directory levels
    $depth = substr_count($scriptDir, '/');
    if ($depth === 0) {
        // Single level directory, assets should be in same directory
        return $assetPath;
    }
    
    return str_repeat('../', $depth) . $assetPath;
}

function media_url(?string $path): string
{
    $path = trim($path ?? '');
    if ($path === '') {
        return asset('images/logo.png');
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    // Remove leading slash
    $cleanPath = ltrim($path, '/');
    
    // Check if path already starts with 'assets/'
    if (strpos($cleanPath, 'assets/') === 0) {
        // Remove 'assets/' prefix and use asset() function
        $relativePath = substr($cleanPath, 7); // Remove 'assets/' (7 characters)
    } else {
        // If path doesn't start with assets/, assume it's relative to assets/images/
        $relativePath = 'images/' . $cleanPath;
    }
    
    // URL encode each path segment separately to handle spaces and special characters
    // This ensures / characters remain as path separators
    $pathSegments = explode('/', $relativePath);
    $encodedSegments = array_map('rawurlencode', $pathSegments);
    $encodedPath = implode('/', $encodedSegments);
    
    // Use asset() function for images since they're in assets/ directory
    return asset($encodedPath);
}

/**
 * Session helpers
 */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function require_login(string $redirect = 'auth/login.php'): void
{
    if (!is_logged_in()) {
        // Use relative path for redirect
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('index.php');
    }
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
    } else {
        // Use site_url to get correct relative path
        $url = site_url($path);
        header('Location: ' . $url);
    }
    exit;
}

/**
 * Get relative path from current script to target file
 * This is a simpler alternative to site_url() for specific use cases
 */
function relative_path(string $targetPath): string
{
    $targetPath = ltrim($targetPath, '/');
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    $scriptDir = trim(str_replace('\\', '/', $scriptDir), '/');
    $scriptDir = ltrim($scriptDir, '/');
    
    $targetDir = dirname($targetPath);
    $targetDir = trim($targetDir, '/');
    if ($targetDir === '.' || $targetDir === '') {
        $targetDir = '';
    }
    
    // If both are at root or same directory
    if (($scriptDir === '' || $scriptDir === '.') && ($targetDir === '' || $targetDir === '.')) {
        return $targetPath;
    }
    
    // If script is at root and target is in subdirectory
    if (($scriptDir === '' || $scriptDir === '.') && $targetDir !== '') {
        return $targetPath;
    }
    
    // If target is in same directory as script
    if ($targetDir === $scriptDir) {
        return basename($targetPath);
    }
    
    // Calculate relative path
    $scriptParts = explode('/', $scriptDir);
    $targetParts = explode('/', $targetDir);
    
    // Find common prefix
    $commonLength = 0;
    $minLength = min(count($scriptParts), count($targetParts));
    for ($i = 0; $i < $minLength; $i++) {
        if ($scriptParts[$i] === $targetParts[$i]) {
            $commonLength++;
        } else {
            break;
        }
    }
    
    // Go up from script directory
    $upLevels = count($scriptParts) - $commonLength;
    $downPath = array_slice($targetParts, $commonLength);
    
    $result = str_repeat('../', $upLevels);
    if (!empty($downPath)) {
        $result .= implode('/', $downPath) . '/';
    }
    $result .= basename($targetPath);
    
    return $result;
}

function sanitize(string $value): string
{
    /**
     * Sanitizes a string for safe output and database use.
     *
     * - Trims whitespace
     * - Encodes HTML entities to prevent XSS when echoed to templates
     * - Use this for simple text fields; for emails use filter_var + sanitize where appropriate.
     *
     * Note: This does not replace prepared statements for SQL. Always use prepared statements
     * (bind_param / $wpdb->prepare) for database queries.
     */
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper: split full name into first and last name
 */
function split_full_name(string $name): array
{
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') {
        return ['', ''];
    }
    $parts = explode(' ', $name);
    $first = array_shift($parts);
    $last = implode(' ', $parts);
    return [$first, $last];
}

/**
 * FluentCRM subscriber helpers (external WordPress database)
 * These are best-effort helpers – registration will still succeed even if this connection fails.
 */
function fluentcrm_get_connection(): ?mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli && @$conn->ping()) {
        return $conn;
    }

    // Same configuration as used in contact-list.php
    $dbConfig = [
        'host' => 'sql113.byetcluster.com',
        'port' => 3306,
        'name' => 'if0_39892362_wp429',
        'user' => 'if0_39892362',
        'pass' => '7JBXRM8kM6RXT3Q',
    ];

    $conn = @new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['pass'],
        $dbConfig['name'],
        $dbConfig['port']
    );

    if ($conn->connect_errno) {
        error_log('FluentCRM DB connection failed: ' . $conn->connect_error);
        return null;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function fluentcrm_create_subscriber(string $fullName, string $email): void
{
    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    [$firstName, $lastName] = split_full_name($fullName);

    $conn = fluentcrm_get_connection();
    if (!$conn) {
        return;
    }

    $sql = 'INSERT INTO wp5x_fc_subscribers (first_name, last_name, email) VALUES (?, ?, ?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('FluentCRM prepare failed: ' . $conn->error);
        return;
    }

    $stmt->bind_param('sss', $firstName, $lastName, $email);
    if (!$stmt->execute()) {
        // Ignore duplicate email errors, log others
        if ($stmt->errno !== 1062) {
            error_log('FluentCRM insert failed: ' . $stmt->error);
        }
    }

    $stmt->close();
}

/**
 * CSRF protection
 */
function generate_csrf_token(string $form): string
{
    start_session();
    $token = bin2hex(random_bytes(16));
    if (!isset($_SESSION['csrf_tokens'][$form])) {
        $_SESSION['csrf_tokens'][$form] = [];
    }
    $_SESSION['csrf_tokens'][$form][$token] = time();
    return $token;
}

function validate_csrf_token(string $form, ?string $token): bool
{
    start_session();
    if (!$token || !isset($_SESSION['csrf_tokens'][$form][$token])) {
        return false;
    }
    unset($_SESSION['csrf_tokens'][$form][$token]);
    if (empty($_SESSION['csrf_tokens'][$form])) {
        unset($_SESSION['csrf_tokens'][$form]);
    }
    return true;
}

/**
 * Authentication
 *
 * For this project, login information comes from the FluentCRM subscribers table
 * (wp5x_fc_subscribers) in the if0_39892362_wp429 database.
 * We allow login using either email OR phone, and we do not require a password.
 */
function find_subscriber_by_email_or_phone(string $identifier): ?array
{
    $conn = fluentcrm_get_connection();
    if (!$conn) {
        return null;
    }

    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    // Decide whether this looks like an email or a phone number
    $isEmail = strpos($identifier, '@') !== false;

    if ($isEmail) {
        $sql = 'SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers WHERE email = ? LIMIT 1';
    } else {
        $sql = 'SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers WHERE phone = ? LIMIT 1';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('find_subscriber_by_email_or_phone prepare failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param('s', $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscriber = $result->fetch_assoc() ?: null;
    $stmt->close();

    if (!$subscriber) {
        return null;
    }

    // Normalize to the structure expected by current_user()/is_admin() etc.
    $fullName = trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? ''));
    if ($fullName === '') {
        $fullName = $subscriber['email'] ?? 'Subscriber';
}

    return [
        'id' => (int) ($subscriber['id'] ?? 0),
        'name' => $fullName,
        'email' => $subscriber['email'] ?? '',
        'role' => 'customer', // default role for all subscribers
    ];
}

/**
 * Find subscriber by both email AND phone from wp5x_fc_subscribers.
 * Both must match for successful authentication.
 */
function find_subscriber_by_email_and_phone(string $email, string $phone): ?array
{
    $conn = fluentcrm_get_connection();
    if (!$conn) {
        return null;
    }

    $email = trim($email);
    $phone = trim($phone);

    if ($email === '' || $phone === '') {
        return null;
    }

    $sql = 'SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers WHERE email = ? AND phone = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('find_subscriber_by_email_and_phone prepare failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param('ss', $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscriber = $result->fetch_assoc() ?: null;
    $stmt->close();

    if (!$subscriber) {
        return null;
    }

    // Normalize to the structure expected by current_user()/is_admin() etc.
    $fullName = trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? ''));
    if ($fullName === '') {
        $fullName = $subscriber['email'] ?? 'Subscriber';
    }

    return [
        'id' => (int) ($subscriber['id'] ?? 0),
        'name' => $fullName,
        'email' => $subscriber['email'] ?? '',
        'role' => 'customer', // default role for all subscribers
    ];
}

/**
 * Legacy registration function (no longer used for login)
 * We keep it for compatibility with auth/register.php if needed,
 * but the main login source of truth is wp5x_fc_subscribers.
 */
function register_user(array $data): array
{
    $errors = [];

    $name = sanitize($data['name'] ?? '');
    $email = sanitize($data['email'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Save to wp5x_fc_subscribers only
    try {
        fluentcrm_create_subscriber($name, $email);

        // Also create a local users row so we have a created_at for display.
        // If a local user with the same email already exists, do nothing.
        $conn = db();
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if ($check) {
            $check->bind_param('s', $email);
            $check->execute();
            $cres = $check->get_result();
            $exists = $cres->fetch_assoc() ?: null;
            $check->close();
        } else {
            $exists = null;
        }

        if (!$exists) {
            $passwordHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $role = 'customer';
            $createdAt = date('Y-m-d H:i:s');
            $insert = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)');
            if ($insert) {
                $insert->bind_param('sssss', $name, $email, $passwordHash, $role, $createdAt);
                $insert->execute();
                $insert->close();
            } else {
                error_log('Failed to insert local user during registration: ' . $conn->error);
            }
        }
    } catch (Throwable $e) {
        error_log('FluentCRM sync failed during register_user: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Unable to register user. Please try again.']];
    }

    return ['success' => true];
}

/**
 * Login user using email AND phone from wp5x_fc_subscribers.
 * Both email and phone must match. Password is not required.
 */
function login_user(string $email, string $phone, string $password = ''): array
{
    $email = trim($email);
    $phone = trim($phone);

    if ($email === '') {
        return ['success' => false, 'error' => 'Email is required.'];
    }

    if ($phone === '') {
        return ['success' => false, 'error' => 'Phone number is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    $subscriber = find_subscriber_by_email_and_phone($email, $phone);
    if (!$subscriber) {
        return ['success' => false, 'error' => 'No subscriber found with that email and phone combination.'];
    }

    start_session();
    $_SESSION['user'] = [
        'id' => (int) $subscriber['id'],
        'name' => $subscriber['name'],
        'email' => $subscriber['email'],
        'role' => $subscriber['role'],
    ];

        return ['success' => true];
    }

/**
 * Admin login with email and password
 * Uses the users table (not wp5x_fc_subscribers)
 */
function login_admin(string $email, string $password): array
{
    $email = trim($email);
    $password = $password ?? '';
    
    if ($email === '' || $password === '') {
        return ['success' => false, 'error' => 'Email and password are required.'];
    }
    
    // Ensure users table exists and has admin account
    ensure_admin_account();
    
    $conn = db();
    $sql = 'SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('Admin login prepare failed: ' . $conn->error);
        return ['success' => false, 'error' => 'Database error. Please try again.'];
    }
    
    $role = 'admin';
    $stmt->bind_param('ss', $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
}

    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Login successful
    start_session();
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    return ['success' => true];
}

/**
 * Ensure admin account exists in users table
 * Creates default admin if not exists
 */
function ensure_admin_account(): void
{
    $conn = db();
    
    // Check if users table exists, create if not
    $createTableSQL = 'CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM("customer","artisan","admin") NOT NULL DEFAULT "customer",
        phone VARCHAR(40) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    
    $conn->query($createTableSQL);
    
    // Check if admin account exists
    $checkSQL = 'SELECT id FROM users WHERE email = ? AND role = ? LIMIT 1';
    $stmt = $conn->prepare($checkSQL);
    $adminEmail = 'admin@example.com';
    $adminRole = 'admin';
    $stmt->bind_param('ss', $adminEmail, $adminRole);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    // Create default admin if not exists
    if (!$exists) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertSQL = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($insertSQL);
        $adminName = 'Administrator';
        $stmt->bind_param('ssss', $adminName, $adminEmail, $defaultPassword, $adminRole);
        $stmt->execute();
        $stmt->close();
    }
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}

/**
 * User management
 */
// Profile editing has been removed — no update_user_profile() function.

function get_all_users(): array
{
    $db = db();

    // Fetch local users from the `users` table
    $localResult = $db->query('SELECT id, name, email, role, phone, created_at FROM users ORDER BY created_at DESC');
    $localUsers = $localResult ? $localResult->fetch_all(MYSQLI_ASSOC) : [];

    // Normalize and mark source
    $normalizedLocal = [];
    $seenEmails = [];
    foreach ($localUsers as $u) {
        $u['source'] = 'local';
        $u['id'] = (int) ($u['id'] ?? 0);
        $u['name'] = $u['name'] ?? '';
        $u['email'] = $u['email'] ?? '';
        $u['role'] = $u['role'] ?? 'customer';
        $u['phone'] = $u['phone'] ?? null;
        $u['created_at'] = $u['created_at'] ?? null;
        $normalizedLocal[] = $u;
        if ($u['email'] !== '') {
            $seenEmails[strtolower($u['email'])] = true;
        }
    }

    // Try to fetch subscribers from FluentCRM (external WP DB) and merge those not present locally
    $merged = $normalizedLocal;
    $fcConn = fluentcrm_get_connection();
    if ($fcConn) {
        // Detect whether the subscribers table has a created_at column
        $hasCreatedAt = false;
        $checkColRes = $fcConn->query("SHOW COLUMNS FROM wp5x_fc_subscribers LIKE 'created_at'");
        if ($checkColRes && $checkColRes->num_rows > 0) {
            $hasCreatedAt = true;
        }

        // Build select depending on available columns
        if ($hasCreatedAt) {
            $sql = 'SELECT id, first_name, last_name, email, phone, created_at FROM wp5x_fc_subscribers';
        } else {
            $sql = 'SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers';
        }

        $res = $fcConn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = trim((string) ($row['email'] ?? ''));
                if ($email === '') {
                    continue;
                }
                // Skip if email already exists in local users
                if (isset($seenEmails[strtolower($email)])) {
                    continue;
                }

                $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = $email;
                }

                $subscriber = [
                    // Use negative id to avoid colliding with local user IDs
                    'id' => -1 * (int) ($row['id'] ?? 0),
                    'name' => $fullName,
                    'email' => $email,
                    'role' => 'customer',
                    'phone' => $row['phone'] ?? null,
                    // If available, include created_at so the admin UI can show joined date
                    'created_at' => $hasCreatedAt ? ($row['created_at'] ?? null) : null,
                    'source' => 'fluentcrm',
                ];

                $merged[] = $subscriber;
            }
        }
    }

    return $merged;
}

function update_user_role(int $userId, string $role): bool
{
    $allowed = ['customer', 'artisan', 'admin'];
    if (!in_array($role, $allowed, true)) {
        return false;
    }

    $sql = 'UPDATE users SET role = ? WHERE id = ?';
    $stmt = db()->prepare($sql);
    $stmt->bind_param('si', $role, $userId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Product catalogue
 */
function load_products_json(): ?array
{
    static $productsData = null;
    static $lastModified = null;
    
    $jsonFile = __DIR__ . '/../config/products.json';
    
    // Check if file was modified (for cache invalidation)
    $currentModified = file_exists($jsonFile) ? filemtime($jsonFile) : 0;
    if ($productsData !== null && $lastModified === $currentModified && !isset($GLOBALS['_products_json_cache_clear'])) {
        return $productsData;
    }
    
    // Clear cache flag if set
    if (isset($GLOBALS['_products_json_cache_clear'])) {
        unset($GLOBALS['_products_json_cache_clear']);
    }
    
    if (!file_exists($jsonFile)) {
        error_log('Products JSON file not found: ' . $jsonFile);
        return null;
    }
    
    $jsonContent = file_get_contents($jsonFile);
    if ($jsonContent === false) {
        error_log('Failed to read products JSON file: ' . $jsonFile);
        return null;
    }
    
    // Decode JSON safely and validate
    $productsData = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Failed to parse products JSON: ' . json_last_error_msg());
        return null;
    }
    
    $lastModified = $currentModified;
    return $productsData;
}

function get_product_image_map(): array
{
    // Legacy function - no longer needed with new format
    // Kept for backward compatibility
    return [];
}

function ensure_sample_products(): void
{
    $db = db();
    $result = $db->query('SELECT COUNT(*) AS total FROM products');
    $count = $result ? (int) $result->fetch_assoc()['total'] : 0;

    if ($count > 0) {
        return;
    }

    $productsData = load_products_json();
    if (!$productsData || !is_array($productsData)) {
        error_log('Failed to load products from JSON file');
        return;
    }

    $samples = $productsData;

    $stmt = $db->prepare('INSERT INTO products (name, description, story, category, price, image_url) VALUES (?, ?, ?, ?, ?, ?)');

    foreach ($samples as $product) {
        // New format: only has id, name, description, price, image_path
        // For database, use empty strings for story and category
        $name = $product['name'] ?? '';
        $description = $product['description'] ?? '';
        $story = ''; // Not in new format
        $category = ''; // Not in new format
        $price = (float) ($product['price'] ?? 0);
        $imageUrl = $product['image_path'] ?? $product['image_url'] ?? '';
        
        $stmt->bind_param(
            'ssssds',
            $name,
            $description,
            $story,
            $category,
            $price,
            $imageUrl
        );
        $stmt->execute();
    }

    $stmt->close();
}

/**
 * Save products array to JSON file with validation
 */
function save_products_json(array $products): array
{
    $jsonFile = __DIR__ . '/../config/products.json';
    
    // Validate JSON structure before saving
    foreach ($products as $index => $product) {
        if (!isset($product['id']) || !isset($product['name']) || !isset($product['description']) || !isset($product['price']) || !isset($product['image_path'])) {
            return ['success' => false, 'errors' => ['Invalid product structure at index ' . $index]];
        }
        
        // Validate data types
        if (!is_int($product['id']) || $product['id'] <= 0) {
            return ['success' => false, 'errors' => ['Invalid product ID at index ' . $index]];
        }
        if (!is_string($product['name']) || trim($product['name']) === '') {
            return ['success' => false, 'errors' => ['Product name is required at index ' . $index]];
        }
        if (!is_string($product['description']) || trim($product['description']) === '') {
            return ['success' => false, 'errors' => ['Product description is required at index ' . $index]];
        }
        if (!is_numeric($product['price']) || (float) $product['price'] <= 0) {
            return ['success' => false, 'errors' => ['Product price must be greater than zero at index ' . $index]];
        }
        if (!is_string($product['image_path']) || trim($product['image_path']) === '') {
            return ['success' => false, 'errors' => ['Product image path is required at index ' . $index]];
        }
    }
    
    // Encode to JSON with pretty printing
    $jsonContent = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if ($jsonContent === false) {
        return ['success' => false, 'errors' => ['Failed to encode JSON: ' . json_last_error_msg()]];
    }
    
    // Write to file with backup
    /**
     * Important: We create a timestamped backup and use LOCK_EX when writing.
     * - This reduces the risk of JSON corruption from concurrent writes.
     * - If the write fails, we restore the backup to avoid leaving a corrupted file.
     */
    $backupFile = $jsonFile . '.backup.' . date('Y-m-d_H-i-s');
    if (file_exists($jsonFile)) {
        @copy($jsonFile, $backupFile);
    }
    
    $result = @file_put_contents($jsonFile, $jsonContent, LOCK_EX);
    
    if ($result === false) {
        // Restore backup if write failed
        if (file_exists($backupFile)) {
            @copy($backupFile, $jsonFile);
        }
        return ['success' => false, 'errors' => ['Failed to write JSON file. Please check file permissions.']];
    }
    
    // Verify the written JSON is valid
    $verifyContent = @file_get_contents($jsonFile);
    if ($verifyContent === false) {
        return ['success' => false, 'errors' => ['Failed to verify written JSON file.']];
    }
    
    $verifyData = json_decode($verifyContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Restore backup if JSON is invalid
        if (file_exists($backupFile)) {
            @copy($backupFile, $jsonFile);
        }
        return ['success' => false, 'errors' => ['Written JSON is invalid: ' . json_last_error_msg()]];
    }
    
    // Clear cached products data
    $GLOBALS['_products_json_cache_clear'] = true;
    
    return ['success' => true];
}

function create_product(array $data): array
{
    $name = sanitize($data['name'] ?? '');
    $price = (float) ($data['price'] ?? 0);
    $description = sanitize($data['description'] ?? '');
    $imagePath = sanitize($data['image'] ?? $data['image_path'] ?? '/assets/images/logo.png');

    $errors = [];
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    if ($description === '') {
        $errors[] = 'Product description is required.';
    }
    if ($imagePath === '') {
        $errors[] = 'Image path is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Load existing products from JSON
    $products = load_products_json();
    if (!is_array($products)) {
        $products = [];
    }
    
    // Find the next available ID
    $maxId = 0;
    foreach ($products as $product) {
        if (isset($product['id']) && (int) $product['id'] > $maxId) {
            $maxId = (int) $product['id'];
        }
    }
    $newId = $maxId + 1;
    
    // Ensure image_path starts with /
    if (substr($imagePath, 0, 1) !== '/') {
        $imagePath = '/' . $imagePath;
    }
    
    // Create new product
    $newProduct = [
        'id' => $newId,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'image_path' => $imagePath,
    ];
    
    // NOTE (annotated): Inputs above have been sanitized for output safety.
    // The structure is validated in save_products_json() which enforces types/fields
    // before encoding to JSON and writing to disk.

    // Add to products array
    $products[] = $newProduct;
    
    // Save to JSON file
    return save_products_json($products);
}

function update_product(int $productId, array $data): array
{
    $name = sanitize($data['name'] ?? '');
    $price = (float) ($data['price'] ?? 0);
    $description = sanitize($data['description'] ?? '');
    $imagePath = sanitize($data['image'] ?? $data['image_path'] ?? '/assets/images/logo.png');

    $errors = [];
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    if ($description === '') {
        $errors[] = 'Product description is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Load existing products from JSON
    $products = load_products_json();
    if (!is_array($products)) {
        return ['success' => false, 'errors' => ['Failed to load products.']];
    }
    
    // Find and update the product
    $found = false;
    foreach ($products as &$product) {
        if ((int) ($product['id'] ?? 0) === $productId) {
            // Ensure image_path starts with /
            if (substr($imagePath, 0, 1) !== '/') {
                $imagePath = '/' . $imagePath;
            }
            
            $product['name'] = $name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['image_path'] = $imagePath;
            $found = true;
            break;
    }
    }
    
    // NOTE (annotated): We perform server-side validation (save_products_json) when saving.
    // This prevents malformed JSON and enforces the product schema required by the public API.

    if (!$found) {
        return ['success' => false, 'errors' => ['Product not found.']];
    }
    
    // Save to JSON file
    return save_products_json($products);
}

function delete_product(int $productId): bool
{
    // Load existing products from JSON
    $products = load_products_json();
    if (!is_array($products)) {
        return false;
    }
    
    // Find and remove the product
    $found = false;
    $newProducts = [];
    foreach ($products as $product) {
        if ((int) ($product['id'] ?? 0) === $productId) {
            $found = true;
            continue; // Skip this product
        }
        $newProducts[] = $product;
    }
    
    if (!$found) {
        return false;
    }
    
    // Save updated products to JSON file
    $result = save_products_json($newProducts);
    return $result['success'] ?? false;
}

function get_products(?int $limit = null, ?string $search = null): array
{
    // Load products directly from JSON file (new format: top-level array)
    $jsonProducts = load_products_json();
    if (!$jsonProducts || !is_array($jsonProducts)) {
        error_log('Failed to load products from JSON file');
        return [];
    }
    
    // Process products: apply search filter if provided
    $products = [];
    foreach ($jsonProducts as $product) {
        // Apply search filter if provided
        if ($search) {
            $searchLower = strtolower($search);
            $nameMatch = strpos(strtolower($product['name'] ?? ''), $searchLower) !== false;
            $descriptionMatch = strpos(strtolower($product['description'] ?? ''), $searchLower) !== false;
            
            if (!$nameMatch && !$descriptionMatch) {
                continue; // Skip this product if it doesn't match search
            }
        }
        
        // Convert image_path to image_url for backward compatibility
        $imagePath = $product['image_path'] ?? '';
        
        // Handle image path: remove leading slash for proper URL generation
        if ($imagePath !== '') {
            // Remove leading slash - media_url() will handle the path correctly
            $imageUrl = ltrim($imagePath, '/');
            
            // Verify file exists (optional check, but helps with debugging)
            $imageFile = __DIR__ . '/../' . $imageUrl;
            if (!file_exists($imageFile)) {
                error_log("Product image not found: {$imageFile} for product: " . ($product['name'] ?? 'Unknown'));
        }
        } else {
            $imageUrl = '';
        }
        
        $products[] = [
            'id' => (int) ($product['id'] ?? 0),
            'name' => $product['name'] ?? '',
            'description' => $product['description'] ?? '',
            'price' => (float) ($product['price'] ?? 0),
            'image_url' => $imageUrl,
            'image_path' => $imagePath, // Also include image_path for new format
        ];
        }
    
    // Apply limit if provided
    if ($limit && $limit > 0) {
        $products = array_slice($products, 0, $limit);
    }

    return $products;
}

function get_product(int $productId): ?array
{
    // Load products directly from JSON file (new format: top-level array)
    $jsonProducts = load_products_json();
    if (!$jsonProducts || !is_array($jsonProducts)) {
        error_log('Failed to load products from JSON file');
        return null;
    }
    
    // Find product by ID
    foreach ($jsonProducts as $product) {
        if ((int) ($product['id'] ?? 0) === $productId) {
            // Convert image_path to image_url for backward compatibility
            $imagePath = $product['image_path'] ?? '';
            // Remove leading slash if present for site_url function
            $imageUrl = ltrim($imagePath, '/');
            
            return [
                'id' => (int) ($product['id'] ?? 0),
                'name' => $product['name'] ?? '',
                'description' => $product['description'] ?? '',
                'price' => (float) ($product['price'] ?? 0),
                'image_url' => $imageUrl,
                'image_path' => $imagePath, // Also include image_path for new format
            ];
        }
    }
    
    return null; // Product not found
}

/**
 * Shopping cart
 */
function get_cart_items(): array
{
    start_session();
    return $_SESSION['cart'] ?? [];
}

function add_to_cart(int $productId, int $quantity = 1): void
{
    $product = get_product($productId);
    if (!$product) {
        return;
    }

    start_session();
    $cart = $_SESSION['cart'] ?? [];

    if (isset($cart[$productId])) {
        $cart[$productId]['quantity'] += $quantity;
    } else {
        $cart[$productId] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'image_url' => $product['image_url'],
            'quantity' => $quantity,
        ];
    }

    if ($cart[$productId]['quantity'] < 1) {
        unset($cart[$productId]);
    }

    $_SESSION['cart'] = $cart;
}

function update_cart_quantity(int $productId, int $quantity): void
{
    start_session();
    if ($quantity <= 0) {
        remove_from_cart($productId);
        return;
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
    }
}

function remove_from_cart(int $productId): void
{
    start_session();
    unset($_SESSION['cart'][$productId]);
}

function clear_cart(): void
{
    start_session();
    unset($_SESSION['cart']);
}

function cart_totals(): array
{
    $items = get_cart_items();
    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return [
        'items' => count($items),
        'subtotal' => $total,
    ];
}

/**
 * Orders
 */
/**
 * Create order and save to wp_orders table
 * All inputs are sanitized using sanitize() function
 * Uses prepared statements for database queries
 */
function create_order(int $userId, array $data): array
{
    $cartItems = get_cart_items();
    if (empty($cartItems)) {
        return ['success' => false, 'errors' => ['Your cart is empty.']];
    }

    // Sanitize all user inputs
    $fullname = sanitize($data['full_name'] ?? '');
    $phone = sanitize($data['phone'] ?? '');
    $address = sanitize($data['address'] ?? '');
    $notes = sanitize($data['notes'] ?? '');

    // Get user email for customer_email field
    $user = current_user();
    $customerEmail = sanitize($user['email'] ?? '');
    
    // If email is not in session, try to get from form data or database
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        // Try to get email from form if provided
        if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $customerEmail = sanitize($data['email']);
        } else {
            // Try to get from database using user_id
            $conn = db();
            $sql = 'SELECT email FROM wp5x_fc_subscribers WHERE id = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                if ($row && !empty($row['email'])) {
                    $customerEmail = sanitize($row['email']);
                }
            }
        }
    }

    $errors = [];
    if ($fullname === '') {
        $errors[] = 'Full name is required.';
    }
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($address === '') {
        $errors[] = 'Delivery address is required.';
    }
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid customer email is required. Please ensure your account has a valid email address.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $totals = cart_totals();

    // Collect product IDs from cart items
    $productIds = array_map(function($item) {
        return (int) $item['id'];
    }, $cartItems);
    $productIdsJson = json_encode($productIds);

    $conn = db();
    
    // Ensure wp_orders table exists
    $createTableSQL = "CREATE TABLE IF NOT EXISTS wp_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_email VARCHAR(160) NOT NULL,
        product_ids TEXT NOT NULL COMMENT 'JSON array of product IDs',
        total_amount DECIMAL(10,2) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending','confirmed','completed','cancelled','paid') NOT NULL DEFAULT 'pending',
        full_name VARCHAR(160) DEFAULT NULL,
        phone VARCHAR(40) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        INDEX idx_customer_email (customer_email),
        INDEX idx_order_date (order_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($createTableSQL)) {
        $errorMsg = 'Failed to create wp_orders table: ' . $conn->error;
        error_log($errorMsg);
        return ['success' => false, 'errors' => ['Database error: ' . $conn->error]];
    }
    
    $conn->begin_transaction();

    try {
        // Insert into wp_orders table with required fields
        $sql = 'INSERT INTO wp_orders (customer_email, product_ids, total_amount, status, full_name, phone, address, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $status = 'pending';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param('ssdsssssi', $customerEmail, $productIdsJson, $totals['subtotal'], $status, $fullname, $phone, $address, $notes, $userId);
        
        if (!$stmt->execute()) {
            $errorMsg = 'Failed to execute order insert: ' . $stmt->error;
            $stmt->close();
            throw new Exception($errorMsg);
        }
        
        $orderId = $stmt->insert_id;
        $stmt->close();

        if ($orderId === 0) {
            throw new Exception('Failed to create order: insert_id is 0. ' . ($conn->error ?: 'No database error reported.'));
        }

        // Also save to order_items table for detailed order tracking (optional)
        // If this fails, we still want the order to be created
        try {
            $sqlItem = 'CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                INDEX idx_order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            
            if ($conn->query($sqlItem)) {
        $sqlItem = 'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)';
        $stmtItem = $conn->prepare($sqlItem);
                if ($stmtItem) {
        foreach ($cartItems as $item) {
            $stmtItem->bind_param('iiid', $orderId, $item['id'], $item['quantity'], $item['price']);
                        if (!$stmtItem->execute()) {
                            error_log('Failed to insert order item: ' . $stmtItem->error);
                            // Log but continue - order_items is optional
        }
                    }
        $stmtItem->close();
                } else {
                    error_log('Failed to prepare order_items statement: ' . $conn->error);
                    // Continue - order_items is optional
                }
            } else {
                error_log('Failed to create order_items table: ' . $conn->error);
                // Continue - order_items is optional
            }
        } catch (Throwable $e) {
            error_log('Error inserting order_items (non-fatal): ' . $e->getMessage());
            // Continue - order_items is optional, main order is more important
        }

        $conn->commit();
        // Don't clear cart here - wait until payment is completed

        return ['success' => true, 'order_id' => $orderId];
    } catch (Throwable $e) {
        $conn->rollback();
        $errorMessage = $e->getMessage();
        error_log('create_order error: ' . $errorMessage);
        error_log('create_order debug - customerEmail: ' . $customerEmail);
        error_log('create_order debug - userId: ' . $userId);
        error_log('create_order debug - productIdsJson: ' . $productIdsJson);
        error_log('create_order debug - totalAmount: ' . $totals['subtotal']);
        error_log('create_order debug - db error: ' . ($conn->error ?? 'none'));
        error_log('create_order debug - db errno: ' . ($conn->errno ?? 'none'));
        
        // Return more detailed error for debugging
        $displayError = 'Unable to place order. Please try again.';
        // Uncomment the line below to show detailed error (for debugging only)
        // $displayError = 'Unable to place order: ' . htmlspecialchars($errorMessage);
        
        return ['success' => false, 'errors' => [$displayError]];
    }
}

/**
 * Get user orders from wp_orders table
 * Returns sanitized order data
 */
function get_user_orders(int $userId): array
{
    $sql = 'SELECT order_id, customer_email, product_ids, total_amount, order_date, status, full_name, phone, address, notes FROM wp_orders WHERE user_id = ? ORDER BY order_date DESC';
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Decode product_ids JSON for each order
    foreach ($orders as &$order) {
        $order['product_ids'] = json_decode($order['product_ids'] ?? '[]', true);
    }
    
    return $orders;
}

/**
 * Get order by order_id from wp_orders table
 * Returns sanitized order data
 */
function get_order_by_id(int $orderId): ?array
{
    $sql = 'SELECT order_id, customer_email, product_ids, total_amount, order_date, status, full_name, phone, address, notes, user_id FROM wp_orders WHERE order_id = ? LIMIT 1';
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc() ?: null;
    $stmt->close();
    
    if ($order) {
        // Decode product_ids JSON
        $order['product_ids'] = json_decode($order['product_ids'] ?? '[]', true);
    }
    
    return $order;
}

function get_order_items(int $orderId): array
{
    // Try to get from order_items table first (detailed items)
    $db = db();
    $sql = 'SELECT oi.quantity, oi.unit_price, oi.product_id FROM order_items oi WHERE oi.order_id = ?';
    $stmt = $db->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // If we found items in order_items, attach product names and return
        if (!empty($items)) {
            $products = load_products_json() ?: [];
            $productMap = [];
            foreach ($products as $product) {
                $productMap[(int) $product['id']] = $product['name'];
            }

            foreach ($items as &$item) {
                $pid = (int) ($item['product_id'] ?? 0);
                $item['name'] = $productMap[$pid] ?? 'Unknown Product';
            }
            return $items;
        }
    }

    // Fallback: read product_ids from wp_orders (older format)
    $order = get_order_by_id($orderId);
    if (!$order || empty($order['product_ids'])) {
        return [];
    }

    $items = [];
    foreach ($order['product_ids'] as $productId) {
        $product = get_product((int) $productId);
        if ($product) {
            $items[] = [
                'product_id' => $product['id'],
                'name' => $product['name'],
                'quantity' => 1,
                'unit_price' => $product['price']
            ];
        }
    }

    return $items;
}

/**
 * Get all orders from wp_orders table
 * Returns sanitized order data
 */
function get_all_orders(): array
{
    $sql = 'SELECT order_id, customer_email, product_ids, total_amount, order_date, status, full_name, phone, address, notes, user_id FROM wp_orders ORDER BY order_date DESC';
    $result = db()->query($sql);
    if (!$result) {
        return [];
    }
    
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    
    // Decode product_ids JSON for each order
    foreach ($orders as &$order) {
        $order['product_ids'] = json_decode($order['product_ids'] ?? '[]', true);
    }
    
    return $orders;
}

/**
 * Update order status in wp_orders table
 * All inputs are sanitized
 */
function update_order_status(int $orderId, string $status): bool
{
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled', 'paid'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $status = sanitize($status);
    $sql = 'UPDATE wp_orders SET status = ? WHERE order_id = ?';
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $status, $orderId);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function order_statistics(): array
{
    $summary = [
        'users' => 0,
        'products' => 0,
        'orders' => 0,
        'revenue' => 0.0,
    ];

    $db = db();

    $counts = [
        'users' => 'SELECT COUNT(*) AS total FROM users',
        'products' => 'SELECT COUNT(*) AS total FROM products',
        'orders' => 'SELECT COUNT(*) AS total FROM wp_orders',
        'revenue' => 'SELECT COALESCE(SUM(total_amount), 0) AS total FROM wp_orders WHERE status IN ("confirmed", "completed", "paid")',
    ];

    foreach ($counts as $key => $query) {
        $res = $db->query($query);
        if ($res) {
            $row = $res->fetch_assoc();
            $summary[$key] = $row['total'];
        }
    }

    // Include FluentCRM subscribers in users total when available
    $fcConn = fluentcrm_get_connection();
    if ($fcConn) {
        try {
            $res = $fcConn->query('SELECT COUNT(*) AS total FROM wp5x_fc_subscribers');
            if ($res) {
                $row = $res->fetch_assoc();
                $fcCount = (int) ($row['total'] ?? 0);
                // Merge counts (local users + fluentcrm subscribers)
                $summary['users'] = (int) $summary['users'] + $fcCount;
            }
        } catch (Throwable $e) {
            // ignore fluentcrm errors, keep local count
            error_log('FluentCRM count failed in order_statistics: ' . $e->getMessage());
        }
    }
    // If products table is missing or returned zero, fall back to products.json count
    if (empty($summary['products']) || (int) $summary['products'] === 0) {
        $productsData = load_products_json();
        if (is_array($productsData)) {
            $summary['products'] = count($productsData);
        }
    }

    return $summary;
}


