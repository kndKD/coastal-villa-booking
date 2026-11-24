<?php
/**
 * Serene Shores Coastal Villa - Database Configuration
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Tech Stack: PHP 8+, MySQL (PDO with Prepared Statements)
 */

declare(strict_types=1);

// Configuration parameters
$dbHost     = '127.0.0.1';
$dbName     = 'coastal_villa_db';
$dbUser     = 'root';
$dbPass     = '';
$dbCharset  = 'utf8mb4';

// Try primary XAMPP ports (3307 first because XAMPP MariaDB is configured on 3307, then 3306 fallback)
$candidatePorts = [3307, 3306];
$pdo = null;
$lastError = null;

foreach ($candidatePorts as $port) {
    try {
        $dsn = "mysql:host={$dbHost};port={$port};dbname={$dbName};charset={$dbCharset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset} COLLATE utf8mb4_unicode_ci"
        ];

        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        // If connection succeeds, break loop
        if ($pdo) {
            break;
        }
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
    }
}

if (!$pdo) {
    // If neither port connects, display a clean academic error state
    die(
        '<div style="font-family:sans-serif; max-width:600px; margin:50px auto; padding:20px; border:1px solid #e74c3c; border-radius:8px; background:#fff5f5; color:#c0392b;">' .
        '<h2 style="margin-top:0;">Database Connection Error</h2>' .
        '<p>Could not connect to the coastal villa database (<code>' . htmlspecialchars($dbName) . '</code>).</p>' .
        '<p><small>Error details: ' . htmlspecialchars($lastError ?? 'Unknown error') . '</small></p>' .
        '<p>Please verify that MySQL is running in your XAMPP Control Panel and that <code>db_structure.sql</code> has been imported.</p>' .
        '</div>'
    );
}

/**
 * Helper function to retrieve the active PDO instance
 * 
 * @return PDO
 */
function getDBConnection(): PDO {
    global $pdo;
    return $pdo;
}

// Global Currency Formatter
function formatLKR(float|int $amount): string {
    return 'LKR ' . number_format($amount, 2);
}
