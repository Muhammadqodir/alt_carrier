-- Create offers table for customer contact information
-- Run this SQL script to create the offers table in your database

CREATE TABLE IF NOT EXISTS `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','contacted','closed') DEFAULT 'new',
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
