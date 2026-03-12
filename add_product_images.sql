-- Add image column to products table
ALTER TABLE `products` ADD `image` varchar(255) DEFAULT NULL AFTER `stock`;

-- Update existing products with default images (you can replace these with actual image paths)
UPDATE `products` SET `image` = 'mouse.jpg' WHERE `name` = 'Mouse';
UPDATE `products` SET `image` = 'keyboard.jpg' WHERE `name` = 'Keyboard';
UPDATE `products` SET `image` = 'laptop.jpg' WHERE `name` = 'Laptop';
UPDATE `products` SET `image` = 'chargers21.jpg' WHERE `name` = 'Charger';
UPDATE `products` SET `image` = 'usb_cable.jpg' WHERE `name` = 'USB Cable';
UPDATE `products` SET `image` = 'earphone.jpg' WHERE `name` = 'Earphone';
UPDATE `products` SET `image` = 'airpod.jpg' WHERE `name` = 'Air pod';
UPDATE `products` SET `image` = 'cable.jpg' WHERE `name` = 'cable';
UPDATE `products` SET `image` = 'charger_cable.jpg' WHERE `name` = 'Charger Cable';