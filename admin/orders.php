<?php
/**
 * CampusKart - Administrator Order Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$flash = get_flash();

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    require_csrf_token();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $upStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upStmt->execute([$newStatus, $orderId]);
        set_flash('success', "Order #{$orderId} status updated to '{$newStatus}'.");
    }
    redirect('orders.php');
}

// Search and filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($statusFilter !== '') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    {$whereSql}
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Manage Orders - CampusKart Admin';
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
            <li><a href="categories.php">🏷️ Categories</a></li>
            <li><a href="orders.php" class="active">🛒 Orders</a></li>
            <li><a href="customers.php">👥 Customers</a></li>
            <li style="margin-top:2rem; border-top:1px solid #262626; padding-top:1rem;">
                <a href="../shop.php" target="_blank">🌐 Live Storefront &rarr;</a>
            </li>
            <li><a href="../logout.php" style="color:#ef4444;">🚪 Sign Out</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111;">Manage Orders</h1>
                <p style="color:#666; font-size:0.92rem;">Review campus orders, update fulfillment stages, and inspect delivery destinations.</p>
            </div>
            
            <form action="orders.php" method="GET" style="display:flex; gap:0.6rem; align-items:center;">
                <input type="text" name="search" class="form-control" placeholder="Search order #, customer..." value="<?= sanitize($search) ?>" style="padding:0.55rem 0.85rem; font-size:0.88rem; width:220px;">
                <select name="status" class="form-control" style="padding:0.55rem 0.85rem; font-size:0.88rem; width:150px;">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Processing" <?= $statusFilter === 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Shipped" <?= $statusFilter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn-dark" style="padding:0.55rem 1rem; font-size:0.85rem;">Filter</button>
                <?php if ($statusFilter || $search): ?>
                    <a href="orders.php" class="btn-dark" style="background:#eee; color:#333; padding:0.55rem 0.85rem; font-size:0.85rem;">Clear</a>
                <?php endif; ?>
            </form>
        </header>

        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); overflow:hidden;">
            <table class="cart-table" style="font-size:0.9rem;">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Current Status</th>
                        <th>Quick Update</th>
                        <th style="text-align:right;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td>
                                    <strong style="font-family:var(--font-display); font-size:1rem; color:#111;">
                                        <a href="order-details.php?id=<?= $ord['id'] ?>"><?= sanitize($ord['order_number']) ?></a>
                                    </strong>
                                </td>
                                <td>
                                    <strong><?= sanitize($ord['customer_name']) ?></strong>
                                    <div style="font-size:0.75rem; color:#888;"><?= sanitize($ord['email']) ?></div>
                                </td>
                                <td style="font-size:0.85rem; color:#666;">
                                    <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
                                </td>
                                <td>
                                    <?= (int)$ord['item_count'] ?> items
                                </td>
                                <td style="font-family:var(--font-display); font-weight:700;">
                                    <?= format_price((float)$ord['total_amount']) ?>
                                    <div style="font-size:0.72rem; color:#888; font-weight:normal;"><?= sanitize($ord['payment_method']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $statusStyle = match(strtolower($ord['status'])) {
                                        'delivered'  => 'background:#ecfdf5; color:#065f46;',
                                        'processing' => 'background:#eff6ff; color:#1e40af;',
                                        'shipped'    => 'background:#f5f3ff; color:#5b21b6;',
                                        'cancelled'  => 'background:#fef2f2; color:#991b1b;',
                                        default      => 'background:#fffbeb; color:#92400e;'
                                    };
                                    ?>
                                    <span style="display:inline-block; padding:0.25rem 0.65rem; border-radius:12px; font-weight:700; font-size:0.78rem; <?= $statusStyle ?>">
                                        <?= sanitize($ord['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="orders.php" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:0.35rem 0.6rem; font-size:0.8rem; border:1px solid #ccc; border-radius:2px; background:#fff;">
                                            <option value="Pending" <?= $ord['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Processing" <?= $ord['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="Shipped" <?= $ord['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="Delivered" <?= $ord['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Cancelled" <?= $ord['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align:right;">
                                    <a href="order-details.php?id=<?= $ord['id'] ?>" class="btn-dark" style="padding:0.35rem 0.75rem; font-size:0.8rem;">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:3rem; color:#888;">
                                No orders matching the selected filter.
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
