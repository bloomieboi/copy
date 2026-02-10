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

<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
