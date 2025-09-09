<?php
/**
 * Script para probar el nuevo sistema de rutas
 */

echo "🔍 Probando sistema de rutas centralizado...\n";
echo "==========================================\n";

try {
    // Probar desde admin/includes/
    echo "📁 Probando desde admin/includes/:\n";
    chdir('admin/includes');
    
    if (file_exists('paths.php')) {
        echo "✅ paths.php existe\n";
        
        require_once 'paths.php';
        
        if (defined('PROJECT_ROOT')) {
            echo "✅ PROJECT_ROOT definido: " . PROJECT_ROOT . "\n";
        } else {
            echo "❌ PROJECT_ROOT no definido\n";
        }
        
        if (defined('CONFIG_PATH')) {
            echo "✅ CONFIG_PATH definido: " . CONFIG_PATH . "\n";
            echo "✅ CONFIG_PATH existe: " . (file_exists(CONFIG_PATH) ? 'SÍ' : 'NO') . "\n";
        } else {
            echo "❌ CONFIG_PATH no definido\n";
        }
        
        if (defined('AUTH_PATH')) {
            echo "✅ AUTH_PATH definido: " . AUTH_PATH . "\n";
            echo "✅ AUTH_PATH existe: " . (file_exists(AUTH_PATH) ? 'SÍ' : 'NO') . "\n";
        } else {
            echo "❌ AUTH_PATH no definido\n";
        }
        
        // Probar conexión a base de datos
        if (isset($conn) && $conn) {
            echo "✅ Conexión a base de datos establecida\n";
        } else {
            echo "❌ Error de conexión a base de datos\n";
        }
        
        // Probar funciones de autenticación
        if (function_exists('isLoggedIn')) {
            echo "✅ Función isLoggedIn disponible\n";
        } else {
            echo "❌ Función isLoggedIn NO disponible\n";
        }
        
    } else {
        echo "❌ paths.php no existe\n";
    }
    
    echo "\n📁 Probando desde admin/:\n";
    chdir('..');
    
    if (file_exists('includes/paths.php')) {
        echo "✅ includes/paths.php existe\n";
        
        require_once 'includes/paths.php';
        
        if (defined('PROJECT_ROOT')) {
            echo "✅ PROJECT_ROOT definido: " . PROJECT_ROOT . "\n";
        }
        
        if (defined('CONFIG_PATH') && file_exists(CONFIG_PATH)) {
            echo "✅ CONFIG_PATH funciona desde admin/\n";
        }
        
    } else {
        echo "❌ includes/paths.php no existe\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Volver al directorio original
    chdir('../..');
}
?>
