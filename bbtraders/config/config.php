<?php
/**
 * B&B TRADERS BD - Global Configuration / Bootstrap
 */

define('APP_DEBUG', false);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(APP_DEBUG ? E_ALL : 0);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

date_default_timezone_set('Asia/Dhaka');

// Auto-detect BASE_URL so the app works on localhost, subfolders and production
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Normalize when included from subfolders (admin/, account/, ajax/)
$root = preg_replace('#/(admin|account|ajax)(/.*)?$#', '', $scriptDir);
$root = rtrim($root, '/');
define('BASE_URL', $protocol . $host . $root . '/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/products/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/products/');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
