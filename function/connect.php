<?php
// Классический MySQL-порт 3306 (см. лог: "port: 3306")
$host = '127.0.1.28';
$db   = 'copypasteDB';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$port = '3306';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Ошибка подключения к базе данных: ' . $e->getMessage());
}
?>