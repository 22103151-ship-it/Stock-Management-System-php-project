-- Notification Dots System Schema Updates
-- Add notification dots table for colored dot notifications

CREATE TABLE IF NOT EXISTS `notification_dots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` enum('customer_request','staff_product_need','supplier_response','admin_order_request') NOT NULL,
  `from_user_type` enum('customer','staff','supplier','admin') NOT NULL,
  `to_user_type` enum('admin','staff','supplier') NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID, Product ID, or other reference',
  `dot_color` enum('blue','green','yellow','red') NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notification_type` (`notification_type`),
  KEY `to_user_type` (`to_user_type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add product_request table for staff product needs
CREATE TABLE IF NOT EXISTS `product_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL COMMENT 'Staff user ID',
  `quantity_needed` int(11) NOT NULL,
  `urgency_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `reason` text,
  `status` enum('pending','approved','ordered','received','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Admin user ID',
  `supplier_ordered` int(11) DEFAULT NULL COMMENT 'Supplier user ID',
  `supplier_response` enum('pending','accepted','later','cancelled') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `requested_by` (`requested_by`),
  KEY `status` (`status`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add supplier_orders table for admin to supplier communications
CREATE TABLE IF NOT EXISTS `supplier_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_request_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','accepted','preparing','shipped','delivered','cancelled') DEFAULT 'pending',
  `estimated_delivery` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_request_id` (`product_request_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `product_id` (`product_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`product_request_id`) REFERENCES `product_requests` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;