<?php
/**
 * Управление точками обслуживания (адресами копицентров)
 * Объединенный функционал: адреса + точки обслуживания
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$success = '';
$error = '';
$tableExists = false;

// Проверяем наличие таблицы
try {
    $pdo->query("SELECT 1 FROM locations LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        $error = 'Таблица locations не найдена в базе данных. Выполните SQL-скрипт: database/additional_tables.sql';
    }
}

// Создание/редактирование точки обслуживания
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $tableExists) {
    $locationId = $_POST['location_id'] ?? null;
    $locationName = trim($_POST['location_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone'] ?? '');
    $workingHours = trim($_POST['working_hours'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Валидация
    if (empty($locationName)) {
        $error = 'Название точки обслуживания обязательно';
    } elseif (strlen($locationName) > 100) {
        $error = 'Название не должно превышать 100 символов';
    } elseif (empty($address)) {
        $error = 'Адрес обязателен';
    } elseif (strlen($address) > 255) {
        $error = 'Адрес не должен превышать 255 символов';
    } elseif (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
        $error = 'Неверный формат телефона';
    } else {
        try {
            if ($locationId) {
                // Обновление
                $stmt = $pdo->prepare("UPDATE locations SET location_name = ?, address = ?, phone = ?, working_hours = ?, is_active = ? WHERE location_id = ?");
                $stmt->execute([$locationName, $address, $phone, $workingHours, $isActive, $locationId]);
                $success = 'Точка обслуживания обновлена';
            } else {
                // Создание
                $stmt = $pdo->prepare("INSERT INTO locations (location_name, address, phone, working_hours, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$locationName, $address, $phone, $workingHours, $isActive]);
                $success = 'Точка обслуживания создана';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// Удаление (деактивация) точки обслуживания
if (isset($_GET['delete']) && $tableExists) {
    try {
        $locationId = intval($_GET['delete']);
        $stmt = $pdo->prepare("UPDATE locations SET is_active = 0 WHERE location_id = ?");
        $stmt->execute([$locationId]);
        $success = 'Точка обслуживания деактивирована';
    } catch (PDOException $e) {
        $error = 'Ошибка деактивации: ' . $e->getMessage();
    }
}

// Активация точки обслуживания
if (isset($_GET['activate']) && $tableExists) {
    try {
        $locationId = intval($_GET['activate']);
        $stmt = $pdo->prepare("UPDATE locations SET is_active = 1 WHERE location_id = ?");
        $stmt->execute([$locationId]);
        $success = 'Точка обслуживания активирована';
    } catch (PDOException $e) {
        $error = 'Ошибка активации: ' . $e->getMessage();
    }
}

// Получаем все точки обслуживания
$locations = [];
$activeCount = 0;
$inactiveCount = 0;
if ($tableExists) {
    try {
        $locations = $pdo->query("SELECT * FROM locations ORDER BY is_active DESC, location_name ASC")->fetchAll();
        foreach ($locations as $loc) {
            if ($loc['is_active']) {
                $activeCount++;
            } else {
                $inactiveCount++;
            }
        }
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки точек обслуживания: ' . $e->getMessage();
    }
}

// Редактирование точки обслуживания
$editLocation = null;
if (isset($_GET['edit']) && $tableExists) {
    try {
        $locationId = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM locations WHERE location_id = ?");
        $stmt->execute([$locationId]);
        $editLocation = $stmt->fetch();
        if (!$editLocation) {
            $error = 'Точка обслуживания не найдена';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка загрузки: ' . $e->getMessage();
    }
}

$pageTitle = 'Точки обслуживания (Адреса) — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                    Точки обслуживания
                </h2>
                <p class="text-muted mb-0">
                    Управление адресами копицентров и точками обслуживания клиентов
                </p>
            </div>
            <?php if ($tableExists && !$editLocation): ?>
            <div>
                <span class="badge bg-success me-2">
                    <i class="bi bi-check-circle me-1"></i>
                    Активных: <?= $activeCount ?>
                </span>
                <span class="badge bg-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    Неактивных: <?= $inactiveCount ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Успех!</strong> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Ошибка!</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                
                <?php if (strpos($error, 'additional_tables.sql') !== false): ?>
                <hr>
                <h6 class="alert-heading mt-3">Инструкция по устранению:</h6>
                <ol class="mb-0 small">
                    <li>Откройте phpMyAdmin</li>
                    <li>Выберите базу данных <code>copypasteDB</code></li>
                    <li>Перейдите в раздел "SQL"</li>
                    <li>Откройте файл <code>database/additional_tables.sql</code> и скопируйте его содержимое</li>
                    <li>Вставьте скрипт и выполните</li>
                    <li>Обновите эту страницу</li>
                </ol>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($tableExists): ?>
        <section class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-<?= $editLocation ? 'pencil' : 'plus-circle' ?> me-2"></i>
                    <?= $editLocation ? 'Редактирование точки обслуживания' : 'Добавить новую точку обслуживания' ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?php if ($editLocation): ?>
                        <input type="hidden" name="location_id" value="<?= $editLocation['location_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <label for="location_name" class="form-label">
                            <i class="bi bi-building me-1"></i>
                            Название точки обслуживания
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="location_name" 
                               id="location_name" 
                               maxlength="100"
                               value="<?= htmlspecialchars($editLocation['location_name'] ?? '') ?>" 
                               placeholder="Например: Копицентр на Ленина"
                               required>
                        <small class="form-text text-muted">Максимум 100 символов</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="address" class="form-label">
                            <i class="bi bi-geo-alt me-1"></i>
                            Адрес
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="address" 
                               id="address" 
                               maxlength="255"
                               value="<?= htmlspecialchars($editLocation['address'] ?? '') ?>" 
                               placeholder="Например: ул. Ленина, д. 1"
                               required>
                        <small class="form-text text-muted">Максимум 255 символов</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="phone" class="form-label">
                            <i class="bi bi-telephone me-1"></i>
                            Телефон
                        </label>
                        <input type="tel" 
                               class="form-control" 
                               name="phone" 
                               id="phone" 
                               maxlength="20"
                               value="<?= htmlspecialchars($editLocation['phone'] ?? '') ?>" 
                               placeholder="+7 (999) 123-45-67">
                        <small class="form-text text-muted">Необязательно</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="working_hours" class="form-label">
                            <i class="bi bi-clock me-1"></i>
                            Часы работы
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="working_hours" 
                               id="working_hours" 
                               maxlength="100"
                               value="<?= htmlspecialchars($editLocation['working_hours'] ?? '') ?>" 
                               placeholder="Пн-Пт: 9:00-18:00">
                        <small class="form-text text-muted">Необязательно</small>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active" 
                                   id="is_active"
                                   <?= ($editLocation['is_active'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="bi bi-check-circle me-1"></i>
                                Точка активна (доступна для заказов)
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $editLocation ? 'check' : 'plus' ?>-circle me-1"></i>
                            <?= $editLocation ? 'Сохранить изменения' : 'Добавить точку' ?>
                        </button>
                        <?php if ($editLocation): ?>
                            <a href="locations.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>
                                Отмена
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
        
        <section class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Список точек обслуживания
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($locations)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <p class="text-muted mt-3">Точки обслуживания не найдены</p>
                        <p class="small text-muted">Добавьте первую точку обслуживания с помощью формы выше</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Название</th>
                                    <th>Адрес</th>
                                    <th style="width: 150px;">Телефон</th>
                                    <th style="width: 150px;">Часы работы</th>
                                    <th style="width: 100px;" class="text-center">Статус</th>
                                    <th style="width: 280px;" class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($locations as $location): ?>
                                    <tr class="<?= !$location['is_active'] ? 'table-secondary' : '' ?>">
                                        <td><?= $location['location_id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($location['location_name']) ?></strong>
                                        </td>
                                        <td>
                                            <i class="bi bi-geo-alt text-muted me-1"></i>
                                            <?= htmlspecialchars($location['address']) ?>
                                        </td>
                                        <td>
                                            <?php if ($location['phone']): ?>
                                                <i class="bi bi-telephone text-muted me-1"></i>
                                                <?= htmlspecialchars($location['phone']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($location['working_hours']): ?>
                                                <i class="bi bi-clock text-muted me-1"></i>
                                                <?= htmlspecialchars($location['working_hours']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($location['is_active']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Активна
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-x-circle me-1"></i>
                                                    Неактивна
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="location_pricelist.php?location_id=<?= $location['location_id'] ?>" 
                                                   class="btn btn-primary"
                                                   title="Прайс-лист">
                                                    <i class="bi bi-card-list"></i>
                                                    Прайс
                                                </a>
                                                <a href="locations.php?edit=<?= $location['location_id'] ?>" 
                                                   class="btn btn-secondary"
                                                   title="Редактировать">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($location['is_active']): ?>
                                                    <a href="locations.php?delete=<?= $location['location_id'] ?>" 
                                                       class="btn btn-warning"
                                                       onclick="return confirm('Деактивировать точку обслуживания?')"
                                                       title="Деактивировать">
                                                        <i class="bi bi-toggle-off"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="locations.php?activate=<?= $location['location_id'] ?>" 
                                                       class="btn btn-success"
                                                       title="Активировать">
                                                        <i class="bi bi-toggle-on"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Назад в панель администратора
            </a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
