-- Add new columns to customers table
ALTER TABLE `customers` 
ADD COLUMN IF NOT EXISTS `username` varchar(50) NOT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `password` varchar(255) NOT NULL AFTER `username`,
ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add unique indexes
ALTER TABLE `customers`
ADD UNIQUE INDEX IF NOT EXISTS `username_unique` (`username`),
ADD UNIQUE INDEX IF NOT EXISTS `email_unique` (`email`);
