-- Добавление поля location_id для привязки сотрудника к копицентру
-- Это позволит ограничить видимость заказов и создание новых заказов для сотрудников

USE copypasteDB;

-- Добавляем поле location_id в таблицу user_
ALTER TABLE `user_`
ADD COLUMN `location_id` INT NULL DEFAULT NULL COMMENT 'ID закрепленного копицентра для сотрудника' AFTER `role_id`,
ADD CONSTRAINT `fk_user_location`
    FOREIGN KEY (`location_id`)
    REFERENCES `locations` (`location_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;