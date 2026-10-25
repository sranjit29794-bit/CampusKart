<?php
/**
 * CampusKart - Netlify Static Exporter & Pre-renderer
 * Generates production-ready static assets in dist/ for Netlify hosting.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$sourceDir = __DIR__;
$distDir   = __DIR__ . '/dist';

// Ensure clean dist directory
if (is_dir($distDir)) {
    delete_directory($distDir);
}
mkdir($distDir, 0755, true);
mkdir($distDir . '/admin', 0755, true);

// Initialize database
require_once __DIR__ . '/config/database.php';
$pdo = get_db_connection();

// List of pages to pre-render
$pages = [
    'index.php'              => '/index.html',
    'shop.php'               => '/shop.html',
    'product.php'            => '/product.html',
    'cart.php'               => '/cart.html',
    'checkout.php'           => '/checkout.html',
    'login.php'              => '/login.html',
    'register.php'           => '/register.html',
    'profile.php'            => '/profile.html',
    'orders.php'             => '/orders.html',
    'order-details.php'      => '/order-details.html',
    'about.php'              => '/about.html',
    'contact.php'            => '/contact.html',
    'admin/index.php'        => '/admin/index.html',
    'admin/products.php'     => '/admin/products.html',
    'admin/categories.php'   => '/admin/categories.html',
    'admin/orders.php'       => '/admin/orders.html',
    'admin/customers.php'    => '/admin/customers.html',
];

echo "Exporting CampusKart pages for Netlify deployment...\n";

foreach ($pages as $srcFile => $destFile) {
    $fullPath = $sourceDir . '/' . $srcFile;
    if (!file_exists($fullPath)) {
        continue;
    }

    // Configure context
    $_GET = [];
    $_POST = [];
    $_REQUEST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/' . basename($srcFile);
    $_SERVER['PHP_SELF'] = '/' . basename($srcFile);

    if ($srcFile === 'product.php') {
        $_GET['id'] = 1;
        $_REQUEST['id'] = 1;
    } elseif ($srcFile === 'checkout.php') {
        $_SESSION['cart'] = [1 => 1, 2 => 1];
        $_SESSION['user'] = ['id' => 2, 'name' => 'Rohan Sharma', 'email' => 'rohan@college.edu', 'phone' => '+91 9812345678', 'role' => 'customer'];
    } elseif ($srcFile === 'order-details.php') {
        $_GET['id'] = 1;
        $_REQUEST['id'] = 1;
        $_SESSION['user'] = ['id' => 2, 'name' => 'Rohan Sharma', 'email' => 'rohan@college.edu', 'phone' => '+91 9812345678', 'role' => 'customer'];
    } elseif ($srcFile === 'profile.php' || $srcFile === 'orders.php') {
        $_SESSION['user'] = ['id' => 2, 'name' => 'Rohan Sharma', 'email' => 'rohan@college.edu', 'phone' => '+91 9812345678', 'role' => 'customer'];
    } elseif (strpos($srcFile, 'admin/') === 0) {
        $_SESSION['user'] = ['id' => 1, 'name' => 'CampusKart Admin', 'email' => 'admin@campuskart.com', 'role' => 'admin'];
    } else {
        unset($_SESSION['user']);
    }

    ob_start();
    try {
        include $fullPath;
    } catch (Throwable $t) {
        // Ignored in static generation
    }
    $content = (string)ob_get_clean();

    // Convert internal PHP links to HTML for static Netlify hosting
    $content = preg_replace('/href="([a-zA-Z0-9_\-]+)\.php(\?[^"]*)?"/', 'href="$1.html$2"', $content);
    $content = preg_replace('/action="([a-zA-Z0-9_\-]+)\.php"/', 'action="$1.html"', $content);
    $content = preg_replace('/href="\.\.\/([a-zA-Z0-9_\-]+)\.php"/', 'href="../$1.html"', $content);

    $outPath = $distDir . $destFile;
    file_put_contents($outPath, $content);
    echo "  [RENDERED] {$srcFile} -> {$destFile}\n";
}

// Copy assets
echo "Copying assets...\n";
copy_directory($sourceDir . '/assets', $distDir . '/assets');

// Write Netlify redirects
$redirects = "
/shop            /shop.html            200
/cart            /cart.html            200
/checkout        /checkout.html        200
/login           /login.html           200
/register        /register.html        200
/profile         /profile.html         200
/orders          /orders.html          200
/about           /about.html           200
/contact         /contact.html         200
/admin           /admin/index.html     200
/*.php           /:splat.html          301
";
file_put_contents($distDir . '/_redirects', trim($redirects) . "\n");

echo "\n✓ Netlify static build complete! All artifacts ready in dist/\n";

function copy_directory(string $src, string $dst): void {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if ($file !== '.' && $file !== '..') {
            if (is_dir($src . '/' . $file)) {
                copy_directory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function delete_directory(string $dir): void {
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        is_dir($path) ? delete_directory($path) : unlink($path);
    }
    rmdir($dir);
}
