-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 06:53 PM
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
-- Database: `woke_coliving`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 16, 'Lease Signed', 'Reservation #22', '2026-02-07 08:17:42'),
(2, 16, 'Reservation Extended', 'Room: 6-Bed | Status: Pending', '2026-02-07 08:27:32'),
(3, 17, 'Reservation Submitted', 'Room: 6-Bed | Status: Pending', '2026-02-07 08:31:59'),
(4, 17, 'Reservation Approved', 'Reservation #23 has been approved.', '2026-02-07 08:32:05'),
(5, 17, 'Lease Signed', 'Reservation #23', '2026-02-07 08:32:16'),
(6, 17, 'Reservation Extended', 'Room: 6-Bed | Status: Pending', '2026-02-07 08:33:03'),
(7, 17, 'Reservation Extended', 'Room: 6-Bed | Status: Pending', '2026-02-07 08:46:13'),
(8, 17, 'Reservation Approved', 'Reservation #24 has been approved.', '2026-02-07 08:46:22'),
(11, 17, 'Reservation Extended', 'Room: 6-Bed | Status: Pending', '2026-02-07 09:14:06'),
(12, 17, 'Reservation Extended', 'Contract #24 updated (Merged extension).', '2026-02-07 09:14:17'),
(13, 18, 'Reservation Submitted', 'Room: 4-Bed | Status: Pending', '2026-02-07 11:54:14'),
(14, 18, 'Reservation Rejected', 'Reservation #26 has been cancelled.', '2026-02-07 11:54:54'),
(15, 18, 'Reservation Submitted', 'Room: 6-Bed | Status: Pending', '2026-02-07 11:58:40'),
(16, 18, 'Reservation Rejected', 'Reservation #27 has been cancelled.', '2026-02-07 12:00:28'),
(17, 18, 'Reservation Submitted', 'Room: Single | Status: Pending', '2026-02-07 12:05:15'),
(18, 18, 'Reservation Rejected', 'Reservation #28 has been cancelled.', '2026-02-07 12:05:42'),
(19, 18, 'Reservation Submitted', 'Room: Single | Status: Pending', '2026-02-07 12:07:10'),
(20, 18, 'Reservation Approved', 'Reservation #29 has been approved.', '2026-02-07 12:07:20'),
(21, 18, 'Lease Signed', 'Reservation #29', '2026-02-07 12:28:23'),
(29, 20, 'Reservation Submitted', 'Room: 4-Bed | Status: Pending', '2026-02-07 16:52:07'),
(30, 20, 'Reservation Approved', 'Reservation #31 has been approved.', '2026-02-07 16:53:50'),
(31, 20, 'Lease Signed', 'Reservation #31', '2026-02-07 16:54:07'),
(35, 22, 'Reservation Submitted', 'Room: 4-Bed | Status: Pending', '2026-02-07 17:35:30'),
(39, 22, 'Reservation Approved', 'Reservation #34 has been approved.', '2026-02-07 17:36:12'),
(40, 22, 'Lease Signed', 'Reservation #34', '2026-02-07 17:36:32');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_history`
--

CREATE TABLE `admin_password_history` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_requests`
--

