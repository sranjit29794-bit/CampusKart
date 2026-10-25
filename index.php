<?php
/**
 * CampusKart - Homepage
 * Matches docs/ Screenshot 1 (Hero) and Screenshot 2 (Trust Badges Strip)
 */

declare(strict_types=1);

$pageTitle = 'CampusKart - Student E-Commerce Marketplace';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Fetch Featured Products from Database
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.is_featured = 1
    ORDER BY p.id ASC
    LIMIT 6
");
$stmt->execute();
$featuredProducts = $stmt->fetchAll();

// Fetch All Categories for Explore Strip
$catStmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $catStmt->fetchAll();
?>

<!-- Hero Section (Matches Screenshot 1) -->
<section class="hero-section" aria-label="Hero Showcase">
    <div class="container hero-grid">
        <div class="hero-content">
            <h1 class="hero-title">Brand New<br>Collection</h1>
            <p class="hero-desc">Discover curated campus apparel, premium streetwear sneakers, laboratory stationery, and daily tech accessories tailored for college student life.</p>
            
            <div class="hero-features">
                <div class="feature-check">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>Top Brands</span>
                </div>
                <div class="feature-check">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>High Quality</span>
                </div>
                <div class="feature-check">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>Free Delivery</span>
                </div>
            </div>

            <a href="shop.php" class="btn-primary" id="heroExploreBtn">EXPLORE SHOP</a>
        </div>

        <div class="hero-image-wrap">
            <img src="assets/images/hero-model.jpg" alt="Student wearing modern oversized knit sweater" class="hero-image">
        </div>
    </div>
</section>

<!-- Trust Features Strip (Matches Screenshot 2) -->
<section class="trust-strip" aria-label="Buyer Benefits">
    <div class="container trust-grid">
        <!-- Benefit 1 -->
        <div class="trust-item">
            <div class="trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                    <circle cx="16" cy="15" r="2"></circle>
                </svg>
            </div>
            <div class="trust-text">
                <h3>Secure Payment</h3>
                <p>100% secure payment &amp; COD</p>
            </div>
        </div>

        <!-- Benefit 2 -->
        <div class="trust-item">
            <div class="trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                    <text x="12" y="16" font-size="10" font-weight="bold" text-anchor="middle" stroke="none" fill="#fff">₹</text>
                </svg>
            </div>
            <div class="trust-text">
                <h3>30 Days Return</h3>
                <p>If goods have problems</p>
            </div>
        </div>

        <!-- Benefit 3 -->
        <div class="trust-item">
            <div class="trust-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                </svg>
            </div>
            <div class="trust-text">
                <h3>24/7 Support</h3>
                <p>Dedicated campus support</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="container" style="padding: 4.5rem 1.5rem 2rem;" aria-label="Featured Products">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem;">
        <div>
            <h2 style="font-family:var(--font-display); font-size:2.4rem; font-weight:700; color:#111;">Featured Essentials</h2>
            <p style="color:#666; font-size:0.95rem;">High-rated items loved by students this academic term</p>
        </div>
        <a href="shop.php" style="font-family:var(--font-display); font-weight:700; font-size:0.92rem; color:var(--accent-red); border-bottom:2px solid var(--accent-red);">VIEW ALL PRODUCTS &rarr;</a>
    </div>

    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <article class="product-card" id="product-card-<?= (int)$product['id'] ?>">
                <div class="product-image-box">
                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                        <span class="badge-sale">SALE</span>
                    <?php endif; ?>
                    <a href="product.php?id=<?= (int)$product['id'] ?>" aria-label="View details for <?= sanitize($product['name']) ?>">
                        <img src="<?= sanitize($product['image']) ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                    </a>
                </div>

                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product.php?id=<?= (int)$product['id'] ?>"><?= sanitize($product['name']) ?></a>
                    </h3>
                    
                    <div class="product-pricing">
                        <span class="price-current"><?= format_price((float)$product['price']) ?></span>
                        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                            <span class="price-original"><?= format_price((float)$product['original_price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?= render_rating_stars((float)$product['rating']) ?>

                    <span class="product-category-tag"><?= sanitize($product['category_name']) ?></span>

                    <form action="cart-action.php" method="POST" style="margin-top:auto;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn-add-cart">Add to cart</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Promotional Campus Banner -->
<section class="container" style="padding: 2rem 1.5rem 5rem;">
    <div style="background:var(--bg-dark); color:#fff; border-radius:var(--radius-sm); padding:3.5rem; display:grid; grid-template-columns:1.2fr 0.8fr; align-items:center; gap:2.5rem;">
        <div>
            <span style="color:var(--accent-red); font-family:var(--font-display); font-weight:700; letter-spacing:0.05em; text-transform:uppercase; font-size:0.85rem;">CAMPUS EXCLUSIVE</span>
            <h2 style="font-family:var(--font-display); font-size:2.6rem; font-weight:700; margin:0.75rem 0 1.25rem; line-height:1.15;">Smart Gear for Lectures, Labs &amp; Hostels</h2>
            <p style="color:#aaa; font-size:1.02rem; line-height:1.6; margin-bottom:2rem;">Grab scientific calculators, ergonomic wireless peripherals, and rugged water-resistant laptop bags with hostel delivery under 2 hours.</p>
            <a href="shop.php?category=stationery-tech" class="btn-primary">EXPLORE TECH &amp; STUDY</a>
        </div>
        <div style="display:flex; justify-content:center;">
            <img src="assets/images/backpack.jpg" alt="Campus Pro Backpack" style="max-height:280px; object-fit:contain; filter:drop-shadow(0 15px 25px rgba(0,0,0,0.5)); border-radius:4px;">
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
