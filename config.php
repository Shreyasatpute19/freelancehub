<?php
// ---- Settings: edit these to match your XAMPP setup ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'freelance_db');
define('DB_USER', 'root');
define('DB_PASS', '');
// URL path where the project lives. Leave '' to detect it automatically (works for any folder name),
// or set it by hand, e.g. http://localhost/freelance  ->  '/freelance'
$manualBase = '';
if ($manualBase === '') {
    $docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $here = str_replace('\\', '/', __DIR__);
    $manualBase = ($docRoot !== '' && stripos($here, $docRoot) === 0) ? substr($here, strlen($docRoot)) : '/freelance';
}
define('BASE_URL', rtrim($manualBase, '/'));
define('SITE_NAME', 'FreelanceHub');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('CURRENCY', '₹');
date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    die('Database connection failed. Start MySQL in XAMPP, import database.sql, and check DB_USER / DB_PASS in config.php.'
        . ($local ? ' (' . htmlspecialchars($e->getMessage()) . ')' : ''));
}

require_once __DIR__ . '/includes/functions.php';
