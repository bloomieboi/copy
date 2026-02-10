# Система валидации данных

## Обзор

Полная валидация пользовательских данных на клиентской (JavaScript) и серверной (PHP) стороне.

## Правила валидации

### 1. Логин
- ✅ **Формат:** английские буквы (a-z, A-Z), цифры (0-9), подчеркивание (_)
- ✅ **Длина:** максимум 30 символов
- ✅ **Уникальность:** проверка в базе данных
- ❌ **Запрещено:** кириллица, спецсимволы (кроме _), пробелы

**Примеры:**
```
✅ john_doe
✅ user123
✅ Admin_2024
❌ иван_петров (кириллица)
❌ user@name (спецсимвол @)
❌ user name (пробел)
```

### 2. Пароль
- ✅ **Формат:** английские буквы, цифры, спецсимволы
- ✅ **Длина:** минимум 3 символа
- ✅ **Разрешенные спецсимволы:** `!@#$%^&*()_+-=[]{};\:'"|,.<>/?`
- ❌ **Запрещено:** кириллица, пробелы

**Примеры:**
```
✅ Pass123
✅ Qwerty!@#
✅ admin_2024
❌ Пароль123 (кириллица)
❌ pass word (пробел)
```

### 3. Номер телефона
- ✅ **Формат:** `79xxxxxxxxx` (11 цифр)
- ✅ **Начало:** обязательно с `79`
- ✅ **Уникальность:** проверка в базе данных
- ❌ **Запрещено:** буквы, спецсимволы, другие форматы

**Примеры:**
```
✅ 79123456789
✅ 79991234567
❌ 89123456789 (начинается с 8)
❌ 71234567890 (второй символ не 9)
❌ +79123456789 (знак +)
❌ 7-912-345-67-89 (дефисы)
```

### 4. Количество услуг
- ✅ **Диапазон:** от 1 до 2,147,483,647 (INT MAX в MySQL)
- ✅ **Тип:** только целые положительные числа
- ❌ **Запрещено:** 0, отрицательные числа, дроби

**Примеры:**
```
✅ 1
✅ 100
✅ 2147483647
❌ 0
❌ -5
❌ 2147483648 (превышает лимит)
```

## Клиентская валидация (JavaScript)

### Регистрация (`register/index.php`)

#### HTML5 атрибуты
```html
<!-- Логин -->
<input type="text" 
       pattern="[a-zA-Z0-9_]+" 
       maxlength="30"
       required>

<!-- Пароль -->
<input type="password" 
       pattern="[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:'\"\\|,.<>\/?]+"
       minlength="3"
       required>

<!-- Телефон -->
<input type="tel" 
       pattern="79[0-9]{9}"
       maxlength="11"
       required>
```

#### JavaScript валидация в реальном времени

**Логин:**
```javascript
loginInput.addEventListener('input', function() {
    const value = this.value;
    if (!/^[a-zA-Z0-9_]*$/.test(value)) {
        this.setCustomValidity('Логин может содержать только английские буквы, цифры и _');
    } else {
        this.setCustomValidity('');
    }
});
```

**Пароль:**
```javascript
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
```

**Телефон (с автоформатированием):**
```javascript
phoneInput.addEventListener('input', function() {
    // Удаляем нецифровые символы
    let value = this.value.replace(/\D/g, '');
    
    // Автозаполнение 79
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
```

### Оформление заказа (`orders/create.php`)

**Количество:**
```html
<input type="number" 
       min="1" 
       max="2147483647"
       step="1"
       required>
```

## Серверная валидация (PHP)

### Использование функций валидации

```php
require_once __DIR__ . '/../function/validation.php';

// Валидация логина
$loginValidation = validateLogin($login);
if (!$loginValidation['valid']) {
    $error = $loginValidation['error'];
}

// Валидация пароля
$passwordValidation = validatePassword($password, 3);
if (!$passwordValidation['valid']) {
    $error = $passwordValidation['error'];
}

// Валидация телефона
$phoneValidation = validatePhone($phone);
if (!$phoneValidation['valid']) {
    $error = $phoneValidation['error'];
}

// Форматирование телефона
$formattedPhone = formatPhoneNumber($phone);

// Валидация количества
$quantityValidation = validateQuantity($quantity, 1, 2147483647);
if (!$quantityValidation['valid']) {
    $error = $quantityValidation['error'];
}
```

