<?php
/**
 * Script de Instalación Detallado - DR Studio
 * 
 * Este script ejecuta cada comando SQL individualmente para asegurar
 * que todas las tablas se creen correctamente.
 */

// Configuración de la base de datos
require_once __DIR__ . '/includes/install_db_config.php';

echo "🚀 Instalación Detallada de DR Studio...\n";
echo "========================================\n";

try {
    $db = installDbCredentials();
    $host = $db['host'];
    $username = $db['user'];
    $password = $db['pass'];
    $database = $db['name'];

    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "✅ Conexión a MySQL establecida\n";
    
    // Leer el archivo schema.sql
    $schema = file_get_contents('database/schema.sql');
    
    if (!$schema) {
        throw new Exception("No se pudo leer el archivo database/schema.sql");
    }
    
    echo "✅ Archivo schema.sql leído correctamente\n\n";
    
    // Dividir el script en comandos individuales
    $commands = explode(';', $schema);
    
    $successCount = 0;
    $errorCount = 0;
    $commandNumber = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        $commandNumber++;
        
        // Saltar comandos vacíos o comentarios
        if (empty($command) || strpos($command, '--') === 0) {
            continue;
        }
        
        echo "Ejecutando comando $commandNumber: " . substr($command, 0, 50) . "...\n";
        
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
                echo "   Comando: " . substr($command, 0, 100) . "...\n";
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
    echo "Total de comandos procesados: $commandNumber\n\n";
    
    if ($errorCount === 0) {
        echo "🎉 ¡Instalación Completada Exitosamente!\n";
        echo "========================================\n";
        echo "El sistema DR Studio ha sido instalado correctamente.\n\n";
        
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
        echo "Revisa los mensajes anteriores para más detalles.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error de Instalación\n";
    echo "=======================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Verifica que las credenciales de la base de datos sean correctas.\n";
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "\nInstalación completada el " . date('Y-m-d H:i:s') . "\n";
?>
