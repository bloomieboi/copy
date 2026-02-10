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
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');
    
    // Альтернативный поток: недопустимая роль (в т.ч. ручной ввод несуществующего значения)
    if (!in_array($roleId, $validRoleIds, true)) {
        $error = 'Выбрана недопустимая роль. Выберите значение из списка.';
    } elseif (strlen($login) > 30) {
        $error = 'Логин не должен превышать 30 символов.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        $error = 'Логин может содержать только английские буквы, цифры и подчеркивание.';
    } elseif (!preg_match('/^79\d{9}$/', $phone)) {
        $error = 'Номер телефона должен быть в формате 79xxxxxxxxx.';
    } elseif ($password !== '' && strlen($password) < 3) {
        $error = 'Пароль должен содержать минимум 3 символа.';
    } elseif ($password !== '' && !preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]+$/', $password)) {
        $error = 'Пароль может содержать только английские буквы, цифры и спецсимволы.';
    } elseif ($password !== '' && $password !== $passwordConfirm) {
        $error = 'Пароли не совпадают.';
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
                        // Используем password_hash для безопасного хеширования
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ?, password = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $passwordHash, $userId]);
                        $success = 'Учетная запись обновлена. Пароль изменён.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $userId]);
                        $success = 'Учетная запись обновлена.';
                    }
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
        
        <div class="alert alert-info mb-3" role="alert">
            <i class="bi bi-shield-lock-fill me-2"></i>
            <strong>Безопасность:</strong> Пароли хранятся в зашифрованном виде. 
            Администратор <u>не может узнать</u> текущий пароль пользователя, можно только установить новый.
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
            </div>
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
                <input type="password" 
                       name="password" 
                       id="password" 
                       placeholder="Введите новый пароль"
                       minlength="3"
                       autocomplete="new-password">
                <small class="form-text text-muted">
                    <i class="bi bi-shield-lock me-1"></i>
                    Пароль будет зашифрован. Администратор не может просмотреть текущий пароль.
                </small>
            </div>
            
            <div class="form-group" x-data="{ showConfirm: false }">
                <label for="password_confirm">Подтверждение пароля:</label>
                <input type="password" 
                       name="password_confirm" 
                       id="password_confirm" 
                       placeholder="Повторите пароль"
                       autocomplete="new-password"
                       @input="showConfirm = $el.value !== ''">
                <small class="form-text text-muted" x-show="showConfirm">
                    <i class="bi bi-info-circle me-1"></i>
                    Пароли должны совпадать
                </small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check-circle me-1"></i>
                    Сохранить
                </button>
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    Отмена
                </a>
            </div>
        </form>
        
        <script>
        // Проверка совпадения паролей
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.edit-form');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirm');
            const submitBtn = document.getElementById('submitBtn');
            
            function validatePasswords() {
                if (passwordInput.value === '' && confirmInput.value === '') {
                    confirmInput.setCustomValidity('');
                    return true;
                }
                
                if (passwordInput.value !== confirmInput.value) {
                    confirmInput.setCustomValidity('Пароли не совпадают');
                    return false;
                } else {
                    confirmInput.setCustomValidity('');
                    return true;
                }
            }
            
            passwordInput.addEventListener('input', validatePasswords);
            confirmInput.addEventListener('input', validatePasswords);
            
            form.addEventListener('submit', function(e) {
                if (!validatePasswords()) {
                    e.preventDefault();
                    confirmInput.reportValidity();
                }
            });
        });
        </script>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
