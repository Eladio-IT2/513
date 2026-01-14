<?php
declare(strict_types=1);

/**
 * One-off script to import FluentCRM subscribers into the local `users` table
 * and populate a `created_at` timestamp where missing. Run from CLI or via browser
 * (be cautious on public hosts).
 *
 * Usage (CLI):
 *   php scripts/import_subscribers_with_dates.php
 */

require_once __DIR__ . '/../includes/functions.php';

// Prevent accidental web execution on public sites unless explicitly allowed
if (php_sapi_name() !== 'cli' && empty($_GET['allow_web'])) {
    http_response_code(403);
    echo "Forbidden. Run this script from CLI or add ?allow_web=1 to the URL for temporary web execution.\n";
    exit;
}

$fc = fluentcrm_get_connection();
$db = db();

if (!$fc) {
    echo "Unable to connect to FluentCRM database.\n";
    exit(1);
}

$res = $fc->query('SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers');
if (!$res) {
    echo "No subscribers found or query failed.\n";
    exit(1);
}

$inserted = 0;
$updated = 0;
$skipped = 0;

// Helper: random timestamp within last 2 years
function random_timestamp_within_range(): int {
    $start = strtotime('-2 years');
    $end = time();
    return mt_rand($start, $end);
}

while ($row = $res->fetch_assoc()) {
    $email = trim((string)($row['email'] ?? ''));
    if ($email === '') {
        $skipped++;
        continue;
    }

    $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    if ($fullName === '') {
        $fullName = $email;
    }

    // Check local users table for this email
    $stmt = $db->prepare('SELECT id, created_at FROM users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        echo "DB prepare failed: " . $db->error . "\n";
        exit(1);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $local = $result->fetch_assoc() ?: null;
    $stmt->close();

    $randomTs = date('Y-m-d H:i:s', random_timestamp_within_range());

    if ($local) {
        // If created_at is null or empty, update it
        if (empty($local['created_at'])) {
            $ustmt = $db->prepare('UPDATE users SET created_at = ? WHERE id = ?');
            if ($ustmt) {
                $ustmt->bind_param('si', $randomTs, $local['id']);
                if ($ustmt->execute()) {
                    $updated++;
                } else {
                    echo "Failed to update user id {$local['id']}: " . $ustmt->error . "\n";
                }
                $ustmt->close();
            }
        } else {
            $skipped++;
        }
    } else {
        // Insert new local user record with random created_at
        $passwordHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $role = 'customer';
        $phone = $row['phone'] ?? null;

        $istmt = $db->prepare('INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        if ($istmt) {
            $istmt->bind_param('ssssss', $fullName, $email, $passwordHash, $role, $phone, $randomTs);
            if ($istmt->execute()) {
                $inserted++;
            } else {
                echo "Failed to insert {$email}: " . $istmt->error . "\n";
            }
            $istmt->close();
        } else {
            echo "Prepare failed for insert: " . $db->error . "\n";
            exit(1);
        }
    }
}

echo "Import complete. Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}\n";

exit(0);


