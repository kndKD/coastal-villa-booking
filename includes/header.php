<?php
/**
 * Serene Shores Coastal Villa - Master Header Component
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 */

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Serene Shores Coastal Villa | Luxury Boutique Retreat Mirissa';
}

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>

<header class="site-header">
    <div class="container nav-container">
        <a href="index.php" class="brand-logo" aria-label="Serene Shores Home">
            <span class="brand-icon">&#9875;</span>
            <div class="brand-text">
                Serene Shores
                <span>Coastal Villa &bull; Mirissa</span>
            </div>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
            &#9776;
        </button>

        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="rooms.php" class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : ''; ?>">Rooms & Suites</a></li>
            <li><a href="index.php#facilities" class="nav-link">Facilities</a></li>
            <li><a href="index.php#location" class="nav-link">Mirissa Location</a></li>

            <?php if (isLoggedIn()): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php" class="nav-link" style="color:var(--accent-sand);">&#9881; Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="my_bookings.php" class="nav-link <?= $currentPage === 'my_bookings.php' ? 'active' : ''; ?>">My Bookings</a></li>

                <li>
                    <span class="user-badge">
                        &#128100; <?= e($user['name'] ?? 'User'); ?>
                    </span>
                </li>
                <li><a href="logout.php" class="nav-link" title="Sign out of your account">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : ''; ?>">Sign In</a></li>
                <li><a href="register.php" class="nav-link <?= $currentPage === 'register.php' ? 'active' : ''; ?>">Register</a></li>
                <li><a href="rooms.php" class="nav-cta">Book a Stay</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main>
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="container alert-container">
            <?php renderFlash(); ?>
        </div>
    <?php endif; ?>
