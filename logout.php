<?php
/**
 * Serene Shores Coastal Villa - Safe Logout
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session storage
session_destroy();

// Start fresh session to deliver logout notice
session_start();
setFlash('info', 'You have been safely signed out. Thank you for visiting Serene Shores Villa.');

header('Location: login.php');
exit;
