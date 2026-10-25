<?php
/**
 * CampusKart - Secure Logout
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
set_flash('info', 'You have been successfully signed out.');
redirect('index.php');
