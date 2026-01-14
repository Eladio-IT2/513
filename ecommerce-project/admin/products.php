<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Redirect to admin login if not logged in or not admin
if (!is_logged_in() || !is_admin()) {
    redirect('admin/login.php?redirect=admin/products.php');
}

$action = $_GET['action'] ?? 'list';
$productId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin_product', $_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid submission. Please refresh and try again.';
    } else {
        /*
         * Handle optional image file upload from the admin form.
         * - If an image file is provided, validate extension and upload it
         *   to /assets/images/products/, then set $_POST['image'] to the
         *   site-relative path so existing create_product/update_product can use it.
         */
        if (!empty($_FILES['image_file']['name'])) {
            $uploadDir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $file = $_FILES['image_file'];
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            $tmpName = $file['tmp_name'] ?? '';
            $origName = $file['name'] ?? '';
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($origName === '' || $tmpName === '') {
                $errors[] = 'No file selected or upload error.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Invalid image type. Only JPG, JPEG, PNG, GIF are allowed.';
            } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error code: ' . ($file['error'] ?? 'unknown');
            } else {
                // Build a safe filename: lowercase, spaces->dash, strip unsafe chars
                $baseName = strtolower(pathinfo($origName, PATHINFO_FILENAME));
                $baseName = preg_replace('/\s+/', '-', $baseName);
                $baseName = preg_replace('/[^a-z0-9\-_.]/', '', $baseName);
                $baseName = substr($baseName, 0, 200);
                $newName = $baseName . '_' . time() . '.' . $ext;
                $dest = $uploadDir . $newName;

                if (!move_uploaded_file($tmpName, $dest)) {
                    $errors[] = 'Failed to move uploaded file to destination.';
                } else {
                    // Set the POST image path to match existing product JSON format
                    $_POST['image'] = '/assets/images/products/' . $newName;
                }
            }
        }

        // If there were upload errors, skip action handling so admin can fix them
        if (empty($errors)) {
            if ($_POST['form_action'] === 'create') {
                $result = create_product($_POST);
                if ($result['success']) {
                    $successMessage = 'Product created successfully.';
                    $action = 'list';
                } else {
                    $errors = $result['errors'];
                }
            }

            if ($_POST['form_action'] === 'update') {
                $productId = (int) $_POST['product_id'];
                $result = update_product($productId, $_POST);
                if ($result['success']) {
                    $successMessage = 'Product updated successfully.';
                    $action = 'list';
                } else {
                    $errors = $result['errors'];
                    $action = 'edit';
                }
            }

            if ($_POST['form_action'] === 'delete') {
                $productId = (int) $_POST['product_id'];
                if (delete_product($productId)) {
                    $successMessage = 'Product deleted.';
                } else {
                    $errors[] = 'Unable to delete product.';
                }
                $action = 'list';
            }
        }
    }
}

$product = $productId ? get_product($productId) : null;
$products = get_products();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products — Heritage Craft Marketplace</title>
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="admin-layout">
<aside class="admin-sidebar">
    <a class="admin-sidebar__logo" href="<?php echo site_url('index.php'); ?>">
        <i class="fa-solid fa-feather"></i>
        Heritage Admin
    </a>
    <nav class="admin-sidebar__nav">
        <a href="<?php echo site_url('admin/index.php'); ?>"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <a class="active" href="<?php echo site_url('admin/products.php'); ?>"><i class="fa-solid fa-store"></i>Products</a>
        <a href="<?php echo site_url('admin/orders.php'); ?>"><i class="fa-solid fa-file-invoice"></i>Orders</a>
        <a href="<?php echo site_url('admin/users.php'); ?>"><i class="fa-solid fa-users"></i>Users</a>
        <a class="logout-link" href="<?php echo site_url('auth/logout.php'); ?>"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
    </nav>
</aside>

<section class="admin-content">
    <header class="admin-topbar">
        <div>
            <h1>Product Listings</h1>
            <p>Create, edit, and curate crafts showcased on the public storefront.</p>
        </div>
        <div class="admin-topbar__actions">
            <a class="btn-admin" href="<?php echo site_url('admin/products.php?action=create'); ?>">Add New Craft</a>
        </div>
    </header>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endforeach; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <?php if ($action === 'edit' && !$product): ?>
            <div class="admin-card">
                <p>Product not found.</p>
            </div>
        <?php else: ?>
            <div class="admin-card">
                <h2><?php echo $action === 'create' ? 'Create Craft Listing' : 'Edit Craft Listing'; ?></h2>
                <form method="post" class="admin-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('admin_product'); ?>">
                    <input type="hidden" name="form_action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                    <?php endif; ?>
                    <div>
                        <label for="name">Craft Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="price">Price (USD)</label>
                        <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars((string) ($product['price'] ?? '')); ?>" required>
                    </div>
                    <div>
                        <label for="image_file">Upload Image</label>
                        <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.gif">
                        <small>Uploaded files will be saved to <code>/assets/images/products/</code>.</small>
                    </div>
                    <div>
                        <label>Current Image Path</label>
                        <p style="margin:0.25rem 0; color:#cbd5e1;"><small><?php echo htmlspecialchars($product['image_path'] ?? $product['image_url'] ?? 'No image'); ?></small></p>
                        <!-- Preserve existing path for form submission if no new upload occurs -->
                        <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image_path'] ?? $product['image_url'] ?? ''); ?>">
                        <small>You can upload a new image to replace the current one.</small>
                    </div>
                    <div>
                        <label for="description">Product Description</label>
                        <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        <small>Include both the artisan story and product details in the description.</small>
                    </div>
                    <button class="btn-admin" type="submit">Save Craft</button>
                </form>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="admin-card">
            <h2>All Crafts (<?php echo count($products); ?>)</h2>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Image Path</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $item): ?>
                    <tr>
                        <td>#<?php echo (int) $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format((float) $item['price'], 2); ?></td>
                        <td><small><?php echo htmlspecialchars($item['image_path'] ?? $item['image_url'] ?? 'N/A'); ?></small></td>
                        <td style="display:flex; gap:0.5rem;">
                            <a class="btn-admin" style="background:rgba(59,130,246,0.2); color:#93c5fd;" href="<?php echo site_url('admin/products.php?action=edit&id=' . (int) $item['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this product?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token('admin_product'); ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                <button class="btn-admin" style="background:rgba(239,68,68,0.2); color:#fca5a5;" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
</body>
</html>

