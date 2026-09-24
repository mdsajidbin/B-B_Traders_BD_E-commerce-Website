<?php
/**
 * B&B TRADERS BD - Database Connection (PDO)
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3307'); // <-- change this if your MySQL runs on a different port
define('DB_NAME', 'bb_traders_bd');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed. Please try again later.');
}
