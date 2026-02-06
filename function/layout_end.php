<?php
/**
 * Единое завершение страницы: подвал, скрипты.
 */
$baseUrl = $baseUrl ?? '';
?>
    </main>
    <footer class="site-footer bg-light py-3 mt-auto border-top">
        <div class="container">
            <p class="mb-0 text-muted small">&copy; <?= date('Y') ?> ООО «КопиПейст». Все права защищены.</p>
        </div>
    </footer>
    <?php require_once __DIR__ . '/layout_footer.php'; ?>
</body>
</html>
