-- Guest Orders & Membership System Schema
-- Run this SQL to add the new tables

-- Add membership fields to customers table
ALTER TABLE `customers` 
ADD COLUMN IF NOT EXISTS `is_member` TINYINT(1) DEFAULT 0 AFTER `phone`,
ADD COLUMN IF NOT EXISTS `membership_fee_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `is_member`,
ADD COLUMN IF NOT EXISTS `membership_date` DATETIME DEFAULT NULL AFTER `membership_fee_paid`;

-- Guest customers table
CREATE TABLE IF NOT EXISTS `guest_customers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `otp_code` VARCHAR(6) DEFAULT NULL,
    `otp_expires` DATETIME DEFAULT NULL,
    `otp_verified` TINYINT(1) DEFAULT 0,
    `session_id` VARCHAR(64) DEFAULT NULL,
    `total_orders` INT(11) DEFAULT 0,
    `total_stocks_ordered` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `phone` (`phone`),
    KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Guest orders table
CREATE TABLE IF NOT EXISTS `guest_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `guest_id` INT(11) NOT NULL,
    `total_stocks` INT(11) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','paid','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `tran_id` VARCHAR(50) DEFAULT NULL,
    `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `delivery_date` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `guest_id` (`guest_id`),
    CONSTRAINT `guest_orders_guest_fk` FOREIGN KEY (`guest_id`) REFERENCES `guest_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Guest order items table
CREATE TABLE IF NOT EXISTS `guest_order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `guest_order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `guest_order_id` (`guest_order_id`),
    CONSTRAINT `guest_order_items_order_fk` FOREIGN KEY (`guest_order_id`) REFERENCES `guest_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Membership payments table
CREATE TABLE IF NOT EXISTS `membership_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `tran_id` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('pending','completed','failed') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `membership_payments_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cart table for registered customers (multi-product orders)
CREATE TABLE IF NOT EXISTS `customer_cart` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_product` (`customer_id`, `product_id`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `cart_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update customer_orders to support multi-item orders
ALTER TABLE `customer_orders`
ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) DEFAULT 0.00 AFTER `price`,
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_percent`,
ADD COLUMN IF NOT EXISTS `total_stocks` INT(11) DEFAULT 0 AFTER `discount_amount`;
