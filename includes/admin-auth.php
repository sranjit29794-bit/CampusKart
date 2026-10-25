<?php
/**
 * CampusKart - Administrator Authorization Guard
 *
 * Verifies on the server that the active session belongs to an authenticated
 * user with role === 'admin'. Rejects direct access and unauthorized requests.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (!is_logged_in()) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Administrator authentication required.'
    ];
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'admin/index.php'));
    exit;
}

if (!is_admin()) {
    http_response_code(403);
    die('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden - CampusKart Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f5f7;font-family:sans-serif;margin:0;">
    <div style="background:#fff;padding:2.5rem;border-radius:4px;box-shadow:0 4px 20px rgba(0,0,0,0.06);max-width:480px;text-align:center;">
        <h1 style="color:#e53935;font-size:3rem;margin:0 0 1rem;">403</h1>
        <h2 style="margin:0 0 1rem;color:#111;">Access Denied</h2>
        <p style="color:#666;line-height:1.6;margin-bottom:2rem;">You do not possess administrative clearance to access this portal.</p>
        <a href="../index.php" style="display:inline-block;background:#111;color:#fff;padding:0.75rem 1.5rem;text-decoration:none;font-weight:600;border-radius:2px;">Return to CampusKart</a>
    </div>
</body>
</html>');
}
