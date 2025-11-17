-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Ноя 17 2025 г., 12:50
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- База данных: `tour_operator`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin_setting`
--

CREATE TABLE `admin_setting` (
  `id` int(10) UNSIGNED NOT NULL,
  `skey` varchar(100) NOT NULL,
  `svalue` longtext NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admin_setting`
--

INSERT INTO `admin_setting` (`id`, `skey`, `svalue`, `updated_at`) VALUES
(1, 'security.password_policy', '{ \"min_length\": 8, \"require_digits\": true }', NULL),
(2, 'backup.policy', '{ \"enabled\": true, \"retention_days\": 14 }', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `auth_log`
--

CREATE TABLE `auth_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attempted_login` varchar(64) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `is_success` tinyint(1) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `backup_log`
--

CREATE TABLE `backup_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `status` enum('started','success','failed') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `destination`
--

CREATE TABLE `destination` (
  `id` int(10) UNSIGNED NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `destination`
--

INSERT INTO `destination` (`id`, `country`, `city`, `description`) VALUES
(1, 'Россия', 'Сочи', 'Курорт на Чёрном море'),
(2, 'Турция', 'Анталья', 'Пляжный отдых, all inclusive');

-- --------------------------------------------------------

--
-- Структура таблицы `email_queue`
--

CREATE TABLE `email_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_text` text NOT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `error_msg` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `integration`
--

CREATE TABLE `integration` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('payment','email','marketing','analytics') NOT NULL,
  `name` varchar(100) NOT NULL,
  `config` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `buyer_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tour_date_id` bigint(20) UNSIGNED NOT NULL,
  `participants_count` int(10) UNSIGNED NOT NULL,
  `preferences` text DEFAULT NULL,
  `email_to` varchar(255) NOT NULL,
  `payment_method_id` int(10) UNSIGNED NOT NULL,
  `status` enum('new','confirmed','paid','cancelled') NOT NULL DEFAULT 'new',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Триггеры `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_orders_bi_chk` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
  IF NEW.email_to NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'orders.email_to: некорректный e-mail';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_orders_bu_chk` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
  IF NEW.email_to NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'orders.email_to: некорректный e-mail';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `method_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'RUB',
  `status` enum('initiated','authorized','captured','failed','refunded') NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payment_method`
--

CREATE TABLE `payment_method` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `payment_method`
--

INSERT INTO `payment_method` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'card', 'Банковская карта', 1),
(2, 'cash', 'Наличный расчёт', 1),
(3, 'bank_transfer', 'Перевод по реквизитам', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `name`) VALUES
(1, 'tours.manage', 'Управление турпакетами и датами выезда'),
(2, 'orders.manage', 'Управление заявками и статусами бронирований'),
(3, 'reports.view', 'Просмотр отчётности по заявкам и продажам'),
(4, 'users.manage', 'Управление пользователями и ролями'),
(5, 'settings.edit', 'Изменение системных настроек');

-- --------------------------------------------------------

--
-- Структура таблицы `review`
--

