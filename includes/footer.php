<?php
/**
 * CampusKart - Shared Site Footer
 */

declare(strict_types=1);
?>
<!-- Footer Component -->
<footer class="site-footer" role="contentinfo">
    <div class="container footer-grid">
        <!-- Brand Summary -->
        <div class="footer-brand">
            <h2>CampusKart</h2>
            <p>Your premier campus-centric marketplace connecting college students with top-grade apparel, study gear, footwear, and laboratory accessories at honest student prices.</p>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul class="footer-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">All Products</a></li>
                <li><a href="shop.php?category=apparel">Apparel & T-Shirts</a></li>
                <li><a href="shop.php?category=footwear">Sneakers & Shoes</a></li>
                <li><a href="shop.php?category=stationery-tech">Stationery & Tech</a></li>
            </ul>
        </div>

        <!-- Student Support -->
        <div class="footer-col">
            <h4>Customer Care</h4>
            <ul class="footer-links">
                <li><a href="orders.php">Track Order</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
                <li><a href="contact.php">Campus Delivery Info</a></li>
                <li><a href="about.php">About CampusKart</a></li>
                <li><a href="admin/index.php">Staff / Admin Login</a></li>
            </ul>
        </div>

        <!-- Contact & Hours -->
        <div class="footer-col">
            <h4>Campus Hub</h4>
            <p style="color:#a0a0a5;font-size:0.9rem;line-height:1.6;margin-bottom:0.75rem;">
                Student Activity Center, Gate 2<br>
                University Campus Main Road<br>
                Bangalore, Karnataka 560064
            </p>
            <p style="color:#a0a0a5;font-size:0.88rem;">
                <strong>Support:</strong> support@campuskart.edu<br>
                <strong>Helpline:</strong> +91 98765 43210
            </p>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="container footer-bottom">
        <div>&copy; <?= date('Y') ?> CampusKart Marketplace. Built for Web Technology Laboratory Project.</div>
        <div>XAMPP / PHP 8 / MySQL / Pure CSS Grid &amp; Flexbox</div>
    </div>
</footer>

<!-- Interactive Scripts -->
<script src="assets/js/script.js"></script>
</body>
</html>
