<?php
/**
 * CampusKart - Edit Existing Product (Admin)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    set_flash('error', 'Invalid product requested.');
    redirect('products.php');
}

// Fetch existing product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Product not found in catalog.');
    redirect('products.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$errors = [];

$name          = $product['name'];
$categoryId    = (int)$product['category_id'];
$price         = (string)$product['price'];
$originalPrice = !empty($product['original_price']) ? (string)$product['original_price'] : '';
$stock         = (int)$product['stock'];
$description   = $product['description'];
$isFeatured    = (int)$product['is_featured'];
$imagePath     = $product['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $name          = trim($_POST['name'] ?? '');
    $categoryId    = (int)($_POST['category_id'] ?? 0);
    $price         = trim($_POST['price'] ?? '');
    $originalPrice = trim($_POST['original_price'] ?? '');
    $stock         = (int)($_POST['stock'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $isFeatured    = !empty($_POST['is_featured']) ? 1 : 0;

    if ($name === '') $errors[] = 'Product name is required.';
    if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
    if ($price === '' || !is_numeric($price) || (float)$price <= 0) $errors[] = 'Valid positive product price is required.';
    if ($stock < 0) $errors[] = 'Stock cannot be negative.';
    if ($description === '') $errors[] = 'Product description is required.';

    // File Upload handling if new file provided
    if (!empty($_FILES['image_file']['name'])) {
        $file = $_FILES['image_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimes[$mime])) {
                $errors[] = 'Invalid image format. Only JPG, PNG, and WebP are allowed.';
            } else {
                $safeName = 'prod_' . bin2hex(random_bytes(10)) . '.' . $allowedMimes[$mime];
                $targetDir = dirname(__DIR__) . '/assets/images/';
                if (move_uploaded_file($file['tmp_name'], $targetDir . $safeName)) {
                    $imagePath = 'assets/images/' . $safeName;
                }
            }
        }
    } elseif (!empty($_POST['preset_image']) && $_POST['preset_image'] !== $imagePath) {
        $imagePath = $_POST['preset_image'];
    }

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $origPriceVal = $originalPrice !== '' && is_numeric($originalPrice) ? (float)$originalPrice : null;

        $updateStmt = $pdo->prepare("
            UPDATE products SET
                category_id = ?,
                name = ?,
                slug = ?,
                description = ?,
                price = ?,
                original_price = ?,
                stock = ?,
                is_featured = ?,
                image = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $categoryId,
            $name,
            $slug,
            $description,
            (float)$price,
            $origPriceVal,
            $stock,
            $isFeatured,
            $imagePath,
            $productId
        ]);

        set_flash('success', "Product '{$name}' was updated successfully.");
        redirect('products.php');
    }
}

$pageTitle = "Edit {$product['name']} - CampusKart Admin";
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
            <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111; margin-top:0.5rem;">Edit Product #<?= $productId ?></h1>
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

            <form action="product-edit.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-grid" style="margin-bottom:1.5rem;">
                    <div class="form-group full-width">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category *</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Stock Quantity *</label>
                        <input type="number" name="stock" id="stock" class="form-control" min="0" value="<?= $stock ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Selling Price (INR ₹) *</label>
                        <input type="number" step="0.01" name="price" id="price" class="form-control" value="<?= sanitize($price) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="original_price" class="form-label">Original Price (optional)</label>
                        <input type="number" step="0.01" name="original_price" id="original_price" class="form-control" value="<?= sanitize($originalPrice) ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Description *</label>
                        <textarea name="description" id="description" rows="4" class="form-control" required><?= sanitize($description) ?></textarea>
                    </div>
                </div>

                <!-- Current Image & Replacement -->
                <div style="background:var(--bg-subtle); padding:1.5rem; border-radius:var(--radius-sm); margin-bottom:2rem;">
                    <h3 style="font-family:var(--font-display); font-size:1.1rem; margin-bottom:1rem;">Current Product Image</h3>
                    
                    <div style="display:flex; gap:2rem; align-items:center; margin-bottom:1.25rem;">
                        <div style="width:90px; height:90px; background:#fff; border:1px solid #ddd; padding:0.4rem; border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center;">
                            <img src="../<?= sanitize($imagePath) ?>" alt="" style="max-height:100%; object-fit:contain;">
                        </div>
                        <div style="font-size:0.85rem; color:#666;">
                            Current file: <code><?= sanitize($imagePath) ?></code>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="image_file" class="form-label">Replace with New File (optional)</label>
                            <input type="file" name="image_file" id="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="form-group">
                            <label for="preset_image" class="form-label">Or Pick From Studio Assets</label>
                            <select name="preset_image" id="preset_image" class="form-control">
                                <option value="<?= sanitize($imagePath) ?>">Keep Current Image</option>
                                <option value="assets/images/black-tshirt.jpg">Black T-Shirt</option>
                                <option value="assets/images/brown-cap.jpg">Brown Cap</option>
                                <option value="assets/images/color-sneakers.jpg">Color Sneakers</option>
                                <option value="assets/images/elegant-bag.jpg">Elegant Bag</option>
                                <option value="assets/images/gray-shoes.jpg">Gray Shoes</option>
                                <option value="assets/images/quartz-watch.jpg">Quartz Watch</option>
                                <option value="assets/images/hero-model.jpg">Oversized Knit Sweater</option>
                                <option value="assets/images/backpack.jpg">Campus Pro Backpack</option>
                                <option value="assets/images/calculator.jpg">Scientific Calculator FX</option>
                                <option value="assets/images/mouse.jpg">Wireless Mouse</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:2rem;">
                    <label style="display:flex; align-items:center; gap:0.6rem; cursor:pointer; font-weight:600; font-size:0.92rem;">
                        <input type="checkbox" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent-red);">
                        Feature this product on Homepage
                    </label>
                </div>

                <div style="display:flex; gap:1rem;">
                    <button type="submit" class="btn-primary" style="padding:0.85rem 2rem;">SAVE MODIFICATIONS</button>
                    <a href="products.php" class="btn-dark" style="background:#eee; color:#333; padding:0.85rem 1.5rem;">CANCEL</a>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