### Регистрация (`register/index.php`)

```php
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
}
```

### Редактирование пользователя (`admin/user_edit.php`)

```php
if (!in_array($roleId, $validRoleIds, true)) {
    $error = 'Выбрана недопустимая роль.';
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
}
```

### Оформление заказа (`orders/create.php`)

```php
// Максимальное значение INT в MySQL
$maxInt = 2147483647;

if (!$error && (!$serviceList || $basePrice <= 0)) {
    $error = 'Заполните все поля корректно.';
} elseif (!$error && ($quantity <= 0 || $quantity > $maxInt)) {
    $error = 'Количество должно быть от 1 до ' . number_format($maxInt, 0, '', ' ') . '.';
}
```

## Regex паттерны

### Логин
```regex
^[a-zA-Z0-9_]+$
```
- `^` - начало строки
- `[a-zA-Z0-9_]` - один символ: английская буква, цифра или _
- `+` - один или более раз
- `$` - конец строки

### Пароль
```regex
^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]+$
```
- Разрешены: буквы, цифры, все перечисленные спецсимволы

### Телефон
```regex
^79\d{9}$
```
- `^79` - обязательно начинается с 79
- `\d{9}` - ровно 9 цифр после 79
- `$` - конец строки (итого 11 цифр)

## API функций (`function/validation.php`)

### validateLogin($login)
Проверяет логин на соответствие требованиям.

**Параметры:**
- `$login` (string) - логин для проверки

**Возвращает:**
```php
['valid' => bool, 'error' => string|null]
```

**Пример:**
```php
$result = validateLogin('john_doe');
// ['valid' => true, 'error' => null]

$result = validateLogin('иван');
// ['valid' => false, 'error' => 'Логин может содержать только английские буквы...']
```

### validatePassword($password, $minLength = 3)
Проверяет пароль на соответствие требованиям.

**Параметры:**
- `$password` (string) - пароль для проверки
- `$minLength` (int) - минимальная длина (по умолчанию 3)

**Возвращает:**
```php
['valid' => bool, 'error' => string|null]
```

### validatePhone($phone)
Проверяет номер телефона на соответствие формату 79xxxxxxxxx.

**Параметры:**
- `$phone` (string) - номер телефона

**Возвращает:**
```php
['valid' => bool, 'error' => string|null]
```

### validateQuantity($quantity, $min = 1, $max = 2147483647)
Проверяет количество услуг.

**Параметры:**
- `$quantity` (int) - количество
- `$min` (int) - минимальное значение
- `$max` (int) - максимальное значение (INT MAX в MySQL)

**Возвращает:**
```php
['valid' => bool, 'error' => string|null]
```

### formatPhoneNumber($phone)
Форматирует номер телефона в стандартный вид.

**Параметры:**
- `$phone` (string) - номер телефона

**Возвращает:**
- `string` - отформатированный номер (только цифры, 11 символов, начинается с 79)

**Пример:**
```php
formatPhoneNumber('+7 (912) 345-67-89');  // "79123456789"
formatPhoneNumber('8 912 345 67 89');      // "79123456789"
formatPhoneNumber('79123456789');          // "79123456789"
```

### isLoginUnique($pdo, $login, $excludeUserId = null)
Проверяет уникальность логина в БД.

**Параметры:**
- `$pdo` (PDO) - подключение к БД
- `$login` (string) - логин для проверки
- `$excludeUserId` (int|null) - ID пользователя для исключения (при редактировании)

**Возвращает:**
- `bool` - true если логин уникален

### isPhoneUnique($pdo, $phone, $excludeUserId = null)
Проверяет уникальность телефона в БД.

