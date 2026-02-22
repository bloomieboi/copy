<?php
/**
 * Функции авторизации и проверки прав доступа
 */

/**
 * Проверяет, авторизован ли пользователь. Если нет, перенаправляет на страницу входа.
 * Также обновляет данные сессии из БД, если пользователь авторизован.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . getBaseUrl() . "/login/index.php");
        exit;
    }

    // Обновляем данные сессии из БД, чтобы они всегда были актуальны
    global $pdo;
    if (isset($pdo) && $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT role_id, location_id FROM user_ WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['location_id'] = !empty($user['location_id']) ? (int)$user['location_id'] : null;
        }
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

function getUserLocationId() {
    return $_SESSION['location_id'] ?? null;
}

function getRoleId() {
    return $_SESSION['role_id'] ?? 0;
}
