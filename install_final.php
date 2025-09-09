<?php
/**
 * Script de Instalación Final - DR Studio
 * 
 * Este script ejecuta cada comando SQL de manera individual y precisa.
 */

// Configuración de la base de datos
$host = '216.18.195.84';
$username = 'dtstudio_main';
$password = 'm&9!9ejG!5D6A$p&';
$database = 'dtstudio_main';

echo "🚀 Instalación Final de DR Studio...\n";
echo "====================================\n";

try {
    // Conectar a MySQL (sin especificar base de datos)
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "✅ Conexión a MySQL establecida\n";
    
    // Ejecutar comandos SQL uno por uno
    $commands = [
        "CREATE DATABASE IF NOT EXISTS dtstudio_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "USE dtstudio_main",
        
        // Tabla de usuarios
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin', 'ventas', 'produccion', 'lectura') DEFAULT 'lectura',
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabla de categorías
        "CREATE TABLE IF NOT EXISTS categorias (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            imagen VARCHAR(255),
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabla de productos
        "CREATE TABLE IF NOT EXISTS productos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            categoria_id INT,
            sku VARCHAR(50) UNIQUE NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            descripcion TEXT,
            precio_venta DECIMAL(10,2) NOT NULL,
            costo_fabricacion DECIMAL(10,2) NOT NULL,
            tiempo_entrega INT DEFAULT 7,
            imagen_principal VARCHAR(255),
            destacado BOOLEAN DEFAULT FALSE,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
        )",
        
        // Tabla de variantes de producto
        "CREATE TABLE IF NOT EXISTS variantes_producto (
            id INT PRIMARY KEY AUTO_INCREMENT,
            producto_id INT NOT NULL,
            talla VARCHAR(20),
            color VARCHAR(50),
            material VARCHAR(100),
            stock INT DEFAULT 0,
            precio_extra DECIMAL(10,2) DEFAULT 0,
            imagen VARCHAR(255),
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
        )",
        
        // Tabla de clientes
        "CREATE TABLE IF NOT EXISTS clientes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            telefono VARCHAR(20),
            empresa VARCHAR(200),
            direccion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabla de cotizaciones
        "CREATE TABLE IF NOT EXISTS cotizaciones (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_id INT,
            usuario_id INT,
            numero_cotizacion VARCHAR(20) UNIQUE NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            descuento DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            estado ENUM('pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada') DEFAULT 'pendiente',
            fecha_vencimiento DATE,
            observaciones TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )",
        
        // Tabla de items de cotización
        "CREATE TABLE IF NOT EXISTS cotizacion_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cotizacion_id INT NOT NULL,
            producto_id INT NOT NULL,
            variante_id INT,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
            FOREIGN KEY (variante_id) REFERENCES variantes_producto(id) ON DELETE SET NULL
        )",
        
        // Tabla de banners
        "CREATE TABLE IF NOT EXISTS banners (
            id INT PRIMARY KEY AUTO_INCREMENT,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT,
            imagen VARCHAR(255) NOT NULL,
            icono VARCHAR(100),
            enlace VARCHAR(500),
            orden INT DEFAULT 0,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabla de galería
        "CREATE TABLE IF NOT EXISTS galeria (
            id INT PRIMARY KEY AUTO_INCREMENT,
            titulo VARCHAR(200) NOT NULL,
            descripcion TEXT,
            imagen VARCHAR(255) NOT NULL,
            categoria VARCHAR(100),
            orden INT DEFAULT 0,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabla de testimonios
        "CREATE TABLE IF NOT EXISTS testimonios (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cliente_nombre VARCHAR(100) NOT NULL,
            empresa VARCHAR(200),
            testimonio TEXT NOT NULL,
            calificacion INT DEFAULT 5 CHECK (calificacion >= 1 AND calificacion <= 5),
            imagen VARCHAR(255),
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Insertar usuario administrador
        "INSERT INTO usuarios (username, email, password, rol, activo) VALUES 
        ('admin', 'admin@drstudio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1)
        ON DUPLICATE KEY UPDATE username = username",
        
        // Insertar categorías de ejemplo
        "INSERT INTO categorias (nombre, descripcion, activo) VALUES 
        ('Playeras', 'Playeras de algodón con diferentes técnicas de estampado', 1),
        ('Vasos', 'Vasos personalizados con diferentes materiales', 1),
        ('Tazas', 'Tazas de cerámica y otros materiales', 1),
        ('Gorras', 'Gorras y sombreros personalizados', 1),
        ('Lonas', 'Lonas publicitarias y banners', 1)
        ON DUPLICATE KEY UPDATE nombre = nombre",
        
        // Insertar productos de ejemplo
        "INSERT INTO productos (categoria_id, sku, nombre, descripcion, precio_venta, costo_fabricacion, tiempo_entrega, destacado, activo) VALUES 
        (1, 'PLAY-001', 'Playera Básica Algodón', 'Playera 100% algodón, disponible en varios colores', 150.00, 80.00, 5, 1, 1),
        (1, 'PLAY-002', 'Playera Premium', 'Playera de algodón premium con mejor calidad', 200.00, 120.00, 7, 1, 1),
        (2, 'VASO-001', 'Vaso Térmico', 'Vaso térmico de acero inoxidable', 180.00, 100.00, 10, 1, 1),
        (3, 'TAZA-001', 'Taza Cerámica', 'Taza de cerámica blanca personalizable', 120.00, 60.00, 5, 0, 1),
        (4, 'GORRA-001', 'Gorra Trucker', 'Gorra trucker ajustable', 160.00, 90.00, 7, 0, 1)
        ON DUPLICATE KEY UPDATE nombre = nombre",
        
        // Insertar variantes de ejemplo
        "INSERT INTO variantes_producto (producto_id, talla, color, material, stock, precio_extra) VALUES 
        (1, 'S', 'Blanco', 'Algodón 100%', 50, 0.00),
        (1, 'M', 'Blanco', 'Algodón 100%', 75, 0.00),
        (1, 'L', 'Blanco', 'Algodón 100%', 60, 0.00),
        (1, 'XL', 'Blanco', 'Algodón 100%', 40, 0.00),
        (1, 'S', 'Negro', 'Algodón 100%', 45, 10.00),
        (1, 'M', 'Negro', 'Algodón 100%', 70, 10.00),
        (1, 'L', 'Negro', 'Algodón 100%', 55, 10.00),
        (1, 'XL', 'Negro', 'Algodón 100%', 35, 10.00)
        ON DUPLICATE KEY UPDATE stock = stock",
        
        // Insertar banners de ejemplo
        "INSERT INTO banners (titulo, descripcion, imagen, icono, enlace, orden, activo) VALUES 
        ('Promoción de Verano', 'Descuentos especiales en playeras', 'banners/verano.jpg', 'fas fa-sun', '#', 1, 1),
        ('Nuevos Productos', 'Descubre nuestra nueva línea', 'banners/nuevos.jpg', 'fas fa-star', '#', 2, 1),
        ('Servicio Express', 'Entrega en 24 horas', 'banners/express.jpg', 'fas fa-shipping-fast', '#', 3, 1)
        ON DUPLICATE KEY UPDATE titulo = titulo",
        
        // Insertar testimonios de ejemplo
        "INSERT INTO testimonios (cliente_nombre, empresa, testimonio, calificacion, activo) VALUES 
        ('María González', 'Empresa ABC', 'Excelente calidad en los productos y muy buen servicio al cliente', 5, 1),
        ('Carlos López', 'Comercial XYZ', 'Los tiempos de entrega son muy buenos y la calidad supera las expectativas', 5, 1),
        ('Ana Martínez', 'Retail 123', 'Muy satisfecha con el trabajo realizado, definitivamente los recomiendo', 4, 1)
        ON DUPLICATE KEY UPDATE cliente_nombre = cliente_nombre"
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($commands as $index => $command) {
        $commandNumber = $index + 1;
        echo "Ejecutando comando $commandNumber...\n";
        
        try {
            if ($conn->query($command)) {
                $successCount++;
                echo "✅ Comando $commandNumber ejecutado exitosamente\n";
                
                // Mostrar detalles para comandos importantes
                if (strpos($command, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?(\w+)/', $command, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "   📊 Tabla '$tableName' creada\n";
                } elseif (strpos($command, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO.*?(\w+)/', $command, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "   📝 Datos insertados en '$tableName'\n";
                } elseif (strpos($command, 'CREATE DATABASE') !== false) {
                    echo "   🗄️  Base de datos creada/seleccionada\n";
                }
                
            } else {
                $errorCount++;
                echo "❌ Error en comando $commandNumber\n";
                echo "   Error: " . $conn->error . "\n";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Excepción en comando $commandNumber: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "========================================\n";
    echo "📊 Resumen de la Instalación\n";
    echo "========================================\n";
    echo "Comandos ejecutados exitosamente: $successCount\n";
    echo "Errores encontrados: $errorCount\n";
    echo "Total de comandos: " . count($commands) . "\n\n";
    
    if ($errorCount === 0) {
        echo "🎉 ¡Instalación Completada Exitosamente!\n";
        echo "========================================\n";
        
        // Verificar que las tablas se crearon
        echo "🔍 Verificando tablas creadas...\n";
        $conn->select_db($database);
        
        $tables = ['usuarios', 'categorias', 'productos', 'variantes_producto', 'clientes', 'cotizaciones', 'cotizacion_items', 'banners', 'galeria', 'testimonios'];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "✅ Tabla '$table' existe\n";
            } else {
                echo "❌ Tabla '$table' NO existe\n";
            }
        }
        
        echo "\nCredenciales de acceso:\n";
        echo "- Usuario: admin\n";
        echo "- Contraseña: password\n\n";
        
        // Crear archivo de instalación completada
        file_put_contents('INSTALLED', date('Y-m-d H:i:s'));
        echo "✅ Archivo de instalación creado\n";
        
    } else {
        echo "⚠️  Instalación Completada con Errores\n";
        echo "=====================================\n";
        echo "La instalación se completó pero se encontraron algunos errores.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error de Instalación\n";
    echo "=======================\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "\nInstalación completada el " . date('Y-m-d H:i:s') . "\n";
?>
