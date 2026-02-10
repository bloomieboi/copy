<?php
/**
 * Сценарий 7: Изменение статуса заявки.
 * Детальное представление заявки: информация о заказе, клиенте, услуге; комментарий сотрудника; смена статуса на «Выполнен»/«Закрыт» с обязательным отчетом.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
require_once __DIR__ . '/../function/file_upload.php';
session_start();
requireRole(2);

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: index.php");
    exit;
}

$statusError = '';
$employeeCommentDisplay = null; // при ошибке валидации подставляем введённый текст
$takeSuccess = '';

// Обработка взятия заказа в работу
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take_order'])) {
    // Проверяем, что заказ в статусе "Оплачен" (2)
    $stmt = $pdo->prepare("SELECT status_id, executor_id FROM order_ WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $currentOrder = $stmt->fetch();
    
    if ($currentOrder && (int)$currentOrder['status_id'] === 2) {
        // Назначаем текущего сотрудника исполнителем и переводим в статус "В работе" (6)
        $stmt = $pdo->prepare("UPDATE order_ SET status_id = 6, executor_id = ? WHERE order_id = ?");
        $stmt->execute([$_SESSION['user_id'], $orderId]);
        addOrderLog($orderId, $_SESSION['user_id'], 'order_taken', 'Сотрудник взял заказ в работу');
        $takeSuccess = 'Заказ успешно взят в работу!';
        header("Location: order_detail.php?id=" . $orderId . "&taken=1");
        exit;
    } else {
        $statusError = 'Заказ не может быть взят в работу (неверный статус)';
    }
}

if (isset($_GET['taken'])) {
    $takeSuccess = 'Заказ успешно взят в работу!';
}

// Обработка сохранения статуса и комментария сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_id']) && !isset($_FILES['order_file'])) {
    $newStatusId = (int)$_POST['status_id'];
    $employeeComment = trim($_POST['employee_comment'] ?? '');
    
    // Разрешаем изменение статуса только на «Завершен» (3) или «Отменен» (5)
    $allowedStatusIds = [3, 5];
    
    if (!in_array($newStatusId, $allowedStatusIds, true)) {
        $statusError = 'Можно изменить статус только на «Завершен» или «Отменен»';
        $employeeCommentDisplay = $_POST['employee_comment'] ?? '';
    } elseif ($employeeComment === '') {
        // Комментарий обязателен при изменении статуса
        $statusError = 'Необходимо заполнить отчет о выполненной работе';
        $employeeCommentDisplay = $_POST['employee_comment'] ?? '';
    } else {
        // Проверяем, что заказ в статусе «В работе» (6)
        $stmt = $pdo->prepare("SELECT status_id FROM order_ WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $currentOrder = $stmt->fetch();
        
        if (!$currentOrder || (int)$currentOrder['status_id'] !== 6) {
            $statusError = 'Статус можно изменить только для заказов в статусе «В работе»';
            $employeeCommentDisplay = $_POST['employee_comment'] ?? '';
        } else {
            // Сохраняем комментарий
            addOrderLog($orderId, $_SESSION['user_id'], 'employee_comment', $employeeComment);
            
            // Обновляем статус
            $stmt = $pdo->prepare("UPDATE order_ SET status_id = ? WHERE order_id = ?");
            $stmt->execute([$newStatusId, $orderId]);
            addOrderLog($orderId, $_SESSION['user_id'], 'status_changed', 'Сотрудник изменил статус на ' . getStatusName($newStatusId));
            
            if ($newStatusId == 3) {
                addOrderLog($orderId, $_SESSION['user_id'], 'order_completed', 'Заказ завершен, время завершения: ' . date('d.m.Y H:i'));
            } elseif ($newStatusId == 5) {
                addOrderLog($orderId, $_SESSION['user_id'], 'order_cancelled', 'Заказ отменен, время отмены: ' . date('d.m.Y H:i'));
            }
            
            header("Location: order_detail.php?id=" . $orderId);
            exit;
        }
    }
}

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT o.*, s.status_name, s.status_id, u.login as client_login, u.phone_number as client_phone,
                              e.login as executor_login
                       FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       JOIN user_ u ON o.user_id = u.id_user
                       LEFT JOIN user_ e ON o.executor_id = e.id_user
                       WHERE o.order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Проверяем, является ли текущий сотрудник исполнителем
$isExecutor = ($order['executor_id'] && (int)$order['executor_id'] === (int)$_SESSION['user_id']);
$canTakeOrder = ((int)$order['status_id'] === 2 && !$order['executor_id']);}

// Получаем адрес заказа
$stmt = $pdo->prepare("SELECT a.address_name FROM order_address oa 
                       JOIN address a ON oa.address_id = a.address_id 
                       WHERE oa.order_id = ?");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

// Статусы для изменения: только «Завершен» (3) и «Отменен» (5)
$statuses = $pdo->query("SELECT * FROM status WHERE status_id IN (3, 5) ORDER BY status_id")->fetchAll();

// Последний комментарий сотрудника (отчет о выполненной работе) из order_log
$lastEmployeeComment = '';
try {
    $stmt = $pdo->prepare("SELECT action_description FROM order_log WHERE order_id = ? AND action_type = 'employee_comment' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    if ($row && $row['action_description'] !== null && $row['action_description'] !== '') {
        $lastEmployeeComment = $row['action_description'];
    }
} catch (PDOException $e) {
    $stmt = $pdo->prepare("SELECT action_description FROM order_log WHERE order_id = ? AND action_type = 'employee_comment'");
    $stmt->execute([$orderId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $lastEmployeeComment = end($rows)['action_description'] ?? '';
    }
}
if ($employeeCommentDisplay === null) {
    $employeeCommentDisplay = $lastEmployeeComment;
}

// Файлы заказа
$filesError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['order_file'])) {
    try {
        if ($_FILES['order_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            uploadOrderFile($_FILES['order_file'], (int)$orderId, (int)$_SESSION['user_id']);
            addOrderLog($orderId, $_SESSION['user_id'], 'file_uploaded', 'Сотрудник загрузил файл');
            header("Location: order_detail.php?id=" . $orderId);
            exit;
        }
    } catch (Exception $e) {
        $filesError = $e->getMessage();
    }
}
$files = getOrderFiles((int)$orderId);

$pageTitle = 'Детали заказа #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Детали заказа #<?= $orderId ?></h2>
        
        <?php if ($takeSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Успех!</strong> <?= htmlspecialchars($takeSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($statusError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Ошибка!</strong> <?= htmlspecialchars($statusError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($canTakeOrder): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Заказ готов к выполнению.</strong> Вы можете взять его в работу.
            </div>
            <form method="POST" class="mb-3">
                <button type="submit" name="take_order" class="btn btn-success btn-lg">
                    <i class="bi bi-hand-thumbs-up me-2"></i>
                    Взять заказ в работу
                </button>
            </form>
        <?php elseif ($order['executor_id'] && !$isExecutor): ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-person-fill me-2"></i>
                <strong>Заказ в работе у другого сотрудника:</strong> <?= htmlspecialchars($order['executor_login']) ?>
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
                    <?php if ($order['executor_id']): ?>
                    <tr>
                        <th>Исполнитель:</th>
                        <td>
                            <?= htmlspecialchars($order['executor_login']) ?>
                            <?php if ($isExecutor): ?>
                                <span class="badge bg-success ms-2">Вы</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
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
            
            <?php if ($isExecutor && (int)$order['status_id'] === 6): ?>
            <div class="detail-section">
                <h3>Работа с заявкой</h3>
                <p class="form-hint">Внесите результаты работ в поле «Комментарий сотрудника». Вы можете изменить статус заказа на «Завершен» или «Отменен». Комментарий обязателен.</p>
                <form method="POST" class="status-form">
                    <div class="form-group">
                        <label for="employee_comment">Комментарий сотрудника (отчет о выполненной работе):</label>
                        <textarea name="employee_comment" id="employee_comment" rows="4" placeholder="Опишите выполненные работы и результат"><?= htmlspecialchars($employeeCommentDisplay) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status_id">Статус заявки:</label>
                        <select name="status_id" id="status_id">
                            <?php foreach($statuses as $status): ?>
                                <option value="<?= $status['status_id'] ?>" <?= $order['status_id'] == $status['status_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
            <?php else: ?>
            <div class="detail-section">
                <p class="form-hint">Изменение статуса доступно только для заказов в статусе «В работе».</p>
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
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Назад к заказам
            </a>
            <?php if ($isExecutor && (int)$order['status_id'] === 6): ?>
                <a href="chat.php?order_id=<?= $orderId ?>" class="btn btn-primary">
                    <i class="bi bi-chat-dots me-1"></i>
                    Чат с клиентом
                </a>
            <?php endif; ?>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
