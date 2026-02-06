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

// Заказ должен принадлежать пользователю и быть завершенным/закрытым
$stmt = $pdo->prepare("SELECT * FROM order_ WHERE order_id = ? AND user_id = ? AND status_id IN (3,4)");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Проверяем, не существует ли уже отзыв
$stmt = $pdo->prepare("SELECT * FROM review WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$existing = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $rating = (int)($_POST['rating'] ?? 0);
    $text = trim($_POST['review_text'] ?? '');
    if ($rating < 1 || $rating > 5) {
        $error = 'Выберите оценку от 1 до 5';
    } else {
        $stmt = $pdo->prepare("INSERT INTO review (order_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $_SESSION['user_id'], $rating, $text]);
        addOrderLog($orderId, $_SESSION['user_id'], 'review_added', 'Клиент оставил отзыв');
        $success = 'Спасибо за ваш отзыв!';
        $stmt = $pdo->prepare("SELECT * FROM review WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        $existing = $stmt->fetch();
    }
}

$pageTitle = 'Отзыв по заказу #' . (int)$orderId . ' — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Отзыв по заказу #<?= $orderId ?></h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($existing): ?>
            <div class="review-existing">
                <h3>Ваш отзыв</h3>
                <div class="rating-display">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= $existing['rating'] ? 'filled' : '' ?>">★</span>
                    <?php endfor; ?>
                    <span class="rating-value"><?= (int)$existing['rating'] ?>/5</span>
                </div>
                <?php if ($existing['review_text']): ?>
                    <p><?= nl2br(htmlspecialchars($existing['review_text'])) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="POST" class="review-form">
                <div class="form-group">
                    <label>Оценка:</label>
                    <div class="rating-input">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label">
                                <input type="radio" name="rating" value="<?= $i ?>" required>
                                <span class="star">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review_text">Комментарий (необязательно):</label>
                    <textarea name="review_text" id="review_text" rows="4"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                    <a href="order_detail.php?id=<?= $orderId ?>" class="btn btn-secondary">Назад к заказу</a>
                </div>
            </form>
        <?php endif; ?>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>

