<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireLogin();

if (!isClient()) {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$orderCreated = isset($_GET['order_created']);
$cardAdded = isset($_GET['card_added']);
$cardError = '';

// Обработка добавления новой карты оплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment_method'])) {
    $rawCardNumber = $_POST['card_number'] ?? '';
    // Убираем пробелы и прочие разделители
    $normalizedCard = preg_replace('/\D+/', '', $rawCardNumber);
    $len = strlen($normalizedCard);

    // Проверка под ограничение БД chk_card_length (обычно 16 цифр)
    if ($normalizedCard === '' || !ctype_digit($normalizedCard)) {
        $cardError = 'Введите номер карты только цифрами.';
    } elseif ($len !== 16) {
        $cardError = 'Номер карты должен содержать ровно 16 цифр.';
    } else {
        // В демонстрационных целях сохраняем только номер карты
        $stmt = $pdo->prepare("INSERT INTO payment_method (id_user, card_number) VALUES (?, ?)");
        $stmt->execute([$userId, $normalizedCard]);

        header("Location: index.php?card_added=1");
        exit;
    }
}

// Получаем заказы клиента
$stmt = $pdo->prepare("SELECT o.*, s.status_name, s.status_id 
                       FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       WHERE o.user_id = ? 
                       ORDER BY o.created_date DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM user_ u JOIN role_ r ON u.role_id = r.role_id WHERE u.id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Получаем адреса заказов
$orderAddresses = [];
foreach ($orders as $order) {
    $stmt = $pdo->prepare("SELECT a.address_name FROM order_address oa 
                          JOIN address a ON oa.address_id = a.address_id 
                          WHERE oa.order_id = ?");
    $stmt->execute([$order['order_id']]);
    $addr = $stmt->fetch();
    $orderAddresses[$order['order_id']] = $addr ? $addr['address_name'] : null;
}

// Получаем скидочные карты пользователя
$stmt = $pdo->prepare("SELECT dc.discount_id, dc.number_of_bonuses 
                       FROM user_discount_card ud 
                       JOIN discount_card dc ON ud.discount_id = dc.discount_id
                       WHERE ud.id_user = ?");
$stmt->execute([$userId]);
$discountCards = $stmt->fetchAll();

// Получаем способы оплаты пользователя
$stmt = $pdo->prepare("SELECT * FROM payment_method WHERE id_user = ?");
$stmt->execute([$userId]);
$paymentMethods = $stmt->fetchAll();

$pageTitle = 'Личный кабинет — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <div class="profile-header">
            <h2>Личный кабинет</h2>
            <div class="user-info">
                <p><strong>Логин:</strong> <?= htmlspecialchars($user['login']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone_number']) ?></p>
                <p><strong>Роль:</strong> <?= htmlspecialchars($user['role_name']) ?></p>
            </div>
        </div>

        <?php if ($orderCreated): ?>
            <div class="alert alert-success">Заказ успешно создан!</div>
        <?php endif; ?>
        <?php if ($cardAdded): ?>
            <div class="alert alert-success">Новая карта успешно добавлена.</div>
        <?php endif; ?>

        <?php if (!empty($discountCards)): ?>
        <section class="discount-section">
            <h3>Скидочные карты</h3>
            <ul class="params-list">
                <?php foreach($discountCards as $card): ?>
                    <li>
                        <strong>Карта #<?= $card['discount_id'] ?>:</strong>
                        бонусов: <?= (int)$card['number_of_bonuses'] ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <section class="payment-methods-section" id="payment-methods">
            <h3>Способы оплаты</h3>
            <?php if (empty($paymentMethods)): ?>
                <p>У вас еще нет сохраненных карт. Добавьте карту, чтобы оплачивать заказы онлайн.</p>
            <?php else: ?>
                <ul class="params-list">
                    <?php foreach($paymentMethods as $pm): ?>
                        <li>
                            <strong>Карта:</strong>
                            •••• <?= htmlspecialchars(substr($pm['card_number'], -4)) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="POST" class="payment-form">
                <div class="form-group">
                    <label for="card_number">Добавить новую карту (16 цифр)</label>
                    <input
                        type="text"
                        name="card_number"
                        id="card_number"
                        placeholder="0000 0000 0000 0000"
                        maxlength="19"
                        inputmode="numeric"
                        autocomplete="cc-number"
                        required
                    >
                </div>
                <?php if ($cardError): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($cardError) ?></div>
                <?php else: ?>
                    <p><small>Данные карты используются только для демонстрационной оплаты и не передаются в реальные платежные системы.</small></p>
                <?php endif; ?>
                <button type="submit" name="add_payment_method" class="btn btn-primary">Сохранить карту</button>
            </form>
        </section>

        <section class="orders-section">
            <h3>Мои заказы</h3>
            
            <?php if (empty($orders)): ?>
                <p class="empty-state">У вас пока нет заказов. <a href="../index.php">Создайте первый заказ</a></p>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h4>Заказ #<?= $order['order_id'] ?></h4>
                                    <span class="status-badge <?= getStatusBadgeClass($order['status_id']) ?>">
                                        <?= htmlspecialchars($order['status_name']) ?>
                                    </span>
                                </div>
                                <div class="order-price">
                                    <?= formatPrice($order['price']) ?>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <p><strong>Услуга:</strong> <?= htmlspecialchars($order['service_list']) ?></p>
                                <?php if ($orderAddresses[$order['order_id']]): ?>
                                    <p><strong>Адрес:</strong> <?= htmlspecialchars($orderAddresses[$order['order_id']]) ?></p>
                                <?php endif; ?>
                                <p><strong>Создан:</strong> <?= formatDateTime($order['created_date']) ?></p>
                            </div>
                            
                            <div class="order-footer">
                                <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
