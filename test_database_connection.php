<?php
/**
 * Script de prueba de conexión a la base de datos
 * DT Studio - Sistema de Gestión de Promocionales
 */

require_once 'config/database.php';

echo "<h2>Prueba de Conexión a la Base de Datos - DT Studio</h2>\n";

try {
    // Crear instancia de la base de datos
    $db = new Database();
    
    echo "<p style='color: green;'>✅ Conexión a la base de datos establecida correctamente</p>\n";
    
    // Probar la conexión
    if ($db->testConnection()) {
        echo "<p style='color: green;'>✅ Conexión verificada exitosamente</p>\n";
        
        // Obtener información de la base de datos
        $stmt = $db->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
        $info = $stmt->fetch();
        
        echo "<h3>Información de la Base de Datos:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Base de datos actual:</strong> " . $info['current_db'] . "</li>\n";
        echo "<li><strong>Versión de MySQL:</strong> " . $info['mysql_version'] . "</li>\n";
        echo "</ul>\n";
        
        // Verificar tablas existentes
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tablas existentes (" . count($tables) . "):</h3>\n";
        if (count($tables) > 0) {
            echo "<ul>\n";
            foreach ($tables as $table) {
                echo "<li>" . $table . "</li>\n";
            }
            echo "</ul>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontraron tablas en la base de datos</p>\n";
        }
        
        // Probar una consulta simple
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM products");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Tabla 'products' accesible - Total de registros: " . $result['total'] . "</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al acceder a la tabla 'products': " . $e->getMessage() . "</p>\n";
        }
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM customers");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Tabla 'customers' accesible - Total de registros: " . $result['total'] . "</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al acceder a la tabla 'customers': " . $e->getMessage() . "</p>\n";
        }
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM quotations");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Tabla 'quotations' accesible - Total de registros: " . $result['total'] . "</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al acceder a la tabla 'quotations': " . $e->getMessage() . "</p>\n";
        }
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Tabla 'orders' accesible - Total de registros: " . $result['total'] . "</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al acceder a la tabla 'orders': " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error al verificar la conexión</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Fecha y hora:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><strong>Servidor:</strong> " . $_SERVER['SERVER_NAME'] . "</p>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
?>
