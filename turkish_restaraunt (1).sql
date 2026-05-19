-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 19 2026 г., 08:00
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `turkish_restaraunt`
--

-- --------------------------------------------------------

--
-- Структура таблицы `Dishes`
--

CREATE TABLE `Dishes` (
  `Id` int NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Сompound` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `Dishes`
--

INSERT INTO `Dishes` (`Id`, `Name`, `Сompound`, `Price`, `Img`) VALUES
(2, 'Люля-кебаб', 'Мясо,лук,лаваш', '199.99', 'dish_69847f63ae214.jpg'),
(4, 'Чорба', 'Чечевица 1 стакан, Бульон говяжий 2 стакана, Морковь 1 штука, Лук репчатый 1 штука, Тимьян сушеный', '159.98', 'dish_6984841fbc41f.jpg');

-- --------------------------------------------------------

--
-- Структура таблицы `error_logs`
--

CREATE TABLE `error_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `Orders`
--

CREATE TABLE `Orders` (
  `Id` int NOT NULL,
  `IdClient` int NOT NULL,
  `IdCourier` int NOT NULL,
  `TotalSum` decimal(10,2) NOT NULL,
  `Address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` enum('accepted','progress','ready','delivery','delivered') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `Orders`
--

INSERT INTO `Orders` (`Id`, `IdClient`, `IdCourier`, `TotalSum`, `Address`, `Status`) VALUES
(33, 7, 9, '159.98', 'Пермь, улица Луначарского, 24с3', 'delivered'),
(35, 7, 9, '159.98', 'Пермь, Сибирская улица, 55', 'accepted');

-- --------------------------------------------------------

--
-- Структура таблицы `OrdersDishes`
--

CREATE TABLE `OrdersDishes` (
  `Id` int NOT NULL,
  `IdOrder` int NOT NULL,
  `IdDishes` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `OrdersDishes`
--

INSERT INTO `OrdersDishes` (`Id`, `IdOrder`, `IdDishes`, `Quantity`) VALUES
(65, 33, 4, 1),
(68, 35, 4, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `Users`
--

CREATE TABLE `Users` (
  `Id` int NOT NULL,
  `Surname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Patronomyc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('client','courier','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `Users`
--

INSERT INTO `Users` (`Id`, `Surname`, `Name`, `Patronomyc`, `Phone`, `Password`, `Role`) VALUES
(4, 'Админов', 'Админ', 'Админович', '79222222225', '$2y$10$0oLQQC6VYG7mJGgJJuR4i.Fk8nu4exX0TAiGX1lmxg2Pgj7/HuPoa', 'admin'),
(7, 'Турицина', 'Елизавета', 'Сергеевна', '79222222222', '$2y$10$8H9nI6x4Pqig6CXRu5XJGub7rlUxFACo7Q.3LFdJpx2KMP3S0t8My', 'client'),
(8, 'Петров', 'Петр', 'Петрович', '79120000000', '$2y$10$QXaU0KSUpxGJjnHk1p.1UerXKtVK6feOxxW2lklqv2ZObhe9//eKC', 'client'),
(9, 'Курьерович', 'Курьерша', 'Курьеровна', '79222222228', '$2y$10$8H9nI6x4Pqig6CXRu5XJGub7rlUxFACo7Q.3LFdJpx2KMP3S0t8My', 'courier');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `Dishes`
--
ALTER TABLE `Dishes`
  ADD PRIMARY KEY (`Id`);

--
-- Индексы таблицы `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_error_logs_created_at` (`created_at`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user_id` (`user_id`),
  ADD KEY `idx_logs_created_at` (`created_at`);

--
-- Индексы таблицы `Orders`
--
ALTER TABLE `Orders`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `IdClient` (`IdClient`),
  ADD KEY `IdWaiter` (`IdCourier`);

--
-- Индексы таблицы `OrdersDishes`
--
ALTER TABLE `OrdersDishes`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `IdOrder` (`IdOrder`),
  ADD KEY `IdDishes` (`IdDishes`);

--
-- Индексы таблицы `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `Dishes`
--
ALTER TABLE `Dishes`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `Orders`
--
ALTER TABLE `Orders`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT для таблицы `OrdersDishes`
--
ALTER TABLE `OrdersDishes`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT для таблицы `Users`
--
ALTER TABLE `Users`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `Orders`
--
ALTER TABLE `Orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`IdClient`) REFERENCES `Users` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`IdCourier`) REFERENCES `Users` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `OrdersDishes`
--
ALTER TABLE `OrdersDishes`
  ADD CONSTRAINT `ordersdishes_ibfk_1` FOREIGN KEY (`IdDishes`) REFERENCES `Dishes` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ordersdishes_ibfk_2` FOREIGN KEY (`IdOrder`) REFERENCES `Orders` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
