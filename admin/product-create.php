<?php
/**
 * CampusKart - Create New Product (Admin)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();

// Fetch categories for select dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$errors = [];
$name = '';
$categoryId = 0;
$price = '';
$originalPrice = '';
$stock = 10;
$description = '';
$isFeatured = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $name          = trim($_POST['name'] ?? '');
    $categoryId    = (int)($_POST['category_id'] ?? 0);
    $price         = trim($_POST['price'] ?? '');
    $originalPrice = trim($_POST['original_price'] ?? '');
    $stock         = (int)($_POST['stock'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $isFeatured    = !empty($_POST['is_featured']) ? 1 : 0;
    $imagePath     = 'assets/images/black-tshirt.jpg'; // safe default

    // Validation
    if ($name === '') $errors[] = 'Product name is required.';
    if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
    if ($price === '' || !is_numeric($price) || (float)$price <= 0) $errors[] = 'Valid positive product price is required.';
    if ($stock < 0) $errors[] = 'Stock cannot be negative.';
    if ($description === '') $errors[] = 'Product description is required.';

    // Safe File Upload Handling
    if (!empty($_FILES['image_file']['name'])) {
        $file = $_FILES['image_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $maxBytes = 3 * 1024 * 1024; // 3MB limit
            if ($file['size'] > $maxBytes) {
                $errors[] = 'Uploaded image exceeds maximum allowable size (3MB).';
            }

            // Check actual file content MIME type using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimes[$mime])) {
                $errors[] = 'Invalid image file type. Only JPG, PNG, and WebP files are permitted.';
            } else {
                $ext = $allowedMimes[$mime];
                $safeName = 'prod_' . bin2hex(random_bytes(10)) . '.' . $ext;
                $targetDir = dirname(__DIR__) . '/assets/images/';
                $destination = $targetDir . $safeName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $imagePath = 'assets/images/' . $safeName;
                } else {
                    $errors[] = 'Failed to move uploaded file. Check directory write permissions.';
                }
            }
        } else {
            $errors[] = 'Error occurred during image upload.';
        }
    } elseif (!empty($_POST['preset_image'])) {
        // Preset image option
        $allowedPresets = [
            'assets/images/black-tshirt.jpg',
            'assets/images/brown-cap.jpg',
            'assets/images/color-sneakers.jpg',
            'assets/images/elegant-bag.jpg',
            'assets/images/gray-shoes.jpg',
            'assets/images/quartz-watch.jpg',
            'assets/images/hero-model.jpg',
            'assets/images/backpack.jpg',
            'assets/images/calculator.jpg',
            'assets/images/mouse.jpg',
        ];
        if (in_array($_POST['preset_image'], $allowedPresets, true)) {
            $imagePath = $_POST['preset_image'];
        }
    }

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $origPriceVal = $originalPrice !== '' && is_numeric($originalPrice) ? (float)$originalPrice : null;

        $insertStmt = $pdo->prepare("
            INSERT INTO products (
                category_id, name, slug, description, price, original_price,
                stock, rating, rating_count, is_featured, image, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, 5.0, 1, ?, ?, NOW()
            )
        ");

        $insertStmt->execute([
            $categoryId,
            $name,
            $slug,
            $description,
            (float)$price,
            $origPriceVal,
            $stock,
            $isFeatured,
            $imagePath
        ]);

        set_flash('success', "Product '{$name}' created successfully.");
        redirect('products.php');
    }
}

$pageTitle = 'Add Product - CampusKart Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f8f9fa;">

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div style="display:flex; align-items:center; gap:0.6rem; padding-bottom:1.5rem; border-bottom:1px solid #262626;">
            <span class="logo-icon" style="background:var(--accent-red); width:28px; height:28px;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M16 6V4a4 4 0 0 0-8 0v2H3v16h18V6h-5zm-6-2a2 2 0 0 1 4 0v2h-4V4zm9 16H5V8h14v12z"/></svg>
            </span>
            <span style="font-family:var(--font-display); font-size:1.25rem; font-weight:700;">AdminPortal</span>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="products.php" class="active">📦 Products</a></li>
            <li><a href="categories.php">🏷️ Categories</a></li>
            <li><a href="orders.php">🛒 Orders</a></li>
            <li><a href="customers.php">👥 Customers</a></li>
            <li style="margin-top:2rem; border-top:1px solid #262626; padding-top:1rem;">
                <a href="../shop.php" target="_blank">🌐 Live Storefront &rarr;</a>
            </li>
            <li><a href="../logout.php" style="color:#ef4444;">🚪 Sign Out</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <header style="margin-bottom:2rem;">
            <a href="products.php" style="font-size:0.85rem; color:#666;">&larr; Back to Products</a>
            <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111; margin-top:0.5rem;">Create New Product</h1>
        </header>

        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2.5rem; max-width:850px;">
            <?php if (!empty($errors)): ?>
                <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:1rem; border-radius:var(--radius-sm); margin-bottom:1.75rem; font-size:0.9rem;">
                    <strong>Please correct the following:</strong>
                    <ul style="margin:0.5rem 0 0 1.25rem;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= sanitize($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="product-create.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-grid" style="margin-bottom:1.5rem;">
                    <div class="form-group full-width">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" placeholder="e.g. Campus Premium Hoodie" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category *</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Inventory Stock Quantity *</label>
                        <input type="number" name="stock" id="stock" class="form-control" min="0" value="<?= $stock ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Selling Price (INR ₹) *</label>
                        <input type="number" step="0.01" name="price" id="price" class="form-control" placeholder="499.00" value="<?= sanitize($price) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="original_price" class="form-label">Original Price (optional strike-through)</label>
                        <input type="number" step="0.01" name="original_price" id="original_price" class="form-control" placeholder="799.00" value="<?= sanitize($originalPrice) ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Full Product Description *</label>
                        <textarea name="description" id="description" rows="4" class="form-control" placeholder="Detailed materials, specifications, and campus suitability..." required><?= sanitize($description) ?></textarea>
                    </div>
                </div>

                <!-- Product Image Option -->
                <div style="background:var(--bg-subtle); padding:1.5rem; border-radius:var(--radius-sm); margin-bottom:2rem;">
                    <h3 style="font-family:var(--font-display); font-size:1.1rem; margin-bottom:1rem;">Product Imagery</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="image_file" class="form-label">Upload Image File (JPG, PNG, WebP &bull; Max 3MB)</label>
                            <input type="file" name="image_file" id="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="form-group">
                            <label for="preset_image" class="form-label">Or Choose Existing Studio Asset</label>
                            <select name="preset_image" id="preset_image" class="form-control">
                                <option value="assets/images/black-tshirt.jpg">Black T-Shirt (Graphic Crest)</option>
                                <option value="assets/images/brown-cap.jpg">Brown Suede Cap</option>
                                <option value="assets/images/color-sneakers.jpg">Color Pastel Sneakers</option>
                                <option value="assets/images/elegant-bag.jpg">Elegant Tan Handbag</option>
                                <option value="assets/images/gray-shoes.jpg">Gray Skate Shoes</option>
                                <option value="assets/images/quartz-watch.jpg">Quartz Wristwatch</option>
                                <option value="assets/images/hero-model.jpg">Oversized Knit Sweater</option>
                                <option value="assets/images/backpack.jpg">Campus Pro Backpack</option>
                                <option value="assets/images/calculator.jpg">Scientific Calculator FX</option>
                                <option value="assets/images/mouse.jpg">Wireless Peripherals Mouse</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:2rem;">
                    <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:600; font-size:0.92rem;">
                        <input type="checkbox" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent-red);">
                        Feature this product prominently on the Homepage
                    </label>
                </div>

                <div style="display:flex; gap:1rem;">
                    <button type="submit" class="btn-primary" style="padding:0.85rem 2rem;">SAVE PRODUCT</button>
                    <a href="products.php" class="btn-dark" style="background:#eee; color:#333; padding:0.85rem 1.5rem;">CANCEL</a>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
