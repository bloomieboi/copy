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
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        $error = "Логин может содержать только английские буквы, цифры и подчеркивание";
    } elseif (empty($password)) {
        $error = "Пароль не может быть пустым";
    } elseif (strlen($password) < 3) {
        $error = "Пароль должен содержать минимум 3 символа";
    } elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]+$/', $password)) {
        $error = "Пароль может содержать только английские буквы, цифры и спецсимволы";
    } elseif (empty($phone)) {
        $error = "Номер телефона не может быть пустым";
    } elseif (!preg_match('/^79\d{9}$/', $phone)) {
        $error = "Номер телефона должен быть в формате 79xxxxxxxxx (11 цифр, начинается с 79)";
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
                // Используем password_hash для безопасного хеширования
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
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
        
        <form method="POST" class="register-form" id="registerForm">
            <div class="form-group">
                <label for="login">
                    Логин (английские буквы, цифры, _):
                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Максимум 30 символов"></i>
                </label>
                <input type="text" 
                       name="login" 
                       id="login" 
                       maxlength="30" 
                       pattern="[a-zA-Z0-9_]+" 
                       required 
                       placeholder="example_user123"
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
                <small class="form-text text-muted">Только английские буквы, цифры и подчеркивание</small>
            </div>
            
            <div class="form-group">
                <label for="password">
                    Пароль (английские буквы, цифры, спецсимволы):
                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Минимум 3 символа"></i>
                </label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       minlength="3"
                       pattern="[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:'\"\\|,.<>\/?]+"
                       required 
                       placeholder="Pass123!">
                <small class="form-text text-muted">Минимум 3 символа, только латиница</small>
            </div>
            
            <div class="form-group">
                <label for="phone_number">
                    Номер телефона (формат: 79xxxxxxxxx):
                    <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="11 цифр, начинается с 79"></i>
                </label>
                <input type="tel" 
                       name="phone_number" 
                       id="phone_number" 
                       maxlength="11" 
                       pattern="79[0-9]{9}"
                       required 
                       placeholder="79123456789"
                       value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                <small class="form-text text-muted">Пример: 79123456789</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>
                    Зарегистрироваться
                </button>
                <a href="../login/index.php" class="btn btn-secondary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Уже есть аккаунт? Войти
                </a>
            </div>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            const form = document.getElementById('registerForm');
            const loginInput = document.getElementById('login');
            const passwordInput = document.getElementById('password');
            const phoneInput = document.getElementById('phone_number');
            
            // Валидация логина в реальном времени
            loginInput.addEventListener('input', function() {
                const value = this.value;
                if (!/^[a-zA-Z0-9_]*$/.test(value)) {
                    this.setCustomValidity('Логин может содержать только английские буквы, цифры и _');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Валидация пароля
            passwordInput.addEventListener('input', function() {
                const value = this.value;
                if (value.length > 0 && !/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:'"\\|,.<>\/?]*$/.test(value)) {
                    this.setCustomValidity('Пароль может содержать только латинские буквы, цифры и спецсимволы');
                } else if (value.length > 0 && value.length < 3) {
                    this.setCustomValidity('Пароль должен содержать минимум 3 символа');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Валидация телефона + автоформатирование
            phoneInput.addEventListener('input', function() {
                // Удаляем все нецифровые символы
                let value = this.value.replace(/\D/g, '');
                
                // Автозаполнение 79 в начале
                if (value.length === 1 && value !== '7') {
                    value = '7' + value;
                }
                if (value.length >= 1 && value[0] !== '7') {
                    value = '7' + value;
                }
                if (value.length >= 2 && value.substring(0, 2) !== '79') {
                    if (value[1] !== '9') {
                        value = '79' + value.substring(2);
                    }
                }
                
                this.value = value.substring(0, 11);
                
                // Проверка формата
                if (this.value.length === 11 && !/^79[0-9]{9}$/.test(this.value)) {
                    this.setCustomValidity('Номер должен начинаться с 79');
                } else if (this.value.length > 0 && this.value.length < 11) {
                    this.setCustomValidity('Номер должен содержать 11 цифр');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
        </script>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
