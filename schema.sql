-- Dermamed PMS Schema
-- Based on requirements: separate Roles, CUIT for Users/Patients, Service-based Appointments, Billing & Stock modules.

SET FOREIGN_KEY_CHECKS=0;

-- -----------------------------------------------------
-- 1. Core Users & Auth
-- -----------------------------------------------------

-- Roles: Admin, Doctor, Receptionist
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL, -- 'admin', 'doctor', 'receptionist'
    PRIMARY KEY (`id`),
    UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed for Roles
INSERT INTO `roles` (`id`, `name`) VALUES (1, 'admin'), (2, 'doctor'), (3, 'receptionist');

-- Users: Replaces 'Usuarios', 'Medicos'. Linked to Roles.
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `cuit` VARCHAR(20) NULL UNIQUE, -- Tax ID
    `specialty` VARCHAR(100) NULL, -- Only for doctors
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE (`email`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Patients
-- -----------------------------------------------------

-- Patients (Pacientes)
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `cuit` VARCHAR(20) NULL UNIQUE, -- Replaces DNI
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `birth_date` DATE NULL,
    `address` VARCHAR(255) NULL,
    `insurance_provider` VARCHAR(100) NULL, -- Obra Social
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Clinical Management
-- -----------------------------------------------------

-- Services (Tipo_Tratamiento)
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `duration_minutes` INT NOT NULL DEFAULT 30,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments (Turnos)
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` BIGINT UNSIGNED NOT NULL,
    `doctor_id` BIGINT UNSIGNED NOT NULL,
    `service_id` BIGINT UNSIGNED NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'cancelled', 'attended') NOT NULL DEFAULT 'pending',
    `reserve_channel` VARCHAR(50) NULL, -- 'whatsapp', 'manual', 'web'
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Records (Historia_Clinica)
DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE `medical_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` BIGINT UNSIGNED NOT NULL,
    `doctor_id` BIGINT UNSIGNED NOT NULL,
    `appointment_id` BIGINT UNSIGNED NULL,
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `content` TEXT NOT NULL,
    -- Future: photos will be in a separate attachments table or handled by media manager
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Availabilities (Disponibilidad_Medico)
DROP TABLE IF EXISTS `doctor_availabilities`;
CREATE TABLE `doctor_availabilities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `doctor_id` BIGINT UNSIGNED NOT NULL,
    `day_of_week` TINYINT UNSIGNED NOT NULL, -- 0=Sunday, 6=Saturday
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Products & Stock
-- -----------------------------------------------------

-- Products (Productos)
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `min_stock` INT NOT NULL DEFAULT 5,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Movements (Movimientos_Stock)
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL, -- Who performed the action
    `type` ENUM('in', 'out', 'adjustment') NOT NULL,
    `quantity` INT NOT NULL,
    `reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Billing & Finance
-- -----------------------------------------------------

-- Payment Methods (Metodo_Pago)
DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL, -- Cash, Transfer, Credit Card, etc.
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Voucher Types (Tipo_Comprobante)
DROP TABLE IF EXISTS `voucher_types`;
CREATE TABLE `voucher_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL, -- Invoice A, Invoice B, Receipt X
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices (Facturas)
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `patient_id` BIGINT UNSIGNED NOT NULL,
    `voucher_type_id` BIGINT UNSIGNED NOT NULL,
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    `cae` VARCHAR(100) NULL, -- For future AFIP integration
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`voucher_type_id`) REFERENCES `voucher_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Items (Detalle_Factura)
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `service_id` BIGINT UNSIGNED NULL,
    `description` VARCHAR(255) NOT NULL, -- Snapshot of name
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Payments (Pagos_Factura) - Allows split payments
DROP TABLE IF EXISTS `invoice_payments`;
CREATE TABLE `invoice_payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `payment_method_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
