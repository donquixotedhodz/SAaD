-- Add columns for tracking booking approval and cancellation
ALTER TABLE `bookings`
ADD COLUMN `confirmed_by` int(11) DEFAULT NULL,
ADD COLUMN `confirmed_at` datetime DEFAULT NULL,
ADD COLUMN `cancelled_by` int(11) DEFAULT NULL,
ADD COLUMN `cancelled_at` datetime DEFAULT NULL,
ADD COLUMN `admin_cancelled` tinyint(1) DEFAULT 0,
ADD FOREIGN KEY (`confirmed_by`) REFERENCES `admin`(`id`),
ADD FOREIGN KEY (`cancelled_by`) REFERENCES `admin`(`id`);