CREATE TABLE `housekeeping_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') DEFAULT 'Pending',
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `user_id` varchar(256) NOT NULL,
  `created_at` varchar(256) NOT NULL,
  `is_read` varchar(256) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'System'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`user_id`, `created_at`, `is_read`, `message`, `type`) VALUES
('16', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('16', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('16', '', '', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('17', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('17', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('17', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('17', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('18', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('18', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>6-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('18', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('18', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>Single</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('20', '', '1', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status'),
('22', '', '', '✅ <strong>Reservation Received!</strong><br>Your booking for <strong>4-Bed</strong> is now <strong>Pending</strong>. Please wait for admin approval.', 'Booking Status');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `payment_date` timestamp NULL DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT 'Room Payment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `reservation_id`, `amount`, `payment_method`, `payment_status`, `payment_date`, `reference_number`, `proof_image`, `description`) VALUES
(5, 17, 48.78, 'Cash', 'Unpaid', '2026-02-06 19:10:21', NULL, NULL, 'Room Payment'),
(6, 18, 35.69, 'GCash', 'Paid', '2026-02-07 07:14:48', '434423423', '1770448488_Gcashqr.jfif', 'Room Payment'),
(7, 19, 2099.30, 'GCash', 'Paid', '2026-02-07 07:19:31', '09876312', '1770448771_Gcashqr.jfif', 'Room Payment'),
(8, 20, 2799.07, 'GCash', 'Paid', '2026-02-07 07:53:55', '09876312', '1770450835_Gcashqr.jfif', 'Room Payment'),
(9, 21, 2799.07, 'GCash', 'Paid', '2026-02-07 07:55:23', '09876312', '1770450923_Gcashqr.jfif', 'Room Payment'),
(10, 22, 2799.07, 'GCash', 'Paid', '2026-02-07 07:56:54', '09876312', '1770451014_Gcashqr.jfif', 'Room Payment'),
(11, 22, 1399.53, 'Cash', 'Unpaid', '2026-02-07 08:05:40', NULL, NULL, 'Room Payment'),
(12, 22, 1199.60, 'GCash', 'Paid', '2026-02-07 08:27:30', '09876312', '1770452850_Gcashqr.jfif', 'Room Payment'),
(13, 23, 2099.30, 'GCash', 'Paid', '2026-02-07 08:31:57', '098763122', '1770453117_Gcashqr.jfif', 'Room Payment'),
(14, 23, 3098.97, 'GCash', 'Paid', '2026-02-07 08:33:01', '09876312', '1770453181_Gcashqr.jfif', 'Room Payment'),
(15, 24, 2899.03, 'GCash', 'Paid', '2026-02-07 08:46:11', '09876312', '1770453971_Gcashqr.jfif', 'Room Payment'),
(16, 24, 1699.43, 'GCash', 'Paid', '2026-02-07 09:14:04', '09876312', '1770455644_Gcashqr.jfif', 'Room Payment'),
(17, 26, 4700.00, 'Cash', 'Unpaid', '2026-02-07 11:54:12', NULL, NULL, 'Room Payment'),
(18, 27, 11125.00, 'Cash', 'Unpaid', '2026-02-07 11:58:38', NULL, NULL, 'Room Payment'),
(19, 28, 42000.00, 'Cash', 'Unpaid', '2026-02-07 12:05:13', NULL, NULL, 'Room Payment'),
(20, 29, 42000.00, 'Cash', 'Unpaid', '2026-02-07 12:07:08', NULL, NULL, 'Room Payment'),
(22, 31, 25200.00, 'Cash', 'Unpaid', '2026-02-07 16:52:05', NULL, NULL, 'Room Payment'),
(25, 34, 4700.00, 'Cash', 'Unpaid', '2026-02-07 17:35:28', NULL, NULL, 'Room Payment');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `Email` varchar(64) NOT NULL,
  `Phone_number` varchar(64) NOT NULL,
  `months` int(11) NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Approved','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `bed_preference` varchar(50) DEFAULT 'Any',
  `signature_image` varchar(255) DEFAULT NULL,
  `is_archived` varchar(256) NOT NULL,
  `extended_from` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `room_id`, `Email`, `Phone_number`, `months`, `total_price`, `status`, `created_at`, `start_date`, `end_date`, `cancellation_reason`, `bed_preference`, `signature_image`, `is_archived`, `extended_from`) VALUES
(17, 16, 3, '', '', 1, 48.78, 'Cancelled', '2026-02-06 19:10:21', '2026-02-07', '2026-02-28', NULL, 'Any', NULL, '1', NULL),
(18, 16, 1, '', '', 1, 35.69, 'Cancelled', '2026-02-07 07:14:48', '2026-02-07', '2026-02-28', NULL, 'Any', NULL, '1', NULL),
(19, 16, 5, '', '', 1, 2099.30, 'Approved', '2026-02-07 07:19:31', '2026-02-07', '2026-02-28', NULL, 'Lower Bunk', 'sig_19_1770449840.png', '', NULL),
(20, 16, 5, '', '', 1, 2799.07, 'Cancelled', '2026-02-07 07:53:55', '2026-02-28', '2026-03-28', NULL, 'Lower Bunk', NULL, '1', NULL),
(21, 16, 5, '', '', 1, 2799.07, 'Cancelled', '2026-02-07 07:55:23', '2026-02-28', '2026-03-28', NULL, 'Lower Bunk', NULL, '1', NULL),
(22, 16, 5, '', '', 3, 5398.20, 'Approved', '2026-02-07 07:56:54', '2026-02-28', '2026-04-23', NULL, 'Lower Bunk', 'sig_22_1770452262.png', '', NULL),
(23, 17, 5, '', '', 2, 5198.27, 'Approved', '2026-02-07 08:31:57', '2026-02-07', '2026-03-31', NULL, 'Lower Bunk', 'sig_23_1770453136.png', '', NULL),
(24, 17, 5, '', '', 2, 4598.46, 'Approved', '2026-02-07 08:46:11', '2026-03-31', '2026-05-16', NULL, 'Lower Bunk', 'sig_23_1770453136.png', '', NULL),
(26, 18, 3, '', '', 1, 4700.00, 'Cancelled', '2026-02-07 11:54:12', '2026-02-07', '2026-03-09', NULL, 'Lower Bunk', NULL, '1', NULL),
(27, 18, 5, '', '', 3, 11125.00, 'Cancelled', '2026-02-07 11:58:38', '2026-02-07', '2026-05-07', NULL, 'Upper Bunk', NULL, '1', NULL),
(28, 18, 1, '', '', 3, 42000.00, 'Cancelled', '2026-02-07 12:05:13', '2026-02-07', '2026-05-07', NULL, 'Any', NULL, '1', NULL),
(29, 18, 1, '', '', 3, 42000.00, 'Approved', '2026-02-07 12:07:08', '2026-02-07', '2026-05-07', NULL, 'Any', 'sig_29_1770467303.png', '', NULL),
(31, 20, 3, '', '', 6, 25200.00, 'Approved', '2026-02-07 16:52:05', '2026-02-07', '2026-08-07', NULL, 'Upper Bunk', 'sig_31_1770483247.png', '', NULL),
(34, 22, 3, '', '', 1, 4700.00, 'Approved', '2026-02-07 17:35:28', '2026-02-07', '2026-03-07', NULL, 'Lower Bunk', 'sig_34_1770485792.png', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `room_type` enum('Single','4-Bed','6-Bed') NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `total_beds` int(11) NOT NULL,
  `available_beds` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `availability` varchar(50) NOT NULL DEFAULT 'Available',
  `price_upper` decimal(10,2) DEFAULT 0.00,
  `price_lower` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `room_type`, `total_price`, `total_beds`, `available_beds`, `description`, `image`, `status`, `availability`, `price_upper`, `price_lower`) VALUES
(1, 'Single Bed', 'Single', 14000.00, 1, 0, NULL, '434612699_2697344013763217_6695140230318829305_n.jpg', 'Available', 'Available', 0.00, 0.00),
(3, '4 Beds', '4-Bed', 4700.00, 8, 0, NULL, '553086532_1458289505383792_3468955167122582667_n.jpg', 'Available', 'Available', 4200.00, 4700.00),
(5, '6 Beds', '6-Bed', 4500.00, 6, 0, NULL, '502053110_10074917945917331_5607640182378445538_n.jpg', 'Available', 'Available', 3750.00, 4500.00);

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

CREATE TABLE `room_images` (
  `image_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'hero_image', '[\"1770447047_hero.png\",\"1770471778_hero_edit.png\",\"1770447312_hero_edit.png\"]'),
