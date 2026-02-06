<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Статистика по заказам
$stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(price) as total FROM orders 
                       WHERE created_at >= ? AND created_at <= ? AND is_archived = FALSE");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$orderStats = $stmt->fetch();

// Статистика по статусам
$stmt = $pdo->prepare("SELECT s.status_name, COUNT(o.order_id) as count 
                       FROM statuses s 
                       LEFT JOIN orders o ON s.status_id = o.status_id 
                       AND o.created_at >= ? AND o.created_at <= ? AND o.is_archived = FALSE
                       GROUP BY s.status_id, s.status_name 
                       ORDER BY s.status_id");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$statusStats = $stmt->fetchAll();

// Статистика по исполнителям
$stmt = $pdo->prepare("SELECT u.id_user, u.login, u.full_name, 
                       COUNT(o.order_id) as orders_count, 
                       SUM(o.price) as total_revenue,
                       AVG(r.rating) as avg_rating
                       FROM users u 
                       LEFT JOIN orders o ON u.id_user = o.executor_id 
                       AND o.created_at >= ? AND o.created_at <= ? AND o.is_archived = FALSE
                       LEFT JOIN reviews r ON o.order_id = r.order_id
                       WHERE u.role_id = 2 AND u.is_active = TRUE
                       GROUP BY u.id_user, u.login, u.full_name
                       ORDER BY orders_count DESC");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$executorStats = $stmt->fetchAll();

// Статистика по услугам
$stmt = $pdo->prepare("SELECT sv.service_name, COUNT(o.order_id) as orders_count, SUM(o.price) as total_revenue
                       FROM services sv
                       LEFT JOIN orders o ON sv.service_id = o.service_id 
                       AND o.created_at >= ? AND o.created_at <= ? AND o.is_archived = FALSE
                       GROUP BY sv.service_id, sv.service_name
                       ORDER BY orders_count DESC");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$serviceStats = $stmt->fetchAll();

$pageTitle = 'Отчеты и аналитика — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Отчеты и аналитика</h2>
        
        <section class="report-filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="date_from">С:</label>
                    <input type="date" name="date_from" id="date_from" value="<?= $dateFrom ?>" required>
                </div>
                <div class="form-group">
                    <label for="date_to">По:</label>
                    <input type="date" name="date_to" id="date_to" value="<?= $dateTo ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Применить</button>
            </form>
        </section>
        
        <section class="report-summary">
            <h3>Общая статистика</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Всего заказов</h4>
                    <div class="stat-value"><?= $orderStats['count'] ?></div>
                </div>
                <div class="stat-card">
                    <h4>Общая выручка</h4>
                    <div class="stat-value"><?= formatPrice($orderStats['total'] ?? 0) ?></div>
                </div>
            </div>
        </section>
        
        <section class="report-section">
            <h3>Статистика по статусам</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Статус</th>
                        <th>Количество заказов</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($statusStats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['status_name']) ?></td>
                            <td><?= $stat['count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <section class="report-section">
            <h3>Статистика по исполнителям</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Исполнитель</th>
                        <th>Заказов</th>
                        <th>Выручка</th>
                        <th>Средняя оценка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($executorStats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['full_name'] ?: $stat['login']) ?></td>
                            <td><?= $stat['orders_count'] ?></td>
                            <td><?= formatPrice($stat['total_revenue'] ?? 0) ?></td>
                            <td>
                                <?php if ($stat['avg_rating']): ?>
                                    <?= number_format($stat['avg_rating'], 2) ?>/5
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <section class="report-section">
            <h3>Статистика по услугам</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th>Количество заказов</th>
                        <th>Выручка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($serviceStats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['service_name']) ?></td>
                            <td><?= $stat['orders_count'] ?></td>
                            <td><?= formatPrice($stat['total_revenue'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Назад</a>
            <button onclick="window.print()" class="btn btn-primary">Печать отчета</button>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
