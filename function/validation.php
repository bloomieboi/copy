<?php
/**
 * Функции валидации данных для системы КопиПейст
 */

/**
 * Валидация логина
 * @param string $login
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateLogin(string $login): array {
    $login = trim($login);
    
    if (empty($login)) {
        return ['valid' => false, 'error' => 'Логин не может быть пустым'];
    }
    
    if (strlen($login) > 30) {
        return ['valid' => false, 'error' => 'Логин не должен превышать 30 символов'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        return ['valid' => false, 'error' => 'Логин может содержать только английские буквы, цифры и подчеркивание'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Валидация пароля
 * @param string $password
 * @param int $minLength Минимальная длина (по умолчанию 3)
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validatePassword(string $password, int $minLength = 3): array {
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Пароль не может быть пустым'];
    }
    
    if (strlen($password) < $minLength) {
        return ['valid' => false, 'error' => "Пароль должен содержать минимум {$minLength} символа"];
    }
    
    if (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]+$/', $password)) {
        return ['valid' => false, 'error' => 'Пароль может содержать только английские буквы, цифры и спецсимволы'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Валидация номера телефона (формат 79xxxxxxxxx)
 * @param string $phone
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validatePhone(string $phone): array {
    $phone = trim($phone);
    
    if (empty($phone)) {
        return ['valid' => false, 'error' => 'Номер телефона не может быть пустым'];
    }
    
    // Удаляем все нецифровые символы
    $digitsOnly = preg_replace('/\D/', '', $phone);
    
    if (!preg_match('/^79\d{9}$/', $digitsOnly)) {
        return ['valid' => false, 'error' => 'Номер телефона должен быть в формате 79xxxxxxxxx (11 цифр, начинается с 79)'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Валидация количества для заказа
 * @param int $quantity
 * @param int $min Минимальное значение (по умолчанию 1)
 * @param int $max Максимальное значение (по умолчанию INT_MAX из MySQL)
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateQuantity(int $quantity, int $min = 1, int $max = 2147483647): array {
    if ($quantity < $min) {
        return ['valid' => false, 'error' => "Количество должно быть не меньше {$min}"];
    }
    
    if ($quantity > $max) {
        return ['valid' => false, 'error' => 'Количество превышает максимально допустимое значение (' . number_format($max, 0, '', ' ') . ')'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Форматирование номера телефона в стандартный формат
 * @param string $phone
 * @return string
 */
function formatPhoneNumber(string $phone): string {
    // Удаляем все нецифровые символы
    $digitsOnly = preg_replace('/\D/', '', $phone);
    
    // Автоматическое добавление 79 в начале, если нужно
    if (strlen($digitsOnly) === 10 && substr($digitsOnly, 0, 1) === '9') {
        $digitsOnly = '7' . $digitsOnly;
    }
    
    if (strlen($digitsOnly) === 11 && substr($digitsOnly, 0, 1) === '8') {
        $digitsOnly = '7' . substr($digitsOnly, 1);
    }
    
    return $digitsOnly;
}

/**
 * Проверка уникальности логина в базе данных
 * @param PDO $pdo
 * @param string $login
 * @param int|null $excludeUserId ID пользователя для исключения (при редактировании)
 * @return bool true если логин уникален
 */
function isLoginUnique(PDO $pdo, string $login, ?int $excludeUserId = null): bool {
    if ($excludeUserId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_ WHERE login = ? AND id_user != ?");
        $stmt->execute([$login, $excludeUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_ WHERE login = ?");
        $stmt->execute([$login]);
    }
    
    $result = $stmt->fetch();
    return (int)$result['count'] === 0;
}

/**
 * Проверка уникальности телефона в базе данных
 * @param PDO $pdo
 * @param string $phone
 * @param int|null $excludeUserId ID пользователя для исключения (при редактировании)
 * @return bool true если телефон уникален
 */
function isPhoneUnique(PDO $pdo, string $phone, ?int $excludeUserId = null): bool {
    if ($excludeUserId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_ WHERE phone_number = ? AND id_user != ?");
        $stmt->execute([$phone, $excludeUserId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_ WHERE phone_number = ?");
        $stmt->execute([$phone]);
    }
    
    $result = $stmt->fetch();
    return (int)$result['count'] === 0;
}

/**
 * Получить все ошибки валидации для формы регистрации/редактирования
 * @param array $data Массив данных ['login' => ..., 'password' => ..., 'phone' => ...]
 * @param PDO $pdo
 * @param int|null $excludeUserId ID пользователя для исключения при проверке уникальности
 * @return array Массив ошибок ['login' => 'error', 'password' => 'error', ...]
 */
function validateUserData(array $data, PDO $pdo, ?int $excludeUserId = null): array {
    $errors = [];
    
    // Валидация логина
    if (isset($data['login'])) {
        $loginValidation = validateLogin($data['login']);
        if (!$loginValidation['valid']) {
            $errors['login'] = $loginValidation['error'];
        } elseif (!isLoginUnique($pdo, $data['login'], $excludeUserId)) {
            $errors['login'] = 'Пользователь с таким логином уже существует';
        }
    }
    
    // Валидация пароля (если передан)
    if (isset($data['password']) && !empty($data['password'])) {
        $passwordValidation = validatePassword($data['password']);
        if (!$passwordValidation['valid']) {
            $errors['password'] = $passwordValidation['error'];
        }
        
        // Проверка совпадения паролей
        if (isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Пароли не совпадают';
        }
    }
    
    // Валидация телефона
    if (isset($data['phone'])) {
        $phoneValidation = validatePhone($data['phone']);
        if (!$phoneValidation['valid']) {
            $errors['phone'] = $phoneValidation['error'];
        } elseif (!isPhoneUnique($pdo, formatPhoneNumber($data['phone']), $excludeUserId)) {
            $errors['phone'] = 'Пользователь с таким номером телефона уже существует';
        }
    }
    
    return $errors;
}
