<?php
/**
 * CampusKart - Category Management (Admin)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$flash = get_flash();
$errors = [];

// Handle Actions (Create, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = 'Category name cannot be blank.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            
            // Check uniqueness of slug
            $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $checkStmt->execute([$slug]);
            if ($checkStmt->fetch()) {
                $slug .= '-' . bin2hex(random_bytes(2));
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            set_flash('success', "Category '{$name}' created.");
            redirect('categories.php');
        }
    }

    if ($action === 'delete') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delStmt->execute([$catId]);
            set_flash('success', 'Category deleted successfully.');
            redirect('categories.php');
        }
    }
}

// Fetch categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.id ASC
")->fetchAll();

$pageTitle = 'Manage Categories - CampusKart Admin';
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
    <aside class="admin-sidebar">
        <div style="display:flex; align-items:center; gap:0.6rem; padding-bottom:1.5rem; border-bottom:1px solid #262626;">
            <span class="logo-icon" style="background:var(--accent-red); width:28px; height:28px;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M16 6V4a4 4 0 0 0-8 0v2H3v16h18V6h-5zm-6-2a2 2 0 0 1 4 0v2h-4V4zm9 16H5V8h14v12z"/></svg>
            </span>
            <span style="font-family:var(--font-display); font-size:1.25rem; font-weight:700;">AdminPortal</span>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="products.php">📦 Products</a></li>
            <li><a href="categories.php" class="active">🏷️ Categories</a></li>
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
            <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111;">Manage Categories</h1>
            <p style="color:#666; font-size:0.92rem;">Organize products into campus departments and shopping classifications.</p>
        </header>

        <div style="display:grid; grid-template-columns:1.3fr 0.7fr; gap:2.5rem; align-items:start;">
            <!-- Categories Table -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); overflow:hidden;">
                <table class="cart-table" style="font-size:0.9rem;">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong style="color:#111; font-size:0.98rem; display:block;"><?= sanitize($cat['name']) ?></strong>
                                    <span style="font-size:0.8rem; color:#777;"><?= sanitize($cat['description'] ?? '') ?></span>
                                </td>
                                <td>
                                    <code><?= sanitize($cat['slug']) ?></code>
                                </td>
                                <td>
                                    <span style="background:var(--bg-subtle); padding:0.25rem 0.65rem; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                        <?= (int)$cat['product_count'] ?> items
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <form action="categories.php" method="POST" onsubmit="return confirm('Delete category \'<?= addslashes(sanitize($cat['name'])) ?>\'?');" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn-dark" style="background:#fee2e2; color:#991b1b; padding:0.35rem 0.75rem; font-size:0.8rem;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Create New Category Card -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem;">
                <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700; margin-bottom:1.25rem;">+ Add New Category</h3>

                <?php if (!empty($errors)): ?>
                    <div style="background:#fef2f2; color:#991b1b; padding:0.75rem; border-radius:2px; font-size:0.85rem; margin-bottom:1rem;">
                        <?= sanitize(implode(' ', $errors)) ?>
                    </div>
                <?php endif; ?>

                <form action="categories.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="form-group" style="margin-bottom:1.25rem;">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Laboratory Coat &amp; Safety" required>
                    </div>

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label for="description" class="form-label">Description (optional)</label>
                        <textarea name="description" id="description" rows="3" class="form-control" placeholder="Brief summary of department items..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; padding:0.8rem;">SAVE CATEGORY</button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
