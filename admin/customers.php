<?php
/**
 * CampusKart - Customer Directory (Admin)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();

// Fetch customer accounts with aggregate metrics (Never selects password_hash)
$customers = $pdo->query("
    SELECT 
        u.id, u.name, u.email, u.phone, u.role, u.created_at,
        COUNT(o.id) AS total_orders,
        COALESCE(SUM(o.total_amount), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'Cancelled'
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();

$pageTitle = 'Registered Customers - CampusKart Admin';
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
            <li><a href="products.php">📦 Products</a></li>
            <li><a href="categories.php">🏷️ Categories</a></li>
            <li><a href="orders.php">🛒 Orders</a></li>
            <li><a href="customers.php" class="active">👥 Customers</a></li>
            <li style="margin-top:2rem; border-top:1px solid #262626; padding-top:1rem;">
                <a href="../shop.php" target="_blank">🌐 Live Storefront &rarr;</a>
            </li>
            <li><a href="../logout.php" style="color:#ef4444;">🚪 Sign Out</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <header style="margin-bottom:2rem;">
            <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111;">Registered Customers</h1>
            <p style="color:#666; font-size:0.92rem;">Verified student accounts, activity metrics, and lifetime transaction values.</p>
        </header>

        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); overflow:hidden;">
            <table class="cart-table" style="font-size:0.9rem;">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered Date</th>
                        <th style="text-align:center;">Total Orders</th>
                        <th style="text-align:right;">Lifetime Spend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <strong style="color:#111; font-size:0.98rem;"><?= sanitize($c['name']) ?></strong>
                                    <div style="font-size:0.75rem; color:#888;">ID: #USER-<?= $c['id'] ?></div>
                                </td>
                                <td>
                                    <code><?= sanitize($c['email']) ?></code>
                                </td>
                                <td>
                                    <?= sanitize($c['phone']) ?>
                                </td>
                                <td style="color:#666; font-size:0.85rem;">
                                    <?= date('d M Y', strtotime($c['created_at'])) ?>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; background:var(--bg-subtle); padding:0.2rem 0.6rem; border-radius:10px;">
                                        <?= (int)$c['total_orders'] ?>
                                    </span>
                                </td>
                                <td style="text-align:right; font-family:var(--font-display); font-weight:700; color:#111;">
                                    <?= format_price((float)$c['total_spent']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:#888;">
                                No customer accounts registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
