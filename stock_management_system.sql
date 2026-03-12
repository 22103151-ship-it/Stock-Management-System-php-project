-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 04, 2025 at 06:27 AM
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
-- Database: `stock_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_payments`
--

CREATE TABLE `admin_payments` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_payments`
--

INSERT INTO `admin_payments` (`id`, `transaction_id`, `payment_type`, `transaction_date`, `created_at`) VALUES
(11, 'SSLCZ_TEST_68dfcf6a06938', 'DBBLMOBILEB-Dbbl Mobile Banking', '2025-10-03 19:28:10', '2025-10-03 13:28:21'),
(12, 'SSLCZ_TEST_68dfd6804923f', 'VISA-Dutch Bangla', '2025-10-03 19:58:24', '2025-10-03 13:58:27'),
(13, 'SSLCZ_TEST_68e0128e3cc2a', 'IBBL-Islami Bank', '2025-10-04 00:14:38', '2025-10-03 18:14:50'),
(14, 'SSLCZ_TEST_68e098874098e', 'BKASH-BKash', '2025-10-04 09:46:15', '2025-10-04 03:46:21');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `supplier_id`, `created_at`) VALUES
(2, 'Mouse', 500.00, 65, 1, '2025-09-23 16:59:45'),
(3, 'Keyboard', 1500.00, 100, 2, '2025-09-23 16:59:45'),
(6, 'Laptop', 50000.00, 58, 1, '2025-09-27 11:32:16'),
(12, 'Charger', 3000.00, 51, 2, '2025-09-27 11:39:53'),
(17, 'USB Cable', 450.00, 345, 1, '2025-10-02 05:37:16'),
(19, 'Charger', 450.00, 18, 1, '2025-10-02 08:41:56'),
(21, 'Earphone', 250.00, 130, 1, '2025-10-02 08:53:01'),
(22, 'Air pod', 250.00, 6, 1, '2025-10-02 09:00:51'),
(23, 'cable', 150.00, 28, 1, '2025-10-03 09:32:16'),
(24, 'Charger Cable', 250.00, 40, 1, '2025-10-03 09:56:27');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','delivered','returned') NOT NULL DEFAULT 'pending',
  `invoice_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `payment_status` enum('Pending','Paid') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `product_id`, `quantity`, `price`, `status`, `invoice_id`, `created_at`, `payment_status`) VALUES
(1, 3, 24, 0.00, 'delivered', NULL, '2025-10-02 14:57:43', 'Paid'),
(2, 6, 23, 0.00, 'delivered', NULL, '2025-10-02 14:58:01', 'Pending'),
(3, 2, 45, 0.00, 'delivered', NULL, '2025-10-02 14:58:08', 'Paid'),
(4, 12, 22, 0.00, 'delivered', NULL, '2025-10-03 16:56:14', 'Paid'),
(5, 22, 200, 0.00, 'delivered', NULL, '2025-10-03 19:24:57', 'Paid'),
(6, 24, 20, 0.00, 'delivered', NULL, '2025-10-03 19:56:46', 'Paid'),
(7, 22, 2, 0.00, 'delivered', NULL, '2025-10-04 00:14:01', 'Paid'),
(8, 21, 30, 0.00, 'delivered', NULL, '2025-10-04 09:39:55', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `sell_product`
--

CREATE TABLE `sell_product` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sell_product`
--

INSERT INTO `sell_product` (`id`, `product_id`, `product_name`, `quantity`, `price`, `created_at`) VALUES
(19, 12, 'Charger', 1, 3000.00, '2025-10-02 13:29:59'),
(20, 12, 'Charger', 1, 3000.00, '2025-10-02 13:32:20'),
(21, 3, 'Keyboard', 1, 1500.00, '2025-10-02 13:33:11'),
(22, 12, 'Charger', 1, 3000.00, '2025-10-02 13:36:10'),
(23, 12, 'Charger', 1, 3000.00, '2025-10-02 13:37:30'),
(24, 6, 'Laptop', 1, 50000.00, '2025-10-02 13:38:43'),
(25, 19, 'Charger', 2, 450.00, '2025-10-03 19:30:02'),
(26, 22, 'Air pod', 1, 250.00, '2025-10-04 09:02:53'),
(27, 22, 'Air pod', 1, 250.00, '2025-10-04 09:40:40'),
(28, 22, 'Air pod', 2, 250.00, '2025-10-04 09:41:44'),
(29, 22, 'Air pod', 1, 250.00, '2025-10-04 09:44:43'),
(30, 23, 'cable', 2, 150.00, '2025-10-04 10:06:26'),
(31, 22, 'Air pod', 1, 250.00, '2025-10-04 10:09:44'),
(32, 12, 'Charger', 1, 3000.00, '2025-10-04 10:10:30');

-- --------------------------------------------------------

--
-- Table structure for table `staff_orders`
--

CREATE TABLE `staff_orders` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_orders`
--

INSERT INTO `staff_orders` (`id`, `product_name`, `quantity`, `status`, `created_at`) VALUES
(1, 'Keyboard', 11, 'Delivered', '2025-09-27 12:21:16'),
(2, 'Laptop', 2, 'Pending', '2025-09-27 12:44:29'),
(3, 'Mouse', 22, 'Pending', '2025-09-27 12:44:34'),
(4, 'Laptop', 13, 'Pending', '2025-09-27 12:44:40'),
(5, 'Laptop', 45, 'Delivered', '2025-09-27 12:44:45'),
(6, 'Keyboard', 12, 'pending', '2025-09-27 14:41:38');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'Supplier One', 'sup1@example.com', '0123456789', 'Dhaka', '2025-09-23 16:59:45'),
(2, 'Supplier Two', 'sup2@example.com', '0987654321', 'Chittagong', '2025-09-23 16:59:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','supplier') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@stock.com', '123', 'admin'),
(2, 'staff', 'staff@stock.com', '123', 'staff'),
(4, 'supplier', 'supplier@stock.com', '123', 'supplier');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_payments`
--
ALTER TABLE `admin_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `sell_product`
--
ALTER TABLE `sell_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `staff_orders`
--
ALTER TABLE `staff_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_payments`
--
ALTER TABLE `admin_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sell_product`
--
ALTER TABLE `sell_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `staff_orders`
--
ALTER TABLE `staff_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sell_product`
--
ALTER TABLE `sell_product`
  ADD CONSTRAINT `sell_product_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
