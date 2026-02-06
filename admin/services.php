<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$success = '';
$error = '';

// Проверка существования таблицы services
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM services LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    $tableExists = false;
    $error = 'Таблица услуг (services) не найдена в базе данных. Создайте таблицу или импортируйте схему БД.';
}

// Создание/редактирование услуги
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $tableExists) {
    $serviceId = $_POST['service_id'] ?? null;
    $serviceName = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $basePrice = floatval($_POST['base_price']);
    $locationId = $_POST['location_id'] ? intval($_POST['location_id']) : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($serviceName && $basePrice > 0) {
        try {
            if ($serviceId) {
                $stmt = $pdo->prepare("UPDATE services SET service_name = ?, description = ?, base_price = ?, location_id = ?, is_active = ? WHERE service_id = ?");
                $stmt->execute([$serviceName, $description, $basePrice, $locationId, $isActive, $serviceId]);
                $success = 'Услуга обновлена';
            } else {
                $stmt = $pdo->prepare("INSERT INTO services (service_name, description, base_price, location_id, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$serviceName, $description, $basePrice, $locationId, $isActive]);
                $success = 'Услуга создана';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка сохранения услуги: ' . $e->getMessage();
        }
    } else {
        if (!$serviceName) {
            $error = 'Заполните все обязательные поля';
        } else {
            $error = 'Цена должна быть положительным числом';
        }
    }
}

// Удаление услуги
if (isset($_GET['delete']) && $tableExists) {
    try {
        $serviceId = intval($_GET['delete']);
        $stmt = $pdo->prepare("UPDATE services SET is_active = FALSE WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        $success = 'Услуга деактивирована';
    } catch (PDOException $e) {
        $error = 'Ошибка деактивации услуги: ' . $e->getMessage();
    }
}

// Получаем услуги
$services = [];
if ($tableExists) {
    try {
        $services = $pdo->query("SELECT s.*, l.location_name FROM services s 
                                 LEFT JOIN locations l ON s.location_id = l.location_id 
                                 ORDER BY s.service_name")->fetchAll();
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки списка услуг: ' . $e->getMessage();
        $services = [];
    }
}

// Получаем локации
$locations = [];
try {
    $locations = $pdo->query("SELECT * FROM locations WHERE is_active = TRUE ORDER BY location_name")->fetchAll();
} catch (PDOException $e) {
    // Таблица locations может отсутствовать, это не критично
    $locations = [];
}

// Редактирование услуги
$editService = null;
if (isset($_GET['edit']) && $tableExists) {
    try {
        $serviceId = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        $editService = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки услуги: ' . $e->getMessage();
    }
}

$pageTitle = 'Управление услугами — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Управление услугами и прайс-листом</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!$tableExists): ?>
            <div class="alert alert-error">
                <strong>Таблица услуг не найдена.</strong> Для работы с услугами необходимо создать таблицу <code>services</code> в базе данных или импортировать схему БД.
            </div>
        <?php endif; ?>
        
        <?php if ($tableExists): ?>
        <section class="service-form-section">
            <h3><?= $editService ? 'Редактирование услуги' : 'Создание новой услуги' ?></h3>
            <form method="POST" class="service-form">
                <?php if ($editService): ?>
                    <input type="hidden" name="service_id" value="<?= $editService['service_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="service_name">Название услуги:</label>
                    <input type="text" name="service_name" id="service_name" value="<?= htmlspecialchars($editService['service_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea name="description" id="description" rows="3"><?= htmlspecialchars($editService['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="base_price">Базовая цена:</label>
                    <input type="number" name="base_price" id="base_price" step="0.01" value="<?= $editService['base_price'] ?? '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="location_id">Локация (необязательно):</label>
                    <select name="location_id" id="location_id">
                        <option value="">Все локации</option>
                        <?php foreach($locations as $location): ?>
                            <option value="<?= $location['location_id'] ?>" <?= ($editService['location_id'] ?? null) == $location['location_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?= ($editService['is_active'] ?? true) ? 'checked' : '' ?>>
                        Активна
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editService ? 'Сохранить' : 'Создать' ?></button>
                    <?php if ($editService): ?>
                        <a href="services.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        <?php endif; ?>
        
        <section class="services-list">
            <h3>Список услуг</h3>
            <?php if (empty($services)): ?>
                <p class="empty-state"><?= $tableExists ? 'Услуги не найдены. Создайте первую услугу.' : 'Таблица услуг не найдена в базе данных.' ?></p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Цена</th>
                            <th>Локация</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($services as $service): ?>
                            <tr>
                                <td><?= $service['service_id'] ?></td>
                                <td><?= htmlspecialchars($service['service_name']) ?></td>
                                <td><?= htmlspecialchars(mb_substr($service['description'] ?? '', 0, 50)) ?><?= mb_strlen($service['description'] ?? '') > 50 ? '...' : '' ?></td>
                                <td><?= formatPrice($service['base_price']) ?></td>
                                <td><?= htmlspecialchars($service['location_name'] ?? 'Все') ?></td>
                                <td><span class="status-badge <?= $service['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $service['is_active'] ? 'Активна' : 'Неактивна' ?></span></td>
                                <td>
                                    <a href="services.php?edit=<?= $service['service_id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                    <?php if ($service['is_active']): ?>
                                        <a href="services.php?delete=<?= $service['service_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Деактивировать услугу?')">Деактивировать</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
