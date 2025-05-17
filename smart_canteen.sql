-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 05:00 PM
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
-- Database: `smart_canteen`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`activity_id`, `timestamp`, `activity_type`, `description`, `admin_id`, `user_id`, `related_id`) VALUES
(1, '2025-05-09 12:15:19', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(2, '2025-05-09 12:15:51', 'new_order', 'New order #TRN-25 completed by student ID 12 for ₹20.00.', NULL, 12, 25),
(3, '2025-05-09 13:33:50', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(4, '2025-05-09 13:33:54', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(5, '2025-05-09 13:33:58', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(6, '2025-05-09 13:34:06', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(7, '2025-05-09 22:20:05', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(9, '2025-05-10 13:55:52', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(10, '2025-05-10 13:57:03', 'staff_edit', 'Admin \'admin\' updated details for staff \'admin\' (ID: 1). Password updated.', 1, NULL, 1),
(11, '2025-05-10 13:57:23', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(12, '2025-05-10 15:20:23', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(13, '2025-05-10 15:20:29', 'new_order', 'New order #TRN-26 completed by student ID 12 for ₹23.00.', NULL, 12, 26),
(14, '2025-05-10 16:04:09', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(15, '2025-05-11 16:17:29', 'password_change_self', 'Admin \'admin\' (ID: 1) changed their password.', 1, NULL, 1),
(16, '2025-05-11 16:17:55', 'password_change_self', 'Admin \'admin\' (ID: 1) changed their password.', 1, NULL, 1),
(17, '2025-05-11 16:18:24', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(18, '2025-05-12 18:01:29', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(19, '2025-05-12 18:32:32', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(20, '2025-05-12 20:01:49', 'new_order', 'New order #TRN-27 completed by student ID 1 for ₹15.00.', NULL, 1, 27),
(21, '2025-05-12 21:15:27', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(22, '2025-05-12 21:23:43', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(23, '2025-05-16 11:54:49', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(24, '2025-05-16 12:19:37', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(25, '2025-05-16 12:29:28', 'balance_update', 'Admin \'admin\' added NPR 1000 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 1,000.00.', 1, NULL, 15),
(26, '2025-05-16 12:40:25', 'balance_update', 'Admin \'admin\' deducted NPR 50 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 950.00.', 1, NULL, 15),
(27, '2025-05-16 12:53:18', 'balance_update', 'Admin \'admin\' added NPR 50 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 1,000.00.', 1, NULL, 15),
(28, '2025-05-16 14:01:37', 'new_order', 'New order #TRN-28 completed by student ID 15 for ₹15.00.', NULL, 15, 28),
(29, '2025-05-16 14:01:59', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(30, '2025-05-16 14:03:26', 'interface_update', 'Admin \'admin\' (ID: 1) updated interface settings (Theme: dark, Items: 10).', 1, NULL, 1),
(31, '2025-05-16 14:03:38', 'interface_update', 'Admin \'admin\' (ID: 1) updated interface settings (Theme: light, Items: 10).', 1, NULL, 1),
(32, '2025-05-17 08:37:41', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(33, '2025-05-17 08:39:12', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(34, '2025-05-17 08:40:09', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'324\').', 1, NULL, 9),
(35, '2025-05-17 08:56:15', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(36, '2025-05-17 08:56:22', 'interface_update', 'Admin \'admin\' (ID: 1) updated interface settings (Theme: dark, Items: 10).', 1, NULL, 1),
(37, '2025-05-17 08:56:38', 'interface_update', 'Admin \'admin\' (ID: 1) updated interface settings (Theme: light, Items: 10).', 1, NULL, 1),
(38, '2025-05-17 08:59:02', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(39, '2025-05-17 09:37:28', 'new_order', 'New order #TRN-29 completed by student ID 1 for ₹23.00.', NULL, 1, 29),
(40, '2025-05-17 09:37:45', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(41, '2025-05-17 09:38:06', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'chicken burger\').', 1, NULL, 9),
(42, '2025-05-17 11:21:16', 'new_order', 'New order #TRN-30 completed by student ID 15 for ₹170.00.', NULL, 15, 30),
(43, '2025-05-17 11:21:55', 'admin_login', 'Admin \'admin\' logged in successfully.', 1, NULL, NULL),
(44, '2025-05-17 11:23:27', 'product_updated', 'Admin \'admin\' updated product ID 9 (\'chicken burger\').', 1, NULL, 9),
(45, '2025-05-17 11:23:55', 'balance_update', 'Admin \'admin\' added NPR 175 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 990.00.', 1, NULL, 15),
(46, '2025-05-17 11:24:04', 'balance_update', 'Admin \'admin\' added NPR 20 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 1,010.00.', 1, NULL, 15),
(47, '2025-05-17 11:24:11', 'balance_update', 'Admin \'admin\' deducted NPR 10 for NFC ID \'1019\' (Student ID: 15). New balance: NPR 1,000.00.', 1, NULL, 15);

-- --------------------------------------------------------

--
-- Table structure for table `food`
--

CREATE TABLE `food` (
  `food_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food`
--

INSERT INTO `food` (`food_id`, `name`, `price`, `image_path`, `category`, `description`, `is_available`) VALUES
(1, 'samosa', 20.00, 'images/samosa.jpg', 'Veg', 'tasty samosa', 1),
(2, 'sandwich', 85.00, 'images/veg_sandwich.jpg', 'Veg', 'veg sandwich', 1),
(3, 'masala chai', 15.00, 'images/masala_chai.jpg', 'Veg', 'chai lelo', 1),
(9, 'chicken burger', 90.00, 'images/img_6827fa8d0bcb94.02161481.webp', 'Non-Veg', 'delicious', 1);

-- --------------------------------------------------------

--
-- Table structure for table `nfc_card`
--

CREATE TABLE `nfc_card` (
  `nfc_id` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hashed PIN/password',
  `password_change_hash` varchar(255) DEFAULT NULL COMMENT 'For reset purposes',
  `status` enum('Active','Inactive','Lost','Blocked') DEFAULT 'Active',
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nfc_card`
--

INSERT INTO `nfc_card` (`nfc_id`, `student_id`, `current_balance`, `password_hash`, `password_change_hash`, `status`, `last_used`) VALUES
('1001', 1, 402.00, '$2y$10$iw5XDmhzv.u8t4MmzjZ5A.7IHi1Gc4Ubzyyotmp0YQjiEvUxiNWyC', NULL, 'Active', '2025-05-08 09:49:57'),
('1002', 12, 537.00, '$2y$10$bG6m3z4Ljv8LRQt56w/kTOOld7Xf4Rmsx4JYEUlmLdbufLVsjbVbi', NULL, 'Active', '2025-05-08 23:05:32'),
('1003', 13, 500.00, 'sample_hashed_password_bob', NULL, 'Active', '2025-05-08 23:05:32'),
('1004', 14, 1000.00, 'sample_hashed_password_charlie', NULL, 'Active', '2025-05-08 23:05:32'),
('1019', 15, 1000.00, '$2y$10$o2GPtcK5iapB1.Mb8/q5TucGiQT9WG5/N7vhnoR2rrh8KE9C2uRje', NULL, 'Active', '2025-05-16 11:52:38');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Store only hashed passwords',
  `role` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `password_reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `theme_preference` varchar(10) DEFAULT 'light' COMMENT 'User preferred theme (light/dark)',
  `items_per_page` int(11) DEFAULT 10 COMMENT 'Preferred number of items per page in tables'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `full_name`, `username`, `password_hash`, `role`, `is_active`, `last_login`, `created_at`, `password_reset_token`, `token_expiry`, `theme_preference`, `items_per_page`) VALUES
(1, 'admin user', 'admin', '$2y$10$gQV.R4xUWck7z6zoAzNeAep.1J3BjBJqwsuGTbujeOkWOn2BJiL/G', 'administrator', 1, NULL, '2025-05-08 01:06:43', NULL, NULL, 'light', 10);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `student_email` varchar(100) NOT NULL,
  `parent_email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nfc_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `full_name`, `contact_number`, `student_email`, `parent_email`, `username`, `nfc_id`) VALUES
(1, 'Test Student One', '9876543210', 'test.student1@example.com', 'parent.one@example.com', 'testuser', '1001'),
(12, 'Alice Smith', '9812345678', 'alice.s@example.com', 'parent.a@example.com', 'alicesmith', '1002'),
(13, 'Bob Johnson', '9823456789', 'bob.j@example.com', 'parent.b@example.com', 'bobjohnson', NULL),
(14, 'Charlie Brown', '9834567890', 'charlie.b@example.com', 'parent.c@example.com', 'charliebrown', NULL),
(15, 'Rozal Dahal', '1234567890', 'rozal@gmail.com', '', 'rozal', '1019');

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `txn_id` int(11) NOT NULL,
  `nfc_id` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('success','failed','refunded') DEFAULT 'success',
  `transaction_time` datetime NOT NULL DEFAULT current_timestamp(),
  `formatted_id` varchar(20) GENERATED ALWAYS AS (concat('TXN-',date_format(`transaction_time`,'%Y%m%d-'),lpad(`txn_id`,5,'0'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`txn_id`, `nfc_id`, `student_id`, `total_amount`, `status`, `transaction_time`) VALUES
(8, '1001', 1, 85.00, 'success', '2025-05-08 09:59:38'),
(9, '1001', 1, 170.00, 'success', '2025-05-08 09:59:49'),
(10, '1001', 1, 15.00, 'success', '2025-05-08 11:12:23'),
(11, '1001', 1, 20.00, 'success', '2025-05-08 11:16:15'),
(12, '1001', 1, 15.00, 'success', '2025-05-08 11:34:58'),
(13, '1001', 1, 85.00, 'success', '2025-05-08 11:40:23'),
(14, '1001', 1, 20.00, 'success', '2025-05-08 11:54:25'),
(15, '1001', 1, 20.00, 'success', '2025-05-08 12:02:59'),
(16, '1001', 1, 20.00, 'success', '2025-05-08 12:21:46'),
(17, '1001', 1, 170.00, 'success', '2025-05-08 12:32:43'),
(18, '1001', 1, 85.00, 'success', '2025-05-08 12:32:48'),
(19, '1001', 1, 40.00, 'success', '2025-05-08 12:33:46'),
(20, '1001', 1, 20.00, 'success', '2025-05-08 13:27:25'),
(21, '1001', 1, 20.00, 'success', '2025-05-08 13:39:00'),
(22, '1001', 1, 85.00, 'success', '2025-05-08 20:13:21'),
(23, '1002', 12, 120.00, 'success', '2025-05-08 23:14:05'),
(24, '1002', 12, 50.00, 'success', '2025-05-09 11:57:19'),
(25, '1002', 12, 20.00, 'success', '2025-05-09 12:15:51'),
(26, '1002', 12, 23.00, 'success', '2025-05-10 15:20:29'),
(27, '1001', 1, 15.00, 'success', '2025-05-12 20:01:49'),
(28, '1019', 15, 15.00, 'success', '2025-05-16 14:01:37'),
(29, '1001', 1, 23.00, 'success', '2025-05-17 09:37:28'),
(30, '1019', 15, 170.00, 'success', '2025-05-17 11:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_item`
--

CREATE TABLE `transaction_item` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `txn_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) UNSIGNED NOT NULL,
  `item_total` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_item`
--

INSERT INTO `transaction_item` (`item_id`, `txn_id`, `food_id`, `quantity`, `unit_price`) VALUES
(1, 8, 2, 1, 85.00),
(2, 9, 2, 2, 85.00),
(3, 10, 3, 1, 15.00),
(4, 11, 1, 1, 20.00),
(5, 12, 3, 1, 15.00),
(6, 13, 2, 1, 85.00),
(7, 14, 1, 1, 20.00),
(8, 15, 1, 1, 20.00),
(9, 16, 1, 1, 20.00),
(10, 17, 2, 2, 85.00),
(11, 18, 2, 1, 85.00),
(12, 19, 1, 2, 20.00),
(13, 20, 1, 1, 20.00),
(14, 21, 1, 1, 20.00),
(15, 22, 1, 2, 20.00),
(16, 22, 3, 3, 15.00),
(17, 23, 1, 1, 20.00),
(18, 23, 2, 1, 85.00),
(19, 23, 3, 1, 15.00),
(20, 24, 1, 1, 20.00),
(21, 24, 3, 2, 15.00),
(22, 25, 1, 1, 20.00),
(23, 26, 9, 1, 23.00),
(24, 27, 3, 1, 15.00),
(25, 28, 3, 1, 15.00),
(26, 29, 9, 1, 23.00),
(27, 30, 2, 2, 85.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_related_id` (`related_id`);

--
-- Indexes for table `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`food_id`);

--
-- Indexes for table `nfc_card`
--
ALTER TABLE `nfc_card`
  ADD PRIMARY KEY (`nfc_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_nfc_student` (`student_id`),
  ADD KEY `idx_nfc_status` (`status`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_email` (`student_email`),
  ADD UNIQUE KEY `nfc_id` (`nfc_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`txn_id`),
  ADD KEY `nfc_id` (`nfc_id`),
  ADD KEY `idx_transaction_student` (`student_id`);

--
-- Indexes for table `transaction_item`
--
ALTER TABLE `transaction_item`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_item_transaction` (`txn_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `food`
--
ALTER TABLE `food`
  MODIFY `food_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `txn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `transaction_item`
--
ALTER TABLE `transaction_item`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nfc_card`
--
ALTER TABLE `nfc_card`
  ADD CONSTRAINT `nfc_card_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`nfc_id`) REFERENCES `nfc_card` (`nfc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_item`
--
ALTER TABLE `transaction_item`
  ADD CONSTRAINT `transaction_item_ibfk_1` FOREIGN KEY (`txn_id`) REFERENCES `transaction` (`txn_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_item_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food` (`food_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
