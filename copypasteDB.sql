-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.4:3306
-- Время создания: Фев 26 2026 г., 19:29
-- Версия сервера: 8.4.6
-- Версия PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `copypasteDB`
--

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `address`
-- (См. Ниже фактическое представление)
--
CREATE TABLE `address` (
`address_id` int
,`address_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Структура таблицы `address_deprecated`
--

CREATE TABLE `address_deprecated` (
  `address_id` int NOT NULL,
  `address_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `address_deprecated`
--

INSERT INTO `address_deprecated` (`address_id`, `address_name`) VALUES
(1, 'г. Екатеринбург, ул. Широкореченская 43'),
(3, 'г. Москва, ул. Гагарина, д. 5'),
(2, 'г. Москва, ул. Пушкина, д. 25');

-- --------------------------------------------------------

--
-- Структура таблицы `admin_request`
--

CREATE TABLE `admin_request` (
  `request_id` int NOT NULL,
  `order_id` int NOT NULL,
  `executor_id` int NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `request_text` text NOT NULL,
  `status` enum('pending','resolved','rejected') DEFAULT 'pending',
  `admin_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `discount_card`
--

CREATE TABLE `discount_card` (
  `discount_id` int NOT NULL,
  `user_id` int NOT NULL DEFAULT '0',
  `number_of_bonuses` int NOT NULL
) ;

--
-- Дамп данных таблицы `discount_card`
--

INSERT INTO `discount_card` (`discount_id`, `user_id`, `number_of_bonuses`) VALUES
(2, 5, 78);

-- --------------------------------------------------------

--
-- Структура таблицы `locations`
--

CREATE TABLE `locations` (
  `location_id` int NOT NULL,
  `location_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `working_hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Часы работы'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `address`, `phone`, `is_active`, `created_at`, `working_hours`) VALUES
(1, 'Копицентр на Ленина', 'г. Москва, ул. Ленина, д. 10', '+7 (495) 123-45-67', 1, '2026-02-09 16:13:52', NULL),
(2, 'Копицентр на Пушкина', 'г. Москва, ул. Пушкина, д. 25', '+7 (495) 234-56-78', 1, '2026-02-09 16:13:52', NULL),
(3, 'Копицентр на Гагарина', 'г. Москва, ул. Гагарина, д. 5', '+7 (495) 345-67-89', 1, '2026-02-09 16:13:52', NULL),
(4, 'Копицентр на Широкой', 'г. Екатеринбург, ул. Широкореченская 43', '+7 (922)-123-34-56', 1, '2026-02-11 08:46:23', '');

-- --------------------------------------------------------

--
-- Структура таблицы `message`
--

CREATE TABLE `message` (
  `message_id` int NOT NULL,
  `order_id` int NOT NULL,
  `from_user_id` int NOT NULL,
  `to_user_id` int NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `message`
--

INSERT INTO `message` (`message_id`, `order_id`, `from_user_id`, `to_user_id`, `message_text`, `created_at`, `is_read`) VALUES
(1, 5, 3, 1, 'ЛОЛ', '2026-02-02 05:16:34', 0),
(2, 8, 3, 1, 'распечатай лол', '2026-02-04 14:51:11', 0),
(3, 5, 4, 3, 'ладно бро', '2026-02-04 15:12:17', 0),
(4, 15, 3, 4, 'fghfhg', '2026-02-05 04:07:19', 1),
(6, 3, 4, 2, 'ахахахаххахах че не можешь да ответить', '2026-02-11 23:25:43', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `order_`
--

CREATE TABLE `order_` (
  `order_id` int NOT NULL,
  `service_list` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status_id` int NOT NULL,
  `user_id` int NOT NULL,
  `executor_id` int DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `order_`
--

INSERT INTO `order_` (`order_id`, `service_list`, `price`, `status_id`, `user_id`, `executor_id`, `created_date`) VALUES
(1, 'Печать документов (A3)', 20.00, 5, 1, NULL, '2026-01-27 21:28:18'),
(2, 'Печать документов (A3)', 20.00, 3, 2, 4, '2026-01-28 19:03:32'),
(3, 'Печать документов (A4) (количество: 1)', 10.00, 6, 2, 4, '2026-02-01 15:05:31'),
(4, 'Печать на кружках (количество: 3)', 1500.00, 2, 2, NULL, '2026-02-01 15:07:21'),
(5, 'Печать на кружках (количество: 1)', 500.00, 6, 3, 4, '2026-02-02 05:15:25'),
(6, 'Печать документов (A3) (количество: 5)', 100.00, 1, 1, NULL, '2026-02-02 05:40:35'),
(7, 'Ламинирование (количество: 1)', 50.00, 1, 1, NULL, '2026-02-04 04:03:53'),
(8, 'Печать документов (A4) (количество: 1)', 10.00, 2, 3, NULL, '2026-02-04 14:50:22'),
(9, 'Печать документов (A4) (количество: 1)', 10.00, 1, 4, NULL, '2026-02-04 15:21:12'),
(10, 'Печать документов (A4) (количество: 1)', 10.00, 1, 4, NULL, '2026-02-04 15:21:14'),
(11, 'Печать документов (A3) (количество: 1)', 20.00, 1, 4, NULL, '2026-02-04 15:21:18'),
(12, 'Печать документов (A4) (количество: 1)', 10.00, 3, 3, NULL, '2026-02-04 15:21:33'),
(13, 'Печать документов (A4) (количество: 1)', 10.00, 3, 3, NULL, '2026-02-04 15:28:59'),
(14, 'Печать документов (A4) (количество: 8)', 80.00, 1, 4, NULL, '2026-02-05 04:04:57'),
(15, 'Печать документов (A4) (количество: 6)', 60.00, 3, 3, NULL, '2026-02-05 04:05:37'),
(16, 'Печать на кружках (количество: 7)', 1488.00, 5, 5, NULL, '2026-02-10 03:39:46'),
(18, 'Печать документов (A3) (количество: 1)', 20.00, 1, 4, NULL, '2026-02-22 11:15:27');

-- --------------------------------------------------------

--
-- Структура таблицы `order_address`
--

CREATE TABLE `order_address` (
  `order_address_id` int NOT NULL,
  `order_id` int NOT NULL,
  `address_id` int NOT NULL,
  `location_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `order_address`
--

INSERT INTO `order_address` (`order_address_id`, `order_id`, `address_id`, `location_id`) VALUES
(3, 16, 1, 4),
(5, 18, 3, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `order_file`
--

CREATE TABLE `order_file` (
  `file_id` int NOT NULL,
  `order_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `order_file`
--

INSERT INTO `order_file` (`file_id`, `order_id`, `file_name`, `file_path`, `file_size`, `uploaded_at`, `uploaded_by`) VALUES
(1, 1, 'ROFLS.txt', 'uploads/orders/1/file_697a2149673b89.98128327_ROFLS.txt', 768, '2026-01-28 14:46:33', 1),
(2, 4, 'snapshot5743622107303290389.jpeg', 'uploads/orders/4/file_697f6d376ac884.44313203_snapshot5743622107303290389.jpeg', 36946, '2026-02-01 15:11:51', 2),
(3, 4, 'commandline.txt', 'uploads/orders/4/file_697f6d3bcc7b21.89218666_commandline.txt', 21, '2026-02-01 15:11:55', 2),
(4, 8, 'snapshot5743622107303290389.jpeg', 'uploads/orders/8/file_69835cba4dda27.49427319_snapshot5743622107303290389.jpeg', 36946, '2026-02-04 14:50:34', 3),
(5, 16, 'snapshot5743622107303290389.jpeg', 'uploads/orders/16/file_698aa88dd7f400.06462437_snapshot5743622107303290389.jpeg', 36946, '2026-02-10 03:39:57', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `order_log`
--

CREATE TABLE `order_log` (
  `log_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `order_log`
--

INSERT INTO `order_log` (`log_id`, `order_id`, `user_id`, `action_type`, `action_description`, `created_at`) VALUES
(1, 1, 1, 'file_uploaded', 'Клиент загрузил файл', '2026-01-28 14:46:33'),
(2, 2, 2, 'order_created', 'Клиент создал заказ', '2026-01-28 19:03:32'),
(3, 2, 2, 'payment_completed', 'Клиент оплатил заказ онлайн', '2026-01-30 09:44:10'),
(4, 3, 2, 'order_created', 'Клиент создал заказ', '2026-02-01 15:05:31'),
(5, 3, 2, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-01 15:06:35'),
(6, 4, 2, 'order_created', 'Клиент создал заказ', '2026-02-01 15:07:21'),
(7, 4, 2, 'file_uploaded', 'Клиент загрузил файл', '2026-02-01 15:11:51'),
(8, 4, 2, 'file_uploaded', 'Клиент загрузил файл', '2026-02-01 15:11:55'),
(9, 4, 2, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-01 15:12:54'),
(10, 1, 1, 'status_changed', 'Администратор изменил статус на Отменен', '2026-02-02 05:09:42'),
(11, 1, 1, 'admin_notify_employee', 'Ответственный сотрудник уведомлен об изменении статуса администратором.', '2026-02-02 05:09:42'),
(12, 5, 3, 'order_created', 'Клиент создал заказ', '2026-02-02 05:15:25'),
(13, 5, 3, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-02 05:16:21'),
(14, 5, 3, 'message_sent', 'Клиент отправил сообщение', '2026-02-02 05:16:34'),
(15, 6, 1, 'order_created', 'Клиент создал заказ', '2026-02-02 05:40:35'),
(16, 7, 1, 'order_created', 'Клиент создал заказ', '2026-02-04 04:03:53'),
(17, 8, 3, 'order_created', 'Клиент создал заказ', '2026-02-04 14:50:22'),
(18, 8, 3, 'file_uploaded', 'Клиент загрузил файл', '2026-02-04 14:50:34'),
(19, 8, 3, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-04 14:50:42'),
(20, 8, 3, 'message_sent', 'Клиент отправил сообщение', '2026-02-04 14:51:11'),
(21, 5, 4, 'message_sent', 'Сотрудник отправил сообщение клиенту', '2026-02-04 15:12:17'),
(22, 9, 4, 'order_created', 'Клиент создал заказ', '2026-02-04 15:21:12'),
(23, 10, 4, 'order_created', 'Клиент создал заказ', '2026-02-04 15:21:14'),
(24, 11, 4, 'order_created', 'Клиент создал заказ', '2026-02-04 15:21:18'),
(25, 12, 3, 'order_created', 'Клиент создал заказ', '2026-02-04 15:21:33'),
(26, 12, 3, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-04 15:21:42'),
(27, 13, 3, 'order_created', 'Клиент создал заказ', '2026-02-04 15:28:59'),
(28, 13, 3, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-04 15:29:04'),
(29, 14, 4, 'order_created', 'Клиент создал заказ', '2026-02-05 04:04:57'),
(30, 15, 3, 'order_created', 'Клиент создал заказ', '2026-02-05 04:05:37'),
(31, 15, 3, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-05 04:06:23'),
(32, 15, 3, 'message_sent', 'Клиент отправил сообщение', '2026-02-05 04:07:19'),
(33, 15, 4, 'status_changed', 'Сотрудник изменил статус на Завершен', '2026-02-05 04:07:50'),
(34, 13, 4, 'employee_comment', 'сделал!', '2026-02-05 17:59:48'),
(35, 13, 4, 'status_changed', 'Сотрудник изменил статус на Завершен', '2026-02-05 17:59:48'),
(36, 13, 4, 'order_completed', 'Заказ завершен, время завершения: 05.02.2026 22:59', '2026-02-05 17:59:48'),
(37, 16, 5, 'order_created', 'Клиент создал заказ', '2026-02-10 03:39:46'),
(38, 16, 5, 'file_uploaded', 'Клиент загрузил файл', '2026-02-10 03:39:57'),
(39, 16, 5, 'payment_completed', 'Клиент оплатил заказ по карте в приложении', '2026-02-10 03:49:34'),
(40, 16, 4, 'employee_comment', 'нУ кароч кружка ращзбилась', '2026-02-10 03:50:18'),
(41, 16, 4, 'status_changed', 'Сотрудник изменил статус на Отменен', '2026-02-10 03:50:18'),
(42, 16, 4, 'order_cancelled', 'Заказ отменен, время отмены: 10.02.2026 08:50', '2026-02-10 03:50:18'),
(47, 2, 4, 'order_taken', 'Сотрудник взял заказ в работу', '2026-02-11 23:18:51'),
(48, 2, 4, 'employee_comment', 'найс распечатал', '2026-02-11 23:19:19'),
(49, 2, 4, 'status_changed', 'Сотрудник изменил статус на Завершен', '2026-02-11 23:19:19'),
(50, 2, 4, 'order_completed', 'Заказ завершен, время завершения: 12.02.2026 04:19', '2026-02-11 23:19:19'),
(51, 3, 4, 'order_taken', 'Сотрудник взял заказ в работу', '2026-02-11 23:20:04'),
(52, 3, 4, 'message_sent', 'Сотрудник отправил сообщение клиенту', '2026-02-11 23:25:43'),
(53, 5, 4, 'order_taken', 'Сотрудник взял заказ в работу', '2026-02-11 23:26:22'),
(54, 18, 4, 'order_created', 'Клиент создал заказ', '2026-02-22 11:15:27');

-- --------------------------------------------------------

--
-- Структура таблицы `payment`
--

CREATE TABLE `payment` (
  `payment_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `payment_method_id` int DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payment_method`
--

CREATE TABLE `payment_method` (
  `payment_id` int NOT NULL,
  `card_number` varchar(16) NOT NULL,
  `id_user` int NOT NULL
) ;

--
-- Дамп данных таблицы `payment_method`
--

INSERT INTO `payment_method` (`payment_id`, `card_number`, `id_user`) VALUES
(1, '1234567890123456', 2),
(2, '8888888888888888', 2),
(3, '1111111111111111', 3),
(4, '1488677842526939', 5),
(5, '9999999922221111', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `review`
--

CREATE TABLE `review` (
  `review_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Структура таблицы `role_`
--

CREATE TABLE `role_` (
  `role_id` int NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `role_`
--

INSERT INTO `role_` (`role_id`, `role_name`) VALUES
(3, 'Администратор'),
(1, 'Клиент'),
(2, 'Сотрудник');

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `service_id` int NOT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `base_price` decimal(10,2) NOT NULL,
  `location_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_offline` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 - услуга только оффлайн, 0 - можно заказать онлайн'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `base_price`, `location_id`, `is_active`, `created_at`, `is_offline`) VALUES
(1, 'Печать документов (A4)', 'Оперативная ч/б и цветная печать документов формата A4.', 10.00, NULL, 1, '2026-02-09 16:13:52', 0),
(2, 'Печать документов (A3)', 'Печать документов большого формата A3.', 20.00, NULL, 1, '2026-02-09 16:13:52', 0),
(3, 'Печать на кружках', 'Создание индивидуальных принтов на керамических кружках.', 500.00, NULL, 1, '2026-02-09 16:13:52', 0),
(5, 'Ламинирование', 'Защита документов ламинацией.', 50.00, NULL, 1, '2026-02-09 16:13:52', 0),
(7, 'Ксерокопия', 'Делаем ксерокопии', 5.00, NULL, 1, '2026-02-22 10:49:05', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `status`
--

CREATE TABLE `status` (
  `status_id` int NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `status`
--

INSERT INTO `status` (`status_id`, `status_name`) VALUES
(1, 'В процессе оплаты'),
(6, 'В работе'),
(3, 'Завершен'),
(4, 'Закрыт'),
(2, 'Оплачен'),
(5, 'Отменен');

-- --------------------------------------------------------

--
-- Структура таблицы `user_`
--

CREATE TABLE `user_` (
  `id_user` int NOT NULL,
  `login` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(11) NOT NULL,
  `role_id` int NOT NULL,
  `location_id` int DEFAULT NULL COMMENT 'ID закрепленного копицентра для сотрудника'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_`
--

INSERT INTO `user_` (`id_user`, `login`, `password`, `phone_number`, `role_id`, `location_id`) VALUES
(1, 'bob', '$2y$12$KeremgTzaSVZGFeEHpw7cOkwbDEFFRZJUT9JFz/76J7m83mHpZ9eG', '79996666778', 3, NULL),
(2, 'skebob', '787878', '79696669669', 1, NULL),
(3, 'lol', '$2y$12$DR/pwnIzstvDXf4wX/nVIOC7EoVZ4Mx7QtXeCfv9kIdmILwIJUKxy', '79999999999', 1, NULL),
(4, 'pop', '$2y$12$2eAt0ykInJk11UxfWSkgOezBNUjZWtyVMxYZcGfrI1k8Lt06u19Nu', '79888888888', 2, 3),
(5, 'Gamemode1', '$2y$12$/RsAFl1XTSEsn.Nj1DzBv.wZhGo5XRsa32u1LJnHhTX6oUOgJxniK', '89655113090', 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_discount_card`
--

CREATE TABLE `user_discount_card` (
  `user_discount_id` int NOT NULL,
  `id_user` int NOT NULL,
  `discount_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `address_deprecated`
--
ALTER TABLE `address_deprecated`
  ADD PRIMARY KEY (`address_id`),
  ADD UNIQUE KEY `address_name` (`address_name`);

--
-- Индексы таблицы `admin_request`
--
ALTER TABLE `admin_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `executor_id` (`executor_id`),
  ADD KEY `idx_admin_req_status` (`status`),
  ADD KEY `idx_admin_req_order` (`order_id`);

--
-- Индексы таблицы `discount_card`
--
ALTER TABLE `discount_card`
  ADD PRIMARY KEY (`discount_id`),
  ADD UNIQUE KEY `uq_user_card` (`user_id`);

--
-- Индексы таблицы `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Индексы таблицы `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `idx_message_order` (`order_id`),
  ADD KEY `idx_message_to` (`to_user_id`,`is_read`);

--
-- Индексы таблицы `order_`
--
ALTER TABLE `order_`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_executor_id` (`executor_id`);

--
-- Индексы таблицы `order_address`
--
ALTER TABLE `order_address`
  ADD PRIMARY KEY (`order_address_id`),
  ADD UNIQUE KEY `unique_order_address` (`order_id`,`address_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `idx_location_id` (`location_id`);

--
-- Индексы таблицы `order_file`
--
ALTER TABLE `order_file`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_order_file_order` (`order_id`);

--
-- Индексы таблицы `order_log`
--
ALTER TABLE `order_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_order_log_order` (`order_id`);

--
-- Индексы таблицы `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `idx_payment_order` (`order_id`),
  ADD KEY `idx_payment_user` (`user_id`);

--
-- Индексы таблицы `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `card_number` (`card_number`),
  ADD KEY `id_user` (`id_user`);

--
-- Индексы таблицы `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_order_user` (`order_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `role_`
--
ALTER TABLE `role_`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `idx_location` (`location_id`);

--
-- Индексы таблицы `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Индексы таблицы `user_`
--
ALTER TABLE `user_`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `fk_user_location` (`location_id`);

--
-- Индексы таблицы `user_discount_card`
--
ALTER TABLE `user_discount_card`
  ADD PRIMARY KEY (`user_discount_id`),
  ADD UNIQUE KEY `unique_user_discount` (`id_user`,`discount_id`),
  ADD KEY `discount_id` (`discount_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `address_deprecated`
--
ALTER TABLE `address_deprecated`
  MODIFY `address_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `admin_request`
--
ALTER TABLE `admin_request`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `discount_card`
--
ALTER TABLE `discount_card`
  MODIFY `discount_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `order_`
--
ALTER TABLE `order_`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_address`
--
ALTER TABLE `order_address`
  MODIFY `order_address_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `order_file`
--
ALTER TABLE `order_file`
  MODIFY `file_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `order_log`
--
ALTER TABLE `order_log`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT для таблицы `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `role_`
--
ALTER TABLE `role_`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `user_`
--
ALTER TABLE `user_`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `user_discount_card`
--
ALTER TABLE `user_discount_card`
  MODIFY `user_discount_id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Структура для представления `address`
--
DROP TABLE IF EXISTS `address`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `address`  AS SELECT `locations`.`location_id` AS `address_id`, `locations`.`location_name` AS `address_name` FROM `locations` WHERE (`locations`.`is_active` = 1) ;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `admin_request`
--
ALTER TABLE `admin_request`
  ADD CONSTRAINT `admin_request_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_request_ibfk_2` FOREIGN KEY (`executor_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `discount_card`
--
ALTER TABLE `discount_card`
  ADD CONSTRAINT `fk_discount_card_user` FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`from_user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_3` FOREIGN KEY (`to_user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_`
--
ALTER TABLE `order_`
  ADD CONSTRAINT `fk_order_executor` FOREIGN KEY (`executor_id`) REFERENCES `user_` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `order__ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `order__ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `order_address`
--
ALTER TABLE `order_address`
  ADD CONSTRAINT `order_address_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_address_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `address_deprecated` (`address_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_file`
--
ALTER TABLE `order_file`
  ADD CONSTRAINT `order_file_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_file_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `user_` (`id_user`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `order_log`
--
ALTER TABLE `order_log`
  ADD CONSTRAINT `order_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`payment_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `payment_method`
--
ALTER TABLE `payment_method`
  ADD CONSTRAINT `payment_method_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_`
--
ALTER TABLE `user_`
  ADD CONSTRAINT `fk_user_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `user__ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role_` (`role_id`) ON DELETE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `user_discount_card`
--
ALTER TABLE `user_discount_card`
  ADD CONSTRAINT `user_discount_card_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user_` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_discount_card_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `discount_card` (`discount_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
