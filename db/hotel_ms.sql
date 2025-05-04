-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2025 at 03:56 PM
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
-- Database: `hotel_ms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `room_id`, `check_in`, `check_out`, `status`, `total_amount`, `notes`, `created_at`) VALUES
(2, 2, 1, '2025-04-02 21:00:00', '2025-04-02 00:00:00', 'confirmed', 500.00, NULL, '2025-03-29 13:47:02'),
(3, 3, 6, '2025-04-11 21:00:00', '2025-04-12 09:00:00', 'confirmed', 1200.00, NULL, '2025-03-29 13:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `id_type` varchar(50) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `id_type`, `id_number`, `created_at`) VALUES
(2, 'Josh McDowell', 'Trapal', 'student.joshmcdowelltrapal@gmail.com', '09958714112', NULL, '', '', '2025-03-29 13:47:02'),
(3, 'Krisxan', 'Castillon', 'krsxncastillon@gmail.com', '09452592763', NULL, '', '', '2025-03-29 13:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online','pending') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `payment_date`, `created_at`) VALUES
(1, 2, 500.00, 'pending', 'pending', '2025-03-29 13:47:02', '2025-03-29 13:47:02'),
(2, 3, 1200.00, 'pending', 'pending', '2025-03-29 13:49:06', '2025-03-29 13:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `status`, `created_at`) VALUES
(1, '101', 1, 'occupied', '2025-03-29 13:46:48'),
(2, '102', 1, 'available', '2025-03-29 13:46:48'),
(3, '103', 1, 'available', '2025-03-29 13:46:48'),
(4, '104', 1, 'available', '2025-03-29 13:46:48'),
(5, '105', 1, 'available', '2025-03-29 13:46:48'),
(6, '201', 2, 'occupied', '2025-03-29 13:46:48'),
(7, '202', 2, 'available', '2025-03-29 13:46:48'),
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

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price`, `duration_hours`, `capacity`, `created_at`) VALUES
(1, 'Standard Room (3 Hours)', 'Comfortable room with essential amenities perfect for short stays.', 500.00, 3, 2, '2025-03-29 10:40:52'),
(2, 'Standard Room (12 Hours)', 'Well-appointed room ideal for overnight stays.', 1200.00, 12, 2, '2025-03-29 10:40:52'),
(3, 'Standard Room (1 Day)', 'Spacious room with all amenities for a full day stay.', 1000.00, 24, 2, '2025-03-29 10:40:52'),
(4, 'Family Room', 'Large room perfect for families, featuring multiple beds and extra space.', 2000.00, 24, 4, '2025-03-29 10:40:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

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
