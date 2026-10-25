<?php
/**
 * CampusKart - Administrator Dashboard Overview
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();

// Database-backed KPI statistics (Never hardcoded)
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();

// Fetch 5 Recent Orders
$recentOrdersStmt = $pdo->query("
    SELECT o.*, u.name AS user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC
    LIMIT 5
");
$recentOrders = $recentOrdersStmt->fetchAll();

// Fetch Low Stock Products (< 15 items remaining)
$lowStockStmt = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= 15
    ORDER BY p.stock ASC
    LIMIT 6
");
$lowStockProducts = $lowStockStmt->fetchAll();

$pageTitle = 'Admin Dashboard - CampusKart';
$adminSection = 'dashboard';
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
    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div style="display:flex; align-items:center; gap:0.6rem; padding-bottom:1.5rem; border-bottom:1px solid #262626;">
            <span class="logo-icon" style="background:var(--accent-red); width:28px; height:28px;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;"><path d="M16 6V4a4 4 0 0 0-8 0v2H3v16h18V6h-5zm-6-2a2 2 0 0 1 4 0v2h-4V4zm9 16H5V8h14v12z"/></svg>
            </span>
            <span style="font-family:var(--font-display); font-size:1.25rem; font-weight:700;">AdminPortal</span>
        </div>

        <ul class="admin-nav">
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="products.php">📦 Products</a></li>
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

        <div style="margin-top:auto; font-size:0.8rem; color:#777; border-top:1px solid #262626; padding-top:1rem;">
            Logged in as <strong><?= sanitize($_SESSION['user']['name']) ?></strong>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
            <div>
                <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111;">Dashboard Overview</h1>
                <p style="color:#666; font-size:0.92rem;">Real-time campus sales performance and inventory status.</p>
            </div>
            <div>
                <a href="product-create.php" class="btn-primary" style="padding:0.75rem 1.5rem; font-size:0.85rem;">+ ADD NEW PRODUCT</a>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value" style="color:#10b981;"><?= format_price($totalRevenue) ?></div>
                <div style="font-size:0.8rem; color:#888; margin-top:0.25rem;">Excluding cancelled orders</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?= $totalOrders ?></div>
                <div style="font-size:0.8rem; color:#888; margin-top:0.25rem;">Lifetime completed &amp; pending</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Active Catalog Items</div>
                <div class="kpi-value"><?= $totalProducts ?></div>
                <div style="font-size:0.8rem; color:#888; margin-top:0.25rem;">Across 5 departments</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Registered Customers</div>
                <div class="kpi-value"><?= $totalCustomers ?></div>
                <div style="font-size:0.8rem; color:#888; margin-top:0.25rem;">Verified student accounts</div>
            </div>
        </div>

        <!-- 2 Column Layout: Recent Orders & Low Stock Warning -->
        <div style="display:grid; grid-template-columns:1.35fr 0.65fr; gap:2rem; align-items:start;">
            <!-- Recent Orders Table -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:1.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <h2 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700;">Recent Campus Orders</h2>
                    <a href="orders.php" style="font-size:0.85rem; color:var(--accent-red); font-weight:700;">View All Orders &rarr;</a>
                </div>

                <?php if (!empty($recentOrders)): ?>
                    <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
                        <thead>
                            <tr style="border-bottom:1px solid #eee; text-align:left; color:#888;">
                                <th style="padding:0.75rem 0.5rem;">Order Ref</th>
                                <th style="padding:0.75rem 0.5rem;">Customer</th>
                                <th style="padding:0.75rem 0.5rem;">Amount</th>
                                <th style="padding:0.75rem 0.5rem;">Status</th>
                                <th style="padding:0.75rem 0.5rem; text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ord): ?>
                                <tr style="border-bottom:1px solid #f2f2f2;">
                                    <td style="padding:0.85rem 0.5rem; font-weight:700;">
                                        <a href="order-details.php?id=<?= $ord['id'] ?>" style="color:#111;"><?= sanitize($ord['order_number']) ?></a>
                                    </td>
                                    <td style="padding:0.85rem 0.5rem; color:#444;">
                                        <?= sanitize($ord['customer_name']) ?>
                                    </td>
                                    <td style="padding:0.85rem 0.5rem; font-weight:600;">
                                        <?= format_price((float)$ord['total_amount']) ?>
                                    </td>
                                    <td style="padding:0.85rem 0.5rem;">
                                        <span style="display:inline-block; padding:0.2rem 0.6rem; border-radius:10px; font-size:0.75rem; font-weight:700; background:#f0f0f2;">
                                            <?= sanitize($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding:0.85rem 0.5rem; text-align:right;">
                                        <a href="order-details.php?id=<?= $ord['id'] ?>" style="color:var(--accent-red); font-weight:600;">Manage &rarr;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#888; font-size:0.9rem; padding:1.5rem 0; text-align:center;">No orders placed yet.</p>
                <?php endif; ?>
            </div>

            <!-- Low Stock Warnings -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:1.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <h2 style="font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#b91c1c;">⚠️ Low Stock Alerts</h2>
                    <a href="products.php" style="font-size:0.85rem; color:#888;">All Items</a>
                </div>

                <?php if (!empty($lowStockProducts)): ?>
                    <div style="display:flex; flex-direction:column; gap:0.85rem;">
                        <?php foreach ($lowStockProducts as $lp): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 0; border-bottom:1px solid #f2f2f2; font-size:0.88rem;">
                                <div>
                                    <strong style="display:block; color:#111;"><?= sanitize($lp['name']) ?></strong>
                                    <span style="font-size:0.75rem; color:#888;"><?= sanitize($lp['category_name']) ?></span>
                                </div>
                                <div style="text-align:right;">
                                    <span style="color:#dc2626; font-weight:700; font-size:0.9rem;"><?= $lp['stock'] ?> left</span>
                                    <div style="margin-top:0.2rem;">
                                        <a href="product-edit.php?id=<?= $lp['id'] ?>" style="font-size:0.75rem; color:var(--accent-red); font-weight:600;">Restock</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#10b981; font-size:0.9rem; padding:1.5rem 0; text-align:center;">All products have healthy inventory levels.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>
