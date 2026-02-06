<?php
require_once __DIR__ . '/../function/connect.php';
session_start();

// Предусловие: пользователь не должен быть авторизован в системе
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone_number'] ?? '');
    
    // Валидация данных
    if (empty($login)) {
        $error = "Логин не может быть пустым";
    } elseif (strlen($login) > 30) {
        $error = "Логин не должен превышать 30 символов";
    } elseif (empty($password)) {
        $error = "Пароль не может быть пустым";
    } elseif (strlen($password) < 3) {
        $error = "Пароль должен содержать минимум 3 символа";
    } elseif (empty($phone)) {
        $error = "Номер телефона не может быть пустым";
    } elseif (strlen($phone) != 11) {
        $error = "Номер телефона должен содержать 11 символов";
    } else {
        // Проверка уникальности логина
        $stmt = $pdo->prepare("SELECT id_user FROM user_ WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            $error = "Пользователь с таким логином уже существует";
        } else {
            // Проверка уникальности телефона (опционально, но лучше проверить)
            $stmt = $pdo->prepare("SELECT id_user FROM user_ WHERE phone_number = ?");
            $stmt->execute([$phone]);
            
            if ($stmt->fetch()) {
                $error = "Пользователь с таким номером телефона уже существует";
            } else {
                // Создаем новую учетную запись
                // Используем SHA1 для пароля (VARCHAR(40) в БД)
                $passwordHash = sha1($password);
                $stmt = $pdo->prepare("INSERT INTO user_ (login, password, phone_number, role_id) VALUES (?, ?, ?, 1)");
                $stmt->execute([$login, $passwordHash, $phone]);
                
                // Уведомление об успешной регистрации через редирект
                header("Location: ../login/index.php?registered=1");
                exit;
            }
        }
    }
}

$pageTitle = 'Регистрация — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Регистрация</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="register-form">
            <div class="form-group">
                <label for="login">Логин (макс. 30 символов):</label>
                <input type="text" name="login" id="login" maxlength="30" required placeholder="Логин">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" id="password" required placeholder="Пароль">
            </div>
            
            <div class="form-group">
                <label for="phone_number">Номер телефона (11 символов):</label>
                <input type="text" name="phone_number" id="phone_number" maxlength="11" minlength="11" required placeholder="Номер телефона">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                <a href="../login/index.php" class="btn btn-secondary">Уже есть аккаунт? Войти</a>
            </div>
        </form>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
