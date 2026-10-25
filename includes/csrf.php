<?php
/**
 * CampusKart - CSRF Protection Utility
 *
 * Implements cryptographically secure anti-CSRF tokens for all state-changing
 * POST/PUT/DELETE requests across customer and administrator interfaces.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the current CSRF token from session, or generates a new one.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden input field containing the CSRF token.
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validates the provided CSRF token against the session token.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Convenience guard for POST requests: rejects with HTTP 403 on invalid token.
 */
function require_csrf_token(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die('Invalid or expired security token. Please refresh the page and try again.');
        }
    }
}
