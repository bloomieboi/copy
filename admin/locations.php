<?php
/**
 * Сценарий 9: Редактирование прайс-листа и ассортимента услуг.
 * Раздел «Точки обслуживания»: список всех точек; для каждой — переход к прайс-листу.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$success = '';
$error = '';

// Создание/редактирование локации
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $locationId = $_POST['location_id'] ?? null;
    $locationName = trim($_POST['location_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($locationName && $address) {
        if ($locationId) {
            $stmt = $pdo->prepare("UPDATE locations SET location_name = ?, address = ?, phone = ?, is_active = ? WHERE location_id = ?");
            $stmt->execute([$locationName, $address, $phone, $isActive, $locationId]);
            $success = 'Локация обновлена';
        } else {
            $stmt = $pdo->prepare("INSERT INTO locations (location_name, address, phone, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$locationName, $address, $phone, $isActive]);
            $success = 'Локация создана';
        }
    } else {
        $error = 'Заполните все обязательные поля';
    }
}

// Удаление локации
if (isset($_GET['delete'])) {
    $locationId = intval($_GET['delete']);
    $stmt = $pdo->prepare("UPDATE locations SET is_active = FALSE WHERE location_id = ?");
    $stmt->execute([$locationId]);
    $success = 'Локация деактивирована';
}

// Получаем локации
$locations = $pdo->query("SELECT * FROM locations ORDER BY location_name")->fetchAll();

// Редактирование локации
$editLocation = null;
if (isset($_GET['edit'])) {
    $locationId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE location_id = ?");
    $stmt->execute([$locationId]);
    $editLocation = $stmt->fetch();
}

$pageTitle = 'Точки обслуживания — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Точки обслуживания</h2>
        <p class="form-hint">Список всех точек обслуживания (копицентров). Выберите точку для редактирования прайс-листа или данных.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <section class="location-form-section">
            <h3><?= $editLocation ? 'Редактирование локации' : 'Создание новой локации' ?></h3>
            <form method="POST" class="location-form">
                <?php if ($editLocation): ?>
                    <input type="hidden" name="location_id" value="<?= $editLocation['location_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="location_name">Название локации:</label>
                    <input type="text" name="location_name" id="location_name" value="<?= htmlspecialchars($editLocation['location_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Адрес:</label>
                    <input type="text" name="address" id="address" value="<?= htmlspecialchars($editLocation['address'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон:</label>
                    <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($editLocation['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?= ($editLocation['is_active'] ?? true) ? 'checked' : '' ?>>
                        Активна
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editLocation ? 'Сохранить' : 'Создать' ?></button>
                    <?php if ($editLocation): ?>
                        <a href="locations.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <section class="locations-list">
            <h3>Список точек обслуживания</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Адрес</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($locations as $location): ?>
                        <tr>
                            <td><?= $location['location_id'] ?></td>
                            <td><?= htmlspecialchars($location['location_name']) ?></td>
                            <td><?= htmlspecialchars($location['address']) ?></td>
                            <td><?= htmlspecialchars($location['phone'] ?? '-') ?></td>
                            <td><span class="status-badge <?= $location['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $location['is_active'] ? 'Активна' : 'Неактивна' ?></span></td>
                            <td>
                                <a href="location_pricelist.php?location_id=<?= $location['location_id'] ?>" class="btn btn-sm btn-primary">Прайс-лист</a>
                                <a href="locations.php?edit=<?= $location['location_id'] ?>" class="btn btn-sm btn-secondary">Редактировать</a>
                                <?php if ($location['is_active']): ?>
                                    <a href="locations.php?delete=<?= $location['location_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Деактивировать локацию?')">Деактивировать</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Назад</a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
