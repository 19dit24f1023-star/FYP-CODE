-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2026 at 02:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sa_design`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `customer_id`, `name`, `phone`, `address`, `created_at`) VALUES
(2, 6, 'Muhammad Aiman', '0123456789', 'Jalan Nuri, Batang kali', '2026-08-22 08:07:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `username`, `email`, `phone`, `password`, `created_at`, `reset_token`, `reset_expires`, `remember_token`, `remember_expires`, `image`) VALUES
(5, 'Administrator', 'admin_sadesign', 'sadesign914@gmail.com', '012-3456789', '$2y$10$3wnz..6FtJQSYMFU5G76cujGX.S5IF9OwpC55HVqsLLQ6txoa7pjS', '2026-08-29 13:01:45', 'def629fce28e88d35bcae03412f9be12200001e3a307612764e730746471e1b1', '2026-08-30 10:39:20', NULL, NULL, 'uploads/admin_5_1788663134.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `phone_number` varchar(12) NOT NULL,
  `address` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `phone_number`, `address`, `profile_image`, `reset_token`, `reset_expires`, `remember_token`, `remember_expires`) VALUES
(5, 'Akram Azman', 'akram@gmail.com', '$2y$10$ucBW9raEMCQKjBwKrp78f.1TMf.JRrYJ.w27j8jEzofHCS3jU3TMG', '012345667', 'Gombak', NULL, NULL, NULL, NULL, NULL),
(6, 'Muhammad Aiman', 'aiman@gmail.com', '$2y$10$N0MEm7AP79NWcgeSgOd7wu4aaQt6LjrPx8ui.egX0tV7iArffO5R6', '0123445781', '', 'profile_6_1787387721_a1da52b5.jpg', NULL, NULL, NULL, NULL),
(7, 'Nor Fariza', 'fariza@gmail.com', '$2y$10$eQ/P.SgXbuCtM/wmn17pveUjR3487/cs27fdDP.XHk6jhZ7SR3HLG', '0189152530', 'BANDAR JENGKA', NULL, NULL, NULL, NULL, NULL),
(8, 'Nor Farissha', 'farissha@gmail.com', '$2y$10$Laqh/7C1eVJ9.rQ0Ftj8GOoSAlQspywvezdA1UWb/aUlGAo7kqDrK', '0189152530', 'N.8 FELDA ULU JEMPOL', NULL, NULL, NULL, NULL, NULL),
(9, 'AINA FARZANA', 'aina@gmail.com', '$2y$10$f0kMU5h.atUecUaZH2oLNu5YUMpot30cYRsmVWsPLq7uZVDcHh7Ge', '0123456790', 'MELAKA', NULL, NULL, NULL, NULL, NULL),
(10, 'ALI', 'ali@gmail.com', '$2y$10$sYhFgygaNgfK7hImtQtzFetHDSrIRf8zQUTW8BFF.aU5Ho8zXvgEa', '01158543121', 'pms, pahang', 'profile_10_1787966536_a2cd18.jpg', NULL, NULL, NULL, NULL),
(15, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '$2y$10$LOcYMvNV9T0koi340MVsiu7wZAahADLFkvDeSgf7FpBJpZkXzb4M6', '01158543121', 'melaka', 'profile_15_1789525727_467d34.jpg', 'df46a16b0aaa0b9129698803c8b72df263964b6467fadf9bc7d7cd528444a72e', '2026-08-31 22:51:16', NULL, NULL),
(16, 'AINA FARZANA MOHD SHAHRIL', '19dit24f1003@pms.mypolycc.edu.my', '$2y$10$/VxOQHH13yc84iFrOEKcceOVKbNEvb5l0ifBHwri.iM67RuaAZ42S', '01158543121', 'pms', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custom_request`
--

