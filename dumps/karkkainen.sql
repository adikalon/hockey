-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Дек 12 2017 г., 21:58
-- Версия сервера: 5.7.16-log
-- Версия PHP: 7.1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `hockey`
--

-- --------------------------------------------------------

--
-- Структура таблицы `karkkainen`
--

CREATE TABLE `karkkainen` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `season` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_without_discount` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_age` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availableurl` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `koko` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vari` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paahineen_koko` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pituus_cm` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sidetyyppi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maku` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_of_measure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vaihteiden_maara` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `katisyys` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated` tinyint(1) UNSIGNED ZEROFILL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `karkkainen`
--
ALTER TABLE `karkkainen`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `karkkainen`
--
ALTER TABLE `karkkainen`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
