-- Миграция: Объединение address и locations в единую таблицу locations
-- Дата: 2026-02-10
-- Описание: Адреса и точки обслуживания - это одно и то же

USE copypasteDB;

-- Шаг 1: Убедимся, что таблица locations существует и имеет нужные поля
ALTER TABLE `locations` 
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) NULL COMMENT 'Телефон точки обслуживания',
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Активна ли точка',
ADD COLUMN IF NOT EXISTS `working_hours` VARCHAR(100) NULL COMMENT 'Часы работы';

-- Шаг 2: Мигрируем данные из address в locations (если есть уникальные адреса)
INSERT INTO `locations` (`location_name`, `address`, `is_active`)
SELECT 
    a.address_name as location_name,
    a.address_name as address,
    1 as is_active
FROM `address` a
WHERE NOT EXISTS (
    SELECT 1 FROM `locations` l 
    WHERE l.address = a.address_name COLLATE utf8mb4_unicode_ci
       OR l.location_name = a.address_name COLLATE utf8mb4_unicode_ci
)
ON DUPLICATE KEY UPDATE location_name = location_name;

-- Шаг 3: Обновляем ссылки в order_address
-- Создаем временную таблицу для маппинга
CREATE TEMPORARY TABLE IF NOT EXISTS address_location_map AS
SELECT 
    a.address_id,
    COALESCE(
        (SELECT location_id FROM locations WHERE address = a.address_name COLLATE utf8mb4_unicode_ci LIMIT 1),
        (SELECT location_id FROM locations WHERE location_name = a.address_name COLLATE utf8mb4_unicode_ci LIMIT 1)
    ) as location_id
FROM address a;

-- Добавляем поле location_id в order_address, если его нет
ALTER TABLE `order_address` 
ADD COLUMN IF NOT EXISTS `location_id` INT NULL COMMENT 'ID точки обслуживания' AFTER `address_id`,
ADD KEY IF NOT EXISTS `idx_location_id` (`location_id`);

-- Заполняем location_id на основе address_id
UPDATE `order_address` oa
JOIN address_location_map alm ON oa.address_id = alm.address_id
SET oa.location_id = alm.location_id
WHERE oa.location_id IS NULL;

-- Шаг 4: Переименовываем таблицу address в address_deprecated (для безопасности)
-- НЕ удаляем сразу, а переименовываем на случай отката
RENAME TABLE `address` TO `address_deprecated`;

-- Шаг 5: Создаем VIEW address для обратной совместимости
CREATE OR REPLACE VIEW `address` AS
SELECT 
    location_id as address_id,
    location_name as address_name
FROM `locations`
WHERE is_active = 1;

-- Комментарии
SELECT 'Миграция завершена!' as status,
       'Таблица address переименована в address_deprecated' as info1,
       'Создан VIEW address для обратной совместимости' as info2,
       'Используйте таблицу locations для всех операций' as recommendation;

-- ВАЖНО:
-- 1. Проверьте корректность миграции данных
-- 2. Обновите все запросы для использования locations вместо address
-- 3. После проверки можно удалить address_deprecated: DROP TABLE address_deprecated;
