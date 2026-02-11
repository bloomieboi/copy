<?php
/**
 * Сценарий 8: Администрирование статусов заявок.
 * Раздел «Все заказы»: полный список тикетов с фильтрацией по статусу и «Архивные».
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$statusFilter = $_GET['status'] ?? null;
$archiveStatusIds = [4, 5, 6]; // Готов, Выполнен, Отменен — архивные статусы

// Построение запроса: фильтр по статусу или «Архивные»
$where = [];
$params = [];

if ($statusFilter === 'archive') {
    $placeholders = implode(',', array_fill(0, count($archiveStatusIds), '?'));
    $where[] = "o.status_id IN ($placeholders)";
    $params = array_merge($params, $archiveStatusIds);
} elseif ($statusFilter !== null && $statusFilter !== '') {
    $where[] = "o.status_id = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT o.*, s.status_name, s.status_id, u.login as client_login 
        FROM order_ o 
        JOIN status s ON o.status_id = s.status_id 
        JOIN user_ u ON o.user_id = u.id_user
        $whereClause
        ORDER BY o.created_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Получаем статусы для фильтра
$statuses = $pdo->query("SELECT * FROM status ORDER BY status_id")->fetchAll();

// Получаем адреса заказов (точки обслуживания)
$orderAddresses = [];
foreach ($orders as $order) {
    $stmt = $pdo->prepare("SELECT CONCAT(l.location_name, ' - ', l.address) as address_name 
                          FROM order_address oa 
                          JOIN locations l ON oa.address_id = l.location_id 
                          WHERE oa.order_id = ?");
    $stmt->execute([$order['order_id']]);
    $addr = $stmt->fetch();
    $orderAddresses[$order['order_id']] = $addr ? $addr['address_name'] : null;
}

$pageTitle = 'Управление заказами — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Все заказы</h2>
        <p class="form-hint">Полный список заявок с возможностью фильтрации по статусу.</p>
        
        <div class="filters">
            <a href="orders.php" class="btn btn-sm <?= ($statusFilter === null || $statusFilter === '') ? 'btn-primary' : 'btn-secondary' ?>">Все</a>
            <a href="orders.php?status=archive" class="btn btn-sm <?= $statusFilter === 'archive' ? 'btn-primary' : 'btn-secondary' ?>">Архивные</a>
            <?php foreach($statuses as $status): ?>
                <a href="orders.php?status=<?= $status['status_id'] ?>" class="btn btn-sm <?= $statusFilter == $status['status_id'] ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= htmlspecialchars($status['status_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Услуга</th>
                    <th>Адрес</th>
                    <th>Статус</th>
                    <th>Стоимость</th>
                    <th>Создан</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                    <tr>
                        <td><?= $order['order_id'] ?></td>
                        <td><?= htmlspecialchars($order['client_login']) ?></td>
                        <td><?= htmlspecialchars($order['service_list']) ?></td>
                        <td><?= htmlspecialchars($orderAddresses[$order['order_id']] ?? 'Не указан') ?></td>
                        <td><span class="status-badge <?= getStatusBadgeClass($order['status_id']) ?>"><?= htmlspecialchars($order['status_name']) ?></span></td>
                        <td><?= formatPrice($order['price']) ?></td>
                        <td><?= formatDate($order['created_date']) ?></td>
                        <td>
                            <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">Подробнее</a>
                            <a href="edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-secondary">Редактировать</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($orders)): ?>
            <p class="empty-state">Заказы не найдены</p>
        <?php endif; ?>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
