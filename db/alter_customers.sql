ALTER TABLE `customers` 
ADD COLUMN `username` varchar(50) NOT NULL AFTER `email`,
ADD COLUMN `password` varchar(255) NOT NULL AFTER `username`,
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD UNIQUE INDEX `username_unique` (`username`),
ADD UNIQUE INDEX `email_unique` (`email`);
