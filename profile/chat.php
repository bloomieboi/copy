<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireLogin();

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header("Location: index.php");
    exit;
}

// Проверяем, что заказ принадлежит клиенту
$stmt = $pdo->prepare("SELECT * FROM order_ WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Чат доступен только когда заказ в статусе «В работе» (id = 6)
if ((int)$order['status_id'] !== 6) {
    header("Location: order_detail.php?id=" . $orderId);
    exit;
}

// Проверяем, есть ли уже сообщения в чате
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM message WHERE order_id = ?");
$stmt->execute([$orderId]);
$messageCount = (int)$stmt->fetch()['count'];

// Чат может начать только сотрудник!
$chatStarted = $messageCount > 0;

// Определяем собеседника (сотрудника)
$partnerId = null;
if ($chatStarted) {
    // Если чат уже начат, находим сотрудника
    $stmt = $pdo->prepare("SELECT DISTINCT 
                              CASE 
                                WHEN from_user_id = ? THEN to_user_id 
                                ELSE from_user_id 
                              END AS other_id
                           FROM message
                           WHERE order_id = ? AND (from_user_id = ? OR to_user_id = ?)
                           LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $orderId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $existing = $stmt->fetch();
    if ($existing) {
        $partnerId = (int)$existing['other_id'];
    }
}

// Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $partnerId) {
    $text = trim($_POST['message']);
    if ($text !== '') {
        $stmt = $pdo->prepare("INSERT INTO message (order_id, from_user_id, to_user_id, message_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $_SESSION['user_id'], $partnerId, $text]);
        addOrderLog($orderId, $_SESSION['user_id'], 'message_sent', 'Клиент отправил сообщение');
        header("Location: chat.php?order_id=" . $orderId);
        exit;
    }
}

// Сообщения по заказу
$stmt = $pdo->prepare("SELECT m.*, u.login AS from_login 
                       FROM message m 
                       JOIN user_ u ON m.from_user_id = u.id_user
                       WHERE m.order_id = ?
                       ORDER BY m.created_at ASC");
$stmt->execute([$orderId]);
$messages = $stmt->fetchAll();

// Помечаем как прочитанные
$stmt = $pdo->prepare("UPDATE message SET is_read = 1 WHERE order_id = ? AND to_user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);

$pageTitle = 'Чат по заказу #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <div class="chat-header">
            <h2>Чат по заказу #<?= $orderId ?></h2>
            <a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-sm btn-secondary">Детали заказа</a>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <?php if (!$chatStarted): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-chat-left-dots me-2"></i>
                    <strong>Чат еще не начат.</strong><br>
                    Сотрудник, обрабатывающий ваш заказ, свяжется с вами в чате при необходимости.
                </div>
            <?php elseif (empty($messages)): ?>
                <div class="empty-chat">Пока нет сообщений.</div>
            <?php else: ?>
                <?php foreach($messages as $msg): ?>
                    <div class="message <?= $msg['from_user_id'] == $_SESSION['user_id'] ? 'message-out' : 'message-in' ?>">
                        <div class="message-header">
                            <strong><?= htmlspecialchars($msg['from_login']) ?></strong>
                            <span class="message-time"><?= formatDateTime($msg['created_at']) ?></span>
                        </div>
                        <div class="message-body">
                            <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($chatStarted && $partnerId): ?>
        <form method="POST" class="chat-form">
            <div class="chat-input-group">
                <textarea name="message" rows="3" placeholder="Введите сообщение..." required></textarea>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>
                    Отправить
                </button>
            </div>
        </form>
        <?php elseif (!$chatStarted): ?>
            <div class="alert alert-info mt-3" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                Чат будет доступен, когда сотрудник начнет обработку вашего заказа и напишет первое сообщение.
            </div>
        <?php else: ?>
            <div class="alert alert-danger mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Не удалось найти сотрудника для чата. Обратитесь к администратору.
            </div>
        <?php endif; ?>
    <script>
        const box = document.getElementById('chatMessages');
        if (box) box.scrollTop = box.scrollHeight;
    </script>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>

