<?php
/**
 * Сценарий 10: Администрирование учётных записей.
 * Карточка пользователя: редактирование данных; управление скидочной картой.
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
$error   = '';

// ─── Обработка формы редактирования учётной записи ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login           = trim($_POST['login'] ?? '');
    $phone           = trim($_POST['phone_number'] ?? '');
    $roleId          = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    $password        = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    if (!in_array($roleId, $validRoleIds, true)) {
        $error = 'Выбрана недопустимая роль. Выберите значение из списка.';
    } elseif (strlen($login) > 30) {
        $error = 'Логин не должен превышать 30 символов.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        $error = 'Логин может содержать только английские буквы, цифры и подчёркивание.';
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
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ?, password = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $passwordHash, $userId]);
                        $success = 'Учётная запись обновлена. Пароль изменён.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE user_ SET login = ?, phone_number = ?, role_id = ? WHERE id_user = ?");
                        $stmt->execute([$login, $phone, $roleId, $userId]);
                        $success = 'Учётная запись обновлена.';
                    }
                    $user['login']        = $login;
                    $user['phone_number'] = $phone;
                    $user['role_id']      = $roleId;
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка обновления: ' . $e->getMessage();
        }
    }
}

// ─── Скидочная карта пользователя ────────────────────────────────────────────
function fetchDiscountCard(PDO $pdo, int $userId): ?array {
    try {
        $stmt = $pdo->prepare("SELECT discount_id, number_of_bonuses FROM discount_card WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare(
                "SELECT dc.discount_id, dc.number_of_bonuses
                 FROM discount_card dc
                 JOIN user_discount_card udc ON udc.discount_id = dc.discount_id
                 WHERE udc.id_user = ? LIMIT 1"
            );
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e2) {
            return null;
        }
    }
}

$discountCard = fetchDiscountCard($pdo, (int)$userId);
$cardSuccess  = '';
$cardError    = '';

// Обработка операций со скидочной картой
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_action'])) {
    $action  = $_POST['card_action'];
    $bonuses = max(0, (int)($_POST['bonuses'] ?? 0));

    if ($action === 'create' && !$discountCard) {
        try {
            $stmt = $pdo->prepare("INSERT INTO discount_card (user_id, number_of_bonuses) VALUES (?, ?)");
            $stmt->execute([$userId, $bonuses]);
            $cardSuccess = 'Скидочная карта создана. Начислено ' . $bonuses . ' бонусов.';
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("INSERT INTO discount_card (number_of_bonuses) VALUES (?)");
                $stmt->execute([$bonuses]);
                $newId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_discount_card (id_user, discount_id) VALUES (?, ?)");
                $stmt->execute([$userId, $newId]);
                $cardSuccess = 'Скидочная карта создана. Начислено ' . $bonuses . ' бонусов.';
            } catch (PDOException $e2) {
                $cardError = 'Ошибка создания карты: ' . $e2->getMessage();
            }
        }
        $discountCard = fetchDiscountCard($pdo, (int)$userId);

    } elseif ($action === 'add' && $discountCard) {
        $newTotal = (int)$discountCard['number_of_bonuses'] + $bonuses;
        $stmt = $pdo->prepare("UPDATE discount_card SET number_of_bonuses = ? WHERE discount_id = ?");
        $stmt->execute([$newTotal, $discountCard['discount_id']]);
        $discountCard['number_of_bonuses'] = $newTotal;
        $cardSuccess = 'Начислено ' . $bonuses . ' бонусов. Итого на карте: ' . $newTotal . '.';

    } elseif ($action === 'set' && $discountCard) {
        $stmt = $pdo->prepare("UPDATE discount_card SET number_of_bonuses = ? WHERE discount_id = ?");
        $stmt->execute([$bonuses, $discountCard['discount_id']]);
        $discountCard['number_of_bonuses'] = $bonuses;
        $cardSuccess = 'Баланс карты установлен: ' . $bonuses . ' бонусов.';
    }
}

$pageTitle = 'Карточка пользователя #' . (int)$userId . ' — КопиПейст';
$baseUrl   = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>

        <h2>Карточка пользователя #<?= $userId ?></h2>
        <p class="form-hint">Редактирование учётной записи. Роль выбирается только из выпадающего списка.</p>

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

        <!-- Форма редактирования учётной записи -->
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" name="login" id="login" maxlength="30"
                       value="<?= htmlspecialchars($user['login']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Телефон (формат 79xxxxxxxxx):</label>
                <input type="text" name="phone_number" id="phone_number" maxlength="11" minlength="11"
                       value="<?= htmlspecialchars($user['phone_number']) ?>" required>
            </div>

            <div class="form-group">
                <label for="role_id">Роль (выберите из списка):</label>
                <select name="role_id" id="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['role_id'] ?>"
                            <?= (int)$user['role_id'] === (int)$role['role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Новый пароль (оставьте пустым, чтобы не менять):</label>
                <input type="password" name="password" id="password"
                       placeholder="Введите новый пароль" minlength="3" autocomplete="new-password">
                <small class="form-text text-muted">
                    <i class="bi bi-shield-lock me-1"></i>
                    Пароль будет зашифрован. Администратор не может просмотреть текущий пароль.
                </small>
            </div>

            <div class="form-group" x-data="{ showConfirm: false }">
                <label for="password_confirm">Подтверждение пароля:</label>
                <input type="password" name="password_confirm" id="password_confirm"
                       placeholder="Повторите пароль" autocomplete="new-password"
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
        document.addEventListener('DOMContentLoaded', function () {
            const pw  = document.getElementById('password');
            const pw2 = document.getElementById('password_confirm');
            const form = document.querySelector('.edit-form');
            function check() {
                if (pw.value === '' && pw2.value === '') { pw2.setCustomValidity(''); return; }
                pw2.setCustomValidity(pw.value !== pw2.value ? 'Пароли не совпадают' : '');
            }
            pw.addEventListener('input', check);
            pw2.addEventListener('input', check);
            form.addEventListener('submit', function (e) {
                if (!check()) { e.preventDefault(); pw2.reportValidity(); }
            });
        });
        </script>

        <hr style="margin: 2rem 0;">

        <!-- Скидочная карта пользователя -->
        <h3>Скидочная карта</h3>

        <?php if ($cardSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($cardSuccess) ?></div>
        <?php endif; ?>
        <?php if ($cardError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($cardError) ?></div>
        <?php endif; ?>

        <?php if ($discountCard): ?>
            <div class="stat-card" style="display:inline-block;min-width:220px;text-align:center;margin-bottom:1.5rem;">
                <p><strong>Карта #<?= (int)$discountCard['discount_id'] ?></strong></p>
                <div class="stat-value"><?= (int)$discountCard['number_of_bonuses'] ?></div>
                <p class="form-hint">бонусов на карте</p>
            </div>

            <form method="POST" class="edit-form" style="max-width:400px;">
                <input type="hidden" name="card_action" value="add">
                <div class="form-group">
                    <label for="bonuses_add">Начислить бонусов:</label>
                    <input type="number" name="bonuses" id="bonuses_add" min="1" value="10" required>
                    <small class="form-text text-muted">
                        Текущий баланс: <?= (int)$discountCard['number_of_bonuses'] ?> бонусов
                    </small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Начислить</button>
                </div>
            </form>

            <form method="POST" class="edit-form" style="max-width:400px;margin-top:1rem;">
                <input type="hidden" name="card_action" value="set">
                <div class="form-group">
                    <label for="bonuses_set">Установить баланс напрямую:</label>
                    <input type="number" name="bonuses" id="bonuses_set" min="0"
                           value="<?= (int)$discountCard['number_of_bonuses'] ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-secondary">Установить</button>
                </div>
            </form>

        <?php else: ?>
            <p class="form-hint">У пользователя нет скидочной карты.</p>
            <form method="POST" class="edit-form" style="max-width:400px;">
                <input type="hidden" name="card_action" value="create">
                <div class="form-group">
                    <label for="bonuses_create">Начальный баланс бонусов:</label>
                    <input type="number" name="bonuses" id="bonuses_create" min="0" value="0" required>
                    <small class="form-text text-muted">Можно поставить 0 и начислить позже</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Создать скидочную карту</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="actions" style="margin-top:2rem;">
            <a href="users.php" class="btn btn-secondary">← Назад к списку пользователей</a>
        </div>

<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
