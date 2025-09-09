<?php
/**
 * Script de Instalación - DR Studio
 * 
 * Este script crea automáticamente las tablas de la base de datos
 * y configura el sistema para su primer uso.
 */

// Configuración de la base de datos
$host = '216.18.195.84';
$username = 'dtstudio_main';
$password = 'm&9!9ejG!5D6A$p&';
$database = 'dtstudio_main';

echo "<h1>🚀 Instalación de DR Studio</h1>";
echo "<p>Configurando la base de datos y el sistema...</p>";

try {
    // Conectar a MySQL (sin especificar base de datos)
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "✅ Conexión a MySQL establecida<br>";
    
    // Leer el archivo schema.sql
    $schema = file_get_contents('database/schema.sql');
    
    if (!$schema) {
        throw new Exception("No se pudo leer el archivo database/schema.sql");
    }
    
    echo "✅ Archivo schema.sql leído correctamente<br>";
    
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
                    echo "✅ Tabla '$tableName' creada<br>";
                } elseif (strpos($command, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO.*?(\w+)/', $command, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "✅ Datos insertados en '$tableName'<br>";
                }
            } else {
                $errorCount++;
                echo "❌ Error en comando: " . substr($command, 0, 50) . "...<br>";
                echo "   Error: " . $conn->error . "<br>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Excepción en comando: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h2>📊 Resumen de la Instalación</h2>";
    echo "<p><strong>Comandos ejecutados exitosamente:</strong> $successCount</p>";
    echo "<p><strong>Errores encontrados:</strong> $errorCount</p>";
    
    if ($errorCount === 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>🎉 ¡Instalación Completada Exitosamente!</h3>";
        echo "<p>El sistema DR Studio ha sido instalado correctamente.</p>";
        echo "<p><strong>Credenciales de acceso:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Usuario:</strong> admin</li>";
        echo "<li><strong>Contraseña:</strong> password</li>";
        echo "</ul>";
        echo "<p><a href='admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Acceder al Panel de Administración</a></p>";
        echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Ver Sitio Web Público</a></p>";
        echo "</div>";
        
        // Crear archivo de instalación completada
        file_put_contents('INSTALLED', date('Y-m-d H:i:s'));
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>⚠️ Instalación Completada con Errores</h3>";
        echo "<p>La instalación se completó pero se encontraron algunos errores. Revisa los mensajes anteriores.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Error de Instalación</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifica que las credenciales de la base de datos sean correctas.</p>";
    echo "</div>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "<hr>";
echo "<h3>📋 Próximos Pasos</h3>";
echo "<ol>";
echo "<li>Verificar que las carpetas de uploads tengan permisos de escritura</li>";
echo "<li>Configurar el servidor web para que apunte al directorio del proyecto</li>";
echo "<li>Probar el acceso al panel de administración</li>";
echo "<li>Personalizar la información de la empresa</li>";
echo "<li>Subir imágenes de productos y categorías</li>";
echo "</ol>";

echo "<p><small>Instalación completada el " . date('Y-m-d H:i:s') . "</small></p>";
?>
