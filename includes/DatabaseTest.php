<?php
/**
 * Clase Database para Tests - DT Studio
 * Manejo de conexión a base de datos SQLite para pruebas
 */

class DatabaseTest {
    private static $instance = null;
    private $connection;
    private $db_path;

    private function __construct() {
        $this->db_path = __DIR__ . '/../database/test.db';
        
        // Crear directorio si no existe
        $db_dir = dirname($this->db_path);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        try {
            $this->connection = new PDO("sqlite:{$this->db_path}");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Crear tablas si no existen
            $this->createTables();
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos de prueba: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }

    private function createTables() {
        // Leer y ejecutar el esquema completo
        $schemaPath = __DIR__ . '/../database/schema_test.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            $this->connection->exec($schema);
        } else {
            // Fallback: crear tablas básicas
            $this->createBasicTables();
        }
    }
    
    private function createBasicTables() {
        // Crear tablas directamente
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `permissions` TEXT DEFAULT NULL,
                `is_active` INTEGER DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->connection->exec("
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
            )
        ");
        
        $this->connection->exec("
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
            )
        ");
        
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `category_id` INTEGER NOT NULL,
                `sku` VARCHAR(100) NOT NULL UNIQUE,
                `status` VARCHAR(20) DEFAULT 'draft',
                `meta_title` VARCHAR(255) DEFAULT NULL,
                `meta_description` TEXT,
                `created_by` INTEGER NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
            )
        ");
        
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS `product_variants` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `product_id` INTEGER NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `sku` VARCHAR(100) NOT NULL UNIQUE,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `stock` INTEGER DEFAULT 0,
                `attributes` TEXT DEFAULT NULL,
                `is_active` INTEGER DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            )
        ");
        
        $this->connection->exec("
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
            )
        ");
        
        // Insertar datos iniciales
        $this->connection->exec("
            INSERT OR IGNORE INTO `roles` (`name`, `description`, `permissions`) VALUES
            ('Administrador', 'Acceso completo al sistema', '{\"all\": true}'),
            ('Ventas', 'Gestión de clientes y cotizaciones', '{\"customers\": true, \"quotations\": true, \"orders\": true, \"products\": {\"read\": true}}'),
            ('Diseñador/Producción', 'Gestión de productos y producción', '{\"products\": true, \"categories\": true, \"gallery\": true}'),
            ('Solo Lectura', 'Solo visualización de datos', '{\"read\": true}')
        ");
        
        $this->connection->exec("
            INSERT OR IGNORE INTO `users` (`name`, `email`, `password`, `role_id`) VALUES
            ('Administrador', 'admin@dtstudio.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
        ");
        
        $this->connection->exec("
            INSERT OR IGNORE INTO `categories` (`name`, `slug`, `description`) VALUES
            ('Playeras', 'playeras', 'Playeras y camisetas personalizadas'),
            ('Vasos', 'vasos', 'Vasos y tazas personalizadas'),
            ('Gorras', 'gorras', 'Gorras y sombreros personalizados'),
            ('Lonas', 'lonas', 'Lonas y banners publicitarios'),
            ('Accesorios', 'accesorios', 'Accesorios promocionales varios')
        ");
    }

    public function reset() {
        // Eliminar todas las tablas
        $tables = ['banners', 'settings', 'payments', 'order_items', 'orders', 'quotation_items', 'quotations', 'customers', 'product_images', 'product_variants', 'products', 'categories', 'users', 'roles'];
        
        foreach ($tables as $table) {
            $this->connection->exec("DROP TABLE IF EXISTS {$table}");
        }
        
        // Recrear tablas
        $this->createTables();
    }
}
