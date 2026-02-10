<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
// Предусловие: пользователь должен быть авторизован (клиент, сотрудник или администратор)
requireLogin();

$error = '';
$success = '';
$service = null;
$serviceId = intval($_GET['service_id'] ?? 0);

// Получаем информацию об услуге из базы данных
if ($serviceId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    // Проверка доступности услуги
    if (!$service) {
        $error = 'Услуга не найдена';
    } elseif (!$service['is_active']) {
        // Альтернативный поток: Услуга временно недоступна
        $error = 'Услуга временно недоступна';
    }
} else {
    // Поддержка старого формата для обратной совместимости
    $serviceName = $_GET['service'] ?? '';
    $basePrice = floatval($_GET['price'] ?? 0);
    if ($serviceName && $basePrice > 0) {
        // Создаем временный объект услуги для обратной совместимости
        $service = [
            'service_id' => 0,
            'service_name' => $serviceName,
            'description' => '',
            'base_price' => $basePrice,
            'is_active' => 1
        ];
    } else {
        $error = 'Услуга не выбрана';
    }
}

// Получаем адреса для выбора
$addresses = $pdo->query("SELECT * FROM address ORDER BY address_name")->fetchAll();

// Получаем скидочные карты пользователя (предусловие: наличие скидочной карты)
$stmt = $pdo->prepare("SELECT dc.discount_id, dc.number_of_bonuses 
                       FROM user_discount_card ud 
                       JOIN discount_card dc ON ud.discount_id = dc.discount_id
                       WHERE ud.id_user = ?");
$stmt->execute([$_SESSION['user_id']]);
$discountCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalBonuses = 0;
$discountCardsById = [];
foreach ($discountCards as $card) {
    $b = (int)$card['number_of_bonuses'];
    $totalBonuses += $b;
    $discountCardsById[(int)$card['discount_id']] = $card;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $serviceIdFromForm = intval($_POST['service_id'] ?? 0);
    $serviceList = trim($_POST['service_list'] ?? '');
    $basePrice = floatval($_POST['base_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $addressId = intval($_POST['address_id'] ?? 0);
    $discountCardId = isset($_POST['discount_card_id']) ? (int)$_POST['discount_card_id'] : 0;
    $discountCardNumber = trim($_POST['discount_card_number'] ?? '');
    $useBonuses = intval($_POST['use_bonuses'] ?? 0);
    if ($discountCardId <= 0 && $discountCardNumber !== '') {
        $discountCardId = (int)preg_replace('/\D/', '', $discountCardNumber);
    }
    if ($useBonuses < 0) $useBonuses = 0;
    
    // Повторная проверка доступности услуги
    if ($serviceIdFromForm > 0) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = 1");
        $stmt->execute([$serviceIdFromForm]);
        $serviceCheck = $stmt->fetch();
        if (!$serviceCheck) {
            $error = 'Услуга временно недоступна';
        } else {
            $service = $serviceCheck;
            $serviceList = $service['service_name'];
            $basePrice = floatval($service['base_price']);
        }
    }
    
    // Валидация количества (проверка пределов INT в MySQL)
    $maxInt = 2147483647; // Максимальное значение INT в MySQL
    
    if (!$error && (!$serviceList || $basePrice <= 0)) {
        $error = 'Заполните все поля корректно.';
    } elseif (!$error && ($quantity <= 0 || $quantity > $maxInt)) {
        $error = 'Количество должно быть от 1 до ' . number_format($maxInt, 0, '', ' ') . '.';
    }
    
    if (!$error && $addressId <= 0) {
        $error = 'Необходимо выбрать адрес организации';
    }
    
    if (!$error) {
        // Стоимость считается как цена за единицу * количество
        $price = $basePrice * $quantity;

        // Сценарий 6: проверка валидности скидочной карты и применение скидки
        $selectedCard = null;
        if ($discountCardId > 0 && isset($discountCardsById[$discountCardId])) {
            $selectedCard = $discountCardsById[$discountCardId];
        }
        if ($discountCardId > 0 && ($selectedCard === null || (int)$selectedCard['number_of_bonuses'] <= 0)) {
            // Альтернативный поток: карта недействительна, просрочена или не применима к данной услуге
            $error = 'Скидочная карта недействительна';
            $selectedCard = null;
        }

        if (!$error) {
            $maxUsable = 0;
            if ($selectedCard) {
                $maxUsable = min((int)$selectedCard['number_of_bonuses'], (int)floor($price));
                if ($useBonuses > $maxUsable) {
                    $useBonuses = $maxUsable;
                }
            } else {
                $useBonuses = 0;
            }
            $finalPrice = $price - $useBonuses;

            try {
                // Создаем заказ со статусом "В процессе оплаты" (1)
                $serviceWithQuantity = $serviceList . ' (количество: ' . $quantity . ')';
                $stmt = $pdo->prepare("INSERT INTO order_ (service_list, price, status_id, user_id) VALUES (?, ?, 1, ?)");
                $stmt->execute([$serviceWithQuantity, $finalPrice, $_SESSION['user_id']]);
                $orderId = $pdo->lastInsertId();
                addOrderLog($orderId, $_SESSION['user_id'], 'order_created', 'Клиент создал заказ');
                
                if ($addressId > 0) {
                    $stmt = $pdo->prepare("INSERT INTO order_address (order_id, address_id) VALUES (?, ?)");
                    $stmt->execute([$orderId, $addressId]);
                }

                // Списываем использованные бонусы с выбранной/привязанной карты
                if ($useBonuses > 0 && $selectedCard) {
                    $stmt = $pdo->prepare("UPDATE discount_card SET number_of_bonuses = number_of_bonuses - ? WHERE discount_id = ?");
                    $stmt->execute([$useBonuses, $selectedCard['discount_id']]);
                }
                
                header("Location: ../profile/index.php?order_created=1");
                exit;
            } catch (PDOException $e) {
                $error = 'Ошибка создания заказа: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Оформление заказа — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Оформление заказа</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="form-actions">
                <a href="../index.php" class="btn btn-secondary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Вернуться в каталог услуг
                </a>
            </div>
        <?php elseif ($service): ?>
            <form method="POST" class="order-form">
                <div class="form-section">
                    <h3>Информация об услуге</h3>
                    <div class="form-group">
                        <label for="service_list">Услуга:</label>
                        <input type="text" name="service_list" id="service_list" value="<?= htmlspecialchars($service['service_name']) ?>" readonly>
                        <?php if ($service['service_id'] > 0): ?>
                            <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
                        <?php endif; ?>
                        <?php if (!empty($service['description'])): ?>
                            <p><small><?= htmlspecialchars($service['description']) ?></small></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">
                            Количество (листы / услуги):
                            <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Максимум: 2 147 483 647"></i>
                        </label>
                        <input type="number" 
                               name="quantity" 
                               id="quantity" 
                               min="1" 
                               max="2147483647"
                               step="1" 
                               value="1" 
                               required>
                        <input type="hidden" name="base_price" value="<?= $service['base_price'] ?>">
                        <small class="form-text text-muted">
                            Цена за единицу: <?= formatPrice($service['base_price']) ?>. 
                            Итоговая стоимость = цена × количество.
                        </small>
                    </div>
                
                <?php if (!empty($addresses)): ?>
                <div class="form-group">
                    <label for="address_id">Адрес организации (обязательно):</label>
                    <select name="address_id" id="address_id" required>
                        <option value="">Выберите адрес</option>
                        <?php foreach($addresses as $addr): ?>
                            <option value="<?= $addr['address_id'] ?>"><?= htmlspecialchars($addr['address_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="alert alert-error">
                    Нет доступных адресов. Обратитесь к администратору для добавления адресов организации.
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-section">
                <h3>Скидочная карта</h3>
                <p class="form-hint">Выберите привязанную карту из профиля или введите номер скидочной карты. Система проверит валидность карты и применит скидку к итоговой сумме.</p>
                <div class="form-group">
                    <label>Выберите привязанную карту:</label>
                    <label class="radio-card"><input type="radio" name="discount_card_id" value="0" checked> Не использовать скидку</label>
                    <?php foreach ($discountCards as $card): ?>
                        <label class="radio-card">
                            <input type="radio" name="discount_card_id" value="<?= (int)$card['discount_id'] ?>">
                            Карта #<?= (int)$card['discount_id'] ?> — бонусов: <?= (int)$card['number_of_bonuses'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label for="discount_card_number">Или введите номер скидочной карты (ID карты):</label>
                    <input type="text" name="discount_card_number" id="discount_card_number" placeholder="Номер карты" value="">
                </div>
                <?php if ($totalBonuses > 0): ?>
                <div class="form-group">
                    <label for="use_bonuses">Списать бонусов (1 бонус = 1 рубль скидки, макс. по карте и сумме заказа):</label>
                    <input type="number" name="use_bonuses" id="use_bonuses" min="0" max="<?= (int)$totalBonuses ?>" value="0">
                </div>
                <?php endif; ?>
                <p class="order-total-hint" id="order_total_hint"><strong>Итоговая сумма</strong> пересчитывается по выбранной карте и количеству при оформлении заказа.</p>
            </div>
            
                <div class="form-actions">
                    <button type="submit" name="submit_order" class="btn btn-primary">Оформить заказ</button>
                    <a href="../index.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        <?php endif; ?>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
