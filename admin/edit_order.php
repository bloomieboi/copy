<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$archiveStatusIds = [4, 5, 6];

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT * FROM order_ WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Альтернативный поток: попытка изменения закрытого тикета — перенаправляем в карточку с сообщением
if (in_array((int)$order['status_id'], $archiveStatusIds, true)) {
    header("Location: order_detail.php?id=" . $orderId);
    exit;
}

// Получаем статусы
$statuses = $pdo->query("SELECT * FROM status ORDER BY status_id")->fetchAll();

// Получаем точки обслуживания (адреса)
$addresses = $pdo->query("SELECT location_id as address_id, CONCAT(location_name, ' - ', address) as address_name FROM locations WHERE is_active = 1 ORDER BY location_name")->fetchAll();

// Получаем текущий адрес заказа
$stmt = $pdo->prepare("SELECT address_id FROM order_address WHERE order_id = ?");
$stmt->execute([$orderId]);
$currentAddress = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $statusId = intval($_POST['status_id']);
    $serviceList = trim($_POST['service_list']);
    $price = floatval($_POST['price']);
    $addressId = $_POST['address_id'] ? intval($_POST['address_id']) : null;
    
    try {
        $stmt = $pdo->prepare("UPDATE order_ SET status_id = ?, service_list = ?, price = ? WHERE order_id = ?");
        $stmt->execute([$statusId, $serviceList, $price, $orderId]);
        
        // Обновляем адрес
        if ($addressId) {
            // Удаляем старую связь
            $stmt = $pdo->prepare("DELETE FROM order_address WHERE order_id = ?");
            $stmt->execute([$orderId]);
            
            // Создаем новую связь
            $stmt = $pdo->prepare("INSERT INTO order_address (order_id, address_id) VALUES (?, ?)");
            $stmt->execute([$orderId, $addressId]);
        } else {
            // Удаляем связь с адресом
            $stmt = $pdo->prepare("DELETE FROM order_address WHERE order_id = ?");
            $stmt->execute([$orderId]);
        }
        
        $success = 'Заказ успешно обновлен';
        $order['status_id'] = $statusId;
        $order['service_list'] = $serviceList;
        $order['price'] = $price;
    } catch (PDOException $e) {
        $error = 'Ошибка обновления: ' . $e->getMessage();
    }
}

$pageTitle = 'Редактирование заказа #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Редактирование заказа #<?= $orderId ?></h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="status_id">Статус:</label>
                <select name="status_id" id="status_id" required>
                    <?php foreach($statuses as $status): ?>
                        <option value="<?= $status['status_id'] ?>" <?= $order['status_id'] == $status['status_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="service_list">Услуга:</label>
                <input type="text" name="service_list" id="service_list" value="<?= htmlspecialchars($order['service_list']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price">Стоимость:</label>
                <input type="number" name="price" id="price" step="0.01" value="<?= $order['price'] ?>" required>
            </div>
            
            <div class="form-group">
                <label for="address_id">Адрес организации (обязательно):</label>
                <select name="address_id" id="address_id" required>
                    <option value="">Выберите адрес</option>
                    <?php foreach($addresses as $addr): ?>
                        <option value="<?= $addr['address_id'] ?>" <?= ($currentAddress && $currentAddress['address_id'] == $addr['address_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($addr['address_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
