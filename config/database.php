<?php

declare(strict_types=1);



$localConfig = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
$databaseConfig = $localConfig['database'] ?? [];

$host     = getenv('DB_HOST') ?: ($databaseConfig['host'] ?? 'localhost');
$database = getenv('DB_NAME') ?: ($databaseConfig['name'] ?? 'pastorco_fgck_joyland');
$username = getenv('DB_USER') ?: ($databaseConfig['username'] ?? '');
$password = getenv('DB_PASSWORD') ?: ($databaseConfig['password'] ?? '');

$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );

} catch (PDOException $e) {

    exit(
        'Database connection failed. Please check the database configuration.'
    );
}