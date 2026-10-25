-- CampusKart: Student E-Commerce Marketplace Database Schema & Seed Data
-- Compatible with MySQL 8.0, MariaDB, and phpMyAdmin (XAMPP default)

CREATE DATABASE IF NOT EXISTS `campuskart` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campuskart`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products Table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `original_price` DECIMAL(10, 2) NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `rating` DECIMAL(2, 1) NOT NULL DEFAULT 5.0,
  `rating_count` INT NOT NULL DEFAULT 12,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_price` (`price`),
  INDEX `idx_products_stock` (`stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `pincode` VARCHAR(20) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `delivery_charge` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
  `status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Order Items Table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'T-Shirts & Apparel', 'apparel', 'Casual t-shirts, oversized campus hoodies, and college wear.'),
(2, 'Footwear & Sneakers', 'footwear', 'Comfortable sneakers and shoes designed for everyday campus walking.'),
(3, 'Accessories & Caps', 'accessories', 'Caps, watches, wallets, and daily student gear.'),
(4, 'Bags & Backpacks', 'bags', 'Ergonomic laptop backpacks and stylish student messenger bags.'),
(5, 'Stationery & Tech', 'stationery-tech', 'Calculators, wireless mice, engineering tools, and study supplies.');

-- Seed Users
-- Password for Admin is: Admin@123
-- Password for Student is: Student@123
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(1, 'CampusKart Admin', 'admin@campuskart.com', '+91 9876543210', '$2y$10$PMdx5h.71yXdr4VAmgIaTumrZ/LVOcJ2Wkdx9pdlK4QemmTvKKjXC', 'admin', NOW()),
(2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', '$2y$10$NUj5Zeg/8PpjZf.d7AOfi.PeRDOqFAsAO5Bktqnzy8LDdw1ir4vsy', 'customer', NOW());

-- Seed Products matching Design References and Campus Needs
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `original_price`, `stock`, `rating`, `rating_count`, `is_featured`, `image`) VALUES
(1, 1, 'Black T-shirt', 'black-t-shirt', 'Classic relaxed fit 100% combed cotton black graphic t-shirt with signature campus mountain crest print. Pre-shrunk, breathable, and styled for lecture and workshop ease.', 499.00, 799.00, 35, 4.9, 42, 1, 'assets/images/black-tshirt.jpg'),
(2, 3, 'Brown Cap', 'brown-cap', 'Vintage-wash tan brown cotton twill baseball cap with curved sun brim and adjustable bronze clasp strap. Lightweight and breathable for hot campus days.', 349.00, NULL, 24, 4.8, 19, 1, 'assets/images/brown-cap.jpg'),
(3, 2, 'Color Sneakers', 'color-sneakers', 'Street-inspired color-block campus sneakers with pastel panels, cushioned EVA midsole, and high-traction rubber tread. Perfect for walking between lecture halls.', 1299.00, 1899.00, 18, 4.9, 31, 1, 'assets/images/color-sneakers.jpg'),
(4, 4, 'Elegant Bag', 'elegant-bag', 'Cognac brown structured faux-leather crossbody shoulder bag with partitioned interior sleeves for tablets, notebooks, and student essentials.', 899.00, NULL, 15, 4.7, 14, 0, 'assets/images/elegant-bag.jpg'),
(5, 2, 'Gray Shoes', 'gray-shoes', 'Low-profile charcoal gray suede skate sneakers with vulcanized white rubber sole and padded ankle collar for enduring laboratory and daily wear.', 999.00, NULL, 22, 4.6, 23, 1, 'assets/images/gray-shoes.jpg'),
(6, 3, 'Quartz Watch', 'quartz-watch', 'Minimalist matte black dial analog timepiece featuring Japanese quartz precision movement, date window, and stitched genuine leather band.', 799.00, 1299.00, 20, 5.0, 38, 1, 'assets/images/quartz-watch.jpg'),
(7, 1, 'Oversized Knit Sweater', 'oversized-knit-sweater', 'Premium heavyweight ribbed cream knit sweater in drop-shoulder relaxed silhouette. Essential cozy warmth for air-conditioned labs and winter semesters.', 1199.00, 1699.00, 14, 4.9, 27, 1, 'assets/images/hero-model.jpg'),
(8, 4, 'Campus Pro Backpack', 'campus-pro-backpack', 'Weather-resistant 28L student backpack featuring dedicated 15.6-inch padded laptop pocket, ergonomic breathable back support, and dual water bottle pouches.', 1049.00, 1499.00, 30, 4.8, 45, 0, 'assets/images/backpack.jpg'),
(9, 5, 'Scientific Calculator FX-991', 'scientific-calculator-fx-991', 'Advanced 552-function non-programmable engineering calculator with natural textbook display. University exam approved for calculus, matrices, and statistical computing.', 920.00, NULL, 50, 5.0, 89, 0, 'assets/images/calculator.jpg'),
(10, 5, 'Ergonomic Wireless Mouse', 'ergonomic-wireless-mouse', 'Dual-mode Bluetooth and 2.4GHz rechargeable wireless mouse with silent mechanical switches and adjustable 2400 DPI optical sensor for coding marathons.', 649.00, 899.00, 40, 4.7, 56, 0, 'assets/images/mouse.jpg');

-- Sample Initial Orders for Rohan Sharma
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `subtotal`, `delivery_charge`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 'CK-2026-8801', 2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', 'Room 304, Boys Hostel B, University Campus', 'Bangalore', 'Karnataka', '560064', 848.00, 0.00, 848.00, 'Cash on Delivery', 'Delivered', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'CK-2026-8802', 2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', 'Room 304, Boys Hostel B, University Campus', 'Bangalore', 'Karnataka', '560064', 1299.00, 0.00, 1299.00, 'Cash on Delivery', 'Processing', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 'Black T-shirt', 1, 499.00, 499.00),
(2, 1, 2, 'Brown Cap', 1, 349.00, 349.00),
(3, 2, 3, 'Color Sneakers', 1, 1299.00, 1299.00);
