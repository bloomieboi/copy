<?php
/**
 * Единый старт страницы: head, Bootstrap, шапка с навигацией.
 * Перед подключением задать: $pageTitle (обязательно), $baseUrl ('' для корня, '..' для вложенных).
 */
$baseUrl = $baseUrl ?? '';
$base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'КопиПейст') ?></title>
    <?php require_once __DIR__ . '/layout_head.php'; ?>
    <link rel="stylesheet" href="<?= $base ?>css/style.css">
    <link rel="stylesheet" href="<?= $base ?>css/components.css">
</head>
<body class="site-body d-flex flex-column min-vh-100">
    <header class="site-header navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= $base ?>index.php">КопиПейст</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <nav class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php $__roleId = $_SESSION['role_id'] ?? 0; ?>
                        <?php if ($__roleId == 1): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= $base ?>profile/index.php">Личный кабинет</a></li>
                        <?php elseif ($__roleId == 2): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= $base ?>employee/profile.php">Личный кабинет</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= $base ?>employee/index.php">Панель сотрудника</a></li>
                        <?php elseif ($__roleId >= 3): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/profile.php">Личный кабинет</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/index.php">Панель администратора</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>logout.php">Выход</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>login/index.php">Вход</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>register/index.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="site-main container flex-grow-1 py-4">
