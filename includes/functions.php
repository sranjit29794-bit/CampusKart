<?php
/**
 * CampusKart - General Helper and Business Logic Functions
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escapes output for safe HTML rendering to prevent XSS.
 */
function sanitize(?string $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a monetary value into Indian Rupee currency format (e.g., ₹499.00).
 */
function format_price(float $amount): string
{
    return '₹' . number_format($amount, 2, '.', ',');
}

/**
 * Stores a flash notification message in the session.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,    // 'success', 'error', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieves and clears the flash notification from the session.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Performs a safe header redirect and halts script execution.
 */
function redirect(string $url): void
{
    if (!headers_sent()) {
        header("Location: {$url}");
        exit;
    }
}

/**
 * Generates an uppercase unique order reference code, e.g., CK-2026-8941.
 */
function generate_order_number(): string
{
    return 'CK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/**
 * Retrieves the current cart array from session.
 * Format: [product_id => quantity]
 */
function get_cart(): array
{
    return $_SESSION['cart'] ?? [];
}

/**
 * Returns total number of individual items in the cart.
 */
function get_cart_count(): int
{
    $cart = get_cart();
    $count = 0;
    foreach ($cart as $qty) {
        $count += (int)$qty;
    }
    return $count;
}

/**
 * Computes the authoritative cart items and subtotal by querying the database.
 * Never trusts prices or availability from browser input.
 *
 * @param PDO $pdo
 * @return array ['items' => [...], 'subtotal' => float, 'has_errors' => bool, 'messages' => array]
 */
function calculate_cart_details(PDO $pdo): array
{
    $cart = get_cart();
    $items = [];
    $subtotal = 0.00;
    $messages = [];
    $hasErrors = false;

    if (empty($cart)) {
        return [
            'items'      => [],
            'subtotal'   => 0.00,
            'has_errors' => false,
            'messages'   => []
        ];
    }

    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id IN ({$placeholders})
    ");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    $foundMap = [];
    foreach ($products as $p) {
        $foundMap[$p['id']] = $p;
    }

    foreach ($cart as $productId => $qty) {
        $productId = (int)$productId;
        $qty = (int)$qty;

        if (!isset($foundMap[$productId])) {
            // Product was deleted or unavailable
            unset($_SESSION['cart'][$productId]);
            $messages[] = "An item was removed from your cart because it is no longer available.";
            $hasErrors = true;
            continue;
        }

        $prod = $foundMap[$productId];
        $stock = (int)$prod['stock'];

        if ($stock <= 0) {
            unset($_SESSION['cart'][$productId]);
            $messages[] = "Item '{$prod['name']}' is out of stock and was removed from your cart.";
            $hasErrors = true;
            continue;
        }

        if ($qty > $stock) {
            $qty = $stock;
            $_SESSION['cart'][$productId] = $stock;
            $messages[] = "Quantity for '{$prod['name']}' was adjusted to maximum available stock ({$stock}).";
            $hasErrors = true;
        }

        $price = (float)$prod['price'];
        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;

        $items[] = [
            'product_id'     => $productId,
            'name'           => $prod['name'],
            'price'          => $price,
            'original_price' => !empty($prod['original_price']) ? (float)$prod['original_price'] : null,
            'stock'          => $stock,
            'image'          => $prod['image'],
            'category_name'  => $prod['category_name'],
            'quantity'       => $qty,
            'line_total'     => $lineTotal,
        ];
    }

    return [
        'items'      => $items,
        'subtotal'   => $subtotal,
        'has_errors' => $hasErrors,
        'messages'   => $messages,
    ];
}

/**
 * Calculates delivery fee based on order subtotal.
 * Free delivery for orders >= ₹500, otherwise ₹50.00.
 */
function calculate_delivery_charge(float $subtotal): float
{
    if ($subtotal <= 0) {
        return 0.00;
    }
    return $subtotal >= 500.00 ? 0.00 : 50.00;
}

/**
 * Renders HTML star rating display.
 */
function render_rating_stars(float $rating): string
{
    $rounded = round($rating * 2) / 2;
    $html = '<div class="rating-stars" aria-label="Rated ' . $rating . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rounded >= $i) {
            $html .= '<span class="star star-filled" aria-hidden="true">&#9733;</span>';
        } elseif ($rounded >= $i - 0.5) {
            $html .= '<span class="star star-half" aria-hidden="true">&#9733;</span>';
        } else {
            $html .= '<span class="star star-empty" aria-hidden="true">&#9734;</span>';
        }
    }
    $html .= '</div>';
    return $html;
}
