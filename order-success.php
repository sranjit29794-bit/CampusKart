<?php
/**
 * CampusKart - Order Confirmation Success Page
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_auth();

$pdo = get_db_connection();
$user = current_user();
$userId = (int)$user['id'];
$orderId = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    redirect('orders.php');
}

// Fetch order verifying ownership
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found or unauthorized access.');
    redirect('orders.php');
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

$pageTitle = 'Order Confirmed - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4.5rem 1.5rem 6rem; max-width: 780px;">
    <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:3rem; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.04);">
        <!-- Success Icon -->
        <div style="width:72px; height:72px; background:#ecfdf5; color:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1 style="font-family:var(--font-display); font-size:2.2rem; font-weight:700; margin-bottom:0.5rem; color:#111;">Order Successfully Placed!</h1>
        <p style="color:#666; font-size:1.02rem; margin-bottom:1.75rem;">Thank you, <?= sanitize($order['customer_name']) ?>. Your order has been registered in our database and will be delivered to your campus location.</p>

        <div style="background:var(--bg-subtle); padding:1rem 1.5rem; border-radius:var(--radius-sm); display:inline-block; margin-bottom:2.5rem;">
            <span style="font-size:0.85rem; color:#777; text-transform:uppercase; letter-spacing:0.05em; font-weight:700;">Order Reference: </span>
            <strong style="font-family:var(--font-display); font-size:1.25rem; color:var(--accent-red);"><?= sanitize($order['order_number']) ?></strong>
        </div>

        <!-- Order Summary Card -->
        <div style="text-align:left; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:1.75rem; margin-bottom:2rem;">
            <h3 style="font-family:var(--font-display); font-size:1.15rem; margin-bottom:1rem; border-bottom:1px solid var(--border-light); padding-bottom:0.5rem;">
                Purchased Items
            </h3>
            
            <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;">
                <?php foreach ($orderItems as $item): ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.92rem;">
                        <span><?= sanitize($item['product_name']) ?> <strong style="color:#888;">× <?= $item['quantity'] ?></strong></span>
                        <span style="font-weight:600;"><?= format_price((float)$item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top:1px dashed #ddd; padding-top:1rem; display:flex; justify-content:space-between; font-size:1.15rem; font-weight:700; color:#111;">
                <span>Total Paid (COD):</span>
                <span><?= format_price((float)$order['total_amount']) ?></span>
            </div>
        </div>

        <!-- Delivery & Hostel Details -->
        <div style="text-align:left; background:var(--bg-subtle); border-radius:var(--radius-sm); padding:1.5rem; font-size:0.9rem; line-height:1.6; color:#444; margin-bottom:2.5rem;">
            <strong>Campus Delivery Destination:</strong><br>
            <?= nl2br(sanitize($order['address'])) ?><br>
            <?= sanitize($order['city']) ?>, <?= sanitize($order['state']) ?> - <?= sanitize($order['pincode']) ?><br>
            <strong>Contact Phone:</strong> <?= sanitize($order['phone']) ?>
        </div>

        <div style="display:flex; gap:1rem; justify-content:center;">
            <a href="orders.php" class="btn-dark">VIEW MY ORDERS</a>
            <a href="shop.php" class="btn-primary">CONTINUE SHOPPING</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
