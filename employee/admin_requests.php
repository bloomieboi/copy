<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(2);

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Создание запроса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_request'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $requestType = trim($_POST['request_type'] ?? '');
    $requestText = trim($_POST['request_text'] ?? '');
    
    if ($orderId && $requestType && $requestText) {
        $stmt = $pdo->prepare("INSERT INTO admin_request (order_id, executor_id, request_type, request_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $userId, $requestType, $requestText]);
        addOrderLog($orderId, $userId, 'admin_request', 'Сотрудник отправил запрос администратору');
        $success = 'Запрос отправлен администратору';
    } else {
        $error = 'Заполните все поля';
    }
}

// Заказы для выбора
$employeeLocationId = getUserLocationId();
$orders = [];
if ($employeeLocationId) {
    $stmt = $pdo->prepare("SELECT o.order_id, o.service_list 
                           FROM order_ o
                           JOIN order_address oa ON o.order_id = oa.order_id
                           WHERE oa.address_id = ?
                           ORDER BY o.created_date DESC");
    $stmt->execute([$employeeLocationId]);
    $orders = $stmt->fetchAll();
}

// Запросы сотрудника
$stmt = $pdo->prepare("SELECT ar.*, o.service_list 
                       FROM admin_request ar 
                       JOIN order_ o ON ar.order_id = o.order_id
                       WHERE ar.executor_id = ?
                       ORDER BY ar.created_at DESC");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

$pageTitle = 'Запросы к администратору — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Запросы к администратору</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <section class="create-request">
            <h3>Создать запрос</h3>
            <form method="POST" class="request-form">
                <div class="form-group">
                    <label for="order_id">Заказ:</label>
                    <select name="order_id" id="order_id" required>
                        <option value="">Выберите заказ</option>
                        <?php if (empty($orders)): ?>
                            <option value="" disabled>Нет доступных заказов в вашем копицентре</option>
                        <?php else: ?>
                            <?php foreach($orders as $order): ?>
                                <option value="<?= $order['order_id'] ?>">#<?= $order['order_id'] ?> — <?= htmlspecialchars(mb_substr($order['service_list'], 0, 40)) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="request_type">Тип запроса:</label>
                    <select name="request_type" id="request_type" required>
                        <option value="resources">Необходимы ресурсы</option>
                        <option value="problem">Проблема с заказом</option>
                        <option value="clarification">Нужны уточнения</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="request_text">Описание:</label>
                    <textarea name="request_text" id="request_text" rows="4" required></textarea>
                </div>
                <button type="submit" name="create_request" class="btn btn-primary">Отправить запрос</button>
            </form>
        </section>
        
        <section class="requests-list">
            <h3>Мои запросы</h3>
            <?php if (empty($requests)): ?>
                <p class="empty-state">Запросов нет</p>
            <?php else: ?>
                <div class="requests">
                    <?php foreach($requests as $req): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <h4>Заказ #<?= $req['order_id'] ?></h4>
                                <span class="status-badge status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span>
                            </div>
                            <p><strong>Тип:</strong> <?= htmlspecialchars($req['request_type']) ?></p>
                            <p><strong>Текст:</strong> <?= nl2br(htmlspecialchars($req['request_text'])) ?></p>
                            <?php if ($req['admin_response']): ?>
                                <div class="admin-response">
                                    <strong>Ответ администратора:</strong>
                                    <p><?= nl2br(htmlspecialchars($req['admin_response'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <p><small>Создан: <?= formatDateTime($req['created_at']) ?></small></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
