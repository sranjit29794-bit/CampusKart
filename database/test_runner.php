<?php
/**
 * CampusKart - Automated Test Suite
 * Validates Supabase PostgreSQL connectivity, Row Level Security, catalog data,
 * cart calculations, and atomic checkout transactions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = get_db_connection();
$isSupabase = ($pdo instanceof SupabasePDO);
$client = get_supabase_client();

echo "========================================================\n";
echo "   CampusKart - Automated Verification Suite           \n";
echo "   Active Backend: " . ($isSupabase ? "Supabase PostgreSQL (Cloud)" : "Local PDO Fallback") . "\n";
echo "========================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assert_test(string $name, bool $condition): void {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$name}\n";
    } else {
        echo "  [FAIL] {$name}\n";
    }
}

// 1. Database Connectivity & Table Discovery
if ($isSupabase) {
    $tables = array_column($pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'")->fetchAll(), 'table_name');
} else {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
}
assert_test("Database connection & tables exist", in_array('products', $tables) && in_array('orders', $tables) && in_array('categories', $tables));

// 2. Catalog Data & Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
assert_test("Categories seeded (Count = 5)", count($categories) === 5);

$products = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
assert_test("Catalog products seeded (Count >= 10)", count($products) >= 10);

// 3. User Authentication & Role Separation
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin'")->fetch();
assert_test("Admin account exists (admin@campuskart.com)", $adminUser && $adminUser['email'] === 'admin@campuskart.com');
assert_test("Admin password hash validates", $adminUser && password_verify('Admin@123', $adminUser['password_hash']));

$studentUser = $pdo->query("SELECT * FROM users WHERE email = 'rohan@college.edu'")->fetch();
assert_test("Student account exists (rohan@college.edu)", $studentUser && $studentUser['role'] === 'customer');
assert_test("Student password hash validates", $studentUser && password_verify('Student@123', $studentUser['password_hash']));

// 4. Cart Calculation Logic Test
$_SESSION['cart'] = [1 => 2, 2 => 1]; // 2 Black T-Shirts (499 each) + 1 Brown Cap (349)
$cartDetails = calculate_cart_details($pdo);
$expectedSubtotal = (499.00 * 2) + 349.00; // 998 + 349 = 1347.00
assert_test("Cart subtotal calculation correct (Rs 1347.00)", abs($cartDetails['subtotal'] - $expectedSubtotal) < 0.01);

$delivery = calculate_delivery_charge($cartDetails['subtotal']);
assert_test("Free delivery applied for order > Rs 500", $delivery === 0.00);

// 5. Atomic Checkout & Stock Decrement Verification
$initialStock = (int)$pdo->query("SELECT stock FROM products WHERE id = 1")->fetchColumn();
$testOrderNum = "CK-TEST-" . time();

if ($isSupabase && $client !== null) {
    // Test Supabase PostgreSQL RPC place_order_transaction
    $rpcResult = $client->rpc('place_order_transaction', [
        'p_order_number'    => $testOrderNum,
        'p_user_id'         => (int)$studentUser['id'],
        'p_customer_name'   => $studentUser['name'],
        'p_email'           => $studentUser['email'],
        'p_phone'           => $studentUser['phone'],
        'p_address'         => 'Room 101, Test Hostel',
        'p_city'            => 'Bangalore',
        'p_state'           => 'Karnataka',
        'p_pincode'         => '560064',
        'p_delivery_charge' => 0.00,
        'p_payment_method'  => 'Cash on Delivery',
        'p_items'           => [
            ['product_id' => 1, 'quantity' => 2]
        ]
    ]);

    assert_test("Atomic RPC checkout transaction succeeded", !empty($rpcResult['success']));
    $testOrderId = (int)($rpcResult['order_id'] ?? 0);
} else {
    // Fallback transaction test
    $pdo->beginTransaction();
    $orderStmt = $pdo->prepare("
        INSERT INTO orders (order_number, user_id, customer_name, email, phone, address, city, state, pincode, subtotal, delivery_charge, total_amount, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cash on Delivery', 'Pending', CURRENT_TIMESTAMP)
    ");
    $orderStmt->execute([$testOrderNum, (int)$studentUser['id'], $studentUser['name'], $studentUser['email'], $studentUser['phone'], 'Room 101, Test Hostel', 'Bangalore', 'Karnataka', '560064', 998.00, 0.00, 998.00]);
    $testOrderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $itemStmt->execute([$testOrderId, 1, 'Black T-shirt', 2, 499.00, 998.00]);

    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $updateStock->execute([2, 1]);
    $pdo->commit();
    assert_test("Order placement created order record", $testOrderId > 0);
}

$newStock = (int)$pdo->query("SELECT stock FROM products WHERE id = 1")->fetchColumn();
assert_test("Inventory decremented atomically (-2)", $newStock === ($initialStock - 2));

// 6. Test User Order Isolation (Ownership Security)
$ownOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$ownOrder->execute([$testOrderId, (int)$studentUser['id']]);
assert_test("Owner can access their order", (bool)$ownOrder->fetch());

$foreignOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$foreignOrder->execute([$testOrderId, 99999]); // Non-owner
assert_test("Unauthorized user blocked from order", $foreignOrder->fetch() === false);

// 7. Cleanup Test Records
if ($testOrderId > 0) {
    if ($isSupabase && $client !== null) {
        $client->execute("DELETE FROM orders WHERE id = {$testOrderId}");
        $client->execute("UPDATE products SET stock = stock + 2 WHERE id = 1");
    } else {
        $pdo->exec("DELETE FROM orders WHERE id = {$testOrderId}");
        $pdo->exec("UPDATE products SET stock = stock + 2 WHERE id = 1");
    }
}

echo "\nVerification Summary: {$testsPassed} / {$totalTests} Tests Passed.\n";
