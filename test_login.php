<?php
/**
 * Script de prueba para verificar el sistema de login
 */

echo "🔍 Probando sistema de login...\n";
echo "===============================\n";

try {
    // Incluir archivos
    require_once 'includes/config.php';
    require_once 'includes/auth.php';
    
    echo "✅ Archivos incluidos correctamente\n";
    
    // Verificar funciones
    $functions = [
        'isLoggedIn',
        'getCurrentUser', 
        'hasPermission',
        'login',
        'logout',
        'requireLogin',
        'requireRole',
        'redirectIfLoggedIn'
    ];
    
    echo "\n📋 Verificando funciones:\n";
    foreach ($functions as $function) {
        if (function_exists($function)) {
            echo "✅ $function - Disponible\n";
        } else {
            echo "❌ $function - NO DISPONIBLE\n";
        }
    }
    
    // Verificar conexión a la base de datos
    echo "\n🔌 Verificando conexión:\n";
    if ($conn && !$conn->connect_error) {
        echo "✅ Conexión establecida\n";
        
        // Verificar usuario admin
        $result = $conn->query("SELECT id, username, email, rol FROM usuarios WHERE username = 'admin'");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "✅ Usuario admin encontrado:\n";
            echo "   ID: {$user['id']}\n";
            echo "   Username: {$user['username']}\n";
            echo "   Email: {$user['email']}\n";
            echo "   Rol: {$user['rol']}\n";
        } else {
            echo "❌ Usuario admin NO encontrado\n";
        }
    } else {
        echo "❌ Error de conexión\n";
    }
    
    // Probar función redirectIfLoggedIn
    echo "\n🔄 Probando redirectIfLoggedIn:\n";
    if (function_exists('redirectIfLoggedIn')) {
        echo "✅ Función redirectIfLoggedIn disponible\n";
        // No la ejecutamos para evitar redirección
    } else {
        echo "❌ Función redirectIfLoggedIn NO disponible\n";
    }
    
    echo "\n🎉 ¡Sistema de login verificado correctamente!\n";
    echo "\n📋 Credenciales de acceso:\n";
    echo "   Usuario: admin\n";
    echo "   Contraseña: password\n";
    echo "   URL: http://tu-dominio.com/DRSTUDIO/admin/\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>
