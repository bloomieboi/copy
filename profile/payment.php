<?php
/**
 * Сценарий 5: Оплата по карте в приложении
 * Пользователь выбирает способ оплаты, вводит реквизиты карты, система выполняет транзакцию
 * и при успехе меняет статус заказа на «Оплачен» и направляет электронный чек.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();

// Предусловия: пользователь должен быть авторизован
requireLogin();

if (!isClient()) {
    header("Location: ../index.php");
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}

// Предусловия: у пользователя должен быть выбранный заказ для оплаты (статус «В процессе оплаты»)
$stmt = $pdo->prepare("SELECT o.*, s.status_name FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

if ((int)$order['status_id'] !== 1) {
    // Заказ уже оплачен или в другом статусе
    header("Location: order_detail.php?id=" . $orderId);
    exit;
}

$error = '';
$step = $_GET['step'] ?? 'method'; // method | card

// Список сохранённых карт пользователя (без ORDER BY — имя колонки PK в БД может отличаться)
$stmt = $pdo->prepare("SELECT * FROM payment_method WHERE id_user = ?");
$stmt->execute([$_SESSION['user_id']]);
$savedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка выбора способа оплаты: переход на ввод данных карты
if ($step === 'method' && isset($_GET['pay_method']) && $_GET['pay_method'] === 'card') {
    $step = 'card';
}

// Обработка подтверждения оплаты (транзакция через платёжный шлюз)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $cardSource = $_POST['card_source'] ?? ''; // индекс карты в списке (0,1,...) или 'new'
    $cardIndex = (int)$cardSource;
    $newCardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');

    $cardValid = false;
    if ($cardSource !== 'new' && $cardIndex >= 0 && $cardIndex < count($savedCards)) {
        $cardValid = true;
    } elseif ($cardSource === 'new' && strlen($newCardNumber) >= 12 && strlen($newCardNumber) <= 19) {
        $cardValid = true;
    }

    if (!$cardValid) {
        $error = 'Выберите привязанную карту или введите корректные реквизиты карты.';
        $step = 'card';
    } else {
        // 6) Система выполняет транзакцию через платёжный шлюз (демо: имитация; для проверки альтернативного потока добавьте ?simulate_error=1)
        $simulateError = isset($_GET['simulate_error']);
        if ($simulateError) {
            // 7) Платёжный шлюз возвращает ошибку
            // 8) Система уведомляет о неудачной оплате и предлагает повторить или другой способ
            $error = 'Оплата не прошла. Попробуйте ещё раз или выберите другой способ оплаты.';
            $step = 'card';
        } else {
            // 7) Платёжный шлюз подтверждает успешность платежа
            // 8) Система меняет статус заказа на «Оплачен», фиксирует это в логе,
            //    и сразу переводит его в статус «В работе» (status_id = 6)
            $stmt = $pdo->prepare("UPDATE order_ SET status_id = 2 WHERE order_id = ? AND user_id = ? AND status_id = 1");
            $stmt->execute([$orderId, $_SESSION['user_id']]);
            addOrderLog($orderId, $_SESSION['user_id'], 'payment_completed', 'Клиент оплатил заказ по карте в приложении');

            // Немедленный переход в статус «В работе» (id = 6) после оплаты
            $stmt = $pdo->prepare("UPDATE order_ SET status_id = 6 WHERE order_id = ? AND user_id = ? AND status_id = 2");
            $stmt->execute([$orderId, $_SESSION['user_id']]);

            header("Location: receipt.php?order_id=" . $orderId);
            exit;
        }
    }
}

$pageTitle = 'Оплата заказа #' . $orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Оплата заказа #<?= $orderId ?></h2>
        <p><strong>К оплате:</strong> <?= formatPrice($order['price']) ?></p>
        <p><strong>Услуга:</strong> <?= htmlspecialchars($order['service_list']) ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($step === 'method'): ?>
            <!-- 2) Система отображает доступные способы оплаты -->
            <div class="payment-methods">
                <h3>Выберите способ оплаты</h3>
                <div class="payment-method-list">
                    <a href="payment.php?order_id=<?= $orderId ?>&step=card&pay_method=card" class="payment-method-card">
                        <span class="payment-method-title">Оплата по карте в приложении</span>
                        <span class="payment-method-desc">Банковская карта (привязанная или новая)</span>
                    </a>
                </div>
            </div>
            <p><a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-secondary">Назад к заказу</a></p>
        <?php else: ?>
            <!-- 4) Страница ввода данных карты и запрос подтверждения оплаты -->
            <div class="payment-card-form">
                <h3>Оплата по карте в приложении</h3>
                <form method="POST" action="payment.php?order_id=<?= $orderId ?>">
                    <?php if (!empty($savedCards)): ?>
                        <div class="form-group">
                            <label>Привязанные карты:</label>
                            <?php foreach ($savedCards as $idx => $card): ?>
                                <label class="radio-card">
                                    <input type="radio" name="card_source" value="<?= $idx ?>" required>
                                    Карта •••• <?= htmlspecialchars(substr($card['card_number'], -4)) ?>
                                </label>
                            <?php endforeach; ?>
                            <label class="radio-card">
                                <input type="radio" name="card_source" value="new">
                                Ввести новую карту
                            </label>
                        </div>
                        <div class="form-group new-card-fields" style="display:none;">
                            <label for="card_number">Номер карты:</label>
                            <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19">
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="card_source" value="new">
                        <div class="form-group">
                            <label for="card_number">Номер карты:</label>
                            <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required>
                        </div>
                    <?php endif; ?>
                    <div class="form-actions">
                        <button type="submit" name="confirm_payment" class="btn btn-primary">Подтвердить оплату</button>
                        <a href="payment.php?order_id=<?= $orderId ?>" class="btn btn-secondary">Другой способ оплаты</a>
                        <a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
            <?php if ($error): ?>
                <p class="payment-retry-hint">Вы можете <a href="payment.php?order_id=<?= $orderId ?>&step=card">повторить попытку</a> или вернуться к заказу.</p>
            <?php endif; ?>
        <?php endif; ?>
    <script>
    (function() {
        var cardSource = document.querySelectorAll('input[name="card_source"]');
        var newCardFields = document.querySelector('.new-card-fields');
        var cardNumber = document.getElementById('card_number');
        if (!cardSource.length) return;
        cardSource.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (newCardFields) {
                    if (this.value === 'new') {
                        newCardFields.style.display = 'block';
                        if (cardNumber) cardNumber.required = true;
                    } else {
                        newCardFields.style.display = 'none';
                        if (cardNumber) cardNumber.required = false;
                    }
                }
            });
        });
        var firstSaved = document.querySelector('input[name="card_source"]:not([value="new"])');
        if (firstSaved) firstSaved.checked = true;
    })();
    </script>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
