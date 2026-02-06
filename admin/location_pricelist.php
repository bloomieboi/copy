<?php
/**
 * Сценарий 9: Редактирование прайс-листа и ассортимента услуг.
 * Текущий прайс-лист выбранной точки с возможностью редактирования цен; проверка «цена — положительное число».
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$locationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
if ($locationId <= 0) {
    header("Location: locations.php");
    exit;
}

$location = null;
$services = [];
$success = '';
$error = '';
$invalidIds = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE location_id = ?");
    $stmt->execute([$locationId]);
    $location = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Ошибка загрузки данных.';
}

if (!$location) {
    header("Location: locations.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE location_id = ? ORDER BY service_name");
    $stmt->execute([$locationId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
}

$editMode = isset($_GET['edit']) || isset($_POST['save_pricelist']);

// Сохранение изменений прайс-листа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pricelist'])) {
    $prices = $_POST['price'] ?? [];
    $invalidIds = [];
    foreach ($prices as $serviceId => $val) {
        $price = is_numeric($val) ? floatval($val) : null;
        if ($price === null || $price <= 0) {
            $invalidIds[] = (int)$serviceId;
        }
    }
    if (!empty($invalidIds)) {
        $error = 'Цена должна быть положительным числом.';
        $editMode = true;
    } else {
        try {
            foreach ($prices as $serviceId => $val) {
                $sid = (int)$serviceId;
                if ($sid <= 0) continue;
                $price = floatval($val);
                if ($price <= 0) continue;
                $stmt = $pdo->prepare("UPDATE services SET base_price = ? WHERE service_id = ? AND location_id = ?");
                $stmt->execute([$price, $sid, $locationId]);
            }
            $success = 'Прайс-лист обновлён. Изменения сохранены.';
            $editMode = false;
            $stmt = $pdo->prepare("SELECT * FROM services WHERE location_id = ? ORDER BY service_name");
            $stmt->execute([$locationId]);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = 'Ошибка сохранения: ' . $e->getMessage();
            $editMode = true;
        }
    }
}

if (isset($_GET['saved'])) {
    $success = 'Прайс-лист обновлён. Изменения сохранены.';
}

$pageTitle = 'Прайс-лист: ' . htmlspecialchars($location['location_name']) . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Прайс-лист: <?= htmlspecialchars($location['location_name']) ?></h2>
        <p class="form-hint">Текущий прайс-лист и ассортимент услуг выбранной точки. Редактирование цен и добавление/удаление услуг — через форму ниже.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$editMode): ?>
            <div class="actions" style="margin-bottom:1rem;">
                <a href="location_pricelist.php?location_id=<?= $locationId ?>&edit=1" class="btn btn-primary">Редактировать прайс-лист / услугу</a>
            </div>
            <section class="pricelist-view">
                <h3>Текущий прайс-лист</h3>
                <?php if (empty($services)): ?>
                    <p class="empty-state">У этой точки пока нет услуг. Добавьте услуги в разделе <a href="services.php">Услуги и прайс-лист</a>, указав данную локацию.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Услуга</th>
                                <th>Описание</th>
                                <th>Цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['service_name']) ?></td>
                                    <td><?= htmlspecialchars(mb_substr($s['description'] ?? '', 0, 60)) ?><?= mb_strlen($s['description'] ?? '') > 60 ? '...' : '' ?></td>
                                    <td><?= formatPrice($s['base_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="pricelist-edit">
                <h3>Редактирование прайс-листа</h3>
                <p class="form-hint">Измените цены услуг. Цена должна быть положительным числом. Добавление и удаление услуг — в разделе <a href="services.php">Услуги и прайс-лист</a>.</p>
                <?php if (empty($services)): ?>
                    <p class="empty-state">Нет услуг для редактирования. <a href="services.php">Добавьте услуги</a> с привязкой к этой точке.</p>
                <?php else: ?>
                    <form method="POST" action="location_pricelist.php?location_id=<?= $locationId ?>">
                        <input type="hidden" name="save_pricelist" value="1">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Услуга</th>
                                    <th>Цена (руб.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $s):
                                    $sid = $s['service_id'];
                                    $isInvalid = in_array($sid, $invalidIds, true);
                                ?>
                                    <tr class="<?= $isInvalid ? 'field-error' : '' ?>">
                                        <td><?= htmlspecialchars($s['service_name']) ?></td>
                                        <td>
                                            <input type="number" name="price[<?= $sid ?>]" value="<?= htmlspecialchars($s['base_price']) ?>" step="0.01" min="0.01" required class="<?= $isInvalid ? 'input-error' : '' ?>">
                                            <?php if ($isInvalid): ?>
                                                <span class="field-error-msg">Цена должна быть положительным числом</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                            <a href="location_pricelist.php?location_id=<?= $locationId ?>" class="btn btn-secondary">Отмена</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="actions">
            <a href="locations.php" class="btn btn-secondary">Назад к точкам обслуживания</a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
