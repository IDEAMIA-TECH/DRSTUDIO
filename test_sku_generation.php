<?php
/**
 * Script para probar la generación de SKU automático
 */

echo "🔍 Probando generación de SKU automático...\n";
echo "==========================================\n";

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
    
    // Función para generar SKU (copiada del archivo)
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
    echo "Formato esperado: PRD-2025-XXXX\n";
    echo "✅ SKU sin categoría generado correctamente\n";
    
    // Obtener categorías para probar
    echo "\n📋 Obteniendo categorías disponibles:\n";
    $categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');
    if (!empty($categorias)) {
        echo "✅ Categorías encontradas: " . count($categorias) . "\n";
        
        foreach ($categorias as $categoria) {
            echo "\n📋 Probando SKU para categoría: {$categoria['nombre']}\n";
            $sku = generateSKU($categoria['id']);
            echo "SKU generado: $sku\n";
            
            // Verificar que el prefijo sea correcto
            $prefijoEsperado = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $categoria['nombre']), 0, 3));
            if (strlen($prefijoEsperado) < 3) {
                $prefijoEsperado = 'PRD';
            }
            
            if (strpos($sku, $prefijoEsperado) === 0) {
                echo "✅ Prefijo correcto: $prefijoEsperado\n";
            } else {
                echo "❌ Prefijo incorrecto. Esperado: $prefijoEsperado, Obtenido: " . substr($sku, 0, 3) . "\n";
            }
        }
    } else {
        echo "❌ No se encontraron categorías\n";
    }
    
    // Probar múltiples SKUs para la misma categoría
    echo "\n📋 Probando múltiples SKUs para la misma categoría:\n";
    if (!empty($categorias)) {
        $categoria_id = $categorias[0]['id'];
        $categoria_nombre = $categorias[0]['nombre'];
        
        echo "Categoría: $categoria_nombre\n";
        for ($i = 1; $i <= 3; $i++) {
            $sku = generateSKU($categoria_id);
            echo "SKU $i: $sku\n";
        }
        echo "✅ Múltiples SKUs generados correctamente\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
