<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

// Статистика
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) as count FROM order_")->fetch()['count'],
    'pending_orders' => $pdo->query("SELECT COUNT(*) as count FROM order_ WHERE status_id = 1")->fetch()['count'],
    'paid_orders' => $pdo->query("SELECT COUNT(*) as count FROM order_ WHERE status_id = 2")->fetch()['count'],
    'completed_orders' => $pdo->query("SELECT COUNT(*) as count FROM order_ WHERE status_id = 3")->fetch()['count'],
    'total_clients' => $pdo->query("SELECT COUNT(*) as count FROM user_ WHERE role_id = 1")->fetch()['count'],
    'total_employees' => $pdo->query("SELECT COUNT(*) as count FROM user_ WHERE role_id = 2")->fetch()['count'],
];

// Получаем последние заказы
$recentOrders = $pdo->query("SELECT o.*, s.status_name, s.status_id, u.login as client_login 
                             FROM order_ o 
                             JOIN status s ON o.status_id = s.status_id 
                             JOIN user_ u ON o.user_id = u.id_user
                             ORDER BY o.created_date DESC 
                             LIMIT 10")->fetchAll();

$pageTitle = 'Панель администратора — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Панель администратора</h2>
        
        <section class="stats-grid">
            <div class="stat-card">
                <h3>Всего заказов</h3>
                <div class="stat-value"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>В процессе оплаты</h3>
                <div class="stat-value"><?= $stats['pending_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Оплачено</h3>
                <div class="stat-value"><?= $stats['paid_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Завершено</h3>
                <div class="stat-value"><?= $stats['completed_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Клиентов</h3>
                <div class="stat-value"><?= $stats['total_clients'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Сотрудников</h3>
                <div class="stat-value"><?= $stats['total_employees'] ?></div>
            </div>
        </section>
        
        <section class="admin-sections">
            <div class="admin-section">
                <h3>Управление заказами</h3>
                <div class="section-links">
                    <a href="orders.php" class="section-link">Все заказы</a>
                    <a href="orders.php?status=1" class="section-link">В процессе оплаты</a>
                    <a href="orders.php?status=2" class="section-link">Оплачен</a>
                    <a href="orders.php?status=3" class="section-link">Завершен</a>
                </div>
            </div>
            
            <div class="admin-section">
                <h3>Управление пользователями</h3>
                <div class="section-links">
                    <a href="users.php" class="section-link">Все пользователи</a>
                    <a href="users.php?role=1" class="section-link">Клиенты</a>
                    <a href="users.php?role=2" class="section-link">Сотрудники</a>
                    <a href="users.php?role=3" class="section-link">Администраторы</a>
                </div>
            </div>
            
            <div class="admin-section">
                <h3>Точки обслуживания</h3>
                <div class="section-links">
                    <a href="locations.php" class="section-link">Все точки обслуживания</a>
                </div>
            </div>
            <div class="admin-section">
                <h3>Настройки</h3>
                <div class="section-links">
                    <a href="addresses.php" class="section-link">Адреса</a>
                    <a href="services.php" class="section-link">Услуги и прайс-лист</a>
                </div>
            </div>
        </section>
        
        <section class="recent-orders">
            <h3>Последние заказы</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Услуга</th>
                        <th>Статус</th>
                        <th>Стоимость</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentOrders as $order): ?>
                        <tr>
                            <td><?= $order['order_id'] ?></td>
                            <td><?= htmlspecialchars($order['client_login']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($order['service_list'], 0, 50)) ?><?= mb_strlen($order['service_list']) > 50 ? '...' : '' ?></td>
                            <td><span class="status-badge <?= getStatusBadgeClass($order['status_id']) ?>"><?= htmlspecialchars($order['status_name']) ?></span></td>
                            <td><?= formatPrice($order['price']) ?></td>
                            <td>
                                <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">Подробнее</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
