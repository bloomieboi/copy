-- Миграция: скидочные карты уникальны для каждого пользователя
-- Каждый пользователь имеет не более одной скидочной карты

-- 1. Добавить поле user_id в discount_card (если не существует)
ALTER TABLE `discount_card`
  ADD COLUMN IF NOT EXISTS `user_id` INT NOT NULL DEFAULT 0 AFTER `discount_id`;

-- 2. Перенести данные из user_discount_card в discount_card
UPDATE `discount_card` dc
  JOIN `user_discount_card` udc ON dc.discount_id = udc.discount_id
  SET dc.user_id = udc.id_user
WHERE dc.user_id = 0;

-- 3. Удалить "осиротевшие" карты без владельца
DELETE FROM `discount_card` WHERE user_id = 0;

-- 4. Добавить уникальный индекс — один пользователь, одна карта
ALTER TABLE `discount_card`
  ADD UNIQUE KEY `uq_user_card` (`user_id`),
  ADD CONSTRAINT `fk_discount_card_user`
    FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

-- 5. Таблица user_discount_card больше не нужна, но сохраняем для совместимости
-- (при желании можно удалить командой ниже)
-- DROP TABLE IF EXISTS `user_discount_card`;

-- 6. Проверка результата
SELECT u.login, dc.discount_id, dc.number_of_bonuses
FROM `discount_card` dc
JOIN `user_` u ON dc.user_id = u.id_user
ORDER BY u.login;
