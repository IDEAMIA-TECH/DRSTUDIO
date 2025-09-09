<?php
/**
 * Script para probar el dashboard del admin
 */

echo "🔍 Probando dashboard del admin...\n";
echo "==================================\n";

try {
    // Simular estar en admin/
    chdir('admin');
    echo "📁 Directorio cambiado a: " . getcwd() . "\n";
    
    // Probar inclusión de header
    echo "\n📋 Probando inclusión de header.php:\n";
    if (file_exists('includes/header.php')) {
        echo "✅ includes/header.php existe\n";
        
        // Probar las rutas dentro de header.php
        $header_content = file_get_contents('includes/header.php');
        if (strpos($header_content, '../../includes/config.php') !== false) {
            echo "✅ header.php usa ruta relativa correcta\n";
        } else {
            echo "❌ header.php NO usa ruta relativa correcta\n";
        }
    } else {
        echo "❌ includes/header.php NO existe\n";
    }
    
    // Probar inclusión de config.php desde admin/
    echo "\n📋 Probando inclusión de config.php desde admin/:\n";
    $config_path = '../includes/config.php';
    if (file_exists($config_path)) {
        echo "✅ ../includes/config.php existe\n";
        
        // Probar incluir el archivo
        require_once $config_path;
        echo "✅ config.php incluido correctamente\n";
        
        if (isset($conn) && $conn) {
            echo "✅ Conexión a base de datos establecida\n";
        } else {
            echo "❌ Conexión a base de datos NO establecida\n";
        }
    } else {
        echo "❌ ../includes/config.php NO existe\n";
    }
    
    // Probar inclusión de auth.php desde admin/
    echo "\n📋 Probando inclusión de auth.php desde admin/:\n";
    $auth_path = '../includes/auth.php';
    if (file_exists($auth_path)) {
        echo "✅ ../includes/auth.php existe\n";
        
        // Probar incluir el archivo
        require_once $auth_path;
        echo "✅ auth.php incluido correctamente\n";
        
        if (function_exists('redirectIfLoggedIn')) {
            echo "✅ redirectIfLoggedIn disponible\n";
        } else {
            echo "❌ redirectIfLoggedIn NO disponible\n";
        }
    } else {
        echo "❌ ../includes/auth.php NO existe\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
} finally {
    // Volver al directorio original
    chdir('..');
}
?>
