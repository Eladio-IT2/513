<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('support_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if ($subject === '') {
            $errors[] = 'Subject is required.';
        }
        if ($message === '') {
            $errors[] = 'Message is required.';
        }

        if (empty($errors)) {
            $conn = db();
            $conn->query('CREATE TABLE IF NOT EXISTS support_tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $sql = 'INSERT INTO support_tickets (name, email, subject, message) VALUES (?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssss', $name, $email, $subject, $message);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Unable to save your feedback. Please try again.';
                }
                $stmt->close();
            } else {
                $errors[] = 'Unexpected error. Please try again later.';
            }

            // Best-effort email notification (may be disabled on free hosting)
            $to = 'hello@heritagecrafts.local';
            $body = "New support request from {$name} <{$email}>\n\nSubject: {$subject}\n\n{$message}";
            @mail($to, '[Support] ' . $subject, $body, "From: {$email}");
        }
    }
}
?>

<section class="container page-header">
    <h1>Customer Support</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Support</p>
</section>

<section class="container two-column">
    <div class="content-card">
        <h2>Contact Our Support Team</h2>
        <p>If you encounter any issues with orders, artisan profiles, or product information, use the form below. Your feedback will be saved to the database and an email notification will be sent to the vendor inbox.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">Thank you for your feedback! Our support team will review your request shortly.</div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endforeach; ?>

        <form method="post" class="two-column">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('support_form'); ?>">
            <div>
                <label for="name">Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div style="grid-column:1/-1;">
                <label for="subject">Subject</label>
                <input type="text" name="subject" id="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
            </div>
            <div style="grid-column:1/-1;">
                <label for="message">Message</label>
                <textarea name="message" id="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
        </form>
    </div>

    <aside class="content-card">
        <h2>Support Coverage</h2>
        <p>This support page is part of a student assignment and demonstrates:</p>
        <ul>
            <li>Validating form input on the server</li>
            <li>Sending an email notification using <code>mail()</code></li>
            <li>Storing feedback in a dedicated <code>support_tickets</code> database table</li>
        </ul>
        <p>On a production WordPress site, this logic would be implemented as a custom plugin or integrated with an existing help desk system.</p>
    </aside>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


