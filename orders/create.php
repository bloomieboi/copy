<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireLogin();

$error   = '';
$service = null;
$serviceId = intval($_GET['service_id'] ?? 0);

// Получаем услугу
if ($serviceId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    if (!$service) {
        $error = 'Услуга не найдена';
    } elseif (!$service['is_active']) {
        $error = 'Услуга временно недоступна';
    } elseif ($service['is_offline']) {
        // Альтернативный поток: Услуга только оффлайн
        $error = 'Данная услуга доступна для заказа только в наших копицентрах. Онлайн-заказ невозможен.';
    }
} else {
    $serviceName = $_GET['service'] ?? '';
    $basePrice   = floatval($_GET['price'] ?? 0);
    if ($serviceName && $basePrice > 0) {
        // Создаем временный объект услуги для обратной совместимости
        $service = [
            'service_id' => 0,
            'service_name' => $serviceName,
            'description' => '',
            'base_price' => $basePrice,
            'is_active' => 1,
            'is_offline' => 0
        ];
    } else {
        $error = 'Услуга не выбрана';
    }
}

// Точки обслуживания для выбора
$addresses = $pdo->query("SELECT location_id as address_id, CONCAT(location_name, ' - ', address) as address_name FROM locations WHERE is_active = 1 ORDER BY location_name")->fetchAll();

