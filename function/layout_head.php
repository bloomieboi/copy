<?php
/**
 * Подключение технологического стека (HTML5 + Bootstrap 5 + CSS3)
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Копицентр КопиПейст - печать, копирование, ламинирование документов">
<meta name="theme-color" content="#0d6efd">
<?php
$baseUrl = $baseUrl ?? '';
$base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
?>
<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="<?= $base ?>favicon.svg">
<link rel="alternate icon" type="image/x-icon" href="<?= $base ?>favicon.ico">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
      rel="stylesheet" 
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" 
      crossorigin="anonymous">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" 
      rel="stylesheet">
