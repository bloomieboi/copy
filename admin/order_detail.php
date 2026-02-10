<?php
/**
 * Сценарий 8: Администрирование статусов заявок.
 * Детальная карточка заявки: полная история изменений, текущий статус; изменение статуса (для активных) или блокировка для архивных.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$archiveStatusIds = [4, 5, 6];
$statusError = '';
$statusSuccess = '';

// Обработка «Сохранить изменения» (только для активных заявок)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_save_status'])) {
    $stmt = $pdo->prepare("SELECT status_id FROM order_ WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $current = $stmt->fetch();
    $isArchive = $current && in_array((int)$current['status_id'], $archiveStatusIds, true);
    
    if ($isArchive) {
        $statusError = 'Редактирование архивных заявок запрещено.';
    } else {
        $newStatusId = (int)($_POST['status_id'] ?? 0);
        $comment = trim($_POST['status_comment'] ?? '');
        if ($newStatusId >= 1 && $newStatusId <= 6) {
            $stmt = $pdo->prepare("UPDATE order_ SET status_id = ? WHERE order_id = ?");
            $stmt->execute([$newStatusId, $orderId]);
            addOrderLog($orderId, $_SESSION['user_id'], 'status_changed', 'Администратор изменил статус на ' . getStatusName($newStatusId) . ($comment ? '. Комментарий: ' . $comment : ''));
            addOrderLog($orderId, $_SESSION['user_id'], 'admin_notify_employee', 'Ответственный сотрудник уведомлен об изменении статуса администратором.');
            $statusSuccess = 'Изменения сохранены. Ответственный сотрудник уведомлен.';
            header("Location: order_detail.php?id=" . $orderId . "&saved=1");
            exit;
        }
    }
}

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT o.*, s.status_name, s.status_id, u.login as client_login, u.phone_number as client_phone
                       FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       JOIN user_ u ON o.user_id = u.id_user
                       WHERE o.order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

$isArchive = in_array((int)$order['status_id'], $archiveStatusIds, true);

// Получаем адрес заказа
$stmt = $pdo->prepare("SELECT a.address_name FROM order_address oa 
                       JOIN address a ON oa.address_id = a.address_id 
                       WHERE oa.order_id = ?");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

// Полная история изменений по заявке
$orderHistory = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM order_log WHERE order_id = ? ORDER BY id DESC");
    $stmt->execute([$orderId]);
    $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stmt = $pdo->prepare("SELECT * FROM order_log WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $orderHistory = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

$statuses = $pdo->query("SELECT * FROM status ORDER BY status_id")->fetchAll();
if (isset($_GET['saved'])) {
    $statusSuccess = 'Изменения сохранены. Ответственный сотрудник уведомлен.';
}

$pageTitle = 'Детали заказа #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Детали заказа #<?= $orderId ?></h2>
        
        <?php if ($statusSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($statusSuccess) ?></div>
        <?php endif; ?>
        <?php if ($statusError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($statusError) ?></div>
        <?php endif; ?>
        
        <?php if ($isArchive): ?>
            <div class="alert alert-warning">
                <strong>Редактирование архивных заявок запрещено.</strong><br>
                При необходимости внесения изменений создайте новую заявку на основе архивной.
            </div>
        <?php endif; ?>
        
        <div class="order-detail">
            <div class="detail-section">
                <h3>Основная информация</h3>
                <table class="info-table">
                    <tr>
                        <th>Статус:</th>
                        <td><span class="status-badge <?= getStatusBadgeClass($order['status_id']) ?>"><?= htmlspecialchars($order['status_name']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Услуга:</th>
                        <td><?= htmlspecialchars($order['service_list']) ?></td>
                    </tr>
                    <tr>
                        <th>Клиент:</th>
                        <td><?= htmlspecialchars($order['client_login']) ?></td>
                    </tr>
                    <?php if ($order['client_phone']): ?>
                    <tr>
                        <th>Телефон клиента:</th>
                        <td><?= htmlspecialchars($order['client_phone']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Адрес организации:</th>
                        <td><?= htmlspecialchars($address['address_name'] ?? 'Не указан') ?></td>
                    </tr>
                    <tr>
                        <th>Стоимость:</th>
                        <td class="price"><?= formatPrice($order['price']) ?></td>
                    </tr>
                    <tr>
                        <th>Создан:</th>
                        <td><?= formatDateTime($order['created_date']) ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="detail-section">
                <h3>История изменений</h3>
                <?php if (empty($orderHistory)): ?>
                    <p>История пуста.</p>
                <?php else: ?>
                    <ul class="order-history">
                        <?php foreach ($orderHistory as $log): ?>
                            <li>
                                <strong><?= htmlspecialchars($log['action_type'] ?? '—') ?></strong>
                                <?php if (!empty($log['action_description'])): ?>
                                    — <?= htmlspecialchars($log['action_description']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php if (!$isArchive): ?>
            <div class="detail-section">
                <h3>Изменить статус заявки</h3>
                <form method="POST">
                    <input type="hidden" name="admin_save_status" value="1">
                    <div class="form-group">
                        <label for="status_id">Статус:</label>
                        <select name="status_id" id="status_id" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['status_id'] ?>" <?= $order['status_id'] == $status['status_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status_comment">Комментарий к изменению (необязательно):</label>
                        <textarea name="status_comment" id="status_comment" rows="2" placeholder="Причина или пояснение"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
            <?php else: ?>
            <div class="detail-section">
                <p class="form-hint">Поле «Статус» для архивной заявки недоступно для редактирования.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="orders.php" class="btn btn-secondary">Назад к списку (Все заказы)</a>
            <?php if (!$isArchive): ?>
                <a href="edit_order.php?id=<?= $orderId ?>" class="btn btn-secondary">Редактировать заказ (услуга, цена, адрес)</a>
            <?php endif; ?>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
