<?php
/**
 * Вспомогательные функции (PHP 8.2+)
 */

declare(strict_types=1);

/**
 * Форматирование цены
 */
function formatPrice(float|int $price): string
{
    return number_format($price, 2, '.', ' ') . ' руб.';
}

/**
 * Форматирование даты
 */
function formatDate(?string $date): string
{
    if (!$date) return '-';
    
    try {
        $dt = new DateTime($date);
        return $dt->format('d.m.Y');
    } catch (Exception) {
        return '-';
    }
}

/**
 * Форматирование даты и времени
 */
function formatDateTime(?string $datetime): string
{
    if (!$datetime) return '-';
    
    try {
        $dt = new DateTime($datetime);
        return $dt->format('d.m.Y H:i');
    } catch (Exception) {
        return '-';
    }
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
