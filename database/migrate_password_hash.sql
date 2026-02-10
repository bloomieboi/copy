-- Миграция для обновления хранения паролей
-- Увеличиваем длину поля password для поддержки современных хешей

-- password_hash() генерирует строки длиной около 60 символов (bcrypt)
-- Для будущей совместимости используем VARCHAR(255)

USE copypasteDB;

-- Изменяем тип поля password
ALTER TABLE `user_` 
MODIFY COLUMN `password` VARCHAR(255) NOT NULL 
COMMENT 'Хеш пароля (password_hash с bcrypt)';

-- Комментарий к миграции
SELECT 'Поле password обновлено до VARCHAR(255) для поддержки password_hash()' AS result;

-- ВАЖНО: Старые пароли (SHA1, 40 символов) будут автоматически 
-- обновлены на новый формат при следующем входе пользователя в систему
