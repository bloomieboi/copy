<?php
/**
 * Сценарий 7: Изменение статуса заявки.
 * Рабочий кабинет сотрудника: разделы «Заказы в работе» и «Открытые заявки».
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
// Предусловия: пользователь авторизован и имеет роль «Сотрудник»
requireRole(2);

$tab = $_GET['tab'] ?? 'in_progress'; // in_progress | open

// Заказы в работе (статус «В работе» — status_id = 6)
$ordersInProgress = $pdo->query("SELECT o.*, s.status_name, s.status_id, u.login as client_login 
                                 FROM order_ o 
                                 JOIN status s ON o.status_id = s.status_id 
                                 JOIN user_ u ON o.user_id = u.id_user
                                 WHERE o.status_id = 6 
                                 ORDER BY o.created_date ASC")->fetchAll();

// Открытые заявки (ожидают обработки: в обработке, назначен — status_id 1, 2), отсортированы по дате поступления
$ordersOpen = $pdo->query("SELECT o.*, s.status_name, s.status_id, u.login as client_login 
                           FROM order_ o 
                           JOIN status s ON o.status_id = s.status_id 
                           JOIN user_ u ON o.user_id = u.id_user
                           WHERE o.status_id IN (1, 2) 
                           ORDER BY o.created_date ASC")->fetchAll();

$orders = ($tab === 'open') ? $ordersOpen : $ordersInProgress;
$sectionTitle = ($tab === 'open') ? 'Открытые заявки' : 'Заказы в работе';

$pageTitle = 'Панель сотрудника — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Панель сотрудника</h2>
        
        <nav class="tabs">
            <a href="index.php?tab=in_progress" class="<?= $tab === 'in_progress' ? 'active' : '' ?>">Заказы в работе</a>
            <a href="index.php?tab=open" class="<?= $tab === 'open' ? 'active' : '' ?>">Открытые заявки</a>
        </nav>
        
        <section class="orders-section">
            <h3><?= htmlspecialchars($sectionTitle) ?></h3>
            <p class="form-hint">Список заявок отсортирован по дате поступления (сначала старые).</p>
            <?php if (empty($orders)): ?>
                <p class="empty-state">Нет заявок в этом разделе.</p>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h4>Заказ #<?= $order['order_id'] ?></h4>
                                    <span class="status-badge <?= getStatusBadgeClass($order['status_id']) ?>">
                                        <?= htmlspecialchars($order['status_name']) ?>
                                    </span>
                                </div>
                                <div class="order-price">
                                    <?= formatPrice($order['price']) ?>
                                </div>
                            </div>
                            <div class="order-body">
                                <p><strong>Услуга:</strong> <?= htmlspecialchars($order['service_list']) ?></p>
                                <p><strong>Клиент:</strong> <?= htmlspecialchars($order['client_login']) ?></p>
                                <p><strong>Создан:</strong> <?= formatDateTime($order['created_date']) ?></p>
                            </div>
                            <div class="order-footer">
                                <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
