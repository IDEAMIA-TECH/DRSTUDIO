<?php
/**
 * Script para probar las funciones corregidas
 */

echo "🔍 Probando funciones corregidas...\n";
echo "===================================\n";

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
    
    // Probar función createRecord con diferentes tipos de datos
    echo "\n📋 Probando createRecord:\n";
    
    $testData = [
        'categoria_id' => 1,
        'sku' => 'TEST-001',
        'nombre' => 'Producto de Prueba',
        'descripcion' => 'Descripción de prueba',
        'precio_venta' => 99.99,
        'costo_fabricacion' => 50.00,
        'activo' => 1
    ];
    
    echo "Datos de prueba: " . json_encode($testData, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Verificar que la tabla productos existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'productos'");
    if ($checkTable->num_rows > 0) {
        echo "✅ Tabla 'productos' existe\n";
        
        // Probar createRecord
        $result = createRecord('productos', $testData);
        if ($result) {
            echo "✅ createRecord ejecutado correctamente\n";
            
            // Obtener el ID del producto creado
            $lastId = $conn->insert_id;
            echo "✅ Producto creado con ID: $lastId\n";
            
            // Probar getRecord
            echo "\n📋 Probando getRecord:\n";
            $product = getRecord('productos', $lastId);
            if ($product) {
                echo "✅ getRecord ejecutado correctamente\n";
                echo "Producto obtenido: " . $product['nombre'] . "\n";
                
                // Probar updateRecord
                echo "\n📋 Probando updateRecord:\n";
                $updateData = [
                    'nombre' => 'Producto Actualizado',
                    'precio_venta' => 149.99
                ];
                
                $updateResult = updateRecord('productos', $lastId, $updateData);
                if ($updateResult) {
                    echo "✅ updateRecord ejecutado correctamente\n";
                    
                    // Verificar la actualización
                    $updatedProduct = getRecord('productos', $lastId);
                    if ($updatedProduct && $updatedProduct['nombre'] === 'Producto Actualizado') {
                        echo "✅ Actualización verificada correctamente\n";
                    } else {
                        echo "❌ Error en la verificación de actualización\n";
                    }
                } else {
                    echo "❌ Error en updateRecord\n";
                }
                
                // Limpiar: eliminar el producto de prueba
                echo "\n🧹 Limpiando datos de prueba:\n";
                $deleteResult = deleteRecord('productos', $lastId);
                if ($deleteResult) {
                    echo "✅ Producto de prueba eliminado\n";
                } else {
                    echo "❌ Error al eliminar producto de prueba\n";
                }
                
            } else {
                echo "❌ Error en getRecord\n";
            }
            
        } else {
            echo "❌ Error en createRecord\n";
            echo "Error MySQL: " . $conn->error . "\n";
        }
        
    } else {
        echo "❌ Tabla 'productos' no existe\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
