<?php
/**
 * Serene Shores Coastal Villa - Admin Directory Gateway
 * Redirects authorized administrators to dashboard.php, or enforces authentication
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce administrator authentication
requireAdmin('../login.php', '../index.php');

// Redirect authorized admin to dashboard
header('Location: dashboard.php');
exit;
