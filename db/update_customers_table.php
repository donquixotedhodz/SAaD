<?php
require_once '../config/database.php';

try {
    $pdo->exec("
        ALTER TABLE `customers` 
        ADD COLUMN IF NOT EXISTS `username` varchar(50) NOT NULL AFTER `email`,
        ADD COLUMN IF NOT EXISTS `password` varchar(255) NOT NULL AFTER `username`,
        ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ");

    // Add unique indexes if they don't exist
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS `username_unique` ON `customers` (`username`);
        CREATE INDEX IF NOT EXISTS `email_unique` ON `customers` (`email`);
    ");

    echo "Successfully updated customers table structure.";
} catch (PDOException $e) {
    die("Error updating table: " . $e->getMessage());
}
?>
