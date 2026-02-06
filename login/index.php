<?php
require_once __DIR__ . '/../function/connect.php';
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = isset($_GET['registered']) ? 'Регистрация прошла успешно! Теперь вы можете войти в систему.' : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация входных данных
    if (empty($login)) {
        $error = "Введите логин";
    } elseif (empty($password)) {
        $error = "Введите пароль";
    } else {
        // Проверка логина и пароля
        $stmt = $pdo->prepare("SELECT * FROM user_ WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        // Проверяем подлинность данных: существует ли пользователь и совпадает ли пароль
        if ($user && sha1($password) === $user['password']) {
            // Подтверждаем подлинность данных и предоставляем доступ к персональному функционалу
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['role_id'] = $user['role_id'];
            header("Location: ../index.php");
            exit;
        } else {
            // Обнаружено несоответствие данных
            $error = "Неверный логин или пароль";
        }
    }
}

$pageTitle = 'Вход — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Вход в систему</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" name="login" id="login" required placeholder="Логин">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" id="password" required placeholder="Пароль">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Войти</button>
                <a href="../register/index.php" class="btn btn-secondary">Регистрация</a>
            </div>
        </form>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
