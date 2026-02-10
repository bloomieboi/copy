-- Добавление поля executor_id для привязки исполнителя к заказу
-- Это позволит отслеживать, какой сотрудник обрабатывает конкретный заказ

USE copypasteDB;

-- Добавляем поле executor_id в таблицу order_
ALTER TABLE `order_` 
ADD COLUMN `executor_id` INT NULL DEFAULT NULL COMMENT 'ID сотрудника-исполнителя' AFTER `user_id`,
ADD CONSTRAINT `fk_order_executor` 
    FOREIGN KEY (`executor_id`) 
    REFERENCES `user_` (`id_user`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- Создаем индекс для быстрого поиска заказов по исполнителю
CREATE INDEX `idx_executor_id` ON `order_` (`executor_id`);

-- Комментарий
SELECT 'Поле executor_id добавлено в таблицу order_. Теперь можно привязывать исполнителя к заказу.' AS result;

-- ВАЖНО: После выполнения этого скрипта администратор или система должны
-- назначать исполнителя на заказ при переводе в статус "В работе" (status_id = 6)
