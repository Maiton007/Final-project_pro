<?php

// โหลดตัวแปรจาก .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // ข้าม comment
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key !== NULL && $value !== NULL) {
            putenv("$key=$value");
        }
    }
}

$host   = getenv('DB_HOST');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ป้องกันประกาศซ้ำ
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ecommerce-project1.2');
}

?>