// Скидочная карта текущего пользователя (одна, уникальная)
$discountCard = null;
try {
    $stmt = $pdo->prepare("SELECT discount_id, number_of_bonuses FROM discount_card WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $discountCard = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    try {
        $stmt = $pdo->prepare(
            "SELECT dc.discount_id, dc.number_of_bonuses
             FROM discount_card dc
             JOIN user_discount_card udc ON udc.discount_id = dc.discount_id
             WHERE udc.id_user = ? LIMIT 1"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $discountCard = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e2) {
        $discountCard = null;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $serviceIdFromForm = intval($_POST['service_id'] ?? 0);
    $serviceList       = trim($_POST['service_list'] ?? '');
    $basePrice         = floatval($_POST['base_price'] ?? 0);
    $quantity          = intval($_POST['quantity'] ?? 0);
    $addressId         = intval($_POST['address_id'] ?? 0);
    $useBonuses        = intval($_POST['use_bonuses'] ?? 0);
    $clientComment     = trim($_POST['client_comment'] ?? '');
    if ($useBonuses < 0) $useBonuses = 0;

    if ($serviceIdFromForm > 0) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = 1");
        $stmt->execute([$serviceIdFromForm]);
        $serviceCheck = $stmt->fetch();
        if (!$serviceCheck) {
            $error = 'Услуга временно недоступна';
        } else {
            $service     = $serviceCheck;
            $serviceList = $service['service_name'];
            $basePrice   = floatval($service['base_price']);
        }
    }

    $maxQuantity = 1000000;
    $maxInt      = 2147483647;

    if (!$error && (!$serviceList || $basePrice <= 0)) {
        $error = 'Заполните все поля корректно.';
    } elseif (!$error && $quantity <= 0) {
        $error = 'Количество должно быть больше нуля.';
    } elseif (!$error && $quantity > $maxQuantity) {
        $error = 'Количество не может превышать ' . number_format($maxQuantity, 0, '', ' ') . ' единиц.';
    }

    if (!$error && $addressId <= 0) {
        $error = 'Необходимо выбрать точку обслуживания.';
    }

    if (!$error) {
        $price = $basePrice * $quantity;
        if (!is_finite($price) || $price > $maxInt) {
            $error = 'Стоимость заказа слишком велика. Уменьшите количество.';
        }
    }

    if (!$error) {
        $price = $basePrice * $quantity;

        // Применяем бонусы только с карты самого пользователя
        if ($useBonuses > 0 && $discountCard) {
            $maxUsable  = min((int)$discountCard['number_of_bonuses'], (int)floor($price));
            $useBonuses = min($useBonuses, $maxUsable);
        } else {
            $useBonuses = 0;
        }
        $finalPrice = $price - $useBonuses;

        try {
            $serviceWithQuantity = $serviceList . ' (количество: ' . $quantity . ')';
            $stmt = $pdo->prepare("INSERT INTO order_ (service_list, price, status_id, user_id) VALUES (?, ?, 1, ?)");
            $stmt->execute([$serviceWithQuantity, $finalPrice, $_SESSION['user_id']]);
            $orderId = $pdo->lastInsertId();
            addOrderLog($orderId, $_SESSION['user_id'], 'order_created', 'Клиент создал заказ');

            $stmt = $pdo->prepare("INSERT INTO order_address (order_id, address_id) VALUES (?, ?)");
            $stmt->execute([$orderId, $addressId]);

            if ($clientComment !== '') {
                addOrderLog($orderId, $_SESSION['user_id'], 'client_comment', $clientComment);
            }

            if ($useBonuses > 0 && $discountCard) {
                try {
                    $stmt = $pdo->prepare("UPDATE discount_card SET number_of_bonuses = number_of_bonuses - ? WHERE discount_id = ?");
                    $stmt->execute([$useBonuses, $discountCard['discount_id']]);
                } catch (PDOException $e) {}
            }

            header("Location: ../profile/index.php?order_created=1");
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка создания заказа: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Оформление заказа — КопиПейст';
$baseUrl   = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Оформление заказа</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <div class="form-actions">
                <a href="../index.php" class="btn btn-secondary">Вернуться в каталог услуг</a>
            </div>
        <?php elseif ($service): ?>

            <form method="POST" class="order-form">

                <div class="form-section">
                    <h3>Услуга</h3>
                    <div class="form-group">
                        <label>Услуга:</label>
                        <input type="text" value="<?= htmlspecialchars($service['service_name']) ?>" readonly>
                        <?php if ($service['service_id'] > 0): ?>
                            <input type="hidden" name="service_id"   value="<?= $service['service_id'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="service_list" value="<?= htmlspecialchars($service['service_name']) ?>">
                        <input type="hidden" name="base_price"   value="<?= $service['base_price'] ?>">
                        <?php if (!empty($service['description'])): ?>
                            <small class="form-text text-muted"><?= htmlspecialchars($service['description']) ?></small>
                        <?php endif; ?>
                        <p class="form-hint" style="margin-top: 5px;">
                            <i class="bi bi-info-circle"></i> Эту услугу также можно заказать лично в любом из наших копицентров.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Количество:</label>
                        <input type="number" name="quantity" id="quantity" min="1" max="1000000" step="1" value="1" required>
                        <small class="form-text text-muted">Цена за единицу: <?= formatPrice($service['base_price']) ?></small>
                    </div>

                    <?php if (!empty($addresses)): ?>
                    <div class="form-group">
                        <label for="address_id">Точка обслуживания: <span class="text-danger">*</span></label>
                        <select name="address_id" id="address_id" required>
                            <option value="">Выберите точку обслуживания</option>
                            <?php foreach ($addresses as $addr): ?>
                                <option value="<?= $addr['address_id'] ?>"><?= htmlspecialchars($addr['address_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-error">Нет доступных точек обслуживания. Обратитесь к администратору.</div>
                    <?php endif; ?>
                </div>

                <div class="form-section">
                    <h3>Дополнительная информация</h3>
                    <div class="form-group">
                        <label for="client_comment">Комментарий к заказу (необязательно):</label>
                        <textarea name="client_comment" id="client_comment" rows="3" placeholder="Особые пожелания, срочность и т.д."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Скидочная карта</h3>

                    <?php if ($discountCard && (int)$discountCard['number_of_bonuses'] > 0): ?>
                        <p class="form-hint">
                            На вашей карте <strong><?= (int)$discountCard['number_of_bonuses'] ?> бонусов</strong>.
                            1 бонус = 1 рубль скидки.
                        </p>
                        <div class="form-group">
                            <label for="use_bonuses">
                                Списать бонусов
                                <small class="text-muted">(макс. <?= (int)$discountCard['number_of_bonuses'] ?>)</small>:
                            </label>
                            <input type="number" name="use_bonuses" id="use_bonuses"
                                   min="0" max="<?= (int)$discountCard['number_of_bonuses'] ?>" value="0">
                        </div>
                    <?php elseif ($discountCard): ?>
                        <p class="form-hint">На вашей карте 0 бонусов.</p>
                    <?php else: ?>
                        <p class="form-hint">У вас пока нет скидочной карты.</p>
                    <?php endif; ?>

                    <p class="order-total-hint" id="order_total_hint">
                        <strong>Итоговая сумма</strong> будет рассчитана при оформлении заказа.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Оформить заказ</button>
                    <a href="../index.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const qtyInput   = document.getElementById('quantity');
                const bonusInput = document.getElementById('use_bonuses');
                const basePrice  = parseFloat(<?= json_encode((float)$service['base_price']) ?>);
                const maxBonuses = <?= $discountCard ? (int)$discountCard['number_of_bonuses'] : 0 ?>;
                const hint       = document.getElementById('order_total_hint');

                function update() {
                    const qty    = Math.max(0, parseInt(qtyInput.value) || 0);
                    const bonuses = bonusInput ? Math.max(0, parseInt(bonusInput.value) || 0) : 0;
                    const total  = basePrice * qty;
                    const final  = Math.max(0, total - Math.min(bonuses, maxBonuses, total));
                    if (!hint) return;
                    if (qty <= 0) { hint.innerHTML = '<strong>Итоговая сумма:</strong> —'; return; }
                    const fmt = (n) => n.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (bonuses > 0 && bonusInput) {
                        hint.innerHTML = '<strong>Итоговая сумма:</strong> ' + fmt(total) + ' руб. − ' + Math.min(bonuses, maxBonuses, Math.floor(total)) + ' бонусов = <strong>' + fmt(final) + ' руб.</strong>';
                    } else {
                        hint.innerHTML = '<strong>Итоговая сумма:</strong> ' + fmt(total) + ' руб.';
                    }
                }

                qtyInput.addEventListener('input', update);
                if (bonusInput) bonusInput.addEventListener('input', update);
                update();
            });
            </script>

        <?php endif; ?>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