(125, 'living_area_image', 'living_area_1770486291.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `temporary_moves`
--

CREATE TABLE `temporary_moves` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `original_room_id` int(11) NOT NULL,
  `temp_room_id` int(11) NOT NULL,
  `move_date` datetime DEFAULT current_timestamp(),
  `status` enum('Active','Returned') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` varchar(256) NOT NULL,
  `do_not_renew` tinyint(1) DEFAULT 0,
  `gender` varchar(20) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone_number`, `phone`, `password`, `role`, `created_at`, `is_archived`, `do_not_renew`, `gender`, `reset_token`, `reset_expiry`) VALUES
(12, 'bryan', 'nicle@gmail.com', '09673101356', NULL, '$2y$10$KNndQy2NC0daPij1yMSbgeokMHXVHITVG1.1v4IGBZlpxad9OLsZy', 'guest', '2026-02-05 12:23:10', '', 0, NULL, NULL, NULL),
(16, 'takerman', '6takerman@gmail.com', '0962734444', NULL, '$2y$10$fEHRwXCIO8w7l4GsytLu..2yslNSsMyBA1gX6kq5qzxk2GNCRyycC', 'guest', '2026-02-06 19:07:39', '', 0, NULL, NULL, NULL),
(17, 'Stephen Squad', 'stephenpogi3@gmail.com', '096273444422', NULL, '$2y$10$mSxyWyYo.ysMEVQTxxgXnOgdc0sfqeo1fQJ.bxm/zlheDl.6lct4m', 'guest', '2026-02-07 08:31:10', '', 0, NULL, NULL, NULL),
(18, 'Stephen Squad PH', 'stephensquad@gmail.com', '0962734448', NULL, '$2y$10$nXqhn7HQqJzDNGw80dRLgu88Sj3LUSuciYf0q25kcLn8vk4tsR8MS', 'guest', '2026-02-07 10:14:17', '', 0, 'Male', NULL, NULL),
(20, 'Tysoni', 'tysonicrosini@gmail.com', '096273444422', NULL, '$2y$10$0Ph1WpbJiA5FG/0RANtJA.Kt8EoFeP8b1KhyC6T1e9DJymZETlWkW', 'guest', '2026-02-07 16:51:13', '', 0, 'Male', NULL, NULL),
(21, 'Alavino Alamano', 'alvino@gmail.com', '097672634', NULL, '$2y$10$RYf9TDuYiF6.vkf3PHmHpOKM5AtOXTn/hGhmbx6ZFt2F/Tofc7Ima', 'guest', '2026-02-07 17:18:08', '', 0, NULL, NULL, NULL),
(22, 'Marwino Santiano', 'marwino@gmail.com', '0928777631', NULL, '$2y$10$PxwxG0Ew2UCPF61DCWM5ueCoIg0e/t0pIpwbakmegSTLUSFm7yhTy', 'guest', '2026-02-07 17:34:52', '', 0, 'Male', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utility_bills`
--

CREATE TABLE `utility_bills` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `electric_start` decimal(10,2) DEFAULT 0.00,
  `electric_end` decimal(10,2) DEFAULT 0.00,
  `electric_rate` decimal(10,2) DEFAULT 0.00,
  `water_start` decimal(10,2) DEFAULT 0.00,
  `water_end` decimal(10,2) DEFAULT 0.00,
  `water_rate` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

CREATE TABLE `waitlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waitlist`
--

INSERT INTO `waitlist` (`id`, `user_id`, `room_type`, `created_at`) VALUES
(1, 16, '6-Bed', '2026-02-07 07:13:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_password_history`
--
ALTER TABLE `admin_password_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `housekeeping_requests`
--
ALTER TABLE `housekeeping_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `room_images`
--
ALTER TABLE `room_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `temporary_moves`
--
ALTER TABLE `temporary_moves`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_password_history`
--
ALTER TABLE `admin_password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_requests`
--
ALTER TABLE `housekeeping_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_images`
--
ALTER TABLE `room_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `temporary_moves`
--
ALTER TABLE `temporary_moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `utility_bills`
--
ALTER TABLE `utility_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waitlist`
--
ALTER TABLE `waitlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `housekeeping_requests`
--
ALTER TABLE `housekeeping_requests`
  ADD CONSTRAINT `fk_hk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
