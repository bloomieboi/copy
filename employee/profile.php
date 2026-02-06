<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(2);

$userId = $_SESSION['user_id'];

// Информация о сотруднике
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM user_ u JOIN role_ r ON u.role_id = r.role_id WHERE u.id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Заказы (все, кроме отмененных)
$orders = $pdo->query("SELECT o.*, s.status_name, s.status_id, u.login AS client_login
                       FROM order_ o
                       JOIN status s ON o.status_id = s.status_id
                       JOIN user_ u ON o.user_id = u.id_user
                       WHERE o.status_id != 5
                       ORDER BY o.created_date DESC")->fetchAll();

// Непрочитанные сообщения
$unreadMessages = getUnreadMessagesCount($userId);

$pageTitle = 'Личный кабинет сотрудника — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <div class="profile-header">
            <h2>Личный кабинет сотрудника</h2>
            <div class="user-info">
                <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                <p><strong>Роль:</strong> <?= htmlspecialchars($user['role_name']) ?></p>
                <p><strong>Непрочитанных сообщений:</strong> <?= (int)$unreadMessages ?></p>
            </div>
        </div>

        <section class="orders-section">
            <h3>Текущие заказы</h3>
            <?php if (empty($orders)): ?>
                <p class="empty-state">Заказов нет.</p>
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
                                <a href="chat.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-secondary">Чат</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>

