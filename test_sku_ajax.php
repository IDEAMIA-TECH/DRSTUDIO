<?php
/**
 * Script para probar la generación de SKU via AJAX
 */

echo "🔍 Probando generación de SKU via AJAX...\n";
echo "=========================================\n";

try {
    // Simular una petición POST
    $_POST['categoria_id'] = '1';
    
    // Capturar la salida
    ob_start();
    
    // Incluir el archivo AJAX
    include 'ajax/generate_sku.php';
    
    $output = ob_get_clean();
    
    echo "📋 Salida del archivo AJAX:\n";
    echo $output . "\n";
    
    // Decodificar JSON
    $data = json_decode($output, true);
    
    if ($data) {
        echo "\n📋 Datos decodificados:\n";
        print_r($data);
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ SKU generado correctamente: " . $data['sku'] . "\n";
        } else {
            echo "❌ Error en la generación: " . ($data['message'] ?? 'Error desconocido') . "\n";
        }
    } else {
        echo "❌ Error decodificando JSON\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
