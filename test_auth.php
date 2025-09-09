<?php
/**
 * Script de prueba para verificar las funciones de autenticación
 */

echo "🔍 Probando funciones de autenticación...\n";
echo "========================================\n";

try {
    // Incluir archivos
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    
    echo "✅ Archivos incluidos correctamente\n";
    
    // Verificar que las funciones estén definidas
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
    echo "\n🔌 Verificando conexión a la base de datos:\n";
    if ($conn && !$conn->connect_error) {
        echo "✅ Conexión establecida\n";
        
        // Verificar tabla de usuarios
        $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Tabla 'usuarios' existe\n";
            
            // Verificar usuario admin
            $result = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE username = 'admin'");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "✅ Usuario admin encontrado: {$row['count']} registro(s)\n";
            }
        } else {
            echo "❌ Tabla 'usuarios' NO existe\n";
        }
    } else {
        echo "❌ Error de conexión: " . ($conn->connect_error ?? 'Desconocido') . "\n";
    }
    
    echo "\n🎉 ¡Prueba completada!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
