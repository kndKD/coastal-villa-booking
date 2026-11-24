<?php
/**
 * Serene Shores Coastal Villa - Admin Header Component
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Strict Role Guard: Only administrators can access admin portal
requireAdmin('../login.php');

$adminUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin Portal | Serene Shores Villa'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f1f5f9;">

<header class="site-header" style="background: #0a1b2d; border-bottom: 2px solid #028090;">
    <div class="container nav-container">
        <a href="dashboard.php" class="brand-logo" aria-label="Admin Portal">
            <span class="brand-icon" style="color:#028090;">&#9881;</span>
            <div class="brand-text">
                Serene Shores
                <span style="color:#028090;">Admin Control Center</span>
            </div>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            &#9776;
        </button>

        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="rooms.php" class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : ''; ?>">Manage Rooms</a></li>
            <li><a href="bookings.php" class="nav-link <?= $currentPage === 'bookings.php' ? 'active' : ''; ?>">Manage Bookings</a></li>
            <li><a href="../index.php" class="nav-link" target="_blank">&#127760; View Live Site</a></li>
            <li>
                <span class="user-badge" style="background: rgba(2, 128, 144, 0.25); border-color: #028090;">
                    &#128737; <?= e($adminUser['name']); ?> (Admin)
                </span>
            </li>
            <li><a href="../logout.php" class="nav-link">Logout</a></li>
        </ul>
    </div>
</header>

<main style="padding: 40px 0 80px;">
    <div class="container alert-container">
        <?php renderFlash(); ?>
    </div>
