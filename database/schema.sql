-- =====================================================
-- Esquema de Base de Datos - DT Studio
-- Sistema de Gestión de Promocionales
-- =====================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `dtstudio_main` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `dtstudio_main`;

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `permissions` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role_id` int(11) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `avatar` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `last_login_at` timestamp NULL DEFAULT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_email` (`email`),
    KEY `fk_users_role` (`role_id`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: categories
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `parent_id` int(11) DEFAULT NULL,
    `image` varchar(500) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_slug` (`slug`),
    KEY `fk_categories_parent` (`parent_id`),
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: products
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `category_id` int(11) NOT NULL,
    `sku` varchar(100) NOT NULL,
    `status` enum('active','inactive','draft') DEFAULT 'draft',
    `meta_title` varchar(255) DEFAULT NULL,
    `meta_description` text,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sku` (`sku`),
    KEY `fk_products_category` (`category_id`),
    KEY `fk_products_created_by` (`created_by`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: product_variants
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `sku` varchar(100) NOT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
    `stock` int(11) DEFAULT 0,
    `attributes` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_variant_sku` (`sku`),
    KEY `fk_variants_product` (`product_id`),
    CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: product_images
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `variant_id` int(11) DEFAULT NULL,
    `url` varchar(500) NOT NULL,
    `alt_text` varchar(255) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_primary` tinyint(1) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_images_product` (`product_id`),
    KEY `fk_images_variant` (`variant_id`),
    CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_images_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: customers
-- =====================================================
CREATE TABLE IF NOT EXISTS `customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `address` text,
    `city` varchar(100) DEFAULT NULL,
    `state` varchar(100) DEFAULT NULL,
    `postal_code` varchar(20) DEFAULT NULL,
    `country` varchar(100) DEFAULT 'México',
    `notes` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_customer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: quotations
-- =====================================================
CREATE TABLE IF NOT EXISTS `quotations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `quotation_number` varchar(50) NOT NULL,
    `status` enum('draft','sent','reviewed','approved','rejected','converted') DEFAULT 'draft',
    `subtotal` decimal(10,2) DEFAULT 0.00,
    `tax_rate` decimal(5,2) DEFAULT 0.00,
    `tax_amount` decimal(10,2) DEFAULT 0.00,
    `total` decimal(10,2) DEFAULT 0.00,
    `valid_until` date DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_quotation_number` (`quotation_number`),
    KEY `fk_quotations_customer` (`customer_id`),
    KEY `fk_quotations_user` (`user_id`),
    CONSTRAINT `fk_quotations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_quotations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: quotation_items
-- =====================================================
CREATE TABLE IF NOT EXISTS `quotation_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `variant_id` int(11) DEFAULT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total` decimal(10,2) NOT NULL DEFAULT 0.00,
    `notes` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_quotation_items_quotation` (`quotation_id`),
    KEY `fk_quotation_items_product` (`product_id`),
    KEY `fk_quotation_items_variant` (`variant_id`),
    CONSTRAINT `fk_quotation_items_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_quotation_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: orders
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) DEFAULT NULL,
    `customer_id` int(11) NOT NULL,
    `order_number` varchar(50) NOT NULL,
    `status` enum('pending','confirmed','in_production','ready','delivered','cancelled') DEFAULT 'pending',
    `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
    `subtotal` decimal(10,2) DEFAULT 0.00,
    `tax_amount` decimal(10,2) DEFAULT 0.00,
    `total` decimal(10,2) DEFAULT 0.00,
    `delivery_date` date DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_order_number` (`order_number`),
    KEY `fk_orders_quotation` (`quotation_id`),
    KEY `fk_orders_customer` (`customer_id`),
    CONSTRAINT `fk_orders_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: order_items
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `variant_id` int(11) DEFAULT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total` decimal(10,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_order_items_order` (`order_id`),
    KEY `fk_order_items_product` (`product_id`),
    KEY `fk_order_items_variant` (`variant_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payments
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `method` enum('cash','transfer','card','oxxo','paypal','stripe') NOT NULL,
    `reference` varchar(255) DEFAULT NULL,
    `status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
    `gateway` varchar(100) DEFAULT NULL,
    `gateway_transaction_id` varchar(255) DEFAULT NULL,
    `gateway_response` text DEFAULT NULL,
    `payment_date` timestamp NULL DEFAULT NULL,
    `notes` text,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_payments_order` (`order_id`),
    KEY `fk_payments_user` (`created_by`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payment_gateways
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment_gateways` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL UNIQUE,
    `type` enum('stripe','paypal','oxxo','transfer','cash') NOT NULL,
    `description` text DEFAULT NULL,
    `config` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gateway_type` (`type`),
    KEY `idx_gateway_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `notification_id` varchar(100) NOT NULL UNIQUE,
    `type` enum('email','sms','push','system') NOT NULL,
    `recipient` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `template_id` int(11) DEFAULT NULL,
    `data` json DEFAULT NULL,
    `status` enum('pending','sent','failed','delivered','read') DEFAULT 'pending',
    `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `error_message` text DEFAULT NULL,
    `retry_count` int(11) DEFAULT 0,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notification_id` (`notification_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_recipient` (`recipient`),
    KEY `idx_created_at` (`created_at`),
    KEY `fk_notifications_user` (`created_by`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: email_templates
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `body` text NOT NULL,
    `category` varchar(100) DEFAULT 'general',
    `variables` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sms_templates
-- =====================================================
CREATE TABLE IF NOT EXISTS `sms_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `body` text NOT NULL,
    `category` varchar(100) DEFAULT 'general',
    `variables` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: email_settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`setting_key`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sms_settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `sms_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`setting_key`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: settings (actualizada)
-- =====================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL UNIQUE,
    `value` text NOT NULL,
    `type` enum('string','integer','float','boolean','array','json') DEFAULT 'string',
    `description` text DEFAULT NULL,
    `is_public` tinyint(1) DEFAULT 0,
    `group_name` varchar(100) DEFAULT 'general',
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`key`),
    KEY `idx_group` (`group_name`),
    KEY `idx_type` (`type`),
    KEY `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: banners
-- =====================================================
CREATE TABLE IF NOT EXISTS `banners` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `image` varchar(500) NOT NULL,
    `link` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `position` varchar(50) DEFAULT 'home',
    `start_date` datetime DEFAULT NULL,
    `end_date` datetime DEFAULT NULL,
    `target_blank` tinyint(1) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active` (`active`),
    KEY `idx_position` (`position`),
    KEY `idx_sort` (`sort_order`),
    KEY `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `type` enum('string','number','boolean','json') DEFAULT 'string',
    `description` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_setting_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: banners
-- =====================================================
CREATE TABLE IF NOT EXISTS `banners` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `subtitle` varchar(255) DEFAULT NULL,
    `image` varchar(500) NOT NULL,
    `link` varchar(500) DEFAULT NULL,
    `button_text` varchar(100) DEFAULT NULL,
    `position` enum('hero','secondary','footer') DEFAULT 'hero',
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar roles por defecto
INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
('Administrador', 'Acceso completo al sistema', '{"all": true}'),
('Ventas', 'Gestión de clientes y cotizaciones', '{"customers": true, "quotations": true, "orders": true, "products": {"read": true}}'),
('Diseñador/Producción', 'Gestión de productos y producción', '{"products": true, "categories": true, "gallery": true}'),
('Solo Lectura', 'Solo visualización de datos', '{"read": true}');

-- Insertar usuario administrador por defecto
INSERT INTO `users` (`name`, `email`, `password`, `role_id`) VALUES
('Administrador', 'admin@dtstudio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insertar configuraciones por defecto
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('company_name', 'DT Studio', 'string', 'Nombre de la empresa'),
('company_email', 'info@dtstudio.com', 'string', 'Email de contacto'),
('company_phone', '+52 55 1234 5678', 'string', 'Teléfono de contacto'),
('tax_rate', '16.00', 'number', 'Tasa de impuestos por defecto'),
('currency', 'MXN', 'string', 'Moneda por defecto'),
('quotation_valid_days', '30', 'number', 'Días de validez de cotizaciones'),
('low_stock_threshold', '10', 'number', 'Umbral de stock bajo');

-- =====================================================
-- TABLA: stock_movements
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `variant_id` int(11) NOT NULL,
    `type` enum('adjustment_in','adjustment_out','sale','purchase','return','reservation','release','transfer_in','transfer_out','damage','loss') NOT NULL,
    `quantity` int(11) NOT NULL,
    `old_stock` int(11) DEFAULT 0,
    `new_stock` int(11) DEFAULT 0,
    `notes` text DEFAULT NULL,
    `reference_id` int(11) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product` (`product_id`),
    KEY `idx_variant` (`variant_id`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`),
    KEY `fk_stock_movements_product` (`product_id`),
    KEY `fk_stock_movements_variant` (`variant_id`),
    KEY `fk_stock_movements_user` (`created_by`),
    CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_movements_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_movements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: suppliers
-- =====================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `phone` varchar(20) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `state` varchar(100) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `postal_code` varchar(20) DEFAULT NULL,
    `contact_person` varchar(255) DEFAULT NULL,
    `website` varchar(500) DEFAULT NULL,
    `tax_id` varchar(100) DEFAULT NULL,
    `payment_terms` varchar(100) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_name` (`name`),
    KEY `idx_active` (`is_active`),
    KEY `idx_country` (`country`),
    KEY `idx_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna supplier_id a la tabla products
ALTER TABLE `products` ADD COLUMN `supplier_id` int(11) DEFAULT NULL AFTER `category_id`;
ALTER TABLE `products` ADD KEY `fk_products_supplier` (`supplier_id`);
ALTER TABLE `products` ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Insertar categorías por defecto
INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
('Playeras', 'playeras', 'Playeras y camisetas personalizadas'),
('Vasos', 'vasos', 'Vasos y tazas personalizadas'),
('Gorras', 'gorras', 'Gorras y sombreros personalizados'),
('Lonas', 'lonas', 'Lonas y banners publicitarios'),
('Accesorios', 'accesorios', 'Accesorios promocionales varios');
