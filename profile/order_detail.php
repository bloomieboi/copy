<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
require_once __DIR__ . '/../function/file_upload.php';
session_start();
requireLogin();

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: index.php");
    exit;
}

// Оплата выполняется на отдельной странице payment.php (сценарий «Оплата по карте в приложении»).

// Обработка загрузки файла (до вывода HTML)
$filesError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['order_file'])) {
    try {
        if ($_FILES['order_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            uploadOrderFile($_FILES['order_file'], (int)$orderId, (int)$_SESSION['user_id']);
            addOrderLog($orderId, $_SESSION['user_id'], 'file_uploaded', 'Клиент загрузил файл');
            header("Location: order_detail.php?id=" . $orderId);
            exit;
        }
    } catch (Exception $e) {
        $filesError = $e->getMessage();
    }
}

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT o.*, s.status_name, s.status_id, e.login as executor_login
                       FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       LEFT JOIN user_ e ON o.executor_id = e.id_user
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Получаем адрес заказа (точку обслуживания)
$stmt = $pdo->prepare("SELECT CONCAT(l.location_name, ' - ', l.address) as address_name 
                       FROM order_address oa 
                       JOIN locations l ON oa.address_id = l.location_id 
                       WHERE oa.order_id = ?");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

// Файлы заказа
$files = getOrderFiles((int)$orderId);

// Получаем комментарий сотрудника (отчет о выполненной работе)
$employeeComment = '';
try {
    $stmt = $pdo->prepare("SELECT action_description FROM order_log WHERE order_id = ? AND action_type = 'employee_comment' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    if ($row && $row['action_description'] !== null && $row['action_description'] !== '') {
        $employeeComment = $row['action_description'];
    }
} catch (PDOException $e) {
    // Игнорируем ошибки получения комментария
}

$pageTitle = 'Детали заказа #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Детали заказа #<?= $orderId ?></h2>
        
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
                    <?php if ($order['executor_login']): ?>
                    <tr>
                        <th>Ваш сотрудник:</th>
                        <td>
                            <i class="bi bi-person-badge me-1"></i>
                            <?= htmlspecialchars($order['executor_login']) ?>
                        </td>
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
            
            <?php if ($employeeComment): ?>
            <div class="detail-section">
                <h3>
                    <i class="bi bi-chat-left-text me-2"></i>
                    Отчет сотрудника о выполненной работе
                </h3>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?= nl2br(htmlspecialchars($employeeComment)) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-section">
                <h3>Файлы к заказу</h3>
                <?php if ($filesError): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($filesError) ?></div>
                <?php endif; ?>
                <?php if (empty($files)): ?>
                    <p>Файлы не прикреплены.</p>
                <?php else: ?>
                    <div class="files-list">
                        <?php foreach($files as $file): ?>
                            <div class="file-item">
                                <a href="../<?= htmlspecialchars($file['file_path']) ?>" target="_blank">
                                    <?= htmlspecialchars($file['file_name']) ?>
                                </a>
                                <span class="file-size"><?= number_format($file['file_size'] / 1024, 2) ?> KB</span>
                                <span class="file-date"><?= formatDateTime($file['uploaded_at']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="order_file">Загрузить файл:</label>
                        <input type="file" name="order_file" id="order_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                    </div>
                    <button type="submit" class="btn btn-primary">Загрузить</button>
                </form>
            </div>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Назад к списку заказов</a>
            <?php if ($order['status_id'] == 1): ?>
                <a href="payment.php?order_id=<?= (int)$orderId ?>" class="btn btn-primary">Перейти к оплате</a>
            <?php endif; ?>
            <?php if (in_array($order['status_id'], [3,4], true)): ?>
                <a href="review.php?order_id=<?= $orderId ?>" class="btn btn-secondary">Оставить отзыв</a>
            <?php endif; ?>
            <?php if ((int)$order['status_id'] === 6): ?>
                <?php
                // Проверяем, есть ли сообщения в чате
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM message WHERE order_id = ?");
                $stmt->execute([$orderId]);
                $hasMessages = (int)$stmt->fetch()['count'] > 0;
                
                // Проверяем непрочитанные сообщения
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM message WHERE order_id = ? AND to_user_id = ? AND is_read = 0");
                $stmt->execute([$orderId, $_SESSION['user_id']]);
                $unreadCount = (int)$stmt->fetch()['count'];
                ?>
                <a href="chat.php?order_id=<?= $orderId ?>" class="btn <?= $hasMessages ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="bi bi-chat-dots me-1"></i>
                    Чат с сотрудником
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                    <?php endif; ?>
                    <?php if (!$hasMessages): ?>
                        <small class="d-block" style="font-size: 0.75rem; opacity: 0.8;">(Ждет сообщения)</small>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
