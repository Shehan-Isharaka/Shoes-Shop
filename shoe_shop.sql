-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 07, 2026 at 07:59 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoe_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'Nike', 'active', '2025-12-13 17:05:30'),
(2, 'None', 'active', '2025-12-13 17:05:35'),
(4, 'Adidas', 'active', '2026-01-10 16:17:43');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `status`, `created_at`) VALUES
(1, NULL, 'Men', 'men', 'active', '2025-12-13 15:36:33'),
(2, NULL, 'Women', 'women', 'active', '2025-12-13 15:36:33'),
(3, NULL, 'Kids', 'kids', 'active', '2025-12-13 15:36:33'),
(4, 1, 'Casual Shoes', 'casual-shoes', 'active', '2025-12-13 15:37:09'),
(5, 1, 'Sports Shoes', 'men-sports-shoes', 'active', '2025-12-13 15:37:09'),
(6, 1, 'Formal Shoes', 'men-formal-shoes', 'active', '2025-12-13 15:37:09'),
(7, 1, 'Sandals', 'men-sandals', 'active', '2025-12-13 15:37:09'),
(8, 2, 'Sports Shoes', 'sports-shoes', 'active', '2025-12-14 05:42:37'),
(9, 2, 'sandals', 'sandals', 'active', '2025-12-14 05:42:46'),
(10, 3, 'Shoes', 'shoes', 'active', '2025-12-14 05:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
CREATE TABLE IF NOT EXISTS `colors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `hex_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `name`, `hex_code`, `status`) VALUES
(1, 'PINK', '#e571d5', 'active'),
(2, 'Black', '#000000', 'active'),
(3, 'Blue', '#0260f7', 'active'),
(6, 'Gray', '#616161', 'active'),
(7, 'White', '#ffffff', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_otp` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `password`, `status`, `created_at`, `reset_otp`, `otp_expires`) VALUES
(1, 'Shehan Isharaka', 'shehan.isharaka.ac@gmail.com', '0777780071', '$2y$10$3owqfUw6XJaPKLNzpcLHoOxZo2NSSXu9IKijQw1DKIw7ALLqtpbAi', 'active', '2025-12-20 14:00:55', NULL, NULL),
(2, 'Gihan Nirmal', 'gihan@gmail.com', '0778452266', '$2y$10$EqsFqh2z28bVmuRP/E62AuS74k75cwjlbosiELMiZXrjUZDv6hS2m', 'active', '2026-01-06 18:04:34', NULL, NULL),
(3, 'Kamal Sandeepa', 'kamal@gmail.com', '0778457788', '$2y$10$mKReQYOeWahy7bAJweoxxu.27pxLgbcds8Q6w9ru1yjW6FP7g1gDG', 'active', '2026-01-15 17:09:50', NULL, NULL),
(4, 'Anjula Nadeeshan', 'anjula@gmail.com', '0774581122', '$2y$10$.K0iZFs3.seHeUcvF.ApP.L5lMY6PbzGcJC5ucVsSVarQUk7r2IHO', 'active', '2026-01-15 17:20:43', NULL, NULL),
(5, 'Kasuni Wimalaweera', 'kasuni@gmail.com', '0778452266', '$2y$10$AFQ.XvIDPSHNkRMGbhNq2eC2BJpRP4j7KtUe1q6InyevxU4FhfQf.', 'active', '2026-01-16 17:07:04', NULL, NULL),
(6, 'Sports Shoes', 'sunil@gmail.com', '0777780784', '$2y$10$20NUxo/EunWZpbZgjC2kfeFNZd90NBRS2GRVp9locVEu/TKZLzfFW', 'active', '2026-01-18 03:23:50', NULL, NULL),
(7, 'Sports Shoes', 'chamara@gmail.com', '0777780052', '$2y$10$uX44p11t8bYu38l2dvT1Mu9MysgqZOCmfD1P8.Wmr.zZte88gZzsq', 'active', '2026-01-18 03:27:06', NULL, NULL),
(8, 'Shehan Isharaka', 'shehanisharaka@gmail.com', '0777780071', '$2y$10$zIwveOJwVsdM18ryagp4JO4oue0JoiaIwXMJjueiPHyKCVmMEuhhm', 'active', '2026-02-04 15:15:22', '351304', '2026-02-07 04:18:14');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_districts`
--

DROP TABLE IF EXISTS `delivery_districts`;
CREATE TABLE IF NOT EXISTS `delivery_districts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `district_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `district_name` (`district_name`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_districts`
--

INSERT INTO `delivery_districts` (`id`, `district_name`, `delivery_charge`, `status`) VALUES
(1, 'Gampaha', 350.00, 'active'),
(2, 'Colombo', 500.00, 'active'),
(3, 'Kaluthara', 500.00, 'active'),
(4, 'Kandy', 450.00, 'active'),
(5, 'Matale', 500.00, 'active'),
(6, 'Nuwara Eliya', 550.00, 'active'),
(7, 'Galle', 450.00, 'active'),
(8, 'Matara', 500.00, 'active'),
(9, 'Hambantota', 550.00, 'active'),
(10, 'Kurunegala', 450.00, 'active'),
(11, 'Puttalam', 500.00, 'active'),
(12, 'Anuradhapura', 600.00, 'active'),
(13, 'Polonnaruwa', 600.00, 'active'),
(14, 'Badulla', 600.00, 'active'),
(15, 'Monaragala', 650.00, 'active'),
(16, 'Ratnapura', 550.00, 'active'),
(17, 'Kegalle', 500.00, 'active'),
(18, 'Trincomalee', 650.00, 'active'),
(19, 'Batticaloa', 650.00, 'active'),
(20, 'Ampara', 650.00, 'active'),
(21, 'Jaffna', 750.00, 'active'),
(22, 'Kilinochchi', 750.00, 'active'),
(23, 'Mannar', 750.00, 'active'),
(24, 'Vavuniya', 700.00, 'active'),
(25, 'Mullaitivu', 750.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_id` int NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `district` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `stock_deducted` tinyint(1) DEFAULT '0',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` enum('cod','bank') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `cancel_reason` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_code` (`tracking_code`),
  KEY `user_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `tracking_code`, `customer_id`, `customer_name`, `email`, `phone`, `address`, `district`, `city`, `postcode`, `subtotal`, `delivery_fee`, `total`, `order_status`, `stock_deducted`, `status`, `created_at`, `payment_method`, `payment_status`, `cancel_reason`) VALUES
(1, 'dd', 1, 'Shehan Isharaka', 'shehanisharaka123@gmail.com', '0777780071', 'sdadadsadad', 'Gampaha', 'Mirigama', '11200', 10743.00, 350.00, 11093.00, 'processing', 0, 'pending', '2025-12-20 11:00:58', 'cod', 'pending', NULL),
(2, 'cc', 1, 'Black', '', 'dsa', 'sdad', 'Kalutara', 'sad', '', 10542.00, 400.00, 10942.00, 'pending', 0, 'pending', '2025-12-20 11:02:15', 'cod', 'pending', NULL),
(3, 'bb', 1, 'Shehan Isharaka', 'shehanisharaka123@gmail.com', '0777780071', 'sss wwwwww dsddddddd', 'Gampaha', 'Mirigama', '11200', 2952.00, 350.00, 3302.00, 'pending', 0, 'pending', '2025-12-20 11:53:09', 'cod', 'pending', NULL),
(4, 'aa', 1, 'Shehan Isharaka', 'cashier@gmail.com', '0777780071', 'ss sssss', 'Gampaha', 'Mirigama', '11200', 2751.00, 350.00, 3101.00, 'pending', 0, 'pending', '2025-12-20 12:05:35', 'cod', 'pending', NULL),
(5, 'PS-20251220-775677', 0, 'Sports Shoes', 'stock@gmail.com', '11111111111', 'sdadasda', 'Colombo', 'gfd', '11200', 2751.00, 300.00, 3051.00, 'pending', 0, 'pending', '2025-12-20 12:44:55', 'cod', 'pending', NULL),
(6, 'PS-20251220-F0FD34', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Kalutara', 'gfd', '11200', 2550.00, 400.00, 2950.00, 'delivered', 0, 'pending', '2025-12-20 12:45:19', 'cod', 'paid', NULL),
(7, 'PS-20251220-F7F92A', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Colombo', 'gfd', '11200', 2550.00, 300.00, 2850.00, 'pending', 0, 'pending', '2025-12-20 12:46:23', 'cod', 'pending', NULL),
(8, 'PS-20251220-14E35E', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Gampaha', 'gfd', '11200', 201.00, 350.00, 551.00, 'cancelled', 0, 'pending', '2025-12-20 13:18:41', 'cod', 'pending', 'as customer requested'),
(9, 'ORD-6946E30184706', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'aaaaaaaaaaaa', 'Gampaha', 'aaaaaaa', '11200', 2550.00, 350.00, 2900.00, 'shipped', 0, 'pending', '2025-12-20 17:55:13', 'cod', 'pending', NULL),
(10, 'PS-20251221-EB0429', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Colombo', 'gfd', '11200', 6546.00, 300.00, 6846.00, 'cancelled', 0, 'pending', '2025-12-21 14:41:18', 'cod', '', 'As requested by the customer'),
(11, 'PS-20251222-763A10', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Kaluthara', 'gfd', '11200', 3996.00, 500.00, 4496.00, 'pending', 0, 'pending', '2025-12-22 01:02:15', 'cod', 'pending', NULL),
(12, 'PS-20251225-CC6BEC', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Colombo', 'gfd', '11200', 402.00, 500.00, 902.00, 'pending', 0, 'pending', '2025-12-25 07:21:48', 'cod', 'pending', NULL),
(13, 'PS-20251225-22DFA8', 1, 'Sports Shoes', 'stock@gmail.com', '0777780071', 'sdadasda', 'Colombo', 'gfd', '11200', 4398.00, 500.00, 4898.00, 'pending', 0, 'pending', '2025-12-25 09:11:46', 'cod', 'pending', NULL),
(14, 'PS-20251225-EC672D', 1, 'Sports Shoes', 'stock@gmail.com', '11', 'sdadasda', 'Gampaha', 'gfd', '11200', 201.00, 350.00, 551.00, 'pending', 0, 'pending', '2025-12-25 09:27:10', 'cod', 'pending', NULL),
(15, 'PS-20260115-5B6F89', 3, 'Kamal Sandeepa', 'kamal@gmail.com', '0778459955', 'No.12, Kaluthara.', 'Kaluthara', 'Kaluthara', '4500', 3985.00, 500.00, 4485.00, 'processing', 0, 'pending', '2026-01-15 17:11:49', 'cod', 'pending', NULL),
(16, 'PS-20260115-EC8675', 4, 'Anjula Nadeeshan', 'anjula@gmail.com', '0778412255', 'No.163 Kottawa Rd, Maharagama', 'Colombo', 'Colombo', '18450', 2284.00, 500.00, 2784.00, 'delivered', 0, 'pending', '2026-01-15 17:25:02', 'bank', 'paid', NULL),
(17, 'PS-20260116-D92964', 5, 'Kasuni Wimalaweera', 'kasuni@gmail.com', '0714523355', 'No.145, Kandy Road, Nittabuwa', 'Gampaha', 'Nittabuwa', '11450', 1290.00, 350.00, 1640.00, 'delivered', 0, 'pending', '2026-01-16 17:08:45', 'cod', 'paid', NULL),
(18, 'PS-20260117-425F1D', 5, 'Kasuni Wimalaweera', 'kasuni@gmail.com', '0774589966', 'Kaluthara', 'Kaluthara', 'kaluthara', '11458', 2284.00, 500.00, 2784.00, 'delivered', 0, 'pending', '2026-01-17 03:20:20', 'cod', 'paid', NULL),
(19, 'PS-20260118-9DE140', 5, 'Kasuni Wimalaweera', 'kasuni@gmail.com', '07451256688', 'No.42, Matale Road, Matale', 'Matale', 'Matale', '11200', 1290.00, 500.00, 1790.00, 'cancelled', 0, 'pending', '2026-01-18 03:45:29', 'cod', 'pending', 'Please cancel the order.'),
(20, 'PS-20260204-8B859D', 8, 'Shehan Isharaka', 'shehanisharaka@gmail.com', '0777780071', 'No. 163 Test Road, Mirigama', 'Gampaha', 'Mirigama', '11200', 2545.00, 350.00, 2895.00, 'delivered', 0, 'pending', '2026-02-04 16:32:56', 'cod', 'paid', NULL),
(21, 'PS-20260204-C786A0', 8, 'Shehan Isharaka', 'shehanisharaka@gmail.com', '0777780071', 'NO.163 Test Road, Mirigama', 'Gampaha', 'Mirigama', '11200', 2756.00, 350.00, 3106.00, 'pending', 0, 'pending', '2026-02-04 16:34:04', 'cod', 'pending', NULL),
(22, 'PS-20260205-AC7E5E', 8, 'Shehan Isharaka', 'shehanisharaka@gmail.com', '0777780071', 'No 163, Test , Mirigama', 'Gampaha', 'Mirigama', '11200', 1000.00, 350.00, 1350.00, 'delivered', 0, 'pending', '2026-02-05 23:00:58', 'cod', 'paid', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int NOT NULL,
  `qty` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `qty`, `price`, `created_at`) VALUES
(1, 1, 1, 1, 1, 201.00, '2025-12-20 11:00:58'),
(2, 1, 4, 4, 2, 3996.00, '2025-12-20 11:00:58'),
(3, 1, 5, 5, 1, 2550.00, '2025-12-20 11:00:58'),
(4, 2, 4, 4, 2, 3996.00, '2025-12-20 11:02:15'),
(5, 2, 5, 5, 1, 2550.00, '2025-12-20 11:02:15'),
(6, 3, 1, 2, 2, 201.00, '2025-12-20 11:53:09'),
(7, 3, 5, 5, 1, 2550.00, '2025-12-20 11:53:09'),
(8, 4, 1, 2, 1, 201.00, '2025-12-20 12:05:35'),
(9, 4, 5, 5, 1, 2550.00, '2025-12-20 12:05:35'),
(10, 5, 1, 2, 1, 201.00, '2025-12-20 12:44:55'),
(11, 5, 5, 5, 1, 2550.00, '2025-12-20 12:44:55'),
(12, 6, 5, 5, 1, 2550.00, '2025-12-20 12:45:19'),
(13, 7, 5, 5, 1, 2550.00, '2025-12-20 12:46:23'),
(14, 8, 1, 2, 1, 201.00, '2025-12-20 13:18:41'),
(15, 9, 5, 5, 1, 2550.00, '2025-12-20 17:55:13'),
(16, 10, 5, 5, 1, 2550.00, '2025-12-21 14:41:18'),
(17, 10, 4, 4, 1, 3996.00, '2025-12-21 14:41:18'),
(18, 11, 4, 4, 1, 3996.00, '2025-12-22 01:02:15'),
(19, 12, 1, 2, 2, 201.00, '2025-12-25 07:21:48'),
(20, 13, 1, 2, 2, 201.00, '2025-12-25 09:11:46'),
(21, 13, 4, 4, 1, 3996.00, '2025-12-25 09:11:46'),
(22, 14, 1, 2, 1, 201.00, '2025-12-25 09:27:10'),
(23, 15, 4, 4, 1, 3985.00, '2026-01-15 17:11:49'),
(24, 16, 9, 6, 1, 2284.00, '2026-01-15 17:25:02'),
(25, 17, 8, 8, 1, 1290.00, '2026-01-16 17:08:45'),
(26, 18, 9, 6, 1, 2284.00, '2026-01-17 03:20:20'),
(27, 19, 8, 8, 1, 1290.00, '2026-01-18 03:45:29'),
(28, 20, 5, 5, 1, 2545.00, '2026-02-04 16:32:56'),
(29, 21, 1, 2, 1, 2756.00, '2026-02-04 16:34:04'),
(30, 22, 21, 12, 1, 1000.00, '2026-02-05 23:00:58');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `note`, `created_at`, `reason`) VALUES
(1, 5, 'pending', 'Order placed successfully', '2025-12-20 12:44:55', NULL),
(2, 6, 'pending', 'Order placed successfully', '2025-12-20 12:45:19', NULL),
(3, 7, 'pending', 'Order placed successfully', '2025-12-20 12:46:23', NULL),
(4, 8, 'pending', 'Order placed successfully', '2025-12-20 13:18:41', NULL),
(5, 9, 'pending', 'Order placed successfully', '2025-12-20 17:55:13', NULL),
(6, 10, 'pending', 'Order placed', '2025-12-21 14:41:18', NULL),
(7, 6, 'delivered', 'Status changed to delivered', '2025-12-21 14:42:44', NULL),
(8, 11, 'pending', 'Order placed', '2025-12-22 01:02:15', NULL),
(9, 12, 'pending', 'Order placed', '2025-12-25 07:21:48', NULL),
(10, 13, 'pending', 'Order placed', '2025-12-25 09:11:46', NULL),
(11, 14, 'pending', 'Order placed', '2025-12-25 09:27:10', NULL),
(12, 8, 'cancelled', 'Status changed to cancelled', '2026-01-06 17:26:16', NULL),
(13, 8, 'cancelled', 'Status changed to cancelled', '2026-01-06 17:26:23', NULL),
(14, 8, 'cancelled', 'Status changed to cancelled', '2026-01-06 17:57:52', 'as customer requested'),
(15, 15, 'pending', 'Order placed', '2026-01-15 17:11:49', NULL),
(16, 16, 'pending', 'Order placed', '2026-01-15 17:25:02', NULL),
(17, 10, 'cancelled', 'Status changed to cancelled', '2026-01-15 17:29:14', 'As requested by the customer'),
(18, 16, 'delivered', 'Status changed to delivered', '2026-01-15 17:42:38', NULL),
(19, 17, 'pending', 'Order placed', '2026-01-16 17:08:45', NULL),
(20, 17, 'delivered', 'Status changed to delivered', '2026-01-16 17:09:35', NULL),
(21, 18, 'pending', 'Order placed', '2026-01-17 03:20:20', NULL),
(22, 18, 'delivered', 'Status changed to delivered', '2026-01-17 03:20:37', NULL),
(23, 19, 'pending', 'Order placed', '2026-01-18 03:45:29', NULL),
(24, 19, 'cancelled', 'Cancelled by customer: Please cancel the order.', '2026-01-18 03:47:01', NULL),
(25, 18, 'delivered', 'Status changed to delivered', '2026-01-28 18:29:17', NULL),
(26, 20, 'pending', 'Order placed', '2026-02-04 16:32:56', NULL),
(27, 21, 'pending', 'Order placed', '2026-02-04 16:34:04', NULL),
(28, 20, 'delivered', 'Status changed to delivered', '2026-02-04 16:34:51', NULL),
(29, 15, 'processing', 'Status changed to processing', '2026-02-04 18:03:12', NULL),
(30, 22, 'pending', 'Order placed', '2026-02-05 23:00:58', NULL),
(31, 22, 'delivered', 'Status changed to delivered', '2026-02-05 23:30:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `banner` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `banner`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'about-us', 'About Us - Pino Shoes Shop', '<p data-start=\"129\" data-end=\"398\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Pino Shoes is a trusted footwear brand dedicated to providing stylish, comfortable, and durable shoes for&nbsp;<span data-start=\"235\" data-end=\"259\" style=\"font-weight: bolder;\">men, women, and kids</span>. Our collection features a wide range of casual, formal, sports, and fashion footwear designed to suit every lifestyle and every occasion.</p><p data-start=\"400\" data-end=\"763\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">We focus on quality craftsmanship, premium materials, and modern designs to ensure long-lasting comfort for the whole family. At Pino Shoes, we believe great footwear should be affordable and accessible to everyone. With a commitment to excellent customer service and continuous innovation, we aim to deliver the best shopping experience both in-store and online.</p><p data-start=\"765\" data-end=\"839\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Step confidently with Pino Shoes -&nbsp; where style meets comfort for all ages.</p>', 'about-us_1766341221.webp', 1, '2025-12-13 20:32:48', '2025-12-21 18:20:21'),
(2, 'faq', 'Frequently Asked Questions', '<p data-start=\"188\" data-end=\"305\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Welcome to our FAQ section! Here are some quick answers to help you with your PiNO Shoes experience:</p><ul data-start=\"307\" data-end=\"848\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><li data-start=\"307\" data-end=\"433\"><p data-start=\"309\" data-end=\"433\"><span data-start=\"309\" data-end=\"337\" style=\"font-weight: bolder;\">What shoes do you offer?</span><br data-start=\"337\" data-end=\"340\">We offer a wide range for men, women, and kids, including casual, sports, and formal shoes.</p></li><li data-start=\"435\" data-end=\"549\"><p data-start=\"437\" data-end=\"549\"><span data-start=\"437\" data-end=\"457\" style=\"font-weight: bolder;\">How can I order?</span><br data-start=\"457\" data-end=\"460\">Simply add products to your cart and checkout online. Youâ€™ll get an email confirmation.</p></li><li data-start=\"551\" data-end=\"667\"><p data-start=\"553\" data-end=\"667\"><span data-start=\"553\" data-end=\"579\" style=\"font-weight: bolder;\">Returns and Exchanges:</span><br data-start=\"579\" data-end=\"582\">You can return or exchange shoes within 14 days if they donâ€™t fit or are defective.</p></li><li data-start=\"669\" data-end=\"760\"><p data-start=\"671\" data-end=\"760\"><span data-start=\"671\" data-end=\"684\" style=\"font-weight: bolder;\">Delivery:</span><br data-start=\"684\" data-end=\"687\">Orders are processed in 1-2 days and usually delivered within 3-7 days.</p></li><li data-start=\"762\" data-end=\"848\"><p data-start=\"764\" data-end=\"848\"><span data-start=\"764\" data-end=\"777\" style=\"font-weight: bolder;\">Payments:</span><br data-start=\"777\" data-end=\"780\">We accept cards, PayPal, and cash on delivery in select locations.</p></li></ul><p data-start=\"850\" data-end=\"906\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">For other questions, contact our support team anytime!</p>', 'faq_1766341206.webp', 1, '2025-12-13 20:32:48', '2025-12-21 18:20:06'),
(3, 'contact-us', 'Contact Us', '<div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">We\'d love to hear from you! At&nbsp;<span style=\"font-weight: bolder;\">Pino Shoes Shop</span>, customer satisfaction is our top priority. Whether you have questions about our products, need assistance with your order, or want to share your feedback, our friendly team is here to help.</div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><br></div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Our store offers a wide range of high-quality shoes for&nbsp;<span style=\"font-weight: bolder;\">men, women, and kids.</span>&nbsp;From casual sneakers to formal footwear, we\'ve got styles for every occasion. Our expert staff is always ready to guide you in choosing the perfect pair that matches your style and comfort.</div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><br></div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Feel free to reach out to us through any of the following methods:</div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><br></div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><ul><li><span style=\"font-weight: bolder;\">Email</span>: pinoshoe@gmail.com </li><li><span style=\"font-weight: bolder;\">Phone</span>: +94 78 654 9356</li><li><span style=\"font-weight: bolder;\">Address</span>:131/A, Kandakapapu Junction, Kirillawala</li></ul></div><div style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Or use the contact form below to send us a message directly. We usually respond within 24 hours. Come visit us at our store or drop us a message - we are here to make your shopping experience enjoyable!</div>', 'contact-us_1766341190.webp', 1, '2025-12-13 20:32:48', '2025-12-27 15:34:18'),
(4, 'terms', 'Terms & Conditions', '<p data-start=\"1005\" data-end=\"1094\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\">Welcome to PiNO Shoes. By using our website, you agree to the following:</p><ul data-start=\"1096\" data-end=\"1674\" style=\"font-family: Montserrat, system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif; font-weight: 400;\"><li data-start=\"1096\" data-end=\"1217\"><p data-start=\"1098\" data-end=\"1217\"><span data-start=\"1098\" data-end=\"1120\" style=\"font-weight: bolder;\">Orders &amp; Payments:</span><br data-start=\"1120\" data-end=\"1123\">All orders require confirmation. We accept credit/debit cards, PayPal, and cash on delivery.</p></li><li data-start=\"1219\" data-end=\"1327\"><p data-start=\"1221\" data-end=\"1327\"><span data-start=\"1221\" data-end=\"1245\" style=\"font-weight: bolder;\">Shipping &amp; Delivery:</span><br data-start=\"1245\" data-end=\"1248\">We aim to ship orders quickly. Delivery times may vary depending on location.</p></li><li data-start=\"1329\" data-end=\"1469\"><p data-start=\"1331\" data-end=\"1469\"><span data-start=\"1331\" data-end=\"1353\" style=\"font-weight: bolder;\">Returns &amp; Refunds:</span><br data-start=\"1353\" data-end=\"1356\">Returns are accepted within 14 days for defective or ill-fitting shoes. Refunds are processed after inspection.</p></li><li data-start=\"1471\" data-end=\"1557\"><p data-start=\"1473\" data-end=\"1557\"><span data-start=\"1473\" data-end=\"1485\" style=\"font-weight: bolder;\">Privacy:</span><br data-start=\"1485\" data-end=\"1488\">Your personal data is safe with us and only used to fulfill orders.</p></li><li data-start=\"1559\" data-end=\"1674\"><p data-start=\"1561\" data-end=\"1674\"><span data-start=\"1561\" data-end=\"1582\" style=\"font-weight: bolder;\">Changes to Terms:</span><br data-start=\"1582\" data-end=\"1585\">We may update these terms at any time. Continued use of our site means you accept them.</p></li></ul>', 'terms_1766341172.webp', 1, '2025-12-13 20:32:48', '2025-12-21 18:19:37');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `brand_id` int DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_general_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `price`, `discount`, `description`, `status`, `is_featured`, `created_at`) VALUES
(1, 5, 2, 'Casual Shoes 70218 BLK', 'casual-shoes-70218-blk', 10990.00, 2000.00, 'Casual shoe', 'active', 1, '2025-12-14 05:28:44'),
(2, 5, 4, 'Adidas Boa Running Shoes Black/Red', 'adidas-boa-running-shoes-black-red', 10000.00, 0.00, 'Adidas Boa Running Shoes Black/Red', 'active', 1, '2025-12-14 05:41:23'),
(3, 6, 1, 'MEN’S BROWN LEATHER BUCKLE LOAFER', 'men-s-brown-leather-buckle-loafer', 10000.00, 200.00, '-', 'active', 0, '2025-12-14 05:41:42'),
(4, 5, 1, 'AVI Men Sports Lacing Shoes Blue', 'avi-men-sports-lacing-shoes-blue', 4000.00, 200.00, '-', 'active', 0, '2025-12-14 05:42:14'),
(5, 10, 2, 'Sparx Round Toe School Shoes', 'sparx-round-toe-school-shoes', 2550.00, 200.00, 'A pair of round toe black slip-on sneakers ,has regular styling,\r\nVelcro detail\r\nSynthetic suede upper\r\nCushioned footbed\r\nTextured and patterned outsole', 'active', 0, '2025-12-14 05:44:58'),
(6, 6, 2, 'MEN’S FORMAL BLACK LEATHER OXFORD SHOES', 'men-s-formal-black-leather-oxford-shoes', 11000.00, 450.00, '-', 'active', 1, '2025-12-25 03:03:30'),
(7, 10, 1, 'Little Champ Sneakers - A-Style Beige', 'little-champ-sneakers---a-style-beige', 3700.00, 0.00, '-', 'active', 0, '2025-12-25 03:03:43'),
(8, 8, 1, 'Everyday Classic Coat Shoe', 'everyday-classic-coat-shoe', 1290.00, 0.00, '-', 'active', 0, '2025-12-25 03:04:10'),
(9, 7, 2, 'Blue sandal – BeWalk', 'blue-sandal-bewalk', 2299.00, 400.00, '-', 'active', 0, '2025-12-25 03:04:25'),
(10, 10, 2, 'Little Champ Sneakers - A-Style Black', 'little-champ-sneakers---a-style-black', 3700.00, 0.00, '-', 'active', 1, '2025-12-25 03:04:38'),
(11, 10, 2, 'Baby Sneakers - Gray', 'baby-sneakers---gray', 3400.00, 0.00, '-', 'active', 0, '2025-12-25 03:04:58'),
(12, 7, 2, 'Brown Sandals – Dean', 'brown-sandals-dean', 2199.00, 400.00, '-', 'active', 0, '2025-12-25 04:12:00'),
(13, 7, 2, 'Black Sandals – MASON', 'black-sandals-mason', 2500.00, 800.00, '-', 'active', 1, '2025-12-25 04:12:32'),
(14, 5, 2, 'Men\'s UA Charged Pursuit 2 Running Shoes', 'men-s-ua-charged-pursuit-2-running-shoes', 10000.00, 1500.00, 'NEUTRAL: For runners who need flexibility, cushioning & versatility.\r\nCharged Cushioning midsole uses compression molded foam for ultimate responsiveness & durability.\r\nEngineered mesh upper is extremely lightweight & breathable, with strategic support where you need it.\r\nFoam padding placed around your ankle collar & under the tongue for an incredibly comfortable fit & feel.\r\nComfort sockliner molds to your foot with padding in the heel for ultimate cushioning at heel-strike.\r\nTire inspired outsole pattern provides ultimate flex & superior traction.', 'active', 0, '2025-12-25 04:14:08'),
(15, 4, 2, 'Timberland Mens Casual Shoe', 'timberland-mens-casual-shoe', 9999.00, 0.00, 'Timberland casual shoes bring iconic style and durability to your daily wear. Built to last and designed to move, they’re perfect for laid-back days with a touch of rugged edge.', 'active', 1, '2026-01-10 16:09:17'),
(16, 4, 1, 'Nike Mens Shoe', 'nike-mens-shoe', 8999.00, 0.00, 'This Nike essential brings iconic DNA to your everyday rotation with lightweight comfort, clean lines, and just the right amount of attitude. Engineered for movement and built to last, it’s got the grip to keep you grounded and the cushioning to keep you going. Whether you\'re chasing goals or just the weekend, this is your go-to for stepping up and standing out.', 'active', 1, '2026-01-10 16:13:19'),
(17, 4, 1, 'Nike Mens Shoe White', 'nike-mens-shoe-white', 9499.00, 0.00, 'his Nike essential brings iconic DNA to your everyday rotation with lightweight comfort, clean lines, and just the right amount of attitude. Engineered for movement and built to last, it’s got the grip to keep you grounded and the cushioning to keep you going. Whether you\'re chasing goals or just the weekend, this is your go-to for stepping up and standing out.', 'active', 0, '2026-01-10 16:15:45'),
(18, 4, 4, 'Adidas Mens Shoe', 'adidas-mens-shoe', 7499.00, 0.00, 'Adidas shoes combine comfort, performance, and iconic style—perfect for sports, casual wear, or everyday use.', 'active', 0, '2026-01-10 16:18:24'),
(19, 5, 2, 'Men’s Textile Grey and Silver Sports Shoes – KIIT', 'men-s-textile-grey-and-silver-sports-shoes-kiit', 3799.00, 800.00, 'Article Code – *8392166\r\nGender – Men’s\r\nType of Wear -Sports Shoes\r\nColor – Grey/Silver\r\nMaterial -Textile\r\nBrand – Bata\r\nPackage include 1X pair of shoes', 'active', 0, '2026-01-11 04:07:45'),
(20, 6, 2, 'MEN’S CLASSIC BLACK LEATHER LOAFERS', 'men-s-classic-black-leather-loafers', 12995.00, 500.00, '-', 'active', 0, '2026-01-11 04:32:14'),
(21, 7, 1, 'NIKE comfort slides', 'nike-comfort-slides', 4500.00, 1000.00, 'Step into all-day comfort and timeless style with the Nike Slides, available now at The Shoe Station. Designed with a sleek strap and the iconic Nike Swoosh, these slides are perfect for casual wear, post-workout recovery, or simply lounging in style. The cushioned footbed offers unmatched softness while the durable sole ensures long-lasting wear on any surface.', 'active', 1, '2026-02-05 17:32:35');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(32, 1, 'uploads/products/prod_69627d2c4e532.jpg', 1, '2026-01-10 16:24:12'),
(39, 2, 'uploads/products/prod_69632025d3af5.jpg', 1, '2026-01-11 03:59:33'),
(73, 3, 'uploads/products/prod_696329472f816.jpg', 1, '2026-01-11 04:38:31'),
(61, 4, 'uploads/products/prod_696327133b844.jpg', 1, '2026-01-11 04:29:07'),
(85, 5, 'uploads/products/prod_6963347b1e53d.jpg', 1, '2026-01-11 05:26:19'),
(41, 2, 'uploads/products/img_696320417b655.jpg', 0, '2026-01-11 04:00:01'),
(42, 19, 'uploads/products/prod_696322110ff70.png', 1, '2026-01-11 04:07:45'),
(40, 2, 'uploads/products/img_6963203b9e2a8.jpg', 0, '2026-01-11 03:59:55'),
(38, 1, 'uploads/products/img_69627d8b5075b.jpg', 0, '2026-01-10 16:25:47'),
(70, 6, 'uploads/products/prod_6963283c801fa.jpg', 1, '2026-01-11 04:34:04'),
(83, 7, 'uploads/products/prod_696333537e534.jpg', 1, '2026-01-11 05:21:23'),
(77, 8, 'uploads/products/prod_69632ef75f0e0.jpg', 1, '2026-01-11 05:02:47'),
(53, 9, 'uploads/products/prod_6963247a3a15e.png', 1, '2026-01-11 04:18:02'),
(81, 10, 'uploads/products/prod_696332fc10fed.jpg', 1, '2026-01-11 05:19:56'),
(80, 11, 'uploads/products/prod_696332b87bd90.jpg', 1, '2026-01-11 05:18:48'),
(50, 12, 'uploads/products/prod_696323dc35649.jpg', 1, '2026-01-11 04:15:24'),
(47, 13, 'uploads/products/prod_696322fe1254f.jpg', 1, '2026-01-11 04:11:42'),
(57, 14, 'uploads/products/prod_696325ac557e3.webp', 1, '2026-01-11 04:23:08'),
(23, 15, 'uploads/products/prod_696279ade850b.jpg', 1, '2026-01-10 16:09:17'),
(24, 15, 'uploads/products/img_696279e382999.png', 0, '2026-01-10 16:10:11'),
(25, 16, 'uploads/products/prod_69627a9f05349.png', 1, '2026-01-10 16:13:19'),
(26, 15, 'uploads/products/img_69627aaddd9a2.png', 0, '2026-01-10 16:13:33'),
(27, 16, 'uploads/products/img_69627ab392ac1.png', 0, '2026-01-10 16:13:39'),
(28, 17, 'uploads/products/prod_69627b31b82dd.png', 1, '2026-01-10 16:15:45'),
(29, 17, 'uploads/products/img_69627b41e4b09.jpg', 0, '2026-01-10 16:16:01'),
(30, 18, 'uploads/products/prod_69627bd023130.jpg', 1, '2026-01-10 16:18:24'),
(31, 17, 'uploads/products/img_69627bd9dc51f.jpg', 0, '2026-01-10 16:18:33'),
(33, 17, 'uploads/products/img_69627d33badcc.jpg', 0, '2026-01-10 16:24:19'),
(34, 1, 'uploads/products/img_69627d3c57c8b.png', 0, '2026-01-10 16:24:28'),
(43, 19, 'uploads/products/img_69632227d4867.png', 0, '2026-01-11 04:08:07'),
(46, 19, 'uploads/products/img_6963226a9c995.png', 0, '2026-01-11 04:09:14'),
(45, 19, 'uploads/products/img_69632237aa6bb.png', 0, '2026-01-11 04:08:23'),
(48, 13, 'uploads/products/img_69632309e3a6f.jpg', 0, '2026-01-11 04:11:53'),
(49, 13, 'uploads/products/img_6963230fd9ef8.jpg', 0, '2026-01-11 04:11:59'),
(51, 12, 'uploads/products/img_696323e71f65f.jpg', 0, '2026-01-11 04:15:35'),
(52, 12, 'uploads/products/img_696323ee03772.jpg', 0, '2026-01-11 04:15:42'),
(54, 9, 'uploads/products/img_696324860afe0.png', 0, '2026-01-11 04:18:14'),
(55, 9, 'uploads/products/img_69632490a03b7.png', 0, '2026-01-11 04:18:24'),
(56, 13, 'uploads/products/img_69632495d8277.png', 0, '2026-01-11 04:18:29'),
(58, 14, 'uploads/products/img_696325ba02d5f.webp', 0, '2026-01-11 04:23:22'),
(59, 14, 'uploads/products/img_696325c4ab90d.jfif', 0, '2026-01-11 04:23:32'),
(60, 14, 'uploads/products/img_696325cc15041.png', 0, '2026-01-11 04:23:40'),
(62, 4, 'uploads/products/img_69632730079c0.jpg', 0, '2026-01-11 04:29:36'),
(63, 4, 'uploads/products/img_696327349fcce.jpg', 0, '2026-01-11 04:29:40'),
(64, 4, 'uploads/products/img_6963273d3e6e8.jpg', 0, '2026-01-11 04:29:49'),
(65, 4, 'uploads/products/img_696327442b11e.jpg', 0, '2026-01-11 04:29:56'),
(66, 20, 'uploads/products/prod_696327ceb4ee2.jpg', 1, '2026-01-11 04:32:14'),
(67, 20, 'uploads/products/img_696327dd57976.jpg', 0, '2026-01-11 04:32:29'),
(68, 20, 'uploads/products/img_696327e4cc825.jpg', 0, '2026-01-11 04:32:36'),
(69, 20, 'uploads/products/img_696327eade024.jpg', 0, '2026-01-11 04:32:42'),
(71, 6, 'uploads/products/img_69632846e11e9.jpg', 0, '2026-01-11 04:34:14'),
(72, 6, 'uploads/products/img_69632860771c1.jpg', 0, '2026-01-11 04:34:40'),
(74, 3, 'uploads/products/img_69632957a4383.jpg', 0, '2026-01-11 04:38:47'),
(75, 3, 'uploads/products/img_6963295f46bc9.jpg', 0, '2026-01-11 04:38:55'),
(76, 3, 'uploads/products/img_696329656c668.jpg', 0, '2026-01-11 04:39:01'),
(78, 8, 'uploads/products/img_69632f043bc4a.jpg', 0, '2026-01-11 05:03:00'),
(79, 8, 'uploads/products/img_69633194236e6.jpg', 0, '2026-01-11 05:13:56'),
(82, 10, 'uploads/products/img_6963330a038fd.jpg', 0, '2026-01-11 05:20:10'),
(84, 7, 'uploads/products/img_6963336993762.jpg', 0, '2026-01-11 05:21:45'),
(86, 5, 'uploads/products/img_6963348c06837.jpg', 0, '2026-01-11 05:26:36'),
(87, 5, 'uploads/products/img_6963349736582.jpg', 0, '2026-01-11 05:26:47'),
(88, 5, 'uploads/products/img_6963349f0c086.jpg', 0, '2026-01-11 05:26:55'),
(89, 5, 'uploads/products/img_696334a88aa95.jpg', 0, '2026-01-11 05:27:04'),
(90, 21, 'uploads/products/prod_6984d43358ea1.jpg', 1, '2026-02-05 17:32:35'),
(91, 21, 'uploads/products/img_6984d4555ea4f.jpg', 0, '2026-02-05 17:33:09'),
(92, 21, 'uploads/products/img_6984d460a64cf.jpg', 0, '2026-02-05 17:33:20'),
(93, 21, 'uploads/products/img_6984d46bc6710.jpg', 0, '2026-02-05 17:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `size_id` int NOT NULL,
  `color_id` int NOT NULL,
  `stock` int DEFAULT '0',
  `reserved_stock` int NOT NULL DEFAULT '0',
  `sku` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `size_id` (`size_id`),
  KEY `color_id` (`color_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size_id`, `color_id`, `stock`, `reserved_stock`, `sku`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 50, 0, '', 'active', '2025-12-14 05:46:41'),
(2, 1, 2, 2, 50, 4, '', 'active', '2025-12-14 05:46:48'),
(3, 5, 2, 3, 50, 0, '', 'active', '2025-12-14 05:47:01'),
(4, 4, 2, 2, 50, 3, '', 'active', '2025-12-14 05:47:16'),
(5, 5, 1, 2, 38, -1, '', 'active', '2025-12-14 05:47:27'),
(6, 9, 1, 3, 4, 0, '', 'active', '2026-01-15 17:22:32'),
(7, 9, 3, 3, 6, 0, '', 'active', '2026-01-15 17:22:53'),
(8, 8, 1, 2, 14, 0, '', 'active', '2026-01-16 17:05:36'),
(9, 8, 3, 2, 10, 0, '', 'active', '2026-01-16 17:06:16'),
(10, 18, 1, 2, 10, 0, '', 'active', '2026-01-24 14:38:14'),
(11, 18, 2, 2, 10, 0, '', 'active', '2026-01-24 14:38:38'),
(12, 21, 1, 2, 24, 0, '', 'active', '2026-02-05 17:47:01'),
(13, 21, 1, 6, 25, 0, '', 'active', '2026-02-05 17:47:43'),
(14, 21, 2, 2, 25, 0, '', 'active', '2026-02-05 17:48:19'),
(15, 21, 2, 6, 25, 0, '', 'active', '2026-02-05 17:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'free_delivery_min_amount', '15000');

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
CREATE TABLE IF NOT EXISTS `sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `size_label` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `size_label`, `status`) VALUES
(1, '42', 'active'),
(2, '41', 'active'),
(3, '43', 'active'),
(5, '44', 'active'),
(6, '16', 'active'),
(7, '17', 'active'),
(8, '18', 'active'),
(9, '19', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','stock_keeper','customer') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Admin User', 'admin@shoeshop.com', '$2y$10$eDZBBQWlW4U3tq0p7drg5.KV/5yljndldMTZkkqHieN3.NsZntlge', 'admin', 'active', '2025-12-13 13:59:02'),
(2, 'Stock Keeper', 'stock@gmail.com', '$2y$10$y9AXtiWkuyrwaESNob8uje1HHZCWh2w0GdcopNhpCsXBw3w3Rez2K', 'stock_keeper', 'active', '2025-12-13 19:00:26');

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

DROP TABLE IF EXISTS `website_settings`;
CREATE TABLE IF NOT EXISTS `website_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Pino Shoe Shop', '2025-12-14 08:42:29', '2025-12-27 15:35:08'),
(2, 'site_tagline', 'Step into Comfort', '2025-12-14 08:42:29', '2025-12-14 08:43:46'),
(3, 'header_logo', 'header_logo_694842d8c484c.png', '2025-12-14 08:42:29', '2025-12-21 18:56:24'),
(4, 'footer_logo', 'footer_logo_694842d8c6728.png', '2025-12-14 08:42:29', '2025-12-21 18:56:24'),
(5, 'hero_title', 'Premium shoes for every step you take.', '2025-12-14 08:42:29', '2025-12-15 15:44:15'),
(6, 'hero_subtitle', 'Discover curated collections for men, women, and kids.', '2025-12-14 08:42:29', '2025-12-15 15:44:15'),
(7, 'hero_btn1_label', 'Shop Now', '2025-12-14 08:42:29', '2025-12-21 06:13:39'),
(8, 'hero_btn1_link', 'http://localhost/shoe-shop/public/shop.php', '2025-12-14 08:42:29', '2025-12-21 06:13:39'),
(9, 'hero_btn2_label', 'Explore', '2025-12-14 08:42:29', '2025-12-14 08:42:29'),
(10, 'hero_btn2_link', 'shop.php', '2025-12-14 08:42:29', '2025-12-15 16:08:17'),
(11, 'hero_image', 'hero_image_69402cba32ffa.png', '2025-12-14 08:42:29', '2025-12-15 15:43:54'),
(12, 'service1_enabled', '1', '2025-12-14 08:42:29', '2025-12-15 17:16:56'),
(13, 'service1_icon', 'bi bi-patch-check', '2025-12-14 08:42:29', '2026-02-05 17:44:08'),
(14, 'service1_title', 'Quality Products', '2025-12-14 08:42:29', '2026-02-05 17:38:58'),
(15, 'service1_text', 'Durable and comfortable footwear.', '2025-12-14 08:42:29', '2026-02-05 17:38:58'),
(16, 'service2_enabled', '1', '2025-12-14 08:42:29', '2025-12-15 17:12:42'),
(17, 'service2_icon', 'bi bi-person-walking', '2025-12-14 08:42:29', '2026-02-05 17:45:14'),
(18, 'service2_title', 'Perfect Fit', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(19, 'service2_text', 'Wide range of sizes and colors', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(20, 'service3_enabled', '1', '2025-12-14 08:42:29', '2025-12-15 17:12:42'),
(21, 'service3_icon', 'bi bi-shield-lock', '2025-12-14 08:42:29', '2026-02-05 17:44:08'),
(22, 'service3_title', 'Secure Payment', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(23, 'service3_text', 'Safe and easy checkout', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(24, 'service4_enabled', '1', '2025-12-14 08:42:29', '2026-02-05 17:41:44'),
(25, 'service4_icon', 'bi bi-headset', '2025-12-14 08:42:29', '2026-02-05 17:44:08'),
(26, 'service4_title', 'Customer Support', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(27, 'service4_text', 'We are here to help you', '2025-12-14 08:42:29', '2026-02-05 17:41:31'),
(28, 'cat_kids_img', 'cat_kids_img_69632df638937.jpeg', '2025-12-14 08:42:29', '2026-01-11 04:58:30'),
(29, 'cat_men_img', 'cat_men_img_69632d2f997b6.jpg', '2025-12-14 08:42:29', '2026-01-11 04:55:11'),
(30, 'cat_women_img', 'cat_women_img_69632cbcabb5b.jpg', '2025-12-14 08:42:29', '2026-01-11 04:53:16'),
(31, 'latest_products_limit', '4', '2025-12-14 08:42:29', '2025-12-17 17:43:02'),
(32, 'featured_products_limit', '8', '2025-12-14 08:42:29', '2025-12-14 08:43:46'),
(33, 'popular_products_limit', '4', '2025-12-14 08:42:29', '2025-12-14 08:42:29'),
(34, 'footer_address', '131/A, Kandakapapu Junction, Kirillawala', '2025-12-14 08:42:29', '2025-12-27 15:34:56'),
(35, 'footer_phone', '+94 078 654 9356', '2025-12-14 08:42:29', '2025-12-27 15:34:56'),
(36, 'footer_email', 'pinoshoe@gmail.com', '2025-12-14 08:42:29', '2025-12-27 15:34:56'),
(37, 'opening_hours', 'Mon – Sat : 9.00 AM – 7.00 PM', '2025-12-14 08:42:29', '2025-12-14 08:43:46'),
(38, 'social_facebook_enabled', '1', '2025-12-14 08:42:29', '2025-12-17 18:45:53'),
(39, 'social_facebook_url', 'https://web.facebook.com/p/piNo-product-61555766675450/?_rdc=1&_rdr#', '2025-12-14 08:42:29', '2026-01-11 05:31:09'),
(40, 'social_instagram_enabled', '0', '2025-12-14 08:42:29', '2026-01-11 05:31:09'),
(41, 'social_instagram_url', 'https://instagram.com', '2025-12-14 08:42:29', '2025-12-14 08:42:29'),
(42, 'social_tiktok_enabled', '1', '2025-12-14 08:42:29', '2026-01-11 05:31:09'),
(43, 'social_tiktok_url', 'https://www.tiktok.com/@pino_shoes01', '2025-12-14 08:42:29', '2026-01-11 05:31:09'),
(44, 'social_youtube_enabled', '1', '2025-12-14 08:42:29', '2026-01-11 05:31:16'),
(45, 'social_youtube_url', 'https://www.youtube.com/channel/UCTXq7ptfUo3tgn-XE0zyuVw', '2025-12-14 08:42:29', '2026-01-11 05:31:09'),
(52, 'hero_btn4_label', '', '2025-12-15 16:24:47', '2025-12-15 16:24:47'),
(46, 'active_tab', 'services', '2025-12-14 09:31:25', '2026-02-05 17:38:58'),
(47, 'hero_btn1_active', '1', '2025-12-15 16:08:17', '2025-12-15 16:08:17'),
(48, 'hero_btn2_active', '0', '2025-12-15 16:08:17', '2025-12-21 06:38:14'),
(49, 'hero_btn3_label', 'New Arrivals', '2025-12-15 16:08:17', '2025-12-15 16:08:17'),
(50, 'hero_btn3_link', 'shop.php?type=new', '2025-12-15 16:08:17', '2025-12-15 16:08:17'),
(51, 'hero_btn3_active', '0', '2025-12-15 16:08:17', '2025-12-21 06:37:55'),
(53, 'hero_btn4_link', '', '2025-12-15 16:24:47', '2025-12-15 16:24:47');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
