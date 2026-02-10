-- Дополнительные таблицы для системы КопиПейст
-- Выполните этот скрипт, если таблицы отсутствуют

-- Таблица локаций (точек обслуживания)
CREATE TABLE IF NOT EXISTS `locations` (
  `location_id` INT NOT NULL AUTO_INCREMENT,
  `location_name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(500) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица услуг
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` INT NOT NULL AUTO_INCREMENT,
  `service_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `location_id` INT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  KEY `idx_location` (`location_id`),
  CONSTRAINT `fk_services_location` FOREIGN KEY (`location_id`) 
    REFERENCES `locations` (`location_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица адресов организации
CREATE TABLE IF NOT EXISTS `address` (
  `address_id` INT NOT NULL AUTO_INCREMENT,
  `address_name` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица связи заказов с адресами
CREATE TABLE IF NOT EXISTS `order_address` (
  `order_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_address` (`address_id`),
  CONSTRAINT `fk_order_address_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_address_address` FOREIGN KEY (`address_id`) 
    REFERENCES `address` (`address_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов заказов
CREATE TABLE IF NOT EXISTS `order_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `action_type` VARCHAR(100) NOT NULL,
  `action_description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_order_log_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_log_user` FOREIGN KEY (`user_id`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица файлов заказов
CREATE TABLE IF NOT EXISTS `order_file` (
  `file_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `uploaded_by` INT NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`file_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_order_file_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_file_user` FOREIGN KEY (`uploaded_by`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сообщений (чат)
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `from_user_id` INT NOT NULL,
  `to_user_id` INT NOT NULL,
  `message_text` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_from_user` (`from_user_id`),
  KEY `idx_to_user` (`to_user_id`),
  CONSTRAINT `fk_message_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_from_user` FOREIGN KEY (`from_user_id`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_to_user` FOREIGN KEY (`to_user_id`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов
CREATE TABLE IF NOT EXISTS `review` (
  `review_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_order_review` (`order_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_review_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица скидочных карт
CREATE TABLE IF NOT EXISTS `discount_card` (
  `discount_id` INT NOT NULL AUTO_INCREMENT,
  `number_of_bonuses` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`discount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица связи пользователей со скидочными картами
CREATE TABLE IF NOT EXISTS `user_discount_card` (
  `id_user` INT NOT NULL,
  `discount_id` INT NOT NULL,
  PRIMARY KEY (`id_user`, `discount_id`),
  KEY `idx_discount` (`discount_id`),
  CONSTRAINT `fk_user_discount_user` FOREIGN KEY (`id_user`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_discount_card` FOREIGN KEY (`discount_id`) 
    REFERENCES `discount_card` (`discount_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица способов оплаты
CREATE TABLE IF NOT EXISTS `payment_method` (
  `payment_id` INT NOT NULL AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `card_number` VARCHAR(16) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `idx_user` (`id_user`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`id_user`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `chk_card_length` CHECK (CHAR_LENGTH(`card_number`) = 16)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица запросов к администратору
CREATE TABLE IF NOT EXISTS `admin_request` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `executor_id` INT NOT NULL,
  `request_type` VARCHAR(100) NOT NULL,
  `request_text` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
  `admin_response` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_executor` (`executor_id`),
  CONSTRAINT `fk_admin_request_order` FOREIGN KEY (`order_id`) 
    REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_admin_request_executor` FOREIGN KEY (`executor_id`) 
    REFERENCES `user_` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка тестовых данных для локаций
INSERT IGNORE INTO `locations` (`location_id`, `location_name`, `address`, `phone`, `is_active`) VALUES
(1, 'Копицентр на Ленина', 'г. Москва, ул. Ленина, д. 10', '+7 (495) 123-45-67', 1),
(2, 'Копицентр на Пушкина', 'г. Москва, ул. Пушкина, д. 25', '+7 (495) 234-56-78', 1),
(3, 'Копицентр на Гагарина', 'г. Москва, ул. Гагарина, д. 5', '+7 (495) 345-67-89', 1);

-- Вставка тестовых данных для адресов
INSERT IGNORE INTO `address` (`address_id`, `address_name`) VALUES
(1, 'г. Москва, ул. Ленина, д. 10'),
(2, 'г. Москва, ул. Пушкина, д. 25'),
(3, 'г. Москва, ул. Гагарина, д. 5');

-- Вставка тестовых данных для услуг
INSERT IGNORE INTO `services` (`service_id`, `service_name`, `description`, `base_price`, `location_id`, `is_active`) VALUES
(1, 'Печать документов (A4)', 'Оперативная ч/б и цветная печать документов формата A4.', 10.00, NULL, 1),
(2, 'Печать документов (A3)', 'Печать документов большого формата A3.', 20.00, NULL, 1),
(3, 'Печать на кружках', 'Создание индивидуальных принтов на керамических кружках.', 500.00, NULL, 1),
(4, 'Ксерокопия', 'Копирование документов.', 5.00, NULL, 1),
(5, 'Ламинирование', 'Защита документов ламинацией.', 50.00, NULL, 1);

-- Проверка успешности создания таблиц
SELECT 'Таблицы успешно созданы!' AS result;
