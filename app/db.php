<?php
function db(): PDO
{
    global $db_host, $db_name, $db_user, $db_pass;
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    return $pdo;
}
