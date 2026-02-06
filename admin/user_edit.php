<?php
/**
 * Сценарий 10: Администрирование учетных записей.
 * Карточка пользователя: редактирование данных; проверка роли (только из списка) и уникальности логина/телефона.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header("Location: users.php");
    exit;
}

$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM user_ u JOIN role_ r ON u.role_id = r.role_id WHERE u.id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

$roles = $pdo->query("SELECT * FROM role_ ORDER BY role_id")->fetchAll();
$validRoleIds = array_column($roles, 'role_id');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $roleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    $password = trim($_POST['password'] ?? '');
    
    // Альтернативный поток: недопустимая роль (в т.ч. ручной ввод несуществующего значения)
    if (!in_array($roleId, $validRoleIds, true)) {
        $error = 'Выбрана недопустимая роль. Выберите значение из списка.';
    } elseif (strlen($login) > 30) {
        $error = 'Логин не должен превышать 30 символов.';
    } elseif (strlen($phone) != 11) {
        $error = 'Номер телефона должен содержать 11 символов.';
    } else {
        try {
            // Проверка уникальности логина (кроме текущего пользователя)
            $stmt = $pdo->prepare("SELECT id_user FROM user_ WHERE login = ? AND id_user != ?");
            $stmt->execute([$login, $userId]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином уже зарегистрирован.';
            } else {
                $stmt = $pdo->prepare("SELECT id_user FROM user_ WHERE phone_number = ? AND id_user != ?");
                $stmt->execute([$phone, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Пользователь с таким номером телефона уже зарегистрирован.';
                } else {
                    if ($password !== '') {
                        $passwordHash = sha1($password);
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ?, password = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $passwordHash, $userId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $userId]);
                    }
                    $success = 'Учетная запись обновлена. Изменения сохранены.';
                    $user['login'] = $login;
                    $user['phone_number'] = $phone;
                    $user['role_id'] = $roleId;
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка обновления: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Карточка пользователя #' . (int)$userId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Карточка пользователя #<?= $userId ?></h2>
        <p class="form-hint">Редактирование учетной записи. Роль выбирается только из выпадающего списка.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" name="login" id="login" maxlength="30" value="<?= htmlspecialchars($user['login']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone_number">Телефон:</label>
                <input type="text" name="phone_number" id="phone_number" maxlength="11" minlength="11" value="<?= htmlspecialchars($user['phone_number']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role_id">Роль (выберите из списка):</label>
                <select name="role_id" id="role_id" required>
                    <?php foreach($roles as $role): ?>
                        <option value="<?= (int)$role['role_id'] ?>" <?= (int)$user['role_id'] === (int)$role['role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Новый пароль (оставьте пустым, чтобы не менять):</label>
                <input type="password" name="password" id="password" placeholder="Введите новый пароль">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="users.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
