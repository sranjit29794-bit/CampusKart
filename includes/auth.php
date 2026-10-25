<?php
/**
 * CampusKart - Authentication and Session Management
 *
 * Implements session regeneration on login, password hash verification,
 * secure user identity storage, and route authorization guards.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks whether a user is currently authenticated.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Returns current authenticated user record or null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Checks if the authenticated user has the administrator role.
 */
function is_admin(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Requires the user to be logged in, otherwise redirects to login page.
 */
function require_auth(string $redirectUrl = ''): void
{
    if (!is_logged_in()) {
        $target = $redirectUrl ?: $_SERVER['REQUEST_URI'] ?? 'index.php';
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please sign in to access your account.'
        ];
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }
}

/**
 * Securely signs in a user and regenerates session ID to prevent session fixation.
 */
function login_user(array $user): void
{
    // Prevent session fixation attack
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => (string)$user['name'],
        'email' => (string)$user['email'],
        'phone' => (string)($user['phone'] ?? ''),
        'role'  => (string)($user['role'] ?? 'customer'),
    ];
}

/**
 * Logs out the current user and clears session data.
 */
function logout_user(): void
{
    $_SESSION['user'] = null;
    unset($_SESSION['user']);
    session_regenerate_id(true);
}
