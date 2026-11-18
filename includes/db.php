<?php
// Database connection using PDO.
$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Site configuration is missing.');
}

$config = require $configFile;

$dsn = 'mysql:host=' . $config['db_host']
     . ';dbname=' . $config['db_name']
     . ';charset=' . $config['db_charset'];

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
];

try {
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    // Log the real error privately; show only a safe message to visitors.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Sorry, the site is temporarily unavailable. Please try again later.');
}