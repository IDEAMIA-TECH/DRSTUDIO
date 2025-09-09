<?php
/**
 * Script para probar la generación de SKU de manera completa
 */

echo "🔍 Probando generación de SKU completa...\n";
echo "========================================\n";

try {
    // Incluir config y funciones
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    
    echo "✅ Archivos incluidos correctamente\n";
    
    // Probar conexión
    if ($conn && !$conn->connect_error) {
        echo "✅ Conexión a base de datos establecida\n";
    } else {
        echo "❌ Error de conexión a base de datos\n";
        exit;
    }
    
    // Función para generar SKU (copiada del archivo AJAX)
    function generateSKU($categoria_id = null) {
        global $conn;
        
        // Obtener prefijo de categoría
        $prefijo = 'PRD';
        if ($categoria_id) {
            $cat = getRecord('categorias', $categoria_id);
            if ($cat) {
                $prefijo = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cat['nombre']), 0, 3));
                if (strlen($prefijo) < 3) {
                    $prefijo = 'PRD';
                }
            }
        }
        
        // Obtener el siguiente número
        $sql = "SELECT COUNT(*) as total FROM productos WHERE sku LIKE ?";
        $stmt = $conn->prepare($sql);
        $likePattern = $prefijo . '%';
        $stmt->bind_param("s", $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        // Generar SKU con formato: PREFIJO-YYYY-NNNN
        $year = date('Y');
        $numero = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefijo . '-' . $year . '-' . $numero;
    }
    
    // Probar generación de SKU sin categoría
    echo "\n📋 Probando SKU sin categoría:\n";
    $sku1 = generateSKU();
    echo "SKU generado: $sku1\n";
    
    // Obtener categorías para probar
    echo "\n📋 Obteniendo categorías disponibles:\n";
    $categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');
    if (!empty($categorias)) {
        echo "✅ Categorías encontradas: " . count($categorias) . "\n";
        
        foreach ($categorias as $categoria) {
            echo "\n📋 Probando SKU para categoría: {$categoria['nombre']}\n";
            $sku = generateSKU($categoria['id']);
            echo "SKU generado: $sku\n";
        }
    } else {
        echo "❌ No se encontraron categorías\n";
    }
    
    // Probar el archivo AJAX directamente
    echo "\n📋 Probando archivo AJAX directamente:\n";
    
    // Simular una petición POST
    $_POST['categoria_id'] = '1';
    
    // Capturar la salida
    ob_start();
    
    // Incluir el archivo AJAX
    include 'ajax/generate_sku.php';
    
    $output = ob_get_clean();
    
    echo "Salida del archivo AJAX: $output\n";
    
    // Decodificar JSON
    $data = json_decode($output, true);
    
    if ($data) {
        if (isset($data['success']) && $data['success']) {
            echo "✅ SKU generado correctamente via AJAX: " . $data['sku'] . "\n";
        } else {
            echo "❌ Error en la generación via AJAX: " . ($data['message'] ?? 'Error desconocido') . "\n";
        }
    } else {
        echo "❌ Error decodificando JSON del archivo AJAX\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
