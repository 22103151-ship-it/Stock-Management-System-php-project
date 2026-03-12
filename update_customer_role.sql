-- Update users table to include customer role
ALTER TABLE `users` MODIFY COLUMN `role` enum('admin','staff','supplier','customer') NOT NULL;

-- Insert a sample customer user
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(5, 'customer', 'customer@stock.com', '123', 'customer');