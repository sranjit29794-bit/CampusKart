<?php
/**
 * CampusKart - Customer Order History (My Orders)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_auth('orders.php');

$pdo = get_db_connection();
$user = current_user();
$userId = (int)$user['id'];

// Fetch all orders belonging to this user
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>Orders</strong>
        </nav>
        <h1 class="page-title">My Orders</h1>
    </div>
</section>

<main class="container" style="padding: 3.5rem 1.5rem 6rem;">
    <?php if (!empty($orders)): ?>
        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); overflow:hidden;">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Order Ref</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <strong style="font-family:var(--font-display); font-size:1.05rem; color:#111;">
                                    <?= sanitize($ord['order_number']) ?>
                                </strong>
                                <div style="font-size:0.8rem; color:#888;"><?= sanitize($ord['payment_method']) ?></div>
                            </td>
                            <td style="color:#666; font-size:0.9rem;">
                                <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
                            </td>
                            <td>
                                <span style="font-weight:600;"><?= (int)$ord['item_count'] ?></span> <?= (int)$ord['item_count'] === 1 ? 'item' : 'items' ?>
                            </td>
                            <td style="font-family:var(--font-display); font-weight:700; font-size:1.1rem; color:#111;">
                                <?= format_price((float)$ord['total_amount']) ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = match(strtolower($ord['status'])) {
                                    'delivered'  => 'background:#ecfdf5; color:#065f46;',
                                    'processing' => 'background:#eff6ff; color:#1e40af;',
                                    'shipped'    => 'background:#f5f3ff; color:#5b21b6;',
                                    'cancelled'  => 'background:#fef2f2; color:#991b1b;',
                                    default      => 'background:#fffbeb; color:#92400e;'
                                };
                                ?>
                                <span style="display:inline-block; padding:0.3rem 0.75rem; border-radius:12px; font-size:0.8rem; font-weight:700; <?= $statusClass ?>">
                                    <?= sanitize($ord['status']) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a href="order-details.php?id=<?= (int)$ord['id'] ?>" class="btn-dark" style="padding:0.45rem 1rem; font-size:0.82rem;">
                                    VIEW DETAILS &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <!-- Empty Order History -->
        <div style="padding: 5rem 1.5rem; text-align: center; max-width: 500px; margin: 0 auto; background:var(--bg-subtle); border-radius:var(--radius-sm);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h2 style="font-family:var(--font-display); font-size:1.75rem; margin-bottom:0.75rem;">No Orders Placed Yet</h2>
            <p style="color:#666; line-height:1.6; margin-bottom:2rem;">You haven't placed any orders with CampusKart yet. Explore our campus catalog to find student apparel, textbooks, stationery, and tech.</p>
            <a href="shop.php" class="btn-primary">START SHOPPING</a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
