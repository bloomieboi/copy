<?php

declare(strict_types=1);

/**
 * Генерация <head> секции HTML-документа.
 */

$baseUrl = $baseUrl ?? '.';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'КопиПейст' ?></title>

<!-- Fix for missing favicon.ico to prevent 404 errors -->
<link rel="icon" href="data:,">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css">