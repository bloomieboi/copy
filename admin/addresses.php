<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$success = '';
$error = '';

// Создание/редактирование адреса
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $addressId = $_POST['address_id'] ?? null;
    $addressName = trim($_POST['address_name']);
    
    if ($addressName) {
        if ($addressId) {
            $stmt = $pdo->prepare("UPDATE address SET address_name = ? WHERE address_id = ?");
            $stmt->execute([$addressName, $addressId]);
            $success = 'Адрес обновлен';
        } else {
            $stmt = $pdo->prepare("INSERT INTO address (address_name) VALUES (?)");
            $stmt->execute([$addressName]);
            $success = 'Адрес создан';
        }
    } else {
        $error = 'Заполните название адреса';
    }
}

// Удаление адреса
if (isset($_GET['delete'])) {
    $addressId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM address WHERE address_id = ?");
    $stmt->execute([$addressId]);
    $success = 'Адрес удален';
}

// Получаем адреса
$addresses = $pdo->query("SELECT * FROM address ORDER BY address_name")->fetchAll();

// Редактирование адреса
$editAddress = null;
if (isset($_GET['edit'])) {
    $addressId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM address WHERE address_id = ?");
    $stmt->execute([$addressId]);
    $editAddress = $stmt->fetch();
}

$pageTitle = 'Управление адресами — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Управление адресами организации</h2>
        <p class="form-hint">Адреса точек обслуживания копицентра, где клиенты могут получить услуги.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <section class="address-form-section">
            <h3><?= $editAddress ? 'Редактирование адреса' : 'Создание нового адреса' ?></h3>
            <form method="POST" class="address-form">
                <?php if ($editAddress): ?>
                    <input type="hidden" name="address_id" value="<?= $editAddress['address_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="address_name">Адрес организации:</label>
                    <input type="text" name="address_name" id="address_name" value="<?= htmlspecialchars($editAddress['address_name'] ?? '') ?>" required placeholder="Например: г. Москва, ул. Ленина, 10">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editAddress ? 'Сохранить' : 'Создать' ?></button>
                    <?php if ($editAddress): ?>
                        <a href="addresses.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <section class="addresses-list">
            <h3>Список адресов</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Адрес</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($addresses as $addr): ?>
                        <tr>
                            <td><?= $addr['address_id'] ?></td>
                            <td><?= htmlspecialchars($addr['address_name']) ?></td>
                            <td>
                                <a href="addresses.php?edit=<?= $addr['address_id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                                <a href="addresses.php?delete=<?= $addr['address_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить адрес?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($addresses)): ?>
                <p class="empty-state">Адреса не найдены</p>
            <?php endif; ?>
        </section>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Назад</a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
