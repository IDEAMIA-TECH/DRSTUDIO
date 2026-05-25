<?php
/**
 * Script de Instalación CLI - DR Studio
 * 
 * Este script se ejecuta desde la línea de comandos para crear
 * las tablas de la base de datos automáticamente.
 */

require_once __DIR__ . '/includes/install_db_config.php';

echo "🚀 Instalando DR Studio...\n";
echo "================================\n";

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
    
    echo "✅ Archivo schema.sql leído correctamente\n";
    
    // Dividir el script en comandos individuales
    $commands = explode(';', $schema);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        
        // Saltar comandos vacíos o comentarios
        if (empty($command) || strpos($command, '--') === 0) {
            continue;
        }
        
        try {
            if ($conn->query($command)) {
                $successCount++;
                
                // Mostrar progreso para comandos importantes
                if (strpos($command, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?(\w+)/', $command, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "✅ Tabla '$tableName' creada\n";
                } elseif (strpos($command, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO.*?(\w+)/', $command, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "✅ Datos insertados en '$tableName'\n";
                }
            } else {
                $errorCount++;
                echo "❌ Error en comando: " . substr($command, 0, 50) . "...\n";
                echo "   Error: " . $conn->error . "\n";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Excepción en comando: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n================================\n";
    echo "📊 Resumen de la Instalación\n";
    echo "================================\n";
    echo "Comandos ejecutados exitosamente: $successCount\n";
    echo "Errores encontrados: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 ¡Instalación Completada Exitosamente!\n";
        echo "========================================\n";
        echo "El sistema DR Studio ha sido instalado correctamente.\n\n";
        echo "Credenciales de acceso:\n";
        echo "- Usuario: admin\n";
        echo "- Contraseña: password\n\n";
        echo "Enlaces importantes:\n";
        echo "- Panel de Administración: http://tu-dominio.com/DRSTUDIO/admin/\n";
        echo "- Sitio Web Público: http://tu-dominio.com/DRSTUDIO/\n\n";
        
        // Crear archivo de instalación completada
        file_put_contents('INSTALLED', date('Y-m-d H:i:s'));
        echo "✅ Archivo de instalación creado\n";
        
        // Crear directorios necesarios
        echo "\n📁 Creando directorios necesarios...\n";
        $directories = [
            'uploads/categorias',
            'uploads/productos', 
            'uploads/banners',
            'uploads/galeria',
            'images'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "✅ Directorio '$dir' creado\n";
            } else {
                echo "ℹ️  Directorio '$dir' ya existe\n";
            }
        }
        
        echo "\n✨ ¡El sistema está listo para usar!\n";
        
    } else {
        echo "\n⚠️  Instalación Completada con Errores\n";
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

echo "\n📋 Próximos Pasos:\n";
echo "1. Verificar que las carpetas de uploads tengan permisos de escritura\n";
echo "2. Configurar el servidor web para que apunte al directorio del proyecto\n";
echo "3. Probar el acceso al panel de administración\n";
echo "4. Personalizar la información de la empresa\n";
echo "5. Subir imágenes de productos y categorías\n\n";

echo "Instalación completada el " . date('Y-m-d H:i:s') . "\n";
?>
