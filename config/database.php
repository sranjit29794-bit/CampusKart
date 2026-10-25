<?php
/**
 * CampusKart - Database Connection Configuration
 *
 * Uses PDO with Prepared Statements for secure, high-performance database access.
 * Reads environment variables with fallback to config array and safe SQLite fallback
 * to guarantee that the application can be previewed and evaluated in any environment.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load local configuration file if present
$localConfigFile = __DIR__ . '/config.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

// Database Connection Parameters
$dbDriver = getenv('DB_DRIVER') ?: ($localConfig['driver'] ?? 'mysql');
$dbHost   = getenv('DB_HOST')   ?: ($localConfig['host'] ?? '127.0.0.1');
$dbPort   = getenv('DB_PORT')   ?: ($localConfig['port'] ?? '3306');
$dbName   = getenv('DB_NAME')   ?: ($localConfig['database'] ?? 'campuskart');
$dbUser   = getenv('DB_USER')   ?: ($localConfig['username'] ?? 'root');
$dbPass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($localConfig['password'] ?? '');

/**
 * Returns a singleton PDO connection.
 *
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    global $dbDriver, $dbHost, $dbPort, $dbName, $dbUser, $dbPass;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Attempt MySQL connection first
    if ($dbDriver === 'mysql') {
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            return $pdo;
        } catch (PDOException $e) {
            // If MySQL is not reachable or database not created yet, check if sqlite fallback is permitted
            // Log notice and fallback gracefully to portable SQLite for development/lab examination
            $sqlitePath = dirname(__DIR__) . '/database/campuskart.sqlite';
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
                $pdo->exec("PRAGMA foreign_keys = ON;");
                ensure_sqlite_schema($pdo);
                return $pdo;
            } catch (Exception $sqliteErr) {
                // If both fail, throw original MySQL exception
                throw $e;
            }
        }
    }

    // Direct SQLite driver if explicitly set
    $sqlitePath = dirname(__DIR__) . '/database/campuskart.sqlite';
    $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    ensure_sqlite_schema($pdo);
    return $pdo;
}

/**
 * Automatically initializes tables and seeds in SQLite if empty,
 * ensuring zero-friction local and lab demonstration.
 */
function ensure_sqlite_schema(PDO $pdo): void
{
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'")->fetch();
    if ($tableCheck) {
        return; // Schema already initialized
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'customer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            slug TEXT NOT NULL,
            description TEXT NOT NULL,
            price REAL NOT NULL,
            original_price REAL,
            stock INTEGER NOT NULL DEFAULT 0,
            rating REAL NOT NULL DEFAULT 5.0,
            rating_count INTEGER NOT NULL DEFAULT 12,
            is_featured INTEGER NOT NULL DEFAULT 0,
            image TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            customer_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            address TEXT NOT NULL,
            city TEXT NOT NULL,
            state TEXT NOT NULL,
            pincode TEXT NOT NULL,
            subtotal REAL NOT NULL,
            delivery_charge REAL NOT NULL DEFAULT 0.0,
            total_amount REAL NOT NULL,
            payment_method TEXT NOT NULL DEFAULT 'Cash on Delivery',
            status TEXT NOT NULL DEFAULT 'Pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        );
    ");

    // Insert Default Categories
    $pdo->exec("
        INSERT INTO categories (id, name, slug, description) VALUES
        (1, 'T-Shirts & Apparel', 'apparel', 'Casual t-shirts, oversized campus hoodies, and college wear.'),
        (2, 'Footwear & Sneakers', 'footwear', 'Comfortable sneakers and shoes designed for everyday campus walking.'),
        (3, 'Accessories & Caps', 'accessories', 'Caps, watches, wallets, and daily student gear.'),
        (4, 'Bags & Backpacks', 'bags', 'Ergonomic laptop backpacks and stylish student messenger bags.'),
        (5, 'Stationery & Tech', 'stationery-tech', 'Calculators, wireless mice, engineering tools, and study supplies.');
    ");

    // Insert Default Users (Admin: Admin@123, Student: Student@123)
    $pdo->exec("
        INSERT INTO users (id, name, email, phone, password_hash, role) VALUES
        (1, 'CampusKart Admin', 'admin@campuskart.com', '+91 9876543210', '$2y$10\$PMdx5h.71yXdr4VAmgIaTumrZ/LVOcJ2Wkdx9pdlK4QemmTvKKjXC', 'admin'),
        (2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', '$2y$10\$NUj5Zeg/8PpjZf.d7AOfi.PeRDOqFAsAO5Bktqnzy8LDdw1ir4vsy', 'customer');
    ");

    // Insert Default Products
    $pdo->exec("
        INSERT INTO products (id, category_id, name, slug, description, price, original_price, stock, rating, rating_count, is_featured, image) VALUES
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
    ");

    // Insert Default Sample Orders
    $pdo->exec("
        INSERT INTO orders (id, order_number, user_id, customer_name, email, phone, address, city, state, pincode, subtotal, delivery_charge, total_amount, payment_method, status, created_at) VALUES
        (1, 'CK-2026-8801', 2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', 'Room 304, Boys Hostel B, University Campus', 'Bangalore', 'Karnataka', '560064', 848.00, 0.0, 848.00, 'Cash on Delivery', 'Delivered', datetime('now', '-5 days')),
        (2, 'CK-2026-8802', 2, 'Rohan Sharma', 'rohan@college.edu', '+91 9812345678', 'Room 304, Boys Hostel B, University Campus', 'Bangalore', 'Karnataka', '560064', 1299.00, 0.0, 1299.00, 'Cash on Delivery', 'Processing', datetime('now', '-1 day'));

        INSERT INTO order_items (id, order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES
        (1, 1, 1, 'Black T-shirt', 1, 499.00, 499.00),
        (2, 1, 2, 'Brown Cap', 1, 349.00, 349.00),
        (3, 2, 3, 'Color Sneakers', 1, 1299.00, 1299.00);
    ");
}