### validateUserData($data, $pdo, $excludeUserId = null)
Комплексная валидация всех данных пользователя.

**Параметры:**
- `$data` (array) - массив данных `['login' => ..., 'password' => ..., 'phone' => ...]`
- `$pdo` (PDO) - подключение к БД
- `$excludeUserId` (int|null) - ID пользователя для исключения

**Возвращает:**
- `array` - массив ошибок `['login' => 'error', 'password' => 'error', ...]`

## UI/UX улучшения

### Tooltips (подсказки)
```html
<i class="bi bi-info-circle" 
   data-bs-toggle="tooltip" 
   title="Максимум 30 символов"></i>
```

### Вспомогательный текст
```html
<small class="form-text text-muted">
    Только английские буквы, цифры и подчеркивание
</small>
```

### Примеры placeholder
```html
<input placeholder="example_user123">  <!-- Логин -->
<input placeholder="Pass123!">          <!-- Пароль -->
<input placeholder="79123456789">       <!-- Телефон -->
```

### Сохранение введенных данных при ошибке
```php
value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>"
```

## Сообщения об ошибках

### Стандартизированные сообщения

**Логин:**
- "Логин не может быть пустым"
- "Логин не должен превышать 30 символов"
- "Логин может содержать только английские буквы, цифры и подчеркивание"
- "Пользователь с таким логином уже существует"

**Пароль:**
- "Пароль не может быть пустым"
- "Пароль должен содержать минимум 3 символа"
- "Пароль может содержать только английские буквы, цифры и спецсимволы"
- "Пароли не совпадают"

**Телефон:**
- "Номер телефона не может быть пустым"
- "Номер телефона должен быть в формате 79xxxxxxxxx (11 цифр, начинается с 79)"
- "Пользователь с таким номером телефона уже существует"

**Количество:**
- "Количество должно быть от 1 до 2 147 483 647"
- "Количество превышает максимально допустимое значение"

## Тестовые сценарии

### Тест 1: Регистрация с валидными данными
```
Логин: john_doe
Пароль: Pass123!
Телефон: 79123456789
✅ Регистрация успешна
```

### Тест 2: Регистрация с кириллицей
```
Логин: иван_петров
Пароль: Пароль123
Телефон: 79123456789
❌ Ошибка: "Логин может содержать только английские буквы..."
❌ Ошибка: "Пароль может содержать только английские буквы..."
```

### Тест 3: Регистрация с неверным телефоном
```
Логин: user123
Пароль: Pass123
Телефон: 89123456789
❌ Ошибка: "Номер телефона должен быть в формате 79xxxxxxxxx..."
```

### Тест 4: Автоформатирование телефона
```
Ввод: +7 (912) 345-67-89
Результат: 79123456789
Ввод: 8 912 345 67 89
Результат: 79123456789
✅ Автоматическое форматирование работает
```

### Тест 5: Количество услуг
```
Количество: 1 ✅
Количество: 1000 ✅
Количество: 2147483647 ✅
Количество: 0 ❌
Количество: -5 ❌
Количество: 2147483648 ❌
```

## Файлы изменений

### Новые файлы:
- ✅ `function/validation.php` - функции валидации

### Обновленные файлы:
- ✅ `register/index.php` - валидация регистрации + JS
- ✅ `admin/user_edit.php` - валидация редактирования
- ✅ `orders/create.php` - валидация количества
- ✅ `database/VALIDATION.md` - эта документация

## Безопасность

### SQL Injection
✅ Использование prepared statements
```php
$stmt = $pdo->prepare("SELECT * FROM user_ WHERE login = ?");
$stmt->execute([$login]);
```

### XSS
✅ Экранирование вывода
```php
<?= htmlspecialchars($login) ?>
```

### CSRF
✅ Session-based authentication
```php
session_start();
if (!isset($_SESSION['user_id'])) { ... }
```

---

**Дата:** 2026-02-10  
**Версия:** 1.0  
**Статус:** ✅ Внедрено
