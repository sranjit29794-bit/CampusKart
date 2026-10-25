<?php
/**
 * CampusKart - Dedicated Product Details Page
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$pdo = get_db_connection();
$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    set_flash('error', 'Invalid product requested.');
    redirect('shop.php');
}

// Fetch Product Details
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found - CampusKart';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:6rem 1.5rem; text-align:center;">
            <h1 style="font-family:var(--font-display); font-size:2.5rem; margin-bottom:1rem;">Product Not Found</h1>
            <p style="color:#666; margin-bottom:2rem;">The item you requested does not exist or has been discontinued from our campus catalog.</p>
            <a href="shop.php" class="btn-primary">RETURN TO CATALOG</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'] . ' - CampusKart';
require_once __DIR__ . '/includes/header.php';

// Fetch Related Products in same category
$relStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ?
    ORDER BY p.rating DESC
    LIMIT 3
");
$relStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relStmt->fetchAll();

$stock = (int)$product['stock'];
$isAvailable = $stock > 0;
?>

<!-- Breadcrumbs -->
<div style="background:#f5f5f7; border-bottom:1px solid var(--border-light); padding:1rem 0;">
    <div class="container">
        <nav class="breadcrumbs" style="margin:0;" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="shop.php">Products</a>
            <span>/</span>
            <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>"><?= sanitize($product['category_name']) ?></a>
            <span>/</span>
            <strong style="color:#111;"><?= sanitize($product['name']) ?></strong>
        </nav>
    </div>
</div>

<main class="container">
    <div class="product-detail-grid">
        <!-- Left: Large Image Container -->
        <div class="product-gallery-box">
            <img src="<?= sanitize($product['image']) ?>" alt="<?= sanitize($product['name']) ?>" id="productMainImage">
        </div>

        <!-- Right: Summary & Order Configuration -->
        <div class="product-summary-pane">
            <span class="product-category-tag" style="margin-bottom:0.5rem; font-size:0.85rem;"><?= sanitize($product['category_name']) ?></span>
            <h1 class="product-title"><?= sanitize($product['name']) ?></h1>

            <div class="product-meta-row">
                <?= render_rating_stars((float)$product['rating']) ?>
                <span style="font-size:0.88rem; color:#666;">(<?= (int)$product['rating_count'] ?> verified student reviews)</span>
                
                <?php if ($isAvailable): ?>
                    <span class="stock-pill stock-in">In Stock (<?= $stock ?> left)</span>
                <?php else: ?>
                    <span class="stock-pill stock-out">Out of Stock</span>
                <?php endif; ?>
            </div>

            <div class="product-pricing">
                <span class="price-current"><?= format_price((float)$product['price']) ?></span>
                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                    <span class="price-original" style="font-size:1.15rem;"><?= format_price((float)$product['original_price']) ?></span>
                    <span style="background:var(--accent-red-subtle); color:var(--accent-red); font-size:0.82rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:2px;">
                        SAVE <?= round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) ?>%
                    </span>
                <?php endif; ?>
            </div>

            <div class="product-description-full">
                <p><?= nl2br(sanitize($product['description'])) ?></p>
            </div>

            <!-- Add to Cart Form -->
            <?php if ($isAvailable): ?>
                <form action="cart-action.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <div class="cart-action-group">
                        <div class="qty-control">
                            <button type="button" class="qty-btn qty-dec" aria-label="Decrease quantity">−</button>
                            <input type="number" name="quantity" class="qty-input" value="1" min="1" max="<?= $stock ?>" aria-label="Quantity">
                            <button type="button" class="qty-btn qty-inc" aria-label="Increase quantity">+</button>
                        </div>

                        <button type="submit" class="btn-primary" style="padding:0.9rem 2rem;">ADD TO CART</button>
                    </div>
                </form>
            <?php else: ?>
                <div style="background:#fee2e2; color:#991b1b; padding:1rem 1.5rem; border-radius:2px; font-weight:600; margin-bottom:2rem;">
                    Currently out of stock. Check back soon or contact campus store inventory for restocking updates.
                </div>
            <?php endif; ?>

            <!-- Reassurance Checklist -->
            <div style="background:var(--bg-subtle); padding:1.25rem 1.5rem; border-radius:var(--radius-sm); display:flex; flex-direction:column; gap:0.6rem; font-size:0.9rem; color:#444;">
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="color:#10b981; font-weight:bold;">✓</span> Free 2-Hour Campus Hostel Delivery on orders above ₹500
                </div>
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="color:#10b981; font-weight:bold;">✓</span> 30-Day Hassle-free Exchange on defective or sizing issues
                </div>
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="color:#10b981; font-weight:bold;">✓</span> Cash on Delivery (COD) supported across university premises
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <section style="padding: 4rem 0; border-top: 1px solid var(--border-light); margin-top: 3rem;">
            <h2 style="font-family:var(--font-display); font-size:1.8rem; margin-bottom:2rem; color:#111;">Related in <?= sanitize($product['category_name']) ?></h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <article class="product-card">
                        <div class="product-image-box">
                            <a href="product.php?id=<?= (int)$rel['id'] ?>">
                                <img src="<?= sanitize($rel['image']) ?>" alt="<?= sanitize($rel['name']) ?>" loading="lazy">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><a href="product.php?id=<?= (int)$rel['id'] ?>"><?= sanitize($rel['name']) ?></a></h3>
                            <div class="product-pricing">
                                <span class="price-current"><?= format_price((float)$rel['price']) ?></span>
                            </div>
                            <span class="product-category-tag"><?= sanitize($rel['category_name']) ?></span>
                            <a href="product.php?id=<?= (int)$rel['id'] ?>" class="btn-add-cart" style="background:#111;">View Details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
