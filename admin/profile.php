<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$userId = $_SESSION['user_id'];

// Информация об администраторе
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM user_ u JOIN role_ r ON u.role_id = r.role_id WHERE u.id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Простая статистика по системе
$totalOrders = $pdo->query("SELECT COUNT(*) AS c FROM order_")->fetch()['c'] ?? 0;
$totalClients = $pdo->query("SELECT COUNT(*) AS c FROM user_ WHERE role_id = 1")->fetch()['c'] ?? 0;
$totalEmployees = $pdo->query("SELECT COUNT(*) AS c FROM user_ WHERE role_id = 2")->fetch()['c'] ?? 0;

// Последние действия (логи) админа
$stmt = $pdo->prepare("SELECT l.*, o.order_id 
                       FROM order_log l 
                       JOIN order_ o ON l.order_id = o.order_id
                       WHERE l.user_id = ?
                       ORDER BY l.created_at DESC
                       LIMIT 10");
$stmt->execute([$userId]);
$logs = $stmt->fetchAll();

$pageTitle = 'Личный кабинет администратора — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <div class="profile-header">
            <h2>Личный кабинет администратора</h2>
            <div class="user-info">
                <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                <p><strong>Роль:</strong> <?= htmlspecialchars($user['role_name']) ?></p>
            </div>
        </div>

        <section class="stats-grid">
            <div class="stat-card">
                <h3>Всего заказов</h3>
                <div class="stat-value"><?= (int)$totalOrders ?></div>
            </div>
            <div class="stat-card">
                <h3>Клиентов</h3>
                <div class="stat-value"><?= (int)$totalClients ?></div>
            </div>
            <div class="stat-card">
                <h3>Сотрудников</h3>
                <div class="stat-value"><?= (int)$totalEmployees ?></div>
            </div>
        </section>

        <section class="orders-section">
            <h3>Последние действия</h3>
            <?php if (empty($logs)): ?>
                <p class="empty-state">Журнал действий пуст.</p>
            <?php else: ?>
                <div class="logs-list">
                    <?php foreach($logs as $log): ?>
                        <div class="log-item">
                            <div class="log-header">
                                <strong>Заказ #<?= $log['order_id'] ?></strong>
                                <span class="log-date"><?= formatDateTime($log['created_at']) ?></span>
                            </div>
                            <div class="log-body">
                                <span class="log-type"><?= htmlspecialchars($log['action_type']) ?></span>
                                <?php if ($log['action_description']): ?>
                                    <p><?= htmlspecialchars($log['action_description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>

