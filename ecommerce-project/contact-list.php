<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

// Require admin access
if (!is_logged_in() || !is_admin()) {
    redirect('admin/login.php?redirect=contact-list.php');
}

// Get FluentCRM connection
$conn = fluentcrm_get_connection();

$subscribers = [];
$errorMessage = '';

if (!$conn) {
    $errorMessage = 'Database connection failed. Please check your database configuration.';
} else {
    // Fetch all subscribers from wp5x_fc_subscribers table
    // Display: name (first_name + last_name), email, phone
    $query = "SELECT id, first_name, last_name, email, phone FROM wp5x_fc_subscribers ORDER BY id ASC";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row;
        }
        $result->free();
    } else {
        $errorMessage = 'Query failed: ' . $conn->error;
    }
}
?>

<style>
    /* Contact list page specific styles - scoped to avoid affecting navigation */
    .contact-list-page {
        padding: 2rem 0;
    }

    .contact-list-page .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .contact-list-page .page-header h1 {
        color: var(--color-text);
        margin-bottom: 0.5rem;
    }

    .contact-list-page .success-message {
        color: #28a745;
        margin-bottom: 1.5rem;
        font-weight: 600;
        padding: 0.75rem 1rem;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: var(--radius-medium);
    }

    .contact-list-page .error-message {
        color: #dc3545;
        margin-bottom: 1.5rem;
        font-weight: 600;
        padding: 0.75rem 1rem;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: var(--radius-medium);
    }

    .contact-list-page .subscribers-table-wrapper {
        overflow-x: auto;
        margin: 1.5rem 0;
    }

    .contact-list-page .subscribers-table {
        width: 100%;
        border-collapse: collapse;
        background-color: var(--color-white);
        box-shadow: var(--shadow-medium);
        border-radius: var(--radius-medium);
        overflow: hidden;
    }

    .contact-list-page .subscribers-table th,
    .contact-list-page .subscribers-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    .contact-list-page .subscribers-table th {
        background-color: #f8f9fa;
        font-weight: 700;
        color: var(--color-text);
        font-family: 'Libre Baskerville', Georgia, serif;
    }

    .contact-list-page .subscribers-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    .contact-list-page .subscribers-table tbody tr:last-child td {
        border-bottom: none;
    }

    .contact-list-page .subscribers-table td {
        color: var(--color-text);
    }

    .contact-list-page .total-subscribers {
        margin-top: 1.5rem;
        font-weight: 600;
        color: var(--color-text);
        text-align: center;
        padding: 1rem;
        background-color: var(--color-background);
        border-radius: var(--radius-medium);
    }
</style>

<section class="container page-header">
    <h1>Customer List</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Customer List</p>
</section>

<section class="container content-card contact-list-page">
    <?php if ($errorMessage): ?>
        <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
    
    <div class="subscribers-table-wrapper">
        <table class="subscribers-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem; color: var(--color-muted);">
                            No subscribers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <?php
                        // Combine first_name and last_name into full name
                        $fullName = trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? ''));
                        if ($fullName === '') {
                            $fullName = 'N/A';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fullName); ?></td>
                            <td><?php echo htmlspecialchars($subscriber['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($subscriber['phone'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="total-subscribers">
        Total customers: <?php echo count($subscribers); ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
