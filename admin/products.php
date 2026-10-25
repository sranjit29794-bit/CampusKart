<?php
/**
 * CampusKart - Admin Product Catalog Management (List & Delete)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$flash = get_flash();

// Handle Product Deletion via POST with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf_token();
    $deleteId = (int)($_POST['product_id'] ?? 0);

    if ($deleteId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$deleteId]);
        set_flash('success', 'Product was successfully removed from catalog.');
    }
    redirect('products.php');
}

// Fetch all products with categories
$search = trim($_GET['search'] ?? '');
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE p.name LIKE ? OR c.name LIKE ?";
    $params = ["%{$search}%", "%{$search}%"];
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    {$where}
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Manage Products - CampusKart Admin';
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

<?php if ($flash): ?>
<div class="toast-container" role="alert">
    <div class="toast toast-<?= sanitize($flash['type']) ?>">
        <span><?= sanitize($flash['message']) ?></span>
    </div>
</div>
<?php endif; ?>

<div class="admin-shell">
    <!-- Sidebar -->
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
            <li>
                <a href="../logout.php" style="color:#ef4444;">🚪 Sign Out</a>
            </li>
        </ul>
    </aside>

    <!-- Main Table Pane -->
    <main class="admin-main">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111;">Catalog Products</h1>
                <p style="color:#666; font-size:0.92rem;">Create, update pricing, manage stock, and organize listings.</p>
            </div>
            <div style="display:flex; gap:1rem; align-items:center;">
                <form action="products.php" method="GET" style="display:flex; gap:0.5rem;">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= sanitize($search) ?>" style="padding:0.55rem 0.85rem; font-size:0.88rem; width:220px;">
                    <button type="submit" class="btn-dark" style="padding:0.55rem 1rem; font-size:0.85rem;">Search</button>
                    <?php if ($search): ?>
                        <a href="products.php" class="btn-dark" style="padding:0.55rem 0.85rem; background:#eee; color:#333; font-size:0.85rem;">Clear</a>
                    <?php endif; ?>
                </form>
                <a href="product-create.php" class="btn-primary" style="padding:0.65rem 1.25rem; font-size:0.85rem;">+ ADD PRODUCT</a>
            </div>
        </header>

        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); overflow:hidden;">
            <table class="cart-table" style="font-size:0.9rem;">
                <thead>
                    <tr>
                        <th style="width:70px;">Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div style="width:52px; height:52px; background:var(--bg-subtle); border-radius:var(--radius-sm); padding:0.25rem; display:flex; align-items:center; justify-content:center;">
                                        <img src="../<?= sanitize($p['image']) ?>" alt="" style="max-height:100%; object-fit:contain;">
                                    </div>
                                </td>
                                <td>
                                    <strong style="color:#111; display:block; font-size:0.98rem;"><?= sanitize($p['name']) ?></strong>
                                    <span style="font-size:0.75rem; color:#888;">ID: #PROD-<?= $p['id'] ?></span>
                                </td>
                                <td>
                                    <span style="font-size:0.85rem; color:#555;"><?= sanitize($p['category_name']) ?></span>
                                </td>
                                <td style="font-family:var(--font-display); font-weight:700;">
                                    <?= format_price((float)$p['price']) ?>
                                    <?php if (!empty($p['original_price'])): ?>
                                        <div style="font-size:0.75rem; color:#999; text-decoration:line-through;"><?= format_price((float)$p['original_price']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$p['stock'] <= 5): ?>
                                        <span style="background:#fef2f2; color:#991b1b; padding:0.2rem 0.6rem; border-radius:10px; font-weight:700; font-size:0.78rem;">
                                            Critical: <?= $p['stock'] ?>
                                        </span>
                                    <?php elseif ((int)$p['stock'] <= 15): ?>
                                        <span style="background:#fffbeb; color:#92400e; padding:0.2rem 0.6rem; border-radius:10px; font-weight:700; font-size:0.78rem;">
                                            Low: <?= $p['stock'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="background:#ecfdf5; color:#065f46; padding:0.2rem 0.6rem; border-radius:10px; font-weight:600; font-size:0.78rem;">
                                            <?= $p['stock'] ?> in stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $p['is_featured'] ? '<span style="color:var(--accent-red); font-weight:700; font-size:0.78rem;">★ FEATURED</span>' : '<span style="color:#888; font-size:0.78rem;">Standard</span>' ?>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                                        <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn-dark" style="padding:0.4rem 0.8rem; font-size:0.8rem; background:#333;">Edit</a>
                                        <form action="products.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= addslashes(sanitize($p['name'])) ?>\'?');" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-dark" style="padding:0.4rem 0.8rem; font-size:0.8rem; background:#fee2e2; color:#991b1b;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem; color:#888;">
                                No products found matching your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>
