<?php
/**
 * Сценарий 10: Администрирование учетных записей.
 * Окно управления учетными записями: список пользователей с ролями, поиск по имени/логину и роли.
 */
require_once __DIR__ . '/../function/connect.php';
require_once __DIR__ . '/../function/auth.php';
require_once __DIR__ . '/../function/helpers.php';
session_start();
requireRole(3);

$roleFilter = $_GET['role'] ?? null;
$searchQuery = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($roleFilter !== null && $roleFilter !== '') {
    $where[] = "u.role_id = ?";
    $params[] = $roleFilter;
}
if ($searchQuery !== '') {
    $where[] = "(u.login LIKE ? OR u.phone_number LIKE ?)";
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT u.*, r.role_name FROM user_ u 
        JOIN role_ r ON u.role_id = r.role_id 
        $whereClause
        ORDER BY u.id_user DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM role_ ORDER BY role_id")->fetchAll();

$pageTitle = 'Управление учетными записями — КопиПейст';
$baseUrl = '..';
require_once __DIR__ . '/../function/layout_start.php';
?>
        <h2>Управление учетными записями</h2>
        <p class="form-hint">Список зарегистрированных пользователей с указанием ролей. Поиск по имени (логину), телефону или фильтр по роли.</p>
        
        <form method="GET" class="filters-form" style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-bottom:1rem;">
            <input type="text" name="search" placeholder="Поиск по имени (логину) или телефону" value="<?= htmlspecialchars($searchQuery) ?>" style="max-width:260px;">
            <?php if ($roleFilter !== null && $roleFilter !== ''): ?>
                <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-sm btn-primary">Найти</button>
            <a href="users.php" class="btn btn-sm btn-secondary">Сбросить</a>
        </form>
        
        <div class="filters">
            <a href="users.php<?= $searchQuery ? '?search=' . rawurlencode($searchQuery) : '' ?>" class="btn btn-sm <?= ($roleFilter === null || $roleFilter === '') ? 'btn-primary' : 'btn-secondary' ?>">Все</a>
            <?php foreach($roles as $role): ?>
                <a href="users.php?role=<?= $role['role_id'] ?><?= $searchQuery ? '&search=' . rawurlencode($searchQuery) : '' ?>" class="btn btn-sm <?= $roleFilter == $role['role_id'] ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= htmlspecialchars($role['role_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>Телефон</th>
                    <th>Роль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                    <tr>
                        <td><?= $user['id_user'] ?></td>
                        <td><?= htmlspecialchars($user['login']) ?></td>
                        <td><?= htmlspecialchars($user['phone_number']) ?></td>
                        <td><?= htmlspecialchars($user['role_name']) ?></td>
                        <td>
                            <a href="user_edit.php?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($users)): ?>
            <p class="empty-state">Пользователи не найдены</p>
        <?php endif; ?>
<?php require_once __DIR__ . '/../function/layout_end.php'; ?>
