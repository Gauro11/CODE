-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 09:57 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alazima`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Prefer Not to Say') NOT NULL DEFAULT 'Prefer Not to Say',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `password_change_token` varchar(255) DEFAULT NULL,
  `password_change_token_expires_at` datetime DEFAULT NULL,
  `role` enum('administrator') NOT NULL DEFAULT 'administrator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `first_name`, `last_name`, `email`, `contact_number`, `password`, `address`, `birthday`, `age`, `gender`, `reset_token`, `reset_token_expires_at`, `password_change_token`, `password_change_token_expires_at`, `role`, `created_at`, `updated_at`) VALUES
(2, 'Danelle Marie', 'Beltran', 'azimamaids.services@gmail.com', NULL, '$2y$10$p81c9xFvEj3PaNuXgfy4P.oD25zh0UjDnmpES14Npyx8SvJOT1rlG', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, 'administrator', '2025-07-18 03:46:18', '2025-07-21 13:49:17');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birthday` date NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `password_change_token` varchar(64) DEFAULT NULL,
  `password_change_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `last_name`, `birthday`, `contact_number`, `email`, `password`, `created_at`, `reset_token`, `reset_token_expires_at`, `password_change_token`, `password_change_token_expires_at`) VALUES
(7, 'Mona', 'Hassan', '2025-07-17', '+971136726090', 'mona.hassan@example.com', 'WAwA@0234', '2025-07-17 21:10:35', NULL, NULL, NULL, NULL),
(8, 'Tariq', 'Khan', '2025-07-17', '+971110620035', 'tariq.khan@example.com', '$2y$10$XL/9BLg8y6ujTdINCbfmPeNpeKcKbSKIYMbIg/wffbl0uG4hOwu0i', '2025-07-17 21:14:12', NULL, NULL, NULL, NULL),
(12, 'Layla', 'Saeed', '2025-07-17', '+971123456789', 'sinaingsinigang@gmail.com', '$2y$10$SJqjrKqu7.a7iGpHkztPN.zrlFqCLfINCGF/bKA0/V4TCSNY6SHmm', '2025-07-17 23:51:24', NULL, NULL, NULL, NULL),
(13, 'Amira', 'Ali', '2025-07-17', '+971166897032', 'amira.ali@example.com', '$2y$10$TR3jBmrztzBECf3BRjzw5egQiNgS7fa72NkS3.S9VL2aZw0NnyQyC', '2025-07-17 23:56:34', NULL, NULL, NULL, NULL),
(15, 'Danelle Marie', 'Beltran', '2025-07-17', '+971111111111', 'danellemarie6@gmail.com', '$2y$10$YGampWSfCTzEZ2IByqsyceMChp.ODFfJENGf217dsYS0qStBb.4dK', '2025-07-21 18:56:30', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Prefer Not to Say') NOT NULL,
  `birthdate` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `password_change_token` varchar(255) DEFAULT NULL,
  `password_change_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `age`, `gender`, `birthdate`, `position`, `reset_token`, `reset_token_expires_at`, `password_change_token`, `password_change_token_expires_at`, `created_at`, `updated_at`) VALUES
(2, 'Fatima', 'Al Nahyan', 'fatima.alnahyan@example.com', 'hashed_password_fatima', '+971529876543', 32, 'Male', '1992-03-22', 'Driver', NULL, NULL, NULL, NULL, '2025-07-18 14:46:00', '2025-07-26 09:40:25'),
(3, 'Ahmed', 'Al Maktoum', 'ahmed.almaktoum@example.com', '$2y$10$GX1sPk6sP1RRSWX0X9tky.ilCd7nyJN3dkN/lYLBMxPlL9rACoaVW', '+971561122334', 25, 'Male', '2000-11-05', 'Cleaner', NULL, NULL, NULL, NULL, '2025-07-18 14:47:00', '2025-07-26 09:40:25'),
(4, 'Omar', 'Al Falahi', 'omar.alfalahi@example.com', '', '+971501234567', 28, 'Male', '1997-05-12', 'Cleaner', NULL, NULL, NULL, NULL, '2025-07-21 18:56:51', '2025-07-26 09:40:25'),
(6, 'Danelle Marie', 'Beltran', 'danellemarie6@gmail.com', '$2y$10$gVRwufwC/omAdXyFrpnQMePmyOiL4QFOPSuXtlkf4AA1w8t3etLgq', '+971434334443', 27, 'Prefer Not to Say', '1998-03-20', 'Driver', NULL, NULL, NULL, NULL, '2025-07-21 20:24:42', '2025-07-26 09:40:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact_number` (`contact_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD UNIQUE KEY `password_change_token` (`password_change_token`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
