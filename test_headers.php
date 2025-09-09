<?php
/**
 * Script para probar si hay salida antes de los headers
 */

echo "🔍 Probando salida antes de headers...\n";
echo "=====================================\n";

// Verificar si ya hay salida
if (ob_get_level()) {
    echo "❌ Ya hay salida en el buffer\n";
    $output = ob_get_contents();
    echo "Contenido del buffer: " . strlen($output) . " caracteres\n";
    if (strlen($output) > 0) {
        echo "Primeros 100 caracteres: " . substr($output, 0, 100) . "\n";
    }
} else {
    echo "✅ No hay salida en el buffer\n";
}

// Probar incluir header.php
echo "\n📋 Probando inclusión de header.php:\n";

ob_start();

try {
    // Simular estar en admin/
    chdir('admin');
    
    // Incluir header.php
    include 'includes/header.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "✅ header.php incluido correctamente\n";
    echo "Salida generada: " . strlen($output) . " caracteres\n";
    
    if (strlen($output) > 0) {
        echo "Primeros 200 caracteres:\n";
        echo substr($output, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error incluyendo header.php: " . $e->getMessage() . "\n";
} finally {
    // Volver al directorio original
    chdir('..');
}

echo "\n🎉 Prueba completada\n";
?>