CREATE TABLE `custom_request` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `collection_method` varchar(50) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `additional_info` text DEFAULT NULL,
  `artwork` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `job_flow` varchar(50) DEFAULT 'Admin',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_request`
--

INSERT INTO `custom_request` (`id`, `fullname`, `email`, `phone`, `collection_method`, `delivery_address`, `product_name`, `quantity`, `price`, `additional_info`, `artwork`, `created_at`, `job_flow`, `status`, `payment_status`, `payment_method`) VALUES
(3, 'Muhammad Aiman', 'aiman@gmail.com', '0123456789', NULL, NULL, 'CARD', 2, 0.00, 'WEDDING', 'Output_Lab9_20260822_094502_607e99.png', '2026-08-26 19:42:23', 'Admin', 'pending', 'Pending', 'N/A'),
(4, 'Muhammad Aiman', 'aiman@gmail.com', '0123456789', NULL, NULL, 'Baju', 1, 0.00, 'm', 'Output_Lab22_20260822_095054_115462.png', '2026-08-26 19:42:23', 'Admin', 'pending', 'Pending', 'N/A'),
(5, 'aina', 'ainafarzana521@gmail.com', '01158543121', 'Self Collection', '', 'keychain', 4, 20.00, 'test', 'download_20260909_050741_c4235a.jpg', '2026-09-09 11:07:41', 'Admin', 'Processing', 'Paid', 'Stripe'),
(7, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '01158543121', 'Delivery', 'pms, pahang', 'BUKU CERITA', 2, 10.00, 'test', 'baju3_20260909_054119_982e55.jpeg', '2026-09-09 11:41:19', 'Admin', 'Pending', 'Paid', 'Wallet'),
(8, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '01158543121', 'Self Collection', '', 'T-shirt', 1, 10.00, 'test', '7868c364469cb7db0557f018566d0bb9_20260909_151341_a45170.jpg', '2026-09-09 21:13:41', 'Admin', 'Processing', 'Paid', 'Online Banking / FPX'),
(9, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '01158543121', 'Self Collection', '', 'T-shirt', 1, 10.00, 'test', 'download__6_20260909_151817_d521ce.jpg', '2026-09-09 21:18:17', 'Admin', 'Pending', 'Paid', 'DuitNow QR'),
(11, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '01158543121', 'Delivery', 'pms, pahang', 'T-shirt', 2, 10.00, 'test', 'photo_2026-01-04_10-08-39_20260909_153223_5d3406.jpg', '2026-09-09 21:32:23', 'Admin', 'Pending', 'Paid', 'Cash on Collection'),
(12, 'aina', 'ainafarzana521@gmail.com', '012345678', 'Self Collection', '', 'T-shirt', 1, 10.00, 'test', 'banner2_20260910_103042_bb9cb3.png', '2026-09-10 16:30:42', 'Design', 'Pending', 'Pending', 'N/A'),
(13, 'AINA FARZANA MOHD SHAHRIL', 'ainafarzana521@gmail.com', '01158543121', 'Self Collection', '', 'T-shirt', 2, 1.00, 'test', 'baju4_20260912_034144_80f956.jpeg', '2026-09-12 09:41:45', 'Admin', 'Pending', 'Pending', 'ToyyibPay (FPX)'),
(14, 'Aina', 'ainafarzana521@gmail.com', '01158543121', 'Delivery', 'Bukit Katil, Melaka', 'Custom Banner', 2, 0.00, 'Promotional banner', 'd82831fda7d8fdbbb28035966707e0d9_20260914_160726_d79507.jpg', '2026-09-14 22:07:26', 'Admin', 'pending', 'Pending', 'N/A');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(40) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `order_total` decimal(10,2) DEFAULT NULL,
  `shipping_fee` decimal(10,2) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `collection_method` varchar(30) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending',
  `toyyibpay_billcode` varchar(100) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `artwork` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `job_flow` varchar(50) DEFAULT 'Admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `order_total`, `shipping_fee`, `size`, `note`, `delivery_address`, `collection_method`, `payment_method`, `payment_status`, `toyyibpay_billcode`, `payment_reference`, `artwork`, `status`, `created_at`, `job_flow`) VALUES
(23, 'SAD-20260822-B9FB3', 6, NULL, 'Custom Sublimation Apparel', 15, 29.00, 435.00, 10.00, 'ROUNDNECK SHORT SLEEVE / XS', 'Order reference: SAD-20260822-B9FB3\nPayment method: DuitNow QR\nCustomer note: hai\nItem details: \nAdditional notes: ', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-08-22 16:23:29', 'Admin'),
(24, 'SAD-20260826-403A5', 6, 1, 'Custom Self-Inking Stamp', 1, 28.00, 28.00, 10.00, 'CODE 4912 (47x18mm)', 'Order reference: SAD-20260826-403A5\nPayment method: DuitNow QR\nCustomer note: hehe\nItem details: Stamp model: CODE 4912 (47x18mm)\nAdditional notes: ', NULL, 'Delivery', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-08-26 19:04:04', 'Admin'),
(25, 'SAD-20260826-3A66B', 6, NULL, 'Custom Sublimation Apparel', 15, 29.00, 435.00, 10.00, 'ROUNDNECK SHORT SLEEVE / XS', 'Order reference: SAD-20260826-3A66B\nPayment method: DuitNow QR\nCustomer note: \nItem details: \nAdditional notes: ', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-08-26 20:29:49', 'Admin'),
(26, 'SAD-20260826-73FB8', 6, NULL, 'Banner & Bunting Printing', 1, 15.00, 15.00, 10.00, '2 X 2 FT', 'Order reference: SAD-20260826-73FB8\nPayment method: DuitNow QR\nCustomer note: \nItem details: \nAdditional notes: ', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-08-26 20:35:15', 'Admin'),
(27, 'SAD-20260826-D35A1', 9, NULL, 'Banner & Bunting Printing', 1, 15.00, 15.00, 10.00, '2 X 2 FT', 'Order reference: SAD-20260826-D35A1\nPayment method: Cash on collection\nCustomer note: \nItem details: \nAdditional notes: ', NULL, 'Self collection', 'Cash on collection', 'Pending', NULL, NULL, NULL, 'Completed', '2026-08-26 22:05:20', 'Admin'),
(28, 'SAD-20260828-64D9B', 10, 1, 'Custom Self-Inking Stamp', 1, 28.00, 28.00, 10.00, 'CODE 4912 (47x18mm)', 'Order reference: SAD-20260828-64D9B\nPayment method: Online banking / FPX\nCustomer note: halo\nItem details: Stamp model: CODE 4912 (47x18mm)\nAdditional notes: ', NULL, 'Delivery', 'Online banking / FPX', 'Pending', NULL, NULL, NULL, 'Ready', '2026-08-28 08:10:37', 'Admin'),
(29, 'SAD-20260902-9F9EC', 15, 1, 'Custom Self-Inking Stamp', 1, 35.00, 35.00, 10.00, 'CODE 4913 (58x22mm)', 'Order reference: SAD-20260902-9F9EC\nPayment method: Online banking / FPX\nCustomer note: buat cantik ii\nItem details: Stamp model: CODE 4913 (58x22mm)\r\nStamp details: AINA\r\nSTUDENT\nAdditional notes: elok ii', NULL, 'Delivery', 'Online banking / FPX', 'Pending', NULL, NULL, 'photo_2025-01-17_16-15-47.jpg', 'Processing', '2026-09-02 15:28:10', 'Design'),
(30, 'SAD-20260904-00674', 15, NULL, 'Custom Sublimation Apparel', 15, 29.00, 435.00, 10.00, 'ROUNDNECK SHORT SLEEVE / XS', 'Order reference: SAD-20260904-00674\nPayment method: Online banking / FPX\nCustomer note: hii\nItem details: \nAdditional notes: rengoku', NULL, 'Delivery', 'Online banking / FPX', 'Pending', NULL, NULL, 'download (6).jpg', 'Pending', '2026-09-04 21:26:07', 'Admin'),
(40, 'SAD-20260906-D5EA8', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'Standard', 'Order reference: SAD-20260906-D5EA8\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS', NULL, 'Self collection', 'Online banking / FPX', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-06 15:19:47', 'Admin'),
(41, 'SAD-20260906-121CB', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'Standard', 'Order reference: SAD-20260906-121CB\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS', NULL, 'Self collection', 'Online banking / FPX', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-06 15:20:01', 'Admin'),
(42, 'SAD-20260906-653EF', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'Standard', 'Order reference: SAD-20260906-653EF\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS', NULL, 'Self collection', 'Online banking / FPX', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-06 15:23:11', 'Admin'),
(43, 'SAD-20260906-AE59F', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'Standard', 'Order reference: SAD-20260906-AE59F\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 200 PCS', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-06 15:59:47', 'Production'),
(44, 'SAD-20260906-AE59F', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260906-AE59F\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-06 15:59:47', 'Production'),
(45, 'SAD-20260906-2F544', 15, 9, 'Premium Wedding & Business Cards', 1, 28.00, 28.00, 10.00, 'Standard', 'Order reference: SAD-20260906-2F544\nCustomer note: test\nItem details: Type: card_options [SOFT TOUCH] - 100 PCS\nDelivery address: pms, pahang', NULL, 'Delivery', 'Online banking / FPX', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-06 16:07:23', 'Admin'),
(46, 'SAD-20260906-E8065', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 10.00, 'Standard', 'Order reference: SAD-20260906-E8065\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', 'pms, pahang', 'Delivery', 'Online banking / FPX', 'Paid', NULL, NULL, 'uploads/artworks/artwork_1788682415_b13bf9d9.jpg', 'Processing', '2026-09-06 16:13:35', 'Admin'),
(47, 'SAD-20260908-C71AC', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260908-C71AC\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Online banking / FPX', 'Pending', NULL, NULL, 'uploads/artworks/artwork_1788853441_5e59c2bd.jpeg', 'Pending', '2026-09-08 15:44:01', 'Admin'),
(48, 'SAD-20260908-3D62C', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260908-3D62C\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Online banking / FPX', 'Paid', NULL, NULL, 'uploads/artworks/artwork_1788853693_f9f01b43.jpeg', 'Processing', '2026-09-08 15:48:13', 'Admin'),
(49, 'SAD-20260908-37E97', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260908-37E97\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'Online banking / FPX', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-08 16:12:42', 'Admin'),
(50, 'SAD-20260908-E9CAA', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260908-E9CAA\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Online banking / FPX', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-08 16:26:30', 'Admin'),
(51, 'SAD-20260908-E9CAA', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260908-E9CAA\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'Online banking / FPX', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-08 16:26:30', 'Admin'),
(52, 'SAD-20260908-DAEB4', 15, 9, 'Premium Wedding & Business Cards', 1, 28.00, 28.00, 0.00, 'Standard', 'Order reference: SAD-20260908-DAEB4\nCustomer note: test\nItem details: Type: card_options [SOFT TOUCH] - 100 PCS', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-08 16:35:33', 'Admin'),
(53, 'SAD-20260908-134A2', 15, 8, 'Banner & Bunting Printing', 1, 15.00, 15.00, 0.00, '2 X 2 FT', 'Order reference: SAD-20260908-134A2\nCustomer note: test\nItem details: Size: 2 X 2 FT', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-08 16:38:07', 'Admin'),
(54, 'SAD-20260908-84130', 15, 8, 'Banner & Bunting Printing', 1, 15.00, 15.00, 0.00, '2 X 2 FT', 'Order reference: SAD-20260908-84130\nCustomer note: test\nItem details: Size: 2 X 2 FT', NULL, 'Self collection', 'Online banking / FPX', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-08 16:38:15', 'Admin'),
(55, 'SAD-20260908-0D15C', 15, 8, 'Banner & Bunting Printing', 1, 15.00, 15.00, 0.00, '2 X 2 FT', 'Order reference: SAD-20260908-0D15C\nCustomer note: test\nItem details: Size: 2 X 2 FT', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-08 16:42:16', 'Admin'),
(56, 'SAD-20260908-DC3E9', 15, 8, 'Banner & Bunting Printing', 1, 15.00, 15.00, 0.00, '2 X 2 FT', 'Order reference: SAD-20260908-DC3E9\nCustomer note: test\nItem details: Size: 2 X 2 FT', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-08 16:46:12', 'Admin'),
(57, 'SAD-20260909-DE6D9', 15, 7, 'Custom Sublimation Apparel', 1, 29.00, 29.00, 0.00, 'XS', 'Order reference: SAD-20260909-DE6D9\nCustomer note: test\nItem details: Type: ROUNDNECK SHORT SLEEVE\r\nSize: XS', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, 'uploads/artworks/artwork_1788920522_cb84356c.jpg', 'Pending', '2026-09-09 10:22:02', 'Admin'),
(58, 'SAD-20260909-0CF7B', 15, 7, 'Custom Sublimation Apparel', 1, 29.00, 29.00, 0.00, 'XS', 'Order reference: SAD-20260909-0CF7B\nCustomer note: test\nItem details: Type: ROUNDNECK SHORT SLEEVE\r\nSize: XS', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-09 10:25:51', 'Admin'),
(59, 'SAD-20260909-EA374', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260909-EA374\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-09 10:28:31', 'Admin'),
(60, 'SAD-20260909-BD821', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260909-BD821\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-09 10:31:06', 'Admin'),
(61, 'SAD-20260909-A4D98', 15, 9, 'Premium Wedding & Business Cards', 1, 28.00, 28.00, 0.00, 'Standard', 'Order reference: SAD-20260909-A4D98\nCustomer note: test\nItem details: Type: card_options [SOFT TOUCH] - 100 PCS', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-09 21:07:44', 'Admin'),
(62, 'SAD-20260909-D4EE2', 15, 9, 'Premium Wedding & Business Cards', 1, 28.00, 28.00, 10.00, 'Standard', 'Order reference: SAD-20260909-D4EE2\nCustomer note: test\nItem details: Type: card_options [SOFT TOUCH] - 100 PCS', 'pms, pahang', 'Delivery', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-09 21:40:52', 'Admin'),
(63, 'SAD-20260909-CE151', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260909-CE151\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-09 21:45:38', 'Admin'),
(64, 'SAD-20260910-A01ED', 15, 9, 'Premium Wedding & Business Cards', 1, 28.00, 28.00, 0.00, 'Standard', 'Order reference: SAD-20260910-A01ED\nCustomer note: test\nItem details: Type: card_options [SOFT TOUCH] - 100 PCS', NULL, 'Self collection', 'DuitNow QR', 'Paid', NULL, NULL, NULL, 'Processing', '2026-09-10 14:37:11', 'Admin'),
(70, 'SAD-20260910-70B0A', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260910-70B0A\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Cash on collection', 'Pending', NULL, NULL, 'uploads/artworks/artwork_1789022972_8dbbd18f.jpg', 'Pending', '2026-09-10 14:49:32', 'Admin'),
(71, 'SAD-20260910-47F1B', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 0.00, 'CIRCLE', 'Order reference: SAD-20260910-47F1B\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE', NULL, 'Self collection', 'Wallet', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-10 16:29:07', 'Admin'),
(72, 'SAD-20260910-1A666', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260910-1A666\nCustomer note: test\nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'DuitNow QR', 'Pending', NULL, NULL, NULL, 'Completed', '2026-09-10 16:32:01', 'Admin'),
(73, 'SAD-20260912-11675', 15, 11, 'Mirrokote Product Stickers (Round & Square)', 1, 62.00, 62.00, 10.00, 'CIRCLE', 'Order reference: SAD-20260912-11675\nCustomer note: test\nItem details: Type: sticker_options [3 CM] - 100 PCS\r\nSize: CIRCLE\nDelivery address: pms, pahang', NULL, 'Delivery', 'Wallet', 'Paid', NULL, NULL, 'baju2.jpg', 'Processing', '2026-09-12 09:13:09', 'Admin'),
(75, 'SAD-20260912-8117F', 15, NULL, 'keychain', 1, 5.00, 5.00, 0.00, 'Standard', 'Order reference: SAD-20260912-8117F\nCustomer note: test\nItem details: ', NULL, 'Self collection', 'Online banking / FPX', 'Pending', '7x55iddc', NULL, NULL, 'Pending', '2026-09-12 09:35:51', 'Admin'),
(76, 'SAD-20260912-26CB6', 15, NULL, 'keychain', 1, 5.00, 5.00, 0.00, 'Standard', 'Order reference: SAD-20260912-26CB6\nCustomer note: test\nItem details: ', NULL, 'Self collection', 'Cash on collection', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-12 09:36:53', 'Admin'),
(77, 'SAD-20260912-CFFC1', 15, NULL, 'keychain', 1, 5.00, 5.00, 0.00, 'Standard', 'Order reference: SAD-20260912-CFFC1\nCustomer note: test\nItem details: ', NULL, 'Self collection', 'Cash on collection', 'Pending', NULL, NULL, NULL, 'Processing', '2026-09-12 09:40:34', 'Admin'),
(78, 'SAD-20260916-53866', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260916-53866\nCustomer note: \nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Online banking / FPX', 'Pending', 'nbvgv4fv', NULL, 'baju5.jpeg', 'Pending', '2026-09-16 18:35:24', 'Admin'),
(79, 'SAD-20260916-BCBCF', 15, 10, 'Outdoor Promotional Windflag', 1, 190.00, 190.00, 0.00, 'Standard', 'Order reference: SAD-20260916-BCBCF\nCustomer note: \nItem details: Type: windflag_options - 3.4 METERS', NULL, 'Self collection', 'Online banking / FPX', 'Pending', '3ehnv5dk', NULL, 'baju5.jpeg', 'Pending', '2026-09-16 18:35:31', 'Admin'),
(80, 'SAD-20260917-A017C', 15, 8, 'Banner & Bunting Printing', 1, 15.00, 15.00, 0.00, '2 X 2 FT', 'Order reference: SAD-20260917-A017C\nCustomer note: test\nItem details: Size: 2 X 2 FT', NULL, 'Self collection', 'Cash on collection', 'Pending', NULL, NULL, NULL, 'Pending', '2026-09-17 19:30:22', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Uncategorized',
  `badge` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `options` text DEFAULT NULL,
  `minimum_order` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `badge`, `price`, `image`, `gallery`, `description`, `features`, `specifications`, `options`, `minimum_order`, `status`) VALUES
(1, 'Custom Self-Inking Stamp', 'Stationery', 'STAMP · STATIONERY', 25.00, 'uploads/1788643273_8070.png', '[\"uploads\\/gallery\\/1788643484_8710.png\",\"uploads\\/gallery\\/1788643484_2783.png\",\"uploads\\/gallery\\/1788643484_3443.png\",\"uploads\\/gallery\\/1788643484_9208.png\",\"uploads\\/gallery\\/1788643484_3202.png\"]', 'High-quality, durable self-inking stamp delivering sharp and clean impressions without requiring an external ink pad. Ideal for office, school, and official documentation use.', '[\"Automatic self-inking mechanism (No external ink pad required)\",\"Durable for up to 5,000 impressions before refilling ink\",\"Sharp, crisp, and fast-drying ink output\",\"Multiple sizes available for various applications (Text\\/Logo)\"]', '{\"Ink Type\":\"Water-based Ink (Black \\/ Blue \\/ Red)\",\"Body Material\":\"Heavy-duty ABS Plastic\",\"Processing Time\":\"1 - 3 Business Days\"}', '[{\"name\":\"CODE 4912 (47x18mm)\",\"price\":28},{\"name\":\"CODE 4913 (58x22mm)\",\"price\":35},{\"name\":\"CODE 9511 (Pocket Stamp)\",\"price\":28},{\"name\":\"CODE 4630 (Round 30mm)\",\"price\":35},{\"name\":\"CODE 4916 (70x10mm)\",\"price\":25}]', '1', 'Active'),
(7, 'Custom Sublimation Apparel', 'Sportswear', 'SPORTSWEAR · PRINT', 29.00, 'product_images/baju1.jpg', '[\"product_images\\/baju1.jpg\",\"product_images\\/baju2.jpg\",\"product_images\\/baju3.jpeg\",\"product_images\\/baju4.jpeg\",\"product_images\\/baju5.jpeg\"]', 'Premium full-sublimation printed jersey/apparel. Features vibrant colours, fade-resistant print, and comfortable fabric suitable for sports activities or corporate events.', '[\"Full-Color Sublimation Printing (Unlimited colours & sharp details)\",\"Premium Microfiber Fabric (Moisture-wicking & quick-dry)\",\"Long-lasting print quality (No cracking, peeling, or fading)\",\"Custom names, numbers, and sponsor logos included at no extra cost\",\"Comprehensive size range from XS to 5XL\"]', '{\"Fabric Material\":\"Microfiber Eyelet \\/ Interlock 150-170 GSM\",\"Fabric Benefits\":\"Breathable, Quick-Dry, Lightweight\",\"Minimum Order\":\"15 PCS\",\"Lead Time\":\"2 - 3 Working Weeks\"}', '[{\"name\":\"type_options - ROUNDNECK SHORT SLEEVE\",\"price\":29},{\"name\":\"type_options - ROUNDNECK LONG SLEEVE\",\"price\":35},{\"name\":\"type_options - MUSLIMAH CUT\",\"price\":36},{\"name\":\"type_options - SHORT SLEEVE POLO COLLAR\",\"price\":34},{\"name\":\"type_options - SHORT SLEEVE RETRO COLLAR\",\"price\":36},{\"name\":\"size_options - XS\",\"price\":0},{\"name\":\"size_options - S\",\"price\":0},{\"name\":\"size_options - M\",\"price\":0},{\"name\":\"size_options - L\",\"price\":0},{\"name\":\"size_options - XL\",\"price\":0},{\"name\":\"size_options - 2XL\",\"price\":0},{\"name\":\"size_options - 3XL\",\"price\":0},{\"name\":\"size_options - 4XL\",\"price\":0},{\"name\":\"size_options - 5XL\",\"price\":0}]', '15 PCS', 'active'),
(8, 'Banner & Bunting Printing', 'Large Format Printing', 'LARGE FORMAT PRINTING', 15.00, 'product_images/banting1.jpeg', '[\"product_images\\/banting1.jpeg\",\"product_images\\/banting2.jpeg\",\"product_images\\/banting3.jpeg\",\"product_images\\/banting4.jpeg\",\"product_images\\/banting5.jpeg\"]', 'High-resolution banner and bunting printing for business promotions, events, or outdoor advertising. Fully weather-resistant and vibrant.', '[\"Waterproof & UV-resistant Tarpaulin material\",\"Suitable for both Indoor & Outdoor promotional setups\",\"Free finishing options (Eyelets \\/ Wooden Rods \\/ Pocket \\/ Cut-to-size)\",\"High-definition eco-friendly print output\"]', '{\"Tarpaulin Material\":\"380 GSM \\/ 440 GSM Premium Flex\",\"Outdoor Lifespan\":\"6 - 12 Months\",\"Lead Time\":\"2 - 4 Business Days\"}', '[{\"name\":\"size_options - 2 X 2 FT\",\"price\":15},{\"name\":\"size_options - 2 X 3 FT\",\"price\":21},{\"name\":\"size_options - 2 X 4 FT\",\"price\":27},{\"name\":\"size_options - 2 X 5 FT\",\"price\":34},{\"name\":\"size_options - 2 X 6 FT\",\"price\":40},{\"name\":\"size_options - 4 X 2 FT\",\"price\":25},{\"name\":\"size_options - 4 X 3 FT\",\"price\":38},{\"name\":\"size_options - 4 X 4 FT\",\"price\":51},{\"name\":\"size_options - 4 X 5 FT\",\"price\":64},{\"name\":\"size_options - 4 X 6 FT\",\"price\":76},{\"name\":\"size_options - 4 X 7 FT\",\"price\":89},{\"name\":\"size_options - 5 X 2 FT\",\"price\":32},{\"name\":\"size_options - 5 X 3 FT\",\"price\":48},{\"name\":\"size_options - 5 X 4 FT\",\"price\":64},{\"name\":\"size_options - 5 X 5 FT\",\"price\":80},{\"name\":\"size_options - 5 X 6 FT\",\"price\":96},{\"name\":\"size_options - 5 X 7 FT\",\"price\":112},{\"name\":\"size_options - 6 X 2 FT\",\"price\":38},{\"name\":\"size_options - 6 X 3 FT\",\"price\":55},{\"name\":\"size_options - 6 X 4 FT\",\"price\":75},{\"name\":\"size_options - 6 X 5 FT\",\"price\":96},{\"name\":\"size_options - 6 X 6 FT\",\"price\":115},{\"name\":\"size_options - 6 X 7 FT\",\"price\":134},{\"name\":\"size_options - 7 X 2 FT\",\"price\":41},{\"name\":\"size_options - 7 X 3 FT\",\"price\":62},{\"name\":\"size_options - 7 X 4 FT\",\"price\":83},{\"name\":\"size_options - 7 X 5 FT\",\"price\":104},{\"name\":\"size_options - 7 X 6 FT\",\"price\":125},{\"name\":\"size_options - 7 X 7 FT\",\"price\":146},{\"name\":\"size_options - 8 X 2 FT\",\"price\":51},{\"name\":\"size_options - 8 X 3 FT\",\"price\":76},{\"name\":\"size_options - 8 X 4 FT\",\"price\":99},{\"name\":\"size_options - 8 X 5 FT\",\"price\":128},{\"name\":\"size_options - 8 X 6 FT\",\"price\":153},{\"name\":\"size_options - 8 X 7 FT\",\"price\":179},{\"name\":\"size_options - 9 X 2 FT\",\"price\":57},{\"name\":\"size_options - 9 X 3 FT\",\"price\":86},{\"name\":\"size_options - 9 X 4 FT\",\"price\":115},{\"name\":\"size_options - 9 X 5 FT\",\"price\":144},{\"name\":\"size_options - 9 X 6 FT\",\"price\":172},{\"name\":\"size_options - 9 X 7 FT\",\"price\":201},{\"name\":\"size_options - 10 X 2 FT\",\"price\":64},{\"name\":\"size_options - 10 X 3 FT\",\"price\":96},{\"name\":\"size_options - 10 X 4 FT\",\"price\":120},{\"name\":\"size_options - 10 X 5 FT\",\"price\":160},{\"name\":\"size_options - 10 X 6 FT\",\"price\":192},{\"name\":\"size_options - 10 X 7 FT\",\"price\":224}]', '1 PCS', 'Active'),
(9, 'Premium Wedding & Business Cards', 'Stationery', 'EVENT PRINTING · STATIONERY', 28.00, 'product_images/card.jpeg', '[\"product_images\\/card1.jpeg\",\"product_images\\/card.jpeg\",\"product_images\\/card2.jpeg\",\"product_images\\/card3.jpeg\",\"product_images\\/card4.jpeg\"]', 'Professional-grade premium business cards. Perfect for elevating your corporate brand identity or creating elegant wedding invitation cards.', '[\"Thick, durable 300 GSM Art Card stock\",\"Finishing options: Soft Touch (Elegant matte feeling) or Glossy\",\"Full double-sided color printing included\",\"Standard dimension size 9 CM X 5.5 CM\"]', '{\"Card Stock\":\"300 GSM Art Card\",\"Finishing Options\":\"Soft Touch Lamination \\/ Gloss Lamination\",\"Minimum Order\":\"100 PCS\",\"Lead Time\":\"3 - 5 Business Days\"}', '[{\"name\":\"card_options [SOFT TOUCH] - 100 PCS\",\"price\":28},{\"name\":\"card_options [SOFT TOUCH] - 200 PCS\",\"price\":42},{\"name\":\"card_options [SOFT TOUCH] - 300 PCS\",\"price\":51},{\"name\":\"card_options [SOFT TOUCH] - 500 PCS\",\"price\":71},{\"name\":\"card_options [SOFT TOUCH] - 1000 PCS\",\"price\":88},{\"name\":\"card_options [GLOSSY TYPE] - 100 PCS\",\"price\":37},{\"name\":\"card_options [GLOSSY TYPE] - 200 PCS\",\"price\":53},{\"name\":\"card_options [GLOSSY TYPE] - 300 PCS\",\"price\":57},{\"name\":\"card_options [GLOSSY TYPE] - 500 PCS\",\"price\":79},{\"name\":\"card_options [GLOSSY TYPE] - 1000 PCS\",\"price\":103}]', '100 PCS', 'Active'),
(10, 'Outdoor Promotional Windflag', 'Large Format Printing', 'OUTDOOR ADVERTISING · SIGNAGE', 190.00, 'product_images/windflag1.jpeg', '[\"product_images\\/windflag1.jpeg\",\"product_images\\/windflag2.jpeg\",\"product_images\\/windflag.jpeg\"]', 'Outdoor windflag banner system ideal for shopfront promotions, corporate events, and product launches. Wind-resistant, highly visible, and professional.', '[\"Available height choices: 3.4 Meters & 5.0 Meters\",\"Weatherproof high-density Polyester Fabric\",\"Includes complete pole set and weighted base platform\",\"Vibrant high-resolution dye-sublimation print\"]', '{\"Windflag Height\":\"3.4 METERS \\/ 5.0 METERS\",\"Fabric Material\":\"110g Knitted Polyester\",\"Lead Time\":\"Approximately 1 Business Week (u00b1)\",\"Design Fee\":\"RM 30.00 (Applicable if artwork design is required)\"}', '[{\"name\":\"windflag_options - 3.4 METERS\",\"price\":190},{\"name\":\"windflag_options - 5.0 METERS\",\"price\":280}]', '1 PCS', 'Active'),
(11, 'Mirrokote Product Stickers (Round & Square)', 'Labels & Stickers', 'LABELS & STICKERS · PRINT', 62.00, 'product_images/sticker1.jpeg', '[\"product_images\\/sticker.jpeg\",\"product_images\\/sticker1.jpeg\",\"product_images\\/sticker2.jpeg\",\"product_images\\/sticker3.jpeg\"]', 'High-quality Mirrokote paper stickers with strong adhesive backing. Ideal for packaging labels, product branding, bottle stickers, and retail items.', '[\"Mirrokote Glossy surface finishing\",\"Strong adhesive backing suitable for various surfaces\",\"Precise Die-Cut \\/ Kiss-Cut finishing ready to peel & apply\",\"Flexible sizing options (3cm, 4cm, 5cm, 6cm)\"]', '{\"Sticker Material\":\"Mirrokote Paper Base (Glossy)\",\"Shape Options\":\"Round \\/ Square\",\"Lead Time\":\"5 - 7 Business Days\"}', '[{\"name\":\"type_options - sticker_options [3 CM] - 100 PCS\",\"price\":62},{\"name\":\"type_options - sticker_options [3 CM] - 200 PCS\",\"price\":65},{\"name\":\"type_options - sticker_options [3 CM] - 300 PCS\",\"price\":67},{\"name\":\"type_options - sticker_options [3 CM] - 500 PCS\",\"price\":80},{\"name\":\"type_options - sticker_options [3 CM] - 1000 PCS\",\"price\":89},{\"name\":\"type_options - sticker_options [4 CM] - 100 PCS\",\"price\":74},{\"name\":\"type_options - sticker_options [4 CM] - 200 PCS\",\"price\":77},{\"name\":\"type_options - sticker_options [4 CM] - 300 PCS\",\"price\":81},{\"name\":\"type_options - sticker_options [4 CM] - 500 PCS\",\"price\":87},{\"name\":\"type_options - sticker_options [4 CM] - 1000 PCS\",\"price\":105},{\"name\":\"type_options - sticker_options [5 CM] - 100 PCS\",\"price\":75},{\"name\":\"type_options - sticker_options [5 CM] - 200 PCS\",\"price\":82},{\"name\":\"type_options - sticker_options [5 CM] - 300 PCS\",\"price\":87},{\"name\":\"type_options - sticker_options [5 CM] - 500 PCS\",\"price\":97},{\"name\":\"type_options - sticker_options [5 CM] - 1000 PCS\",\"price\":124},{\"name\":\"type_options - sticker_options [6 CM] - 100 PCS\",\"price\":77},{\"name\":\"type_options - sticker_options [6 CM] - 200 PCS\",\"price\":85},{\"name\":\"type_options - sticker_options [6 CM] - 300 PCS\",\"price\":93},{\"name\":\"type_options - sticker_options [6 CM] - 500 PCS\",\"price\":108},{\"name\":\"type_options - sticker_options [6 CM] - 1000 PCS\",\"price\":150},{\"name\":\"size_options - CIRCLE\",\"price\":0},{\"name\":\"size_options - SQUARE\",\"price\":0}]', '100 PCS', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `review_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `customer_id`, `rating`, `review_text`, `created_at`, `review_image`) VALUES
(1, 1, 6, 4, 'bagusss  product ni', '2026-08-21 17:31:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `email`, `phone`, `position`, `status`, `created_at`) VALUES
('SAD001', 'ALI BIN AHMAD ', 'ali@gmail.com', '011-4853269', 'Staff', 'Active', '2026-09-02 12:13:13'),
('SAD002', 'FATIMAH ZAHRAH', 'fahtimah@gmail.com', '011-6527598', 'Graphic Designer', 'Active', '2026-09-02 12:14:01'),
('SAD003', 'MUSA', 'musa@gmail.com', '019-1583256', 'Staff', 'Active', '2026-09-02 14:24:45'),
('SAD004', 'INA ALINA', 'ina@gmail.com', '019-4798532', 'Manager', 'Active', '2026-09-02 14:25:58'),
('SAD005', 'NATASHA', 'natasha@gmail.com', '012345678', 'Staff', 'Active', '2026-09-06 02:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_accounts`
--

CREATE TABLE `wallet_accounts` (
  `customer_id` int(11) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_accounts`
--

INSERT INTO `wallet_accounts` (`customer_id`, `balance`, `updated_at`) VALUES
(15, 28.00, '2026-09-12 09:13:09');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type` enum('topup','order_payment','refund','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `customer_id`, `type`, `amount`, `reference`, `status`, `description`, `created_at`, `completed_at`) VALUES
(1, 15, 'topup', 5.00, 'WT-15-20260906103128-EE63E7B0', 'pending', 'Wallet top up bill fir91qgs', '2026-09-06 16:31:28', NULL),
(2, 15, 'topup', 5.00, 'WT-15-20260906104004-CDBE70A5', 'pending', 'Wallet top up bill 5gyihb2c', '2026-09-06 16:40:04', NULL),
(3, 15, 'topup', 5.00, 'WT-STRIPE-15-20260906104120-B81A4377', 'pending', 'Wallet top up via Stripe', '2026-09-06 16:41:20', NULL),
(4, 15, 'topup', 5.00, 'WT-STRIPE-15-20260906104302-3B89DFC1', 'pending', 'Wallet top up via Stripe', '2026-09-06 16:43:02', NULL),
(5, 15, 'topup', 5.00, 'WT-STRIPE-15-20260906104311-95384C81', 'pending', 'Wallet top up via Stripe', '2026-09-06 16:43:11', NULL),
(6, 15, 'topup', 5.00, 'WT-STRIPE-15-20260906104419-EE4F9214', 'completed', 'Wallet top up via Stripe', '2026-09-06 16:44:19', NULL),
(7, 15, 'topup', 5.00, 'WT-STRIPE-15-20260909151147-85DE6ED6', 'completed', 'Wallet top up via Stripe', '2026-09-09 21:11:47', NULL),
(8, 15, 'topup', 100.00, 'WT-STRIPE-15-20260910102746-34A3C83D', 'completed', 'Wallet top up via Stripe', '2026-09-10 16:27:46', NULL),
(9, 15, 'order_payment', 72.00, 'SAD-20260912-11675', 'completed', 'Payment for order SAD-20260912-11675', '2026-09-12 09:13:09', '2026-09-12 09:13:09'),
(10, 15, 'topup', 10.00, 'WT-TP-15-20260917132541-5A260881', 'pending', 'Wallet top up via ToyyibPay', '2026-09-17 19:25:41', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_request`
--
ALTER TABLE `custom_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `fk_orders_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_customer_product` (`customer_id`,`product_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_customer_id` (`customer_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wallet_reference` (`reference`),
  ADD KEY `idx_wallet_customer` (`customer_id`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `custom_request`
--
ALTER TABLE `custom_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  ADD CONSTRAINT `fk_wallet_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_tx_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
