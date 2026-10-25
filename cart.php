<?php
/**
 * CampusKart - Shopping Cart Page
 */

declare(strict_types=1);

$pageTitle = 'Shopping Cart - CampusKart';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Quick add from URL if provided
if (isset($_GET['add']) || (isset($_GET['action']) && $_GET['action'] === 'add')) {
    $addId = (int)($_GET['product_id'] ?? ($_GET['id'] ?? ($_GET['add'] ?? 0)));
    $addQty = (int)($_GET['quantity'] ?? ($_GET['qty'] ?? 1));
    if ($addId > 0 && $addQty > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$addId] = (int)($_SESSION['cart'][$addId] ?? 0) + $addQty;
        set_flash('success', 'Item added to your cart.');
        redirect('cart.php');
    }
}

$cartDetails = calculate_cart_details($pdo);

$items = $cartDetails['items'];
$subtotal = $cartDetails['subtotal'];
$deliveryCharge = calculate_delivery_charge($subtotal);
$finalTotal = $subtotal + $deliveryCharge;
$freeShippingThreshold = 500.00;
?>

<!-- Page Banner -->
<section class="page-hero-banner" aria-label="Page Title Banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>Cart</strong>
        </nav>
        <h1 class="page-title">Shopping Cart</h1>
    </div>
</section>

<main class="container">
    <?php if (!empty($items)): ?>
        <!-- Free Shipping Motivation Progress -->
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:1rem 1.25rem; border-radius:var(--radius-sm); margin-top:2rem; font-size:0.92rem; display:flex; align-items:center; justify-content:space-between;">
            <?php if ($subtotal >= $freeShippingThreshold): ?>
                <span>🎉 <strong>Congratulations!</strong> Your order qualifies for <strong>FREE Campus Hostel Delivery</strong>.</span>
            <?php else: ?>
                <?php $needed = $freeShippingThreshold - $subtotal; ?>
                <span>Add <strong><?= format_price($needed) ?></strong> more to unlock <strong>FREE Campus Delivery</strong> (Standard delivery: <?= format_price(50.00) ?>).</span>
            <?php endif; ?>
            <a href="shop.php" style="color:#15803d; font-weight:700; text-decoration:underline;">Add more items &rarr;</a>
        </div>

        <div class="cart-layout">
            <!-- Left: Items Table and Update Form -->
            <div class="cart-items-pane">
                <form action="cart-action.php" method="POST" id="cartForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">

                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="text-align:center;">Quantity</th>
                                <th style="text-align:right;">Subtotal</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-product-cell">
                                            <a href="product.php?id=<?= $item['product_id'] ?>" class="cart-product-thumb">
                                                <img src="<?= sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>">
                                            </a>
                                            <div>
                                                <h4 style="font-family:var(--font-display); font-size:1.05rem; margin-bottom:0.25rem;">
                                                    <a href="product.php?id=<?= $item['product_id'] ?>"><?= sanitize($item['name']) ?></a>
                                                </h4>
                                                <span style="font-size:0.75rem; text-transform:uppercase; color:#888; font-weight:600;"><?= sanitize($item['category_name']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= format_price($item['price']) ?></div>
                                    </td>
                                    <td style="text-align:center;">
                                        <div class="qty-control" style="margin:0 auto; width:fit-content;">
                                            <button type="button" class="qty-btn qty-dec">−</button>
                                            <input type="number" name="quantities[<?= $item['product_id'] ?>]" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                                            <button type="button" class="qty-btn qty-inc">+</button>
                                        </div>
                                    </td>
                                    <td style="text-align:right; font-family:var(--font-display); font-weight:700; font-size:1.05rem;">
                                        <?= format_price($item['line_total']) ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="submit" form="remove-form-<?= $item['product_id'] ?>" class="action-btn" style="margin:0 auto; color:#888;" title="Remove Item" aria-label="Remove <?= sanitize($item['name']) ?>">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem;">
                        <a href="shop.php" class="btn-dark" style="background:#eee; color:#111;">&larr; CONTINUE SHOPPING</a>
                        <button type="submit" class="btn-dark">UPDATE CART</button>
                    </div>
                </form>

                <!-- Hidden individual remove forms -->
                <?php foreach ($items as $item): ?>
                    <form action="cart-action.php" method="POST" id="remove-form-<?= $item['product_id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                    </form>
                <?php endforeach; ?>
            </div>

            <!-- Right: Order Summary Card -->
            <aside class="order-summary-box">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Items Subtotal</span>
                    <span style="font-weight:600;"><?= format_price($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Charge</span>
                    <span>
                        <?php if ($deliveryCharge == 0.00): ?>
                            <strong style="color:#10b981;">FREE</strong>
                        <?php else: ?>
                            <?= format_price($deliveryCharge) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Payment Method</span>
                    <span style="color:#111; font-weight:500;">Cash on Delivery</span>
                </div>
                <div class="summary-row total-row">
                    <span>Total Amount</span>
                    <span><?= format_price($finalTotal) ?></span>
                </div>

                <a href="checkout.php" class="btn-primary" style="display:block; text-align:center; width:100%; margin-top:1.5rem; padding:1rem;">
                    PROCEED TO CHECKOUT &rarr;
                </a>

                <div style="margin-top:1.5rem; text-align:center; font-size:0.8rem; color:#888;">
                    🔒 Safe &amp; verified checkout for registered university accounts.
                </div>
            </aside>
        </div>
    <?php else: ?>
        <!-- Empty Cart State -->
        <div style="padding: 6rem 1.5rem; text-align: center; max-width: 500px; margin: 0 auto;">
            <div style="width: 80px; height: 80px; background: var(--bg-subtle); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #999;">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </div>
            <h2 style="font-family:var(--font-display); font-size:1.85rem; margin-bottom:0.75rem;">Your Cart is Empty</h2>
            <p style="color:#666; line-height:1.6; margin-bottom:2rem;">Looks like you haven't added any campus items to your shopping cart yet. Browse our catalog of collegiate apparel, sneakers, stationery, and backpacks.</p>
            <a href="shop.php" class="btn-primary" style="padding:0.9rem 2rem;">EXPLORE PRODUCTS NOW</a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
