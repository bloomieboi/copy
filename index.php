<?php
require_once 'function/connect.php';
require_once 'function/helpers.php';
require_once 'function/auth.php';
session_start();

$pageTitle = 'КопиПейст — Каталог услуг';
$baseUrl = '';
require_once __DIR__ . '/function/layout_start.php';
?>
        <section class="services">
            <h2>Каталог услуг</h2>
            <p class="form-hint">
                Все услуги оказываются в наших офисах. При оформлении заказа вы выберете удобный для вас адрес обслуживания.
            </p>
            
            <!-- Поиск по услугам -->
            <div class="mb-4" x-data="{ search: '' }">
                <div class="input-group input-group-lg">
                    <span class="input-group-text">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" 
                           class="form-control" 
                           placeholder="Поиск услуг..."
                           x-model="search"
                           @input.debounce.300ms="filterServices">
                </div>
            </div>
            <?php
            // Услуги: из БД (таблица services), при отсутствии таблицы — встроенный список
            $services = [];
            try {
                $services = $pdo->query("SELECT * FROM services ORDER BY service_name")->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Таблицы services нет — используем встроенный каталог
                $services = [
                    ['service_id' => 0, 'service_name' => 'Печать документов (A4)', 'description' => 'Оперативная ч/б и цветная печать документов формата A4.', 'base_price' => 10, 'is_active' => 1],
                    ['service_id' => 0, 'service_name' => 'Печать документов (A3)', 'description' => 'Печать документов большого формата A3.', 'base_price' => 20, 'is_active' => 1],
                    ['service_id' => 0, 'service_name' => 'Печать на кружках', 'description' => 'Создание индивидуальных принтов на керамических кружках.', 'base_price' => 500, 'is_active' => 1],
                    ['service_id' => 0, 'service_name' => 'Ксерокопия', 'description' => 'Копирование документов.', 'base_price' => 5, 'is_active' => 1],
                    ['service_id' => 0, 'service_name' => 'Ламинирование', 'description' => 'Защита документов ламинацией.', 'base_price' => 50, 'is_active' => 1],
                ];
            }
            if (empty($services)):
            ?>
                <p class="empty-state">В данный момент услуги недоступны. Пожалуйста, обратитесь к администратору.</p>
            <?php else: ?>
                <div class="services-grid">
                    <?php foreach($services as $service):
                        $isAvailable = !empty($service['is_active']);
                        $hasId = !empty($service['service_id']);
                        $orderUrl = $hasId
                            ? 'orders/create.php?service_id=' . (int)$service['service_id']
                            : 'orders/create.php?service=' . rawurlencode($service['service_name']) . '&price=' . (float)$service['base_price'];
                    ?>
                        <div class="service-item <?= !$isAvailable ? 'service-unavailable' : '' ?>">
                            <h3><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p><?= htmlspecialchars($service['description'] ?? '') ?></p>
                            <div class="service-footer">
                                <span class="price"><?= formatPrice($service['base_price']) ?></span>
                                <?php if (!$isAvailable): ?>
                                    <span class="btn btn-disabled" title="Услуга временно недоступна">Недоступна</span>
                                <?php elseif(isset($_SESSION['user_id'])): ?>
                                    <a href="<?= $orderUrl ?>" class="btn btn-primary">Заказать</a>
                                <?php else: ?>
                                    <a href="login/index.php" class="btn btn-secondary">Войти для заказа</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <script>
        // Поиск по услугам
        function filterServices() {
            const search = document.querySelector('input[x-model="search"]')?.value?.toLowerCase() || '';
            const serviceItems = document.querySelectorAll('.service-item');
            
            serviceItems.forEach(item => {
                const title = item.querySelector('h3')?.textContent?.toLowerCase() || '';
                const description = item.querySelector('p')?.textContent?.toLowerCase() || '';
                
                const matches = title.includes(search) || description.includes(search);
                item.style.display = matches ? '' : 'none';
                
                if (matches && search) {
                    item.style.animation = 'fadeIn 0.3s ease-in';
                }
            });
        }
        </script>
        
        <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        </style>
<?php require_once __DIR__ . '/function/layout_end.php'; ?>