CREATE TABLE `review` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `tour_id` bigint(20) UNSIGNED NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`) VALUES
(1, 'admin', 'Администратор системы'),
(2, 'manager', 'Менеджер туроператора'),
(3, 'client', 'Клиент');

-- --------------------------------------------------------

--
-- Структура таблицы `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 1),
(2, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Структура таблицы `tour_date`
--

CREATE TABLE `tour_date` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tour_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `available_seats` int(10) UNSIGNED NOT NULL,
  `price_per_person` decimal(12,2) NOT NULL,
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tour_date`
--

INSERT INTO `tour_date` (`id`, `tour_id`, `start_date`, `end_date`, `available_seats`, `price_per_person`, `status`) VALUES
(1, 1, '2025-06-01', '2025-06-08', 20, 46000.00, 'open'),
(2, 2, '2025-07-10', '2025-07-17', 15, 57000.00, 'open');

-- --------------------------------------------------------

--
-- Структура таблицы `tour_package`
--

CREATE TABLE `tour_package` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `destination_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `route_description` text DEFAULT NULL,
  `duration_days` int(10) UNSIGNED NOT NULL,
  `base_price` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tour_package`
--

INSERT INTO `tour_package` (`id`, `destination_id`, `title`, `description`, `route_description`, `duration_days`, `base_price`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Сочи: море и горы', 'Классический тур с экскурсиями и отдыхом на море.', 'День 1–2: Сочи, день 3–4: Красная Поляна, день 5–7: отдых на море.', 7, 45000.00, 1, '2025-11-17 11:35:44', NULL),
(2, 2, 'Анталья all inclusive', 'Отдых по системе всё включено.', 'Перелёт, трансфер, проживание в отеле 4*/5* с питанием all inclusive.', 7, 55000.00, 1, '2025-11-17 11:35:44', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `login` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Триггеры `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_bi_chk` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
  IF NEW.login NOT REGEXP '^[A-Za-z0-9]{6,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'login: только латиница/цифры, ≥6 символов';
  END IF;
  IF NEW.phone NOT REGEXP '^8\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'phone: формат 8(XXX)XXX-XX-XX';
  END IF;
  IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'email: некорректный формат';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_bu_chk` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
  IF NEW.login NOT REGEXP '^[A-Za-z0-9]{6,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'login: только латиница/цифры, ≥6 символов';
  END IF;
  IF NEW.phone NOT REGEXP '^8\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'phone: формат 8(XXX)XXX-XX-XX';
  END IF;
  IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'email: некорректный формат';
  END IF;
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admin_setting`
--
ALTER TABLE `admin_setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skey` (`skey`);

--
-- Индексы таблицы `auth_log`
--
ALTER TABLE `auth_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempted_login` (`attempted_login`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `fk_authlog_user` (`user_id`);

--
-- Индексы таблицы `backup_log`
--
ALTER TABLE `backup_log`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `destination`
--
ALTER TABLE `destination`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `integration`
--
ALTER TABLE `integration`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_buyer` (`buyer_user_id`),
  ADD KEY `fk_orders_tourdate` (`tour_date_id`),
  ADD KEY `fk_orders_pm` (`payment_method_id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pay_order` (`order_id`),
  ADD KEY `fk_pay_method` (`method_id`);

--
-- Индексы таблицы `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_order` (`order_id`),
  ADD KEY `fk_review_client` (`client_id`),
  ADD KEY `fk_review_tour` (`tour_id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Индексы таблицы `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_rp_perm` (`permission_id`);

--
-- Индексы таблицы `tour_date`
--
ALTER TABLE `tour_date`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tourdate_tour` (`tour_id`);

--
-- Индексы таблицы `tour_package`
--
ALTER TABLE `tour_package`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tour_dest` (`destination_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admin_setting`
--
ALTER TABLE `admin_setting`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `auth_log`
--
ALTER TABLE `auth_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `backup_log`
--
ALTER TABLE `backup_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `destination`
--
ALTER TABLE `destination`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `integration`
--
ALTER TABLE `integration`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `review`
--
ALTER TABLE `review`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `tour_date`
--
ALTER TABLE `tour_date`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `tour_package`
--
ALTER TABLE `tour_package`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `auth_log`
--
ALTER TABLE `auth_log`
  ADD CONSTRAINT `fk_authlog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_orders_pm` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`),
  ADD CONSTRAINT `fk_orders_tourdate` FOREIGN KEY (`tour_date_id`) REFERENCES `tour_date` (`id`);

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_method` FOREIGN KEY (`method_id`) REFERENCES `payment_method` (`id`),
  ADD CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Ограничения внешнего ключа таблицы `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_review_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_review_tour` FOREIGN KEY (`tour_id`) REFERENCES `tour_package` (`id`);

--
-- Ограничения внешнего ключа таблицы `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tour_date`
--
ALTER TABLE `tour_date`
  ADD CONSTRAINT `fk_tourdate_tour` FOREIGN KEY (`tour_id`) REFERENCES `tour_package` (`id`);

--
-- Ограничения внешнего ключа таблицы `tour_package`
--
ALTER TABLE `tour_package`
  ADD CONSTRAINT `fk_tour_dest` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`id`);

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
