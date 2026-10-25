<?php
/**
 * CampusKart - About Us Page
 */

declare(strict_types=1);

$pageTitle = 'About Us - CampusKart Marketplace';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>About Us</strong>
        </nav>
        <h1 class="page-title">About CampusKart</h1>
    </div>
</section>

<main class="container" style="padding: 4rem 1.5rem 6rem; max-width: 960px;">
    <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:3rem; line-height:1.7; color:#444;">
        <h2 style="font-family:var(--font-display); font-size:2rem; color:#111; margin-bottom:1.25rem;">Student-Centric Commerce, Engineered for College Life</h2>
        <p style="font-size:1.05rem; margin-bottom:1.5rem;">
            <strong>CampusKart</strong> is an e-commerce platform designed to address the unique logistical challenges faced by university students. From lecture-ready apparel and durable backpacks to lab calculators and technical peripherals, CampusKart bridges the gap between campus needs and immediate accessibility.
        </p>

        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:2rem; margin:2.5rem 0; text-align:center;">
            <div style="background:var(--bg-subtle); padding:1.75rem; border-radius:var(--radius-sm);">
                <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:700; color:var(--accent-red);">100%</div>
                <div style="font-weight:700; color:#111; margin:0.35rem 0;">Direct Hostel Delivery</div>
                <div style="font-size:0.85rem; color:#666;">Within 2 hours across all campus blocks</div>
            </div>
            <div style="background:var(--bg-subtle); padding:1.75rem; border-radius:var(--radius-sm);">
                <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:700; color:var(--accent-red);">₹0</div>
                <div style="font-weight:700; color:#111; margin:0.35rem 0;">Delivery Surcharge</div>
                <div style="font-size:0.85rem; color:#666;">On all student orders above ₹500</div>
            </div>
            <div style="background:var(--bg-subtle); padding:1.75rem; border-radius:var(--radius-sm);">
                <div style="font-family:var(--font-display); font-size:2.2rem; font-weight:700; color:var(--accent-red);">COD</div>
                <div style="font-weight:700; color:#111; margin:0.35rem 0;">Safe On-Site Cash</div>
                <div style="font-size:0.85rem; color:#666;">Pay on inspection with zero digital friction</div>
            </div>
        </div>

        <h3 style="font-family:var(--font-display); font-size:1.4rem; color:#111; margin:2rem 0 0.75rem;">Web Technology Mini-Project Implementation</h3>
        <p>
            Developed as a comprehensive <strong>Web Technology (WT) Laboratory Capstone</strong>, CampusKart demonstrates robust full-stack software engineering principles:
        </p>
        <ul style="padding-left:1.5rem; margin-top:0.75rem; line-height:1.8;">
            <li><strong>Frontend Engineering:</strong> Pure semantic HTML5, Vanilla CSS3 (Grid &amp; Flexbox), and Vanilla JavaScript without bloated single-page framework overhead.</li>
            <li><strong>Backend Architecture:</strong> Modern PHP 8.2 backend utilizing reusable modular templates, strict typing, and comprehensive input validation.</li>
            <li><strong>Database Design:</strong> Relational MySQL/MariaDB database with 3NF relational normalization, foreign keys, cascading constraints, and atomic transactions.</li>
            <li><strong>Security Hardening:</strong> Cryptographic password hashing with <code>password_hash()</code>, session fixation protection via <code>session_regenerate_id()</code>, CSRF tokens on state-changing operations, and strict SQL injection mitigation through PDO prepared statements.</li>
        </ul>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
