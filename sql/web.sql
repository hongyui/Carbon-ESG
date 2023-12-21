-- phpMyAdmin SQL Dump
-- version 5.2.1-1.el8.remi
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2023 年 12 月 21 日 03:55
-- 伺服器版本： 10.3.39-MariaDB
-- PHP 版本： 7.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `web`
--

-- --------------------------------------------------------

--
-- 資料表結構 `carbontotal`
--

CREATE TABLE `carbontotal` (
  `id` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `is_check` varchar(1) NOT NULL DEFAULT '否',
  `price` varchar(200) NOT NULL,
  `carbontotal` varchar(200) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `account` varchar(50) NOT NULL,
  `is_people` varchar(1) NOT NULL,
  `buy_people` varchar(20) DEFAULT NULL,
  `detal` varchar(100) DEFAULT NULL,
  `contact` varchar(50) NOT NULL,
  `recall` varchar(2) DEFAULT NULL,
  `wallet` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `jobinfo`
--

CREATE TABLE `jobinfo` (
  `id` int(11) NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `age` int(50) NOT NULL,
  `residence` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contact` int(10) NOT NULL,
  `user` varchar(20) NOT NULL,
  `isCheck` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '否'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `account` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `manager` varchar(2) NOT NULL DEFAULT '否'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `carbontotal`
--
ALTER TABLE `carbontotal`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `jobinfo`
--
ALTER TABLE `jobinfo`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `carbontotal`
--
ALTER TABLE `carbontotal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `jobinfo`
--
ALTER TABLE `jobinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
