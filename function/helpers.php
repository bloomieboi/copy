<?php
/**
 * Вспомогательные функции для работы с новой схемой БД
 */

function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' руб.';
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d.m.Y H:i', strtotime($datetime));
}

function getStatusBadgeClass($statusId) {
    $classes = [
        1 => 'status-pending',      // В процессе оплаты
        2 => 'status-paid',          // Оплачен
        3 => 'status-completed',     // Завершен
        4 => 'status-closed',        // Закрыт
        5 => 'status-cancelled'      // Отменен
    ];
    return $classes[$statusId] ?? 'status-default';
}

function getRoleName($roleId) {
    $roles = [
        1 => 'Клиент',
        2 => 'Сотрудник',
        3 => 'Администратор'
    ];
    return $roles[$roleId] ?? 'Неизвестно';
}

function getStatusName($statusId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT status_name FROM status WHERE status_id = ?");
    $stmt->execute([$statusId]);
    $result = $stmt->fetch();
    return $result ? $result['status_name'] : 'Неизвестно';
}

/**
 * Логирование действий по заказу в таблицу order_log
 */
function addOrderLog($orderId, $userId, $actionType, $description = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO order_log (order_id, user_id, action_type, action_description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderId, $userId, $actionType, $description]);
}

/**
 * Подсчет непрочитанных сообщений для пользователя
 */
function getUnreadMessagesCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM message WHERE to_user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['cnt'] : 0;
}
