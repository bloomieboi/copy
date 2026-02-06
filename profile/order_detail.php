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
$stmt = $pdo->prepare("SELECT o.*, s.status_name, s.status_id 
                       FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Получаем адрес заказа
$stmt = $pdo->prepare("SELECT a.address_name FROM order_address oa 
                       JOIN address a ON oa.address_id = a.address_id 
                       WHERE oa.order_id = ?");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

// Файлы заказа
$files = getOrderFiles((int)$orderId);

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
                    <?php if ($address): ?>
                    <tr>
                        <th>Адрес:</th>
                        <td><?= htmlspecialchars($address['address_name']) ?></td>
                    </tr>
                    <?php endif; ?>
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
                <a href="chat.php?order_id=<?= $orderId ?>" class="btn btn-primary">Чат с сотрудником</a>
            <?php endif; ?>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
