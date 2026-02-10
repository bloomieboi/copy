<?php
/**
 * Подключение скриптов (Bootstrap 5 + Alpine.js 3)
 */
$baseUrl = $baseUrl ?? '';
$base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
?>
<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
        crossorigin="anonymous"></script>

<!-- Alpine.js 3 -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

<!-- Кастомные скрипты -->
<script src="<?= $base ?>js/app.js"></script>
