<?php
// Base configuration
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://mi-dominio.com/logiops/logis_app/');
}

// Database credentials
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'logiops';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/credit.php';
require_once __DIR__ . '/provider_credit.php';
