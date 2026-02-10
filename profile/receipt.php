<?php
/**
 * Электронный чек после успешной оплаты (сценарий 5, шаг 8).
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();

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

$stmt = $pdo->prepare("SELECT o.*, s.status_name FROM order_ o 
                       JOIN status s ON o.status_id = s.status_id 
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Чек показываем только для оплаченного или уже взятого в работу заказа (id 6)
if (!in_array((int)$order['status_id'], [2, 6], true)) {
    header("Location: order_detail.php?id=" . $orderId);
    exit;
}

$stmt = $pdo->prepare("SELECT a.address_name FROM order_address oa 
                       JOIN address a ON oa.address_id = a.address_id 
                       WHERE oa.order_id = ?");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

$receiptDate = date('d.m.Y H:i');

$pageTitle = 'Чек по заказу #' . $orderId . ' — КопиПейст';
$baseUrl = '..';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php require_once __DIR__ . '/../function/layout_head.php'; ?>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .receipt { max-width: 480px; margin: 0 auto; padding: 1.5rem; background: #fff; border: 1px solid #ddd; border-radius: 8px; }
        .receipt h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .receipt-meta { color: #666; font-size: 0.875rem; margin-bottom: 1rem; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .receipt-table th { text-align: left; padding: 0.25rem 0; color: #666; font-weight: normal; }
        .receipt-table td { padding: 0.25rem 0; }
        .receipt-total { font-size: 1.125rem; font-weight: 600; margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid #ddd; }
        .receipt-actions { margin-top: 1.5rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">КопиПейст</a></h1>
            <nav>
                <a href="index.php">Личный кабинет</a>
                <a href="../logout.php">Выход</a>
            </nav>
        </div>
    </header>
    <main class="container">
        <div class="receipt" id="receipt">
            <h1>Электронный чек</h1>
            <p class="receipt-meta">Дата и время: <?= htmlspecialchars($receiptDate) ?></p>
            <table class="receipt-table">
                <tr>
                    <th>Номер заказа</th>
                    <td>#<?= (int)$orderId ?></td>
                </tr>
                <tr>
                    <th>Услуга</th>
                    <td><?= htmlspecialchars($order['service_list']) ?></td>
                </tr>
                <tr>
                    <th>Адрес организации</th>
                    <td><?= htmlspecialchars($address['address_name'] ?? 'Не указан') ?></td>
                </tr>
                <tr>
                    <th>Способ оплаты</th>
                    <td>Оплата по карте в приложении</td>
                </tr>
                <tr>
                    <th>Статус</th>
                    <td><?= htmlspecialchars($order['status_name']) ?></td>
                </tr>
            </table>
            <p class="receipt-total">К оплате: <?= formatPrice($order['price']) ?></p>
            <p class="receipt-meta">ООО «КопиПейст». Спасибо за заказ!</p>
        </div>
        <div class="receipt-actions">
            <a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-primary">К заказу</a>
            <a href="index.php" class="btn btn-secondary">В личный кабинет</a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
