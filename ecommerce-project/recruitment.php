<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('recruitment_form', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if ($position === '') {
            $errors[] = 'Please select the position you are applying for.';
        }

        $cvPath = null;
        if (!empty($_FILES['cv']['name'])) {
            // Save CVs under /wp-content/uploads/cv_uploads/ as requested
            $uploadDir = __DIR__ . '/wp-content/uploads/cv_uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            // Validate extension
            $originalName = $_FILES['cv']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'CV must be a PDF or Word document (.pdf, .doc, .docx).';
            } else {
                // Build a cleaned filename: e.g., john_doe_cv_1701234567.pdf
                $baseName = $fullName !== '' ? $fullName : pathinfo($originalName, PATHINFO_FILENAME);
                $baseClean = preg_replace('/[^a-z0-9]+/i', '_', trim($baseName));
                $baseClean = strtolower(trim($baseClean, '_'));
                if ($baseClean === '') {
                    $baseClean = 'applicant';
                }
                $filename = $baseClean . '_cv_' . time() . '.' . $ext;
                $targetPath = $uploadDir . '/' . $filename;

                if (!move_uploaded_file($_FILES['cv']['tmp_name'], $targetPath)) {
                    $errors[] = 'Unable to upload CV. Please try again.';
                } else {
                    // Store web-accessible relative path
                    $cvPath = 'wp-content/uploads/cv_uploads/' . $filename;
                }
            }
        }

        if (empty($errors)) {
            $conn = db();
            // Create table with wp_ prefix as requested
            $createTableSql = 'CREATE TABLE IF NOT EXISTS wp_job_applications (
                application_id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                position VARCHAR(100) NOT NULL,
                cover_letter TEXT DEFAULT NULL,
                cv_file_path VARCHAR(255) DEFAULT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(20) DEFAULT "received"
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            if (!$conn->query($createTableSql)) {
                error_log('Failed to create wp_job_applications table: ' . $conn->error);
                $errors[] = 'Database error. Please try again later.';
            } else {
                $sql = 'INSERT INTO wp_job_applications (full_name, email, phone, position, cover_letter, cv_file_path) VALUES (?, ?, ?, ?, ?, ?)';
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssssss', $fullName, $email, $phone, $position, $message, $cvPath);
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        error_log('Failed to insert job application: ' . $stmt->error);
                        $errors[] = 'Unable to save your application. Please try again.';
                    }
                    $stmt->close();
                } else {
                    error_log('Prepare failed for inserting job application: ' . $conn->error);
                    $errors[] = 'Unexpected error. Please try again later.';
                }
            }
        }
    }
}
?>

<section class="container page-header">
    <h1>Join Our Team</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Recruitment</p>
</section>

<section class="container two-column">
    <div class="content-card">
        <h2>Current Opportunities</h2>
        <p>We collaborate with students, developers, and artisan coordinators to keep the Heritage Craft Marketplace running smoothly. Submit your application below and our team will review your profile.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">谢谢！您的申请已收到。</div>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" class="two-column">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('recruitment_form'); ?>">
            <div>
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            <div>
                <label for="position">Position</label>
                <select name="position" id="position" required>
                    <option value="">Select a position</option>
                    <option value="student_developer" <?php echo (($_POST['position'] ?? '') === 'student_developer') ? 'selected' : ''; ?>>Student Developer</option>
                    <option value="ux_designer" <?php echo (($_POST['position'] ?? '') === 'ux_designer') ? 'selected' : ''; ?>>UX Designer</option>
                    <option value="artisan_coordinator" <?php echo (($_POST['position'] ?? '') === 'artisan_coordinator') ? 'selected' : ''; ?>>Artisan Coordinator</option>
                    <option value="support_specialist" <?php echo (($_POST['position'] ?? '') === 'support_specialist') ? 'selected' : ''; ?>>Customer Support Specialist</option>
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label for="message">Why would you like to join?</label>
                <textarea name="message" id="message" rows="4" placeholder="Share your experience, motivation, and availability."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <div style="grid-column:1/-1;">
                <label for="cv">Upload CV (PDF/DOC)</label>
                <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx">
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>
    </div>

    <aside class="content-card">
        <h2>Working with Heritage Craft Marketplace</h2>
        <p>Our project simulates a small e-commerce startup focused on supporting grassroots artisans. Team members typically:</p>
        <ul>
            <li>Collaborate remotely with a mix of designers and developers</li>
            <li>Help onboard artisans by digitising product stories</li>
            <li>Experiment with open-source tools, analytics, and automation</li>
        </ul>
        <p>This recruitment page is part of a student assignment brief and demonstrates handling file uploads and storing applications in a database table.</p>
    </aside>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


