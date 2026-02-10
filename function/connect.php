<?php
/**
 * Подключение к базе данных (PHP 8.2+)
 */

declare(strict_types=1);

// Загрузка конфигурации
$config = require __DIR__ . '/../config/database.php';

// Создание DSN
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

// Подключение к БД
try {
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
} catch (PDOException $e) {
    // В продакшене логировать, а не показывать
    if (getenv('APP_ENV') === 'production') {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Сервис временно недоступен. Попробуйте позже.');
    }
    exit('Ошибка подключения к базе данных: ' . $e->getMessage());
}