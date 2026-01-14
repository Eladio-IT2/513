<?php
/**
 * Global database connection helper.
 *
 * The configuration is environment-aware so the same codebase
 * can run locally (e.g., XAMPP) and on shared hosts like
 * InfinityFree without edits. Override credentials via the
 * following environment variables when needed:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 */

declare(strict_types=1);

const DB_DEFAULTS = [
    'host' => 'sql113.byetcluster.com',
    'port' => 3306,
    'name' => 'if0_39892362_wp429', // Changed to use wp429 database instead of ecommerce_db
    'user' => 'if0_39892362',
    'pass' => '7JBXRM8kM6RXT3Q',
];

/**
 * Returns a singleton mysqli connection.
 */
function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $config = [
        'host' => getenv('DB_HOST') ?: DB_DEFAULTS['host'],
        'port' => (int) (getenv('DB_PORT') ?: DB_DEFAULTS['port']),
        'name' => getenv('DB_NAME') ?: DB_DEFAULTS['name'],
        'user' => getenv('DB_USER') ?: DB_DEFAULTS['user'],
        'pass' => getenv('DB_PASS') ?: DB_DEFAULTS['pass'],
    ];

    $connection = @new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        $config['name'],
        $config['port']
    );

    if ($connection->connect_errno) {
        http_response_code(500);
        die('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

/**
 * Gracefully close the database connection (optional).
 */
function close_db(): void
{
    $connection = db();
    if ($connection instanceof mysqli) {
        $connection->close();
    }
}


