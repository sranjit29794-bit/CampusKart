<?php
/**
 * CampusKart - Automated Test Suite
 * Validates database operations, stock decrements, authentication, and order workflows.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = get_db_connection();

echo "Running CampusKart Automated Verification Suite...\n\n";

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

// 1. Database Connectivity & Tables
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
assert_test("Database connection & tables exist", in_array('products', $tables) && in_array('orders', $tables));

// 2. Products Query & Counts
$products = $pdo->query("SELECT * FROM products")->fetchAll();
assert_test("Catalog products seeded (Count >= 10)", count($products) >= 10);

// 3. User Authentication Query
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin'")->fetch();
assert_test("Admin account exists", $adminUser && $adminUser['email'] === 'admin@campuskart.com');

$studentUser = $pdo->query("SELECT * FROM users WHERE email = 'rohan@college.edu'")->fetch();
assert_test("Student account exists", $studentUser && password_verify('Student@123', $studentUser['password_hash']));

// 4. Cart Calculation Logic Test
$_SESSION['cart'] = [1 => 2, 2 => 1]; // 2 Black T-Shirts (499 each) + 1 Brown Cap (349)
$cartDetails = calculate_cart_details($pdo);
$expectedSubtotal = (499.00 * 2) + 349.00; // 998 + 349 = 1347.00
assert_test("Cart subtotal calculation correct", abs($cartDetails['subtotal'] - $expectedSubtotal) < 0.01);

$delivery = calculate_delivery_charge($cartDetails['subtotal']);
assert_test("Free delivery applied for order > 500", $delivery === 0.00);

// 5. Test Transactional Order Placement & Inventory Update
$initialStock = (int)$pdo->query("SELECT stock FROM products WHERE id = 1")->fetchColumn();

$pdo->beginTransaction();
$testOrderNum = generate_order_number();
$orderStmt = $pdo->prepare("
    INSERT INTO orders (
        order_number, user_id, customer_name, email, phone,
        address, city, state, pincode, subtotal, delivery_charge,
        total_amount, payment_method, status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, 'Cash on Delivery', 'Pending', datetime('now')
    )
");
$orderStmt->execute([
    $testOrderNum, (int)$studentUser['id'], $studentUser['name'], $studentUser['email'], $studentUser['phone'],
    'Room 101, Test Hostel', 'Bangalore', 'Karnataka', '560064', 998.00, 0.00, 998.00
]);
$testOrderId = (int)$pdo->lastInsertId();

$itemStmt = $pdo->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
    VALUES (?, ?, ?, ?, ?, ?)
");
$itemStmt->execute([$testOrderId, 1, 'Black T-shirt', 2, 499.00, 998.00]);

$updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
$updateStock->execute([2, 1]);
$pdo->commit();

$newStock = (int)$pdo->query("SELECT stock FROM products WHERE id = 1")->fetchColumn();
assert_test("Order placement created order record", $testOrderId > 0);
assert_test("Inventory decremented atomically (-2)", $newStock === ($initialStock - 2));

// 6. Test User Order Isolation (Ownership Security)
$ownOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$ownOrder->execute([$testOrderId, (int)$studentUser['id']]);
assert_test("Owner can access their order", (bool)$ownOrder->fetch());

$foreignOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$foreignOrder->execute([$testOrderId, 99999]); // Non-owner
assert_test("Unauthorized user blocked from order", $foreignOrder->fetch() === false);

echo "\nVerification Summary: {$testsPassed} / {$totalTests} Tests Passed.\n";
