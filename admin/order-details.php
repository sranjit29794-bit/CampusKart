<?php
/**
 * CampusKart - Admin Order Inspection & Fulfillment
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$flash = get_flash();
$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    set_flash('error', 'Invalid order ID.');
    redirect('orders.php');
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    require_csrf_token();
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (in_array($newStatus, $allowed, true)) {
        $upStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upStmt->execute([$newStatus, $orderId]);
        set_flash('success', "Order fulfillment status updated to '{$newStatus}'.");
    }
    redirect("order-details.php?id={$orderId}");
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('orders.php');
}

// Fetch order items with current product metadata
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$pageTitle = "Order {$order['order_number']} - CampusKart Admin";
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
                <a href="orders.php" style="font-size:0.85rem; color:#666;">&larr; Back to All Orders</a>
                <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:#111; margin-top:0.25rem;">Order <?= sanitize($order['order_number']) ?></h1>
            </div>
            <div>
                <button type="button" class="btn-dark" onclick="window.print()" style="font-size:0.85rem; padding:0.6rem 1.25rem;">
                    🖨️ Print Dispatch Slip
                </button>
            </div>
        </header>

        <div style="display:grid; grid-template-columns:1.35fr 0.65fr; gap:2.5rem; align-items:start;">
            <!-- Left: Order Items & Subtotal -->
            <div>
                <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem; margin-bottom:2rem;">
                    <h2 style="font-family:var(--font-display); font-size:1.25rem; margin-bottom:1.25rem;">Fulfillment Items</h2>
                    
                    <table class="cart-table" style="font-size:0.9rem;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Unit Price</th>
                                <th style="text-align:center;">Qty</th>
                                <th style="text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <?php if (!empty($it['image'])): ?>
                                                <img src="../<?= sanitize($it['image']) ?>" alt="" style="width:40px; height:40px; object-fit:contain; background:#f5f5f5; border-radius:2px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= sanitize($it['product_name']) ?></strong>
                                                <?php if (!empty($it['product_id'])): ?>
                                                    <div style="font-size:0.75rem; color:#888;">ID: #PROD-<?= $it['product_id'] ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= format_price((float)$it['unit_price']) ?></td>
                                    <td style="text-align:center; font-weight:600;"><?= (int)$it['quantity'] ?></td>
                                    <td style="text-align:right; font-family:var(--font-display); font-weight:700;">
                                        <?= format_price((float)$it['subtotal']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top:2rem; max-width:300px; margin-left:auto;">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><?= format_price((float)$order['subtotal']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <span><?= (float)$order['delivery_charge'] == 0.00 ? 'FREE' : format_price((float)$order['delivery_charge']) ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total Due:</span>
                            <span><?= format_price((float)$order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Customer & Destination Details -->
                <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem; display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                    <div>
                        <h3 style="font-family:var(--font-display); font-size:1.1rem; margin-bottom:0.75rem;">Campus Delivery Address</h3>
                        <p style="color:#555; line-height:1.6; font-size:0.9rem;">
                            <strong><?= sanitize($order['customer_name']) ?></strong><br>
                            <?= nl2br(sanitize($order['address'])) ?><br>
                            <?= sanitize($order['city']) ?>, <?= sanitize($order['state']) ?> - <?= sanitize($order['pincode']) ?>
                        </p>
                    </div>
                    <div>
                        <h3 style="font-family:var(--font-display); font-size:1.1rem; margin-bottom:0.75rem;">Recipient Contact</h3>
                        <p style="color:#555; line-height:1.6; font-size:0.9rem;">
                            <strong>Phone:</strong> <?= sanitize($order['phone']) ?><br>
                            <strong>Email:</strong> <?= sanitize($order['email']) ?><br>
                            <strong>Payment:</strong> <?= sanitize($order['payment_method']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Order Status Management Form -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem;">
                <h3 style="font-family:var(--font-display); font-size:1.2rem; margin-bottom:1.25rem;">Order Lifecycle</h3>

                <div style="margin-bottom:1.5rem;">
                    <span style="font-size:0.8rem; text-transform:uppercase; color:#888; font-weight:700;">Active State</span>
                    <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:#111; margin-top:0.25rem;">
                        <?= sanitize($order['status']) ?>
                    </div>
                    <div style="font-size:0.82rem; color:#777; margin-top:0.25rem;">
                        Placed <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                    </div>
                </div>

                <form action="order-details.php?id=<?= $orderId ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label for="status" class="form-label">Transition Order Status</label>
                        <select name="status" id="status" class="form-control" style="font-weight:600;">
                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending (Order Logged)</option>
                            <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing (Items Packed)</option>
                            <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped (Out for Delivery)</option>
                            <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered (Hostel Handover)</option>
                            <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; padding:0.85rem;">UPDATE ORDER STATUS</button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
