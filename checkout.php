<?php
/**
 * CampusKart - Checkout & Transactional Order Placement
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_auth('checkout.php');

$pdo = get_db_connection();
$user = current_user();
$userId = (int)$user['id'];

$cartDetails = calculate_cart_details($pdo);
$items = $cartDetails['items'];

if (empty($items)) {
    set_flash('warning', 'Your shopping cart is empty.');
    redirect('shop.php');
}

$subtotal = $cartDetails['subtotal'];
$deliveryCharge = calculate_delivery_charge($subtotal);
$finalTotal = $subtotal + $deliveryCharge;

$errors = [];
$customerName = $user['name'];
$email        = $user['email'];
$phone        = $user['phone'] ?? '';
$address      = '';
$city         = 'Bangalore';
$state        = 'Karnataka';
$pincode      = '560064';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $customerName = trim($_POST['customer_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $state        = trim($_POST['state'] ?? '');
    $pincode      = trim($_POST['pincode'] ?? '');

    // Server-side input validation
    if ($customerName === '') $errors[] = 'Customer full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($phone === '' || strlen($phone) < 7) $errors[] = 'A valid contact phone number is required.';
    if ($address === '' || strlen($address) < 5) $errors[] = 'Delivery address/hostel room details are required.';
    if ($city === '') $errors[] = 'City is required.';
    if ($state === '') $errors[] = 'State is required.';
    if ($pincode === '' || strlen($pincode) < 4) $errors[] = 'Valid PIN code is required.';

    if (empty($errors)) {
        try {
            $client = get_supabase_client();
            if ($client !== null && $client->isConfigured()) {
                // Execute atomic transactional checkout on Supabase PostgreSQL
                $rpcItems = [];
                foreach ($items as $item) {
                    $rpcItems[] = [
                        'product_id' => (int)$item['product_id'],
                        'quantity'   => (int)$item['quantity'],
                    ];
                }

                $finalDelivery = calculate_delivery_charge($cartSubtotal);
                $orderNumber = generate_order_number();

                $rpcResult = $client->rpc('place_order_transaction', [
                    'p_order_number'    => $orderNumber,
                    'p_user_id'         => $userId,
                    'p_customer_name'   => $customerName,
                    'p_email'           => $email,
                    'p_phone'           => $phone,
                    'p_address'         => $address,
                    'p_city'            => $city,
                    'p_state'           => $state,
                    'p_pincode'         => $pincode,
                    'p_delivery_charge' => $finalDelivery,
                    'p_payment_method'  => 'Cash on Delivery',
                    'p_items'           => $rpcItems,
                ]);

                if (!empty($rpcResult['success'])) {
                    $orderId = (int)$rpcResult['order_id'];
                    $_SESSION['cart'] = [];
                    redirect("order-success.php?order_id={$orderId}&order_num=" . urlencode($orderNumber));
                    exit;
                } else {
                    throw new Exception($rpcResult['message'] ?? 'Unable to complete checkout on database.');
                }
            }

            // Fallback: Local PDO transaction
            $pdo->beginTransaction();

            // Re-verify inventory & lock/fetch current prices
            $verifiedItems = [];
            $recalculatedSubtotal = 0.00;

            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['quantity'];

                $stockStmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
                $stockStmt->execute([$pid]);
                $prod = $stockStmt->fetch();

                if (!$prod || (int)$prod['stock'] < $qty) {
                    throw new Exception("Stock became unavailable for '{$item['name']}'. Please adjust your cart.");
                }

                $unitPrice = (float)$prod['price'];
                $lineSubtotal = $unitPrice * $qty;
                $recalculatedSubtotal += $lineSubtotal;

                $verifiedItems[] = [
                    'product_id'   => $pid,
                    'product_name' => $prod['name'],
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => $lineSubtotal,
                ];
            }

            $finalDelivery = calculate_delivery_charge($recalculatedSubtotal);
            $recalculatedTotal = $recalculatedSubtotal + $finalDelivery;
            $orderNumber = generate_order_number();

            // 1. Insert into orders table
            $orderSql = "
                INSERT INTO orders (
                    order_number, user_id, customer_name, email, phone,
                    address, city, state, pincode,
                    subtotal, delivery_charge, total_amount, payment_method, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 'Cash on Delivery', 'Pending', NOW()
                )
            ";
            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([
                $orderNumber, $userId, $customerName, $email, $phone,
                $address, $city, $state, $pincode,
                $recalculatedSubtotal, $finalDelivery, $recalculatedTotal
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // 2. Insert into order_items table and safely decrement inventory
            $itemSql = "
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $itemStmt = $pdo->prepare($itemSql);

            $updateStockSql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $updateStockStmt = $pdo->prepare($updateStockSql);

            foreach ($verifiedItems as $vItem) {
                // Insert item record
                $itemStmt->execute([
                    $orderId,
                    $vItem['product_id'],
                    $vItem['product_name'],
                    $vItem['quantity'],
                    $vItem['unit_price'],
                    $vItem['subtotal']
                ]);

                // Decrement stock safely
                $updateStockStmt->execute([$vItem['quantity'], $vItem['product_id'], $vItem['quantity']]);
                if ($updateStockStmt->rowCount() === 0) {
                    throw new Exception("Insufficient inventory for product: {$vItem['product_name']}");
                }
            }

            // Commit transaction
            $pdo->commit();

            // Clear session cart
            $_SESSION['cart'] = [];

            // Redirect to confirmation page
            redirect("order-success.php?order_id={$orderId}&order_num=" . urlencode($orderNumber));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage() ?: 'An error occurred while processing your order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="cart.php">Cart</a>
            <span>/</span>
            <strong>Checkout</strong>
        </nav>
        <h1 class="page-title">Order Checkout</h1>
    </div>
</section>

<main class="container" style="padding: 3rem 1.5rem 5rem;">
    <?php if (!empty($errors)): ?>
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:1rem 1.25rem; border-radius:var(--radius-sm); margin-bottom:2rem;">
            <strong>Please resolve the following before placing your order:</strong>
            <ul style="margin:0.5rem 0 0 1.25rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST" id="checkoutForm">
        <?= csrf_field() ?>

        <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:3.5rem; align-items:start;">
            <!-- Left: Delivery Information -->
            <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem;">
                <h2 style="font-family:var(--font-display); font-size:1.45rem; font-weight:700; margin-bottom:1.5rem; padding-bottom:0.75rem; border-bottom:1px solid var(--border-light);">
                    1. Campus Delivery Address
                </h2>

                <div class="form-grid" style="margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label for="customer_name" class="form-label">Recipient Name *</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" value="<?= sanitize($customerName) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Contact Email *</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= sanitize($email) ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label for="phone" class="form-label">Mobile Phone Number (for delivery call) *</label>
                    <input type="tel" name="phone" id="phone" class="form-control" value="<?= sanitize($phone) ?>" placeholder="e.g. +91 98123 45678" required>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label for="address" class="form-label">Hostel, Room No. or Campus Department Address *</label>
                    <textarea name="address" id="address" class="form-control" rows="3" placeholder="e.g. Room 304, Boys Hostel B, Near Campus Basketball Court" required><?= sanitize($address) ?></textarea>
                </div>

                <div class="form-grid" style="margin-bottom:2rem;">
                    <div class="form-group">
                        <label for="city" class="form-label">City *</label>
                        <input type="text" name="city" id="city" class="form-control" value="<?= sanitize($city) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="state" class="form-label">State *</label>
                        <input type="text" name="state" id="state" class="form-control" value="<?= sanitize($state) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="pincode" class="form-label">Campus PIN Code *</label>
                        <input type="text" name="pincode" id="pincode" class="form-control" value="<?= sanitize($pincode) ?>" required>
                    </div>
                </div>

                <h2 style="font-family:var(--font-display); font-size:1.45rem; font-weight:700; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid var(--border-light);">
                    2. Payment Method
                </h2>

                <div style="background:var(--bg-subtle); padding:1.25rem; border-radius:var(--radius-sm); border:2px solid #111; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <input type="radio" name="payment_method" id="cod" value="Cash on Delivery" checked style="accent-color:#111; width:18px; height:18px;">
                        <label for="cod" style="font-weight:700; font-size:0.95rem; cursor:pointer;">Cash on Delivery (COD)</label>
                    </div>
                    <span style="font-size:0.82rem; background:#111; color:#fff; padding:0.2rem 0.6rem; border-radius:2px;">RECOMMENDED</span>
                </div>
                <p style="font-size:0.82rem; color:#777; margin-top:0.5rem; line-height:1.5;">Pay upon inspecting your items at your campus doorstep or hostel reception.</p>
            </div>

            <!-- Right: Order Summary Sidebar -->
            <aside class="order-summary-box">
                <h3>Order Review (<?= count($items) ?> items)</h3>

                <div style="max-height:260px; overflow-y:auto; margin-bottom:1.25rem; padding-right:0.5rem;">
                    <?php foreach ($items as $it): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem; font-size:0.88rem;">
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <img src="<?= sanitize($it['image']) ?>" alt="" style="width:40px; height:40px; object-fit:contain; background:#fff; border-radius:2px;">
                                <div>
                                    <div style="font-weight:600; color:#111;"><?= sanitize($it['name']) ?></div>
                                    <div style="color:#777; font-size:0.8rem;">Qty: <?= $it['quantity'] ?> × <?= format_price($it['price']) ?></div>
                                </div>
                            </div>
                            <div style="font-weight:700;"><?= format_price($it['line_total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?= format_price($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Charge</span>
                    <span><?= $deliveryCharge == 0.00 ? '<strong style="color:#10b981;">FREE</strong>' : format_price($deliveryCharge) ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Grand Total</span>
                    <span><?= format_price($finalTotal) ?></span>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding:1.05rem; margin-top:1.5rem; font-size:0.95rem;">
                    PLACE ORDER NOW &rarr;
                </button>

                <p style="text-align:center; font-size:0.78rem; color:#777; margin-top:1rem; line-height:1.4;">
                    By confirming this order, you authorize delivery to the provided campus address. Stock is decremented in real-time.
                </p>
            </aside>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
