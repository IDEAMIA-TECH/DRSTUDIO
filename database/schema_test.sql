-- =====================================================
-- Esquema de Base de Datos para Tests - DT Studio
-- Sistema de Gestión de Promocionales (SQLite)
-- =====================================================

-- Crear base de datos SQLite
-- El archivo se creará automáticamente

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `permissions` TEXT DEFAULT NULL,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INTEGER NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `is_active` INTEGER DEFAULT 1,
    `email_verified_at` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: categories
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `parent_id` INTEGER DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `sort_order` INTEGER DEFAULT 0,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
);

-- =====================================================
-- TABLA: products
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `category_id` INTEGER NOT NULL,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `status` VARCHAR(20) DEFAULT 'active',
    `is_featured` INTEGER DEFAULT 0,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT,
    `created_by` INTEGER NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: product_variants
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_variants` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `product_id` INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock` INTEGER DEFAULT 0,
    `min_stock` INTEGER DEFAULT 0,
    `max_stock` INTEGER DEFAULT 1000,
    `attributes` TEXT DEFAULT NULL,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: product_images
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `product_id` INTEGER NOT NULL,
    `variant_id` INTEGER DEFAULT NULL,
    `url` VARCHAR(500) NOT NULL,
    `alt_text` VARCHAR(255) DEFAULT NULL,
    `sort_order` INTEGER DEFAULT 0,
    `is_primary` INTEGER DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: customers
-- =====================================================
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `company` VARCHAR(255) DEFAULT NULL,
    `address` TEXT,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'México',
    `notes` TEXT,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: quotations
-- =====================================================
CREATE TABLE IF NOT EXISTS `quotations` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `customer_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `quotation_number` VARCHAR(50) NOT NULL UNIQUE,
    `status` VARCHAR(20) DEFAULT 'draft',
    `subtotal` DECIMAL(10,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total` DECIMAL(10,2) DEFAULT 0.00,
    `valid_until` DATE DEFAULT NULL,
    `notes` TEXT,
    `is_public` INTEGER DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: quotation_items
-- =====================================================
CREATE TABLE IF NOT EXISTS `quotation_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `quotation_id` INTEGER NOT NULL,
    `product_id` INTEGER NOT NULL,
    `variant_id` INTEGER DEFAULT NULL,
    `quantity` INTEGER NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: orders
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `quotation_id` INTEGER DEFAULT NULL,
    `customer_id` INTEGER NOT NULL,
    `created_by` INTEGER NOT NULL,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `status` VARCHAR(20) DEFAULT 'pending',
    `payment_status` VARCHAR(20) DEFAULT 'pending',
    `subtotal` DECIMAL(10,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_address` TEXT,
    `billing_address` TEXT,
    `delivery_date` DATE DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: order_items
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `product_id` INTEGER NOT NULL,
    `variant_id` INTEGER DEFAULT NULL,
    `quantity` INTEGER NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: reports
-- =====================================================
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `type` VARCHAR(50) NOT NULL,
    `user_id` INTEGER NOT NULL,
    `config` TEXT DEFAULT '{}',
    `is_public` INTEGER DEFAULT 0,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: report_data
-- =====================================================
CREATE TABLE IF NOT EXISTS `report_data` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `report_id` INTEGER NOT NULL,
    `data_key` VARCHAR(100) NOT NULL,
    `data_value` TEXT,
    `data_type` VARCHAR(20) DEFAULT 'string',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: payments
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `method` VARCHAR(20) NOT NULL,
    `reference` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `gateway` VARCHAR(100),
    `gateway_transaction_id` VARCHAR(255),
    `gateway_response` TEXT,
    `payment_date` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `created_by` INTEGER NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: payment_gateways
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment_gateways` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `type` VARCHAR(20) NOT NULL,
    `description` TEXT,
    `config` TEXT DEFAULT '{}',
    `is_active` INTEGER DEFAULT 1,
    `sort_order` INTEGER DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `notification_id` VARCHAR(100) NOT NULL UNIQUE,
    `type` VARCHAR(20) NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `template_id` INTEGER DEFAULT NULL,
    `data` TEXT DEFAULT '{}',
    `status` VARCHAR(20) DEFAULT 'pending',
    `priority` VARCHAR(20) DEFAULT 'normal',
    `scheduled_at` DATETIME DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `retry_count` INTEGER DEFAULT 0,
    `created_by` INTEGER NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: email_templates
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `category` VARCHAR(100) DEFAULT 'general',
    `variables` TEXT DEFAULT '{}',
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: sms_templates
-- =====================================================
CREATE TABLE IF NOT EXISTS `sms_templates` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `category` VARCHAR(100) DEFAULT 'general',
    `variables` TEXT DEFAULT '{}',
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: email_settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_settings` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: sms_settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `sms_settings` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `is_active` INTEGER DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: settings (actualizada)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'string',
    description TEXT,
    is_public INTEGER DEFAULT 0,
    group_name VARCHAR(100) DEFAULT 'general',
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: banners
-- =====================================================
CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(500) NOT NULL,
    link VARCHAR(500),
    description TEXT,
    active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    position VARCHAR(50) DEFAULT 'home',
    start_date DATETIME,
    end_date DATETIME,
    target_blank INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: banners
-- =====================================================
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `image` VARCHAR(500) NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `button_text` VARCHAR(100) DEFAULT NULL,
    `position` VARCHAR(20) DEFAULT 'hero',
    `is_active` INTEGER DEFAULT 1,
    `sort_order` INTEGER DEFAULT 0,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar roles por defecto
INSERT OR IGNORE INTO `roles` (`name`, `description`, `permissions`) VALUES
('Administrador', 'Acceso completo al sistema', '{"all": true}'),
('Ventas', 'Gestión de clientes y cotizaciones', '{"customers": true, "quotations": true, "orders": true, "products": {"read": true}}'),
('Diseñador/Producción', 'Gestión de productos y producción', '{"products": true, "categories": true, "gallery": true}'),
('Solo Lectura', 'Solo visualización de datos', '{"read": true}');

-- Insertar usuario administrador por defecto
INSERT OR IGNORE INTO `users` (`name`, `email`, `password`, `role_id`) VALUES
('Administrador', 'admin@dtstudio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insertar configuraciones por defecto
INSERT OR IGNORE INTO `settings` (`key`, `value`, `type`, `description`) VALUES
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
CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    variant_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    old_stock INTEGER DEFAULT 0,
    new_stock INTEGER DEFAULT 0,
    notes TEXT,
    reference_id INTEGER,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA: suppliers
-- =====================================================
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    contact_person VARCHAR(255),
    website VARCHAR(500),
    tax_id VARCHAR(100),
    payment_terms VARCHAR(100),
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Agregar columna supplier_id a la tabla products
ALTER TABLE products ADD COLUMN supplier_id INTEGER;
CREATE INDEX idx_products_supplier ON products(supplier_id);

-- Insertar categorías por defecto
INSERT OR IGNORE INTO `categories` (`name`, `slug`, `description`) VALUES
('Playeras', 'playeras', 'Playeras y camisetas personalizadas'),
('Vasos', 'vasos', 'Vasos y tazas personalizadas'),
('Gorras', 'gorras', 'Gorras y sombreros personalizados'),
('Lonas', 'lonas', 'Lonas y banners publicitarios'),
('Accesorios', 'accesorios', 'Accesorios promocionales varios');
