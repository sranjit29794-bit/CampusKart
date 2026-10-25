<?php
/**
 * CampusKart - Database Setup & Auto-Migrator
 * Run via CLI: php database/setup.php
 * Or visit in browser: http://localhost:8000/database/setup.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================\n";
echo "   CampusKart - Database Setup & Verification Tool      \n";
echo "========================================================\n\n";

try {
    $pdo = get_db_connection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✓ Successfully connected to database via PDO [Driver: {$driver}].\n";

    // Check table count
    if ($driver === 'sqlite') {
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }

    echo "✓ Found " . count($tables) . " active tables: " . implode(', ', $tables) . "\n\n";

    // Verify row counts
    $prodCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $catCount  = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $ordCount  = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    echo "Database Content Summary:\n";
    echo "- Categories: {$catCount}\n";
    echo "- Products:   {$prodCount}\n";
    echo "- Users:      {$userCount}\n";
    echo "- Orders:     {$ordCount}\n\n";

    echo "Default Credentials:\n";
    echo "1. Administrator Account:\n";
    echo "   Email:    admin@campuskart.com\n";
    echo "   Password: Admin@123\n\n";
    echo "2. Student Demo Account:\n";
    echo "   Email:    rohan@college.edu\n";
    echo "   Password: Student@123\n\n";

    echo "✓ Setup and database integrity checks completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error during database setup: " . $e->getMessage() . "\n";
    exit(1);
}
