-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 27, 2025 at 02:53 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_ms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$bB4G1RrArs6dHqqN9kdxReFnsJopDSK/b7IyEPeq/iPjrQKWFva8m', 'admin@hotel.com', '2025-03-29 10:40:52'),
(2, 'staff', '$2y$10$tHU5ySiQxulJPg2ZAil4eeh6Or9elDPcicrVX65xseacDG9TexKzW', 'student.joshmcdowelltrapal@gmail.com', '2025-03-29 14:05:16'),
(3, 'joshm', '$2y$10$22Pg1OKn3dz0YnGvFIEjU.Upv62aAhBZPUw/VsAeRsDUwXqHUmwN2', 'joshmcdowelltrapal@gmail.com', '2025-03-29 14:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_by` int DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `cancelled_by` int DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `admin_cancelled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `room_id` (`room_id`),
  KEY `confirmed_by` (`confirmed_by`),
  KEY `cancelled_by` (`cancelled_by`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `room_id`, `check_in`, `check_out`, `status`, `total_amount`, `notes`, `created_at`, `confirmed_by`, `confirmed_at`, `cancelled_by`, `cancelled_at`, `admin_cancelled`) VALUES
(2, 2, 1, '2025-04-02 21:00:00', '2025-04-02 00:00:00', 'confirmed', 500.00, NULL, '2025-03-29 13:47:02', NULL, NULL, NULL, NULL, 0),
(3, 3, 6, '2025-04-11 21:00:00', '2025-04-12 09:00:00', 'confirmed', 1200.00, NULL, '2025-03-29 13:49:06', NULL, NULL, NULL, NULL, 0),
(4, 5, 7, '2025-04-05 23:00:00', '2025-04-06 11:00:00', 'confirmed', 1200.00, NULL, '2025-03-29 15:05:48', NULL, NULL, NULL, NULL, 0),
(5, 6, 2, '2025-04-03 23:00:00', '2025-04-03 02:00:00', 'confirmed', 500.00, NULL, '2025-03-29 15:11:38', NULL, NULL, NULL, NULL, 0),
(6, 7, 3, '2025-04-01 23:00:00', '2025-04-01 02:00:00', 'confirmed', 500.00, NULL, '2025-03-29 15:15:48', NULL, NULL, NULL, NULL, 0),
(7, 6, 1, '2025-03-30 23:36:48', '2025-04-01 23:36:48', 'confirmed', 500.00, NULL, '2025-03-29 15:36:48', 2, '2025-03-29 23:36:59', NULL, NULL, 0),
(8, 6, 1, '2025-03-30 23:41:48', '2025-04-01 23:41:48', 'cancelled', 500.00, NULL, '2025-03-29 15:41:48', NULL, NULL, 2, '2025-03-29 23:42:01', 1),
(9, 2, 8, '2025-04-05 23:00:00', '2025-04-06 11:00:00', 'cancelled', 1200.00, NULL, '2025-03-29 15:48:59', NULL, NULL, NULL, NULL, 0),
(10, 8, 4, '2025-04-04 12:00:00', '2025-04-04 15:00:00', 'confirmed', 500.00, NULL, '2025-03-30 04:11:34', 2, '2025-03-30 12:11:56', NULL, NULL, 0),
(11, 9, 4, '2025-04-13 11:00:00', '2025-04-13 14:00:00', 'confirmed', 500.00, NULL, '2025-04-12 03:32:01', 2, '2025-04-12 11:34:13', NULL, NULL, 0),
(12, 10, 8, '2025-04-30 09:00:00', '2025-04-30 21:00:00', 'confirmed', 1200.00, NULL, '2025-04-27 01:24:14', 2, '2025-04-27 09:24:28', NULL, NULL, 0),
(13, 11, 11, '2025-04-30 09:00:00', '2025-05-01 09:00:00', 'confirmed', 1000.00, NULL, '2025-04-27 01:30:53', 2, '2025-04-27 09:31:05', NULL, NULL, 0),
(14, 12, 4, '2025-04-27 10:00:00', '2025-04-27 13:00:00', 'confirmed', 500.00, NULL, '2025-04-27 01:32:24', 2, '2025-04-27 09:32:33', NULL, NULL, 0),
(15, 13, 5, '2025-04-27 09:35:00', '2025-04-27 12:35:00', 'confirmed', 500.00, NULL, '2025-04-27 01:34:18', 2, '2025-04-27 09:34:24', NULL, NULL, 0);

--
-- Triggers `bookings`
--
DROP TRIGGER IF EXISTS `after_booking_checkout`;
DELIMITER $$
CREATE TRIGGER `after_booking_checkout` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
            IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' THEN
                -- Wait for a short delay to ensure checkout process is complete
                SET @current_date = CURRENT_DATE();
                
                -- If checkout date is in the past or today, archive immediately
                IF NEW.check_out <= @current_date THEN
                    UPDATE bookings 
                    SET status = 'archived'
                    WHERE id = NEW.id;
                END IF;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `id_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `username`, `password`, `phone`, `address`, `id_type`, `id_number`, `created_at`, `updated_at`) VALUES
(2, 'Josh McDowell', 'Trapal', 'student.joshmcdowelltrapal@gmail.com', '', '', '09482892157', NULL, '', '', '2025-03-29 13:47:02', '2025-03-29 16:03:35'),
(3, 'Krisxan', 'Castillon', 'krsxncastillon@gmail.com', '', '', '09452592763', NULL, '', '', '2025-03-29 13:49:06', '2025-03-29 16:03:35'),
(5, 'Krisxan', 'Castillon', 'krisxancastillon@gmail.com', '', '', '09452592762', NULL, '', '', '2025-03-29 15:05:48', '2025-03-29 16:03:35'),
(6, 'Ann Marisse', 'Cuya', 'amcuya@gmail.com', '', '', '09958714113', NULL, '', '', '2025-03-29 15:11:38', '2025-03-29 16:03:35'),
(7, 'Angel', 'Lamadrid', 'angelmaylamadrid@gmail.com', '', '', '09958714114', NULL, '', '', '2025-03-29 15:15:48', '2025-03-29 16:03:35'),
(8, 'Carm', 'Agustin', 'carmleaagustin@gmail.com', '', '', '09958714112', NULL, '', '', '2025-03-30 04:11:34', '2025-03-30 04:11:34'),
(9, 'AJ Nicole ', 'Salamente', 'ajnicole@gmail.com', '', '', '09985415665', NULL, '', '', '2025-04-12 03:32:01', '2025-04-12 03:32:01'),
(10, 'John Mark', 'Trapal', 'jmtrapal@gmail.com', '', '', '09958714115', NULL, '', '', '2025-04-27 01:24:14', '2025-04-27 01:24:14'),
(11, 'Peddy ', 'Trapal', 'peddyt@gmail.com', '', '', '09958714116', NULL, '', '', '2025-04-27 01:30:53', '2025-04-27 01:30:53'),
(12, 'Mary Grace', 'Trapal', 'mgracet@gmail.com', '', '', '09958714117', NULL, '', '', '2025-04-27 01:32:24', '2025-04-27 01:32:24'),
(13, 'Marifer', 'Trapal', 'marifert@gmail.com', '', '', '09958714118', NULL, '', '', '2025-04-27 01:34:18', '2025-04-27 01:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online','pending') COLLATE utf8mb4_general_ci NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_general_ci NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `payment_date`, `created_at`) VALUES
(1, 2, 500.00, 'pending', 'pending', '2025-03-29 13:47:02', '2025-03-29 13:47:02'),
(2, 3, 1200.00, 'pending', 'pending', '2025-03-29 13:49:06', '2025-03-29 13:49:06'),
(3, 4, 1200.00, 'pending', 'pending', '2025-03-29 15:05:48', '2025-03-29 15:05:48'),
(4, 5, 500.00, 'pending', 'pending', '2025-03-29 15:11:38', '2025-03-29 15:11:38'),
(5, 6, 500.00, 'pending', 'pending', '2025-03-29 15:15:48', '2025-03-29 15:15:48');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `room_type_id` int NOT NULL,
  `status` enum('available','occupied','maintenance') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type_id` (`room_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `status`, `created_at`) VALUES
(1, '101', 1, 'occupied', '2025-03-29 13:46:48'),
(2, '102', 1, 'occupied', '2025-03-29 13:46:48'),
(3, '103', 1, 'occupied', '2025-03-29 13:46:48'),
(4, '104', 1, 'available', '2025-03-29 13:46:48'),
(5, '105', 1, 'available', '2025-03-29 13:46:48'),
(6, '201', 2, 'occupied', '2025-03-29 13:46:48'),
(7, '202', 2, 'occupied', '2025-03-29 13:46:48'),
(8, '203', 2, 'available', '2025-03-29 13:46:48'),
(9, '204', 2, 'available', '2025-03-29 13:46:48'),
(10, '205', 2, 'available', '2025-03-29 13:46:48'),
(11, '301', 3, 'available', '2025-03-29 13:46:48'),
(12, '302', 3, 'available', '2025-03-29 13:46:48'),
(13, '303', 3, 'available', '2025-03-29 13:46:48'),
(14, '304', 3, 'available', '2025-03-29 13:46:48'),
(15, '305', 3, 'available', '2025-03-29 13:46:48'),
(16, '401', 4, 'available', '2025-03-29 13:46:48'),
(17, '402', 4, 'available', '2025-03-29 13:46:48'),
(18, '403', 4, 'available', '2025-03-29 13:46:48'),
(19, '404', 4, 'available', '2025-03-29 13:46:48'),
(20, '405', 4, 'available', '2025-03-29 13:46:48');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `duration_hours` int NOT NULL,
  `capacity` int NOT NULL DEFAULT '2',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price`, `duration_hours`, `capacity`, `created_at`) VALUES
(1, 'Standard Room (3 Hours)', 'Comfortable room with essential amenities perfect for short stays.', 500.00, 3, 2, '2025-03-29 10:40:52'),
(2, 'Standard Room (12 Hours)', 'Well-appointed room ideal for overnight stays.', 1200.00, 12, 2, '2025-03-29 10:40:52'),
(3, 'Standard Room (1 Day)', 'Spacious room with all amenities for a full day stay.', 1000.00, 24, 2, '2025-03-29 10:40:52'),
(4, 'Family Room', 'Large room perfect for families, featuring multiple beds and extra space.', 2000.00, 24, 4, '2025-03-29 10:40:52');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`confirmed_by`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`cancelled_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
