<?php
/**
 * Script de Verificación - DR Studio
 * 
 * Este script verifica que la instalación se haya completado correctamente
 * consultando las tablas y datos de la base de datos.
 */

// Configuración de la base de datos
$host = '173.231.22.109';
$username = 'dtstudio_main';
$password = 'm&9!9ejG!5D6A$p&';
$database = 'dtstudio_main';

echo "🔍 Verificando Instalación de DR Studio...\n";
echo "==========================================\n";

try {
    // Conectar a la base de datos
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "✅ Conexión a la base de datos establecida\n\n";
    
    // Verificar tablas
    echo "📊 Verificando Tablas:\n";
    echo "----------------------\n";
    
    $tables = [
        'usuarios' => 'Sistema de autenticación',
        'categorias' => 'Categorías de productos',
        'productos' => 'Catálogo de productos',
        'variantes_producto' => 'Variantes de productos',
        'clientes' => 'Base de datos de clientes',
        'cotizaciones' => 'Sistema de cotizaciones',
        'cotizacion_items' => 'Items de cotizaciones',
        'banners' => 'Banners del sitio',
        'galeria' => 'Galería de imágenes',
        'testimonios' => 'Testimonios de clientes'
    ];
    
    $allTablesExist = true;
    
    foreach ($tables as $table => $description) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✅ $table - $description\n";
        } else {
            echo "❌ $table - $description (FALTANTE)\n";
            $allTablesExist = false;
        }
    }
    
    echo "\n";
    
    // Verificar datos de ejemplo
    echo "📋 Verificando Datos de Ejemplo:\n";
    echo "--------------------------------\n";
    
    $dataChecks = [
        'usuarios' => "SELECT COUNT(*) as count FROM usuarios WHERE username = 'admin'",
        'categorias' => "SELECT COUNT(*) as count FROM categorias",
        'productos' => "SELECT COUNT(*) as count FROM productos",
        'variantes_producto' => "SELECT COUNT(*) as count FROM variantes_producto",
        'banners' => "SELECT COUNT(*) as count FROM banners",
        'testimonios' => "SELECT COUNT(*) as count FROM testimonios"
    ];
    
    foreach ($dataChecks as $table => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['count'];
            echo "✅ $table: $count registros\n";
        } else {
            echo "❌ $table: Error al consultar\n";
        }
    }
    
    echo "\n";
    
    // Verificar usuario administrador
    echo "👤 Verificando Usuario Administrador:\n";
    echo "------------------------------------\n";
    
    $result = $conn->query("SELECT username, email, rol, activo FROM usuarios WHERE username = 'admin'");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "✅ Usuario: " . $user['username'] . "\n";
        echo "✅ Email: " . $user['email'] . "\n";
        echo "✅ Rol: " . $user['rol'] . "\n";
        echo "✅ Activo: " . ($user['activo'] ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ Usuario administrador no encontrado\n";
    }
    
    echo "\n";
    
    // Verificar estructura de tablas importantes
    echo "🏗️  Verificando Estructura de Tablas:\n";
    echo "------------------------------------\n";
    
    $structureChecks = [
        'usuarios' => ['id', 'username', 'email', 'password', 'rol', 'activo'],
        'categorias' => ['id', 'nombre', 'descripcion', 'imagen', 'activo'],
        'productos' => ['id', 'categoria_id', 'sku', 'nombre', 'precio_venta', 'activo'],
        'banners' => ['id', 'titulo', 'descripcion', 'imagen', 'icono', 'enlace', 'activo']
    ];
    
    foreach ($structureChecks as $table => $fields) {
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            $existingFields = [];
            while ($row = $result->fetch_assoc()) {
                $existingFields[] = $row['Field'];
            }
            
            $missingFields = array_diff($fields, $existingFields);
            if (empty($missingFields)) {
                echo "✅ $table: Estructura correcta\n";
            } else {
                echo "❌ $table: Campos faltantes: " . implode(', ', $missingFields) . "\n";
            }
        } else {
            echo "❌ $table: Error al verificar estructura\n";
        }
    }
    
    echo "\n";
    
    // Resumen final
    if ($allTablesExist) {
        echo "🎉 ¡Verificación Completada Exitosamente!\n";
        echo "==========================================\n";
        echo "Todas las tablas están creadas correctamente.\n";
        echo "El sistema está listo para usar.\n\n";
        
        echo "🌐 Acceso al Sistema:\n";
        echo "- Panel de Administración: http://tu-dominio.com/DRSTUDIO/admin/\n";
        echo "- Sitio Web Público: http://tu-dominio.com/DRSTUDIO/\n";
        echo "- Usuario: admin\n";
        echo "- Contraseña: password\n";
        
    } else {
        echo "⚠️  Verificación Completada con Problemas\n";
        echo "========================================\n";
        echo "Algunas tablas no se crearon correctamente.\n";
        echo "Revisa los errores anteriores.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error de Verificación\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "\nVerificación completada el " . date('Y-m-d H:i:s') . "\n";
?>
