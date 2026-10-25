<?php
/**
 * CampusKart - Product Catalog Page
 * Matches docs/ Screenshot 3 & 4
 */

declare(strict_types=1);

$pageTitle = 'Products - CampusKart Catalog';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Read query parameters
$searchQuery   = trim($_GET['search'] ?? '');
$categorySlug  = trim($_GET['category'] ?? '');
$minPriceInput = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPriceInput = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sortBy        = trim($_GET['sort'] ?? 'newest');

// Fetch All Categories with product counts for sidebar
$categoriesStmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$allCategories = $categoriesStmt->fetchAll();

// Fetch Top Rated Products for Sidebar (Matches Screenshot 3 & 4)
$topRatedStmt = $pdo->query("
    SELECT p.*, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.rating DESC, p.rating_count DESC
    LIMIT 4
");
$topRatedProducts = $topRatedStmt->fetchAll();

// Build dynamic WHERE clause
$whereConditions = [];
$params = [];

if ($searchQuery !== '') {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

if ($categorySlug !== '') {
    $whereConditions[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if ($minPriceInput !== null) {
    $whereConditions[] = "p.price >= ?";
    $params[] = $minPriceInput;
}

if ($maxPriceInput !== null) {
    $whereConditions[] = "p.price <= ?";
    $params[] = $maxPriceInput;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Sorting
$orderBy = "p.id DESC";
switch ($sortBy) {
    case 'price_asc':
        $orderBy = "p.price ASC";
        break;
    case 'price_desc':
        $orderBy = "p.price DESC";
        break;
    case 'rating':
        $orderBy = "p.rating DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'newest':
    default:
        $orderBy = "p.id DESC";
        break;
}

// Fetch Main Products
$sql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    {$whereClause}
    ORDER BY {$orderBy}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$totalCount = count($products);

// Active category name if filtered
$currentCategoryName = '';
if ($categorySlug !== '') {
    foreach ($allCategories as $c) {
        if ($c['slug'] === $categorySlug) {
            $currentCategoryName = $c['name'];
            break;
        }
    }
}
?>

<!-- Page Banner (Matches Screenshot 3) -->
<section class="page-hero-banner" aria-label="Page Title Banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>Products</strong>
            <?php if ($currentCategoryName): ?>
                <span>/</span>
                <span><?= sanitize($currentCategoryName) ?></span>
            <?php endif; ?>
        </nav>
        <h1 class="page-title"><?= $currentCategoryName ? sanitize($currentCategoryName) : 'Products' ?></h1>
    </div>
</section>

<!-- Main Catalog Container -->
<main class="container catalog-section">
    <div class="catalog-layout">
        <!-- Left Sidebar (Matches Screenshot 3 & 4) -->
        <aside class="catalog-sidebar" aria-label="Catalog Filters">
            <!-- Price Filter Widget -->
            <div class="sidebar-widget">
                <h3 class="sidebar-widget-title">Filter by price</h3>
                <form action="shop.php" method="GET" class="price-filter-box">
                    <?php if ($categorySlug): ?>
                        <input type="hidden" name="category" value="<?= sanitize($categorySlug) ?>">
                    <?php endif; ?>
                    <?php if ($searchQuery): ?>
                        <input type="hidden" name="search" value="<?= sanitize($searchQuery) ?>">
                    <?php endif; ?>
                    
                    <div class="price-inputs">
                        <input type="number" name="min_price" placeholder="Min ₹" min="0" value="<?= $minPriceInput !== null ? (int)$minPriceInput : '' ?>">
                        <span>–</span>
                        <input type="number" name="max_price" placeholder="Max ₹" min="0" value="<?= $maxPriceInput !== null ? (int)$maxPriceInput : '' ?>">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; padding:0.65rem; font-size:0.85rem;">APPLY FILTER</button>
                    <?php if ($minPriceInput !== null || $maxPriceInput !== null || $categorySlug !== '' || $searchQuery !== ''): ?>
                        <a href="shop.php" style="display:block; text-align:center; font-size:0.8rem; color:#888; margin-top:0.6rem;">Reset all filters</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Categories Widget -->
            <div class="sidebar-widget">
                <h3 class="sidebar-widget-title">Categories</h3>
                <ul class="category-filter-list">
                    <li class="category-filter-item">
                        <a href="shop.php" class="category-filter-link <?= $categorySlug === '' ? 'active' : '' ?>">
                            <span>All Categories</span>
                            <span class="category-count"><?= array_sum(array_column($allCategories, 'product_count')) ?></span>
                        </a>
                    </li>
                    <?php foreach ($allCategories as $cat): ?>
                        <li class="category-filter-item">
                            <a href="shop.php?category=<?= urlencode($cat['slug']) ?>" class="category-filter-link <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                                <span><?= sanitize($cat['name']) ?></span>
                                <span class="category-count"><?= (int)$cat['product_count'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Top Rated Products (Matches Screenshot 3 & 4) -->
            <div class="sidebar-widget">
                <h3 class="sidebar-widget-title">Top rated products</h3>
                <div class="mini-products-list">
                    <?php foreach ($topRatedProducts as $item): ?>
                        <div class="mini-product-item">
                            <a href="product.php?id=<?= (int)$item['id'] ?>" class="mini-product-thumb">
                                <img src="<?= sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>">
                            </a>
                            <div class="mini-product-info">
                                <h4><a href="product.php?id=<?= (int)$item['id'] ?>"><?= sanitize($item['name']) ?></a></h4>
                                <?= render_rating_stars((float)$item['rating']) ?>
                                <div class="mini-product-price">
                                    <span><?= format_price((float)$item['price']) ?></span>
                                    <?php if (!empty($item['original_price'])): ?>
                                        <span class="original"><?= format_price((float)$item['original_price']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dark Promo Card (Matches Screenshot 3 & 4) -->
            <div class="sidebar-promo-card">
                <h3>New Cloth<br>Technologies</h3>
                <p>Mauris vitae ultricies leo integer malesuada. Odio tempor orci dapibus ultrices in.</p>
                <a href="shop.php?category=apparel" class="btn-primary" style="padding:0.75rem 1.25rem; font-size:0.85rem;">DISCOVER</a>
            </div>
        </aside>

        <!-- Right Main Catalog Content -->
        <section class="catalog-main" aria-label="Product Listings">
            <!-- Toolbar -->
            <div class="catalog-toolbar">
                <div class="catalog-count-text">
                    Showing <strong><?= $totalCount ?></strong> <?= $totalCount === 1 ? 'product' : 'products' ?>
                    <?php if ($searchQuery): ?>
                        for "<em><?= sanitize($searchQuery) ?></em>"
                    <?php endif; ?>
                </div>

                <!-- Sort Control -->
                <form action="shop.php" method="GET" style="display:flex; align-items:center; gap:0.5rem;">
                    <?php if ($categorySlug): ?>
                        <input type="hidden" name="category" value="<?= sanitize($categorySlug) ?>">
                    <?php endif; ?>
                    <?php if ($searchQuery): ?>
                        <input type="hidden" name="search" value="<?= sanitize($searchQuery) ?>">
                    <?php endif; ?>
                    <?php if ($minPriceInput !== null): ?>
                        <input type="hidden" name="min_price" value="<?= (int)$minPriceInput ?>">
                    <?php endif; ?>
                    <?php if ($maxPriceInput !== null): ?>
                        <input type="hidden" name="max_price" value="<?= (int)$maxPriceInput ?>">
                    <?php endif; ?>

                    <label for="sortSelect" style="font-size:0.88rem; font-weight:600; color:#555;">Sort By:</label>
                    <select name="sort" id="sortSelect" class="catalog-sort-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                    </select>
                </form>
            </div>

            <!-- Products Grid (Matches Screenshot 3 & 4) -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
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
            <?php else: ?>
                <!-- Empty State -->
                <div style="background:var(--bg-subtle); padding:4rem 2rem; border-radius:var(--radius-sm); text-align:center;">
                    <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
                    <h2 style="font-family:var(--font-display); font-size:1.6rem; margin-bottom:0.75rem;">No Matching Products Found</h2>
                    <p style="color:#666; max-width:450px; margin:0 auto 1.75rem;">We couldn't find any items matching your selected filters or search criteria. Try broadening your price range or browsing all categories.</p>
                    <a href="shop.php" class="btn-primary">RESET ALL FILTERS</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
