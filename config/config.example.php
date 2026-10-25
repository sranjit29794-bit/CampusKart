<?php
/**
 * CampusKart - Local Environment Configuration Example
 * Copy this file to config.php and customize your local credentials.
 * DO NOT commit passwords or sensitive tokens to version control.
 */

return [
    'driver'   => 'mysql',          // 'mysql' or 'sqlite'
    'host'     => '127.0.0.1',      // Database Host (e.g., localhost)
    'port'     => '3306',           // Database Port
    'database' => 'campuskart',     // Database Name
    'username' => 'root',           // Database Username
    'password' => '',               // Database Password (default XAMPP is empty string)
    'app_name' => 'CampusKart',
    'currency' => '₹',
    'free_shipping_threshold' => 500.00,
    'standard_shipping_fee'   => 50.00,
];
