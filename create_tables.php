<?php
/**
 * Script para crear tablas una por una - DT Studio
 */

echo "=== CREANDO TABLAS EN BASE DE DATOS ===\n\n";

$host = '216.18.195.84';
$db_name = 'dtstudio_main';
$username = 'dtstudio_main';
$password = 'TkC6E7#o#Ds#m??5';

try {
    $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Conectado a la base de datos\n\n";
    
    // Crear tabla roles
    echo "1. Creando tabla 'roles'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'roles' creada\n";
    
    // Crear tabla users
    echo "2. Creando tabla 'users'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'users' creada\n";
    
    // Crear tabla categories
    echo "3. Creando tabla 'categories'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'categories' creada\n";
    
    // Crear tabla products
    echo "4. Creando tabla 'products'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'products' creada\n";
    
    // Crear tabla product_variants
    echo "5. Creando tabla 'product_variants'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'product_variants' creada\n";
    
    // Crear tabla product_images
    echo "6. Creando tabla 'product_images'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'product_images' creada\n";
    
    // Crear tabla customers
    echo "7. Creando tabla 'customers'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'customers' creada\n";
    
    // Crear tabla quotations
    echo "8. Creando tabla 'quotations'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'quotations' creada\n";
    
    // Crear tabla quotation_items
    echo "9. Creando tabla 'quotation_items'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'quotation_items' creada\n";
    
    // Crear tabla orders
    echo "10. Creando tabla 'orders'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'orders' creada\n";
    
    // Crear tabla order_items
    echo "11. Creando tabla 'order_items'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'order_items' creada\n";
    
    // Crear tabla payments
    echo "12. Creando tabla 'payments'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `method` enum('cash','transfer','card','oxxo') NOT NULL,
            `reference` varchar(255) DEFAULT NULL,
            `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
            `payment_date` timestamp NULL DEFAULT NULL,
            `notes` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `fk_payments_order` (`order_id`),
            CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'payments' creada\n";
    
    // Crear tabla settings
    echo "13. Creando tabla 'settings'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `key` varchar(255) NOT NULL,
            `value` text,
            `type` enum('string','number','boolean','json') DEFAULT 'string',
            `description` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_setting_key` (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'settings' creada\n";
    
    // Crear tabla banners
    echo "14. Creando tabla 'banners'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabla 'banners' creada\n";
    
    echo "\n=== INSERTANDO DATOS INICIALES ===\n";
    
    // Insertar roles
    echo "Insertando roles...\n";
    $pdo->exec("
        INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
        ('Administrador', 'Acceso completo al sistema', '{\"all\": true}'),
        ('Ventas', 'Gestión de clientes y cotizaciones', '{\"customers\": true, \"quotations\": true, \"orders\": true, \"products\": {\"read\": true}}'),
        ('Diseñador/Producción', 'Gestión de productos y producción', '{\"products\": true, \"categories\": true, \"gallery\": true}'),
        ('Solo Lectura', 'Solo visualización de datos', '{\"read\": true}')
    ");
    echo "   ✅ Roles insertados\n";
    
    // Insertar usuario administrador
    echo "Insertando usuario administrador...\n";
    $pdo->exec("
        INSERT INTO `users` (`name`, `email`, `password`, `role_id`) VALUES
        ('Administrador', 'admin@dtstudio.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
    ");
    echo "   ✅ Usuario administrador insertado\n";
    
    // Insertar configuraciones
    echo "Insertando configuraciones...\n";
    $pdo->exec("
        INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
        ('company_name', 'DT Studio', 'string', 'Nombre de la empresa'),
        ('company_email', 'info@dtstudio.com', 'string', 'Email de contacto'),
        ('company_phone', '+52 55 1234 5678', 'string', 'Teléfono de contacto'),
        ('tax_rate', '16.00', 'number', 'Tasa de impuestos por defecto'),
        ('currency', 'MXN', 'string', 'Moneda por defecto'),
        ('quotation_valid_days', '30', 'number', 'Días de validez de cotizaciones'),
        ('low_stock_threshold', '10', 'number', 'Umbral de stock bajo')
    ");
    echo "   ✅ Configuraciones insertadas\n";
    
    // Insertar categorías
    echo "Insertando categorías...\n";
    $pdo->exec("
        INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
        ('Playeras', 'playeras', 'Playeras y camisetas personalizadas'),
        ('Vasos', 'vasos', 'Vasos y tazas personalizadas'),
        ('Gorras', 'gorras', 'Gorras y sombreros personalizados'),
        ('Lonas', 'lonas', 'Lonas y banners publicitarios'),
        ('Accesorios', 'accesorios', 'Accesorios promocionales varios')
    ");
    echo "   ✅ Categorías insertadas\n";
    
    echo "\n=== VERIFICACIÓN FINAL ===\n";
    
    // Verificar tablas creadas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas creadas: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
    // Verificar datos
    $roles = $pdo->query("SELECT COUNT(*) as count FROM roles")->fetch();
    $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    $settings = $pdo->query("SELECT COUNT(*) as count FROM settings")->fetch();
    $categories = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch();
    
    echo "\nDatos insertados:\n";
    echo "  - Roles: {$roles['count']}\n";
    echo "  - Usuarios: {$users['count']}\n";
    echo "  - Configuraciones: {$settings['count']}\n";
    echo "  - Categorías: {$categories['count']}\n";
    
    echo "\n🎉 BASE DE DATOS CONFIGURADA EXITOSAMENTE\n";
    echo "El sistema está listo para usar.\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    exit(1);
}

echo "\n=== FIN DE LA CONFIGURACIÓN ===\n";
