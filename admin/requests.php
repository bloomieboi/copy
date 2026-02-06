<?php
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$requestId = $_GET['id'] ?? null;

if ($requestId) {
    // Получаем запрос
    $stmt = $pdo->prepare("SELECT ar.*, o.order_id, u.login as executor_login 
                           FROM admin_request ar 
                           JOIN order_ o ON ar.order_id = o.order_id
                           JOIN user_ u ON ar.executor_id = u.id_user
                           WHERE ar.request_id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        header("Location: index.php");
        exit;
    }
    
    // Обработка ответа
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['response'])) {
        $response = trim($_POST['response']);
        $status = $_POST['status'] ?? 'resolved';
        
        $stmt = $pdo->prepare("UPDATE admin_request SET admin_response = ?, status = ?, resolved_at = NOW() WHERE request_id = ?");
        $stmt->execute([$response, $status, $requestId]);
        
        // Логируем ответ
        addOrderLog($request['order_id'], $_SESSION['user_id'], 'admin_response', 'Администратор ответил на запрос сотрудника');
        
        header("Location: requests.php?id=$requestId&success=1");
        exit;
    }
} else {
    // Список всех запросов
    $statusFilter = $_GET['status'] ?? null;
    
    $where = [];
    $params = [];
    
    if ($statusFilter) {
        $where[] = "ar.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT ar.*, o.order_id, u.login as executor_login 
            FROM admin_request ar 
            JOIN order_ o ON ar.order_id = o.order_id
            JOIN user_ u ON ar.executor_id = u.id_user
            $whereClause
            ORDER BY ar.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
}

$pageTitle = 'Запросы от сотрудников — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <?php if ($requestId && $request): ?>
            <h2>Обработка запроса #<?= $requestId ?></h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Ответ успешно отправлен</div>
            <?php endif; ?>
            
            <div class="request-detail">
                <div class="detail-section">
                    <h3>Информация о запросе</h3>
                    <table class="info-table">
                        <tr>
                            <th>Заказ:</th>
                            <td><a href="order_detail.php?id=<?= $request['order_id'] ?>">#<?= $request['order_id'] ?></a></td>
                        </tr>
                        <tr>
                            <th>Исполнитель:</th>
                            <td><?= htmlspecialchars($request['executor_name'] ?: $request['executor_login']) ?></td>
                        </tr>
                        <tr>
                            <th>Тип запроса:</th>
                            <td><?= htmlspecialchars($request['request_type']) ?></td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td><span class="status-badge status-<?= $request['status'] ?>"><?= ucfirst($request['status']) ?></span></td>
                        </tr>
                        <tr>
                            <th>Создан:</th>
                            <td><?= formatDateTime($request['created_at']) ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="detail-section">
                    <h3>Текст запроса</h3>
                    <p><?= nl2br(htmlspecialchars($request['request_text'])) ?></p>
                </div>
                
                <?php if ($request['admin_response']): ?>
                <div class="detail-section">
                    <h3>Ответ администратора</h3>
                    <p><?= nl2br(htmlspecialchars($request['admin_response'])) ?></p>
                    <p><small>Дата ответа: <?= formatDateTime($request['resolved_at']) ?></small></p>
                </div>
                <?php endif; ?>
                
                <?php if ($request['status'] == 'pending'): ?>
                <div class="detail-section">
                    <h3>Ответить на запрос</h3>
                    <form method="POST" class="response-form">
                        <div class="form-group">
                            <label for="response">Ответ:</label>
                            <textarea name="response" id="response" rows="5" required placeholder="Введите ответ исполнителю..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Статус:</label>
                            <select name="status" id="status" required>
                                <option value="resolved">Решено</option>
                                <option value="rejected">Отклонено</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Отправить ответ</button>
                            <a href="requests.php" class="btn btn-secondary">Назад к списку</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h2>Запросы от исполнителей</h2>
            
            <div class="filters">
                <a href="requests.php" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">Все</a>
                <a href="requests.php?status=pending" class="btn btn-sm <?= $statusFilter == 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Ожидают</a>
                <a href="requests.php?status=resolved" class="btn btn-sm <?= $statusFilter == 'resolved' ? 'btn-primary' : 'btn-secondary' ?>">Решены</a>
                <a href="requests.php?status=rejected" class="btn btn-sm <?= $statusFilter == 'rejected' ? 'btn-primary' : 'btn-secondary' ?>">Отклонены</a>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заказ</th>
                        <th>Исполнитель</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $req): ?>
                        <tr>
                            <td><?= $req['request_id'] ?></td>
                            <td><a href="order_detail.php?id=<?= $req['order_id'] ?>">#<?= $req['order_id'] ?></a></td>
                            <td><?= htmlspecialchars($req['executor_login']) ?></td>
                            <td><?= htmlspecialchars($req['request_type']) ?></td>
                            <td><span class="status-badge status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                            <td><?= formatDateTime($req['created_at']) ?></td>
                            <td>
                                <a href="requests.php?id=<?= $req['request_id'] ?>" class="btn btn-sm btn-primary">Просмотреть</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($requests)): ?>
                <p class="empty-state">Запросы не найдены</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Назад</a>
        </div>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
