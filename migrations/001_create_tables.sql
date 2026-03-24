-- PHP Form Builder - Database Migration
-- Run this file to set up the database schema
-- Default login: admin@admin.com / Admin@1234

CREATE DATABASE IF NOT EXISTS `php_form_builder`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `php_form_builder`;

-- -----------------------------------------------
-- Users (Admin)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','viewer') NOT NULL DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password: Admin@1234  (bcrypt cost=10, PHP compatible $2y$)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@admin.com', '$2y$10$mNzmlKBYeGq0fJAvtomm8uBRhgPrdeybx2Ms7Qs/xvGsB./oJwTN.', 'admin');

-- -----------------------------------------------
-- JWT Refresh Tokens
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(512) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Forms
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `forms` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`        CHAR(36) NOT NULL UNIQUE,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Fields
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `fields` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `form_id`      INT UNSIGNED NOT NULL,
  `field_name`   VARCHAR(100) NOT NULL,
  `field_type`   ENUM('text','email','number','textarea','dropdown','radio','checkbox','file') NOT NULL,
  `label`        VARCHAR(200) NOT NULL,
  `placeholder`  VARCHAR(200) DEFAULT NULL,
  `options`      JSON DEFAULT NULL,
  `is_required`  TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order`   INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Submissions
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `submissions` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `form_id`      INT UNSIGNED NOT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `user_agent`   VARCHAR(500) DEFAULT NULL,
  FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Submission Values (EAV)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `submission_values` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `submission_id` INT UNSIGNED NOT NULL,
  `field_id`      INT UNSIGNED NOT NULL,
  `field_label`   VARCHAR(200) NOT NULL,
  `value`         TEXT,
  FOREIGN KEY (`submission_id`) REFERENCES `submissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`field_id`)      REFERENCES `fields`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
