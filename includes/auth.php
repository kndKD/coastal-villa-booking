<?php
/**
 * Serene Shores Coastal Villa - Authentication & Session Helper
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: Role-based Access Control, Session Guards, CSRF, and Escaping
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check if a user is currently logged in
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged-in user is an administrator
 *
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Retrieve the current logged-in user details from the session
 *
 * @return array|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id'        => (int)($_SESSION['user_id'] ?? 0),
        'name'           => (string)($_SESSION['name'] ?? ''),
        'email'          => (string)($_SESSION['email'] ?? ''),
        'contact_number' => (string)($_SESSION['contact_number'] ?? ''),
        'role'           => (string)($_SESSION['role'] ?? 'guest')
    ];
}

/**
 * Enforce guest/user login. Redirects to login page if unauthorized.
 *
 * @param string|null $redirectAfter
 * @param string $loginUrl
 * @return void
 */
function requireLogin(?string $redirectAfter = null, string $loginUrl = 'login.php'): void {
    if (!isLoggedIn()) {
        $target = $redirectAfter ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
        setFlash('warning', 'Please sign in to proceed with your villa reservation.');
        header('Location: ' . $loginUrl . '?redirect=' . urlencode($target));
        exit;
    }
}

/**
 * Enforce administrator login. Redirects unauthorized users.
 *
 * @param string $loginUrl
 * @return void
 */
function requireAdmin(string $loginUrl = '../login.php', string $forbiddenRedirect = '../index.php'): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Administrator authentication required.');
        header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'admin/dashboard.php'));
        exit;
    }

    if (!isAdmin()) {
        setFlash('danger', 'Access denied. You do not hold administrative privileges.');
        header('Location: ' . $forbiddenRedirect);
        exit;
    }
}

/**
 * HTML escaping shorthand for XSS prevention
 *
 * @param mixed $str
 * @return string
 */
function e(mixed $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @return void
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear the current flash message
 *
 * @return array|null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message banner HTML if one exists
 *
 * @return void
 */
function renderFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']);
        $message = e($flash['message']);
        $icon = match($flash['type']) {
            'success' => '&#10003;', // checkmark
            'danger'  => '&#9888;',  // warning triangle
            'warning' => '&#9888;',
            default   => '&#8505;'   // info
        };

        echo <<<HTML
        <div class="alert alert-{$type} alert-dismissible" role="alert">
            <span class="alert-icon">{$icon}</span>
            <span class="alert-text">{$message}</span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
        </div>
HTML;
    }
}

/**
 * Generate CSRF token and return input tag
 *
 * @return string
 */
function csrfField(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

/**
 * Validate CSRF token from POST
 *
 * @param string|null $token
 * @return bool
 */
function validateCsrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
