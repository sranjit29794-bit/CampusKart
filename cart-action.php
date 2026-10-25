<?php
/**
 * CampusKart - Cart Actions Controller (Add, Update, Remove, Clear)
 * Enforces server-side validation, stock limits, and CSRF protection.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
}

$pdo = get_db_connection();
$action = $_REQUEST['action'] ?? ($_GET['add'] ? 'add' : '');
$returnUrl = $_SERVER['HTTP_REFERER'] ?? 'cart.php';

if ($action === 'add') {
    $productId = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : (isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0);
    $quantity  = isset($_REQUEST['quantity']) ? (int)$_REQUEST['quantity'] : (isset($_REQUEST['qty']) ? (int)$_REQUEST['qty'] : 1);

    if ($productId <= 0 || $quantity <= 0) {
        set_flash('error', 'Invalid product or quantity specified.');
        redirect($returnUrl);
    }

    // Verify product exists and check current database stock
    $stmt = $pdo->prepare("SELECT id, name, stock, price FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('error', 'The requested product does not exist.');
        redirect($returnUrl);
    }

    $availableStock = (int)$product['stock'];
    if ($availableStock <= 0) {
        set_flash('error', "'{$product['name']}' is currently out of stock.");
        redirect($returnUrl);
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $currentQtyInCart = (int)($_SESSION['cart'][$productId] ?? 0);
    $newTotalQty = $currentQtyInCart + $quantity;

    if ($newTotalQty > $availableStock) {
        $allowedToAdd = $availableStock - $currentQtyInCart;
        if ($allowedToAdd <= 0) {
            set_flash('warning', "You already have the maximum available stock ({$availableStock}) in your cart.");
            redirect('cart.php');
        }
        $_SESSION['cart'][$productId] = $availableStock;
        set_flash('warning', "Added {$allowedToAdd} items. Maximum available stock ({$availableStock}) reached.");
    } else {
        $_SESSION['cart'][$productId] = $newTotalQty;
        set_flash('success', "Added '{$product['name']}' to your cart.");
    }

    redirect('cart.php');
}

if ($action === 'update') {
    $quantities = $_POST['quantities'] ?? [];
    if (!is_array($quantities)) {
        set_flash('error', 'Invalid cart update payload.');
        redirect('cart.php');
    }

    foreach ($quantities as $productId => $qty) {
        $productId = (int)$productId;
        $qty = (int)$qty;

        if ($qty <= 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        // Verify stock
        $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            $stock = (int)$product['stock'];
            if ($qty > $stock) {
                $_SESSION['cart'][$productId] = $stock;
                set_flash('warning', "Adjusted '{$product['name']}' to available stock ({$stock}).");
            } else {
                $_SESSION['cart'][$productId] = $qty;
            }
        }
    }

    set_flash('success', 'Your cart was updated.');
    redirect('cart.php');
}

if ($action === 'remove') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        set_flash('info', 'Item removed from your cart.');
    }
    redirect('cart.php');
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    set_flash('info', 'Your cart has been cleared.');
    redirect('cart.php');
}

redirect('cart.php');
