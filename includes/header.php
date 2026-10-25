<?php
/**
 * CampusKart - Shared Site Header
 * Reproduces the visual identity and navigation from docs/ Screenshot 1 & 3
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$cartCount = get_cart_count();
$user = current_user();
$flash = get_flash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'CampusKart - Student E-Commerce Marketplace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="CampusKart is the student-focused marketplace for collegiate apparel, sneakers, study stationery, and campus gear.">
    <!-- Master Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛍️</text></svg>">
</head>
<body>

<!-- Flash Toast Notifications -->
<?php if ($flash): ?>
<div class="toast-container" id="toastContainer" role="alert" aria-live="polite">
    <div class="toast toast-<?= sanitize($flash['type']) ?>">
        <span><?= sanitize($flash['message']) ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Header Navigation Bar -->
<header class="site-header" role="banner">
    <div class="container header-inner">
        <!-- Logo -->
        <a href="index.php" class="site-logo" aria-label="CampusKart Home">
            <span class="logo-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 6V4a4 4 0 0 0-8 0v2H3v16h18V6h-5zm-6-2a2 2 0 0 1 4 0v2h-4V4zm9 16H5V8h14v12z"/></svg>
            </span>
            <span>CampusKart</span>
        </a>

        <!-- Main Navigation (Matches Screenshot 1 & 3) -->
        <nav class="main-nav" role="navigation" aria-label="Primary Navigation">
            <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">HOME</a>
            <a href="shop.php" class="nav-link <?= in_array($currentPage, ['shop', 'product']) ? 'active' : '' ?>">PRODUCTS</a>
            <a href="shop.php?category=stationery-tech" class="nav-link">SERVICES</a>
            <a href="about.php" class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>">ABOUT US</a>
            <a href="shop.php?category=apparel" class="nav-link">NEWS</a>
            <a href="contact.php" class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>">CONTACT</a>
        </nav>

        <!-- Right Utility Actions -->
        <div class="header-actions">
            <!-- Search Button -->
            <button type="button" class="action-btn" id="searchTrigger" aria-label="Search Products">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            <!-- User Profile Menu -->
            <div class="user-menu-wrapper">
                <a href="<?= is_logged_in() ? 'profile.php' : 'login.php' ?>" class="action-btn" aria-label="User Account">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <div class="user-dropdown">
                    <?php if (is_logged_in()): ?>
                        <div class="dropdown-header">
                            <strong><?= sanitize($user['name']) ?></strong>
                            <span><?= sanitize($user['email']) ?></span>
                        </div>
                        <?php if (is_admin()): ?>
                            <a href="admin/index.php" class="dropdown-link" style="color:var(--accent-red);font-weight:700;">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="orders.php" class="dropdown-link">My Orders</a>
                        <a href="profile.php" class="dropdown-link">Account Settings</a>
                        <a href="logout.php" class="dropdown-link" style="border-top:1px solid #eee;">Sign Out</a>
                    <?php else: ?>
                        <div class="dropdown-header">
                            <strong>CampusKart Account</strong>
                            <span>Student & Staff Sign In</span>
                        </div>
                        <a href="login.php" class="dropdown-link">Sign In</a>
                        <a href="register.php" class="dropdown-link">Register Account</a>
                        <a href="admin/index.php" class="dropdown-link" style="border-top:1px solid #eee;color:#888;">Admin Portal</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Shopping Bag with Counter Badge -->
            <a href="cart.php" class="action-btn" aria-label="Shopping Cart (<?= $cartCount ?> items)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
            </a>

            <!-- Mobile Menu Toggle Button -->
            <button type="button" class="action-btn mobile-toggle" id="mobileToggle" aria-label="Toggle Navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
        </div>
    </div>
</header>

<!-- Search Modal Drawer -->
<div class="search-modal" id="searchModal" role="dialog" aria-modal="true" aria-label="Product Search">
    <div class="search-modal-box">
        <form action="shop.php" method="GET" class="search-input-wrap">
            <input type="text" name="search" id="searchInput" placeholder="Search apparel, sneakers, calculators, bags..." required autocomplete="off">
            <button type="submit" class="btn-primary">SEARCH</button>
            <button type="button" class="btn-dark" id="searchClose" aria-label="Close search">✕</button>
        </form>
    </div>
</div>
