<?php
/**
 * Функции авторизации и проверки прав доступа
 */

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . getBaseUrl() . "/login/index.php");
        exit;
    }
}

function requireRole($minRoleId) {
    requireLogin();
    $roleId = $_SESSION['role_id'] ?? 0;
    if ($roleId < $minRoleId) {
        header("Location: " . getBaseUrl() . "/index.php");
        exit;
    }
}

function isClient() {
    return ($_SESSION['role_id'] ?? 0) == 1;
}

function isEmployee() {
    return ($_SESSION['role_id'] ?? 0) == 2;
}

function isAdmin() {
    return ($_SESSION['role_id'] ?? 0) >= 3;
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    return $protocol . '://' . $host . ($path !== '/' ? $path : '');
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getRoleId() {
    return $_SESSION['role_id'] ?? 0;
}
