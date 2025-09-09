<?php
/**
 * Script para probar la carga de variantes via AJAX
 */

echo "🔍 Probando carga de variantes via AJAX...\n";
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
    
    // Obtener productos con variantes
    echo "\n📋 Obteniendo productos con variantes:\n";
    $productos = readRecords('productos', ['activo = 1'], null, 'nombre ASC');
    
    if (!empty($productos)) {
        foreach ($productos as $producto) {
            echo "\nProducto: {$producto['nombre']} (ID: {$producto['id']})\n";
            
            // Obtener variantes del producto
            $variantes = readRecords('variantes_producto', ["producto_id = {$producto['id']}", "activo = 1"], null, 'id ASC');
            
            if (!empty($variantes)) {
                echo "  Variantes encontradas: " . count($variantes) . "\n";
                foreach ($variantes as $variante) {
                    echo "    - ID: {$variante['id']}, Talla: {$variante['talla']}, Color: {$variante['color']}, Material: {$variante['material']}, Precio Extra: {$variante['precio_extra']}\n";
                }
            } else {
                echo "  ❌ No hay variantes para este producto\n";
            }
        }
    } else {
        echo "❌ No se encontraron productos\n";
    }
    
    // Probar el archivo AJAX directamente
    echo "\n📋 Probando archivo AJAX de variantes:\n";
    
    // Simular una petición POST
    $_POST['action'] = 'get_variantes';
    $_POST['producto_id'] = '1'; // Usar el primer producto
    
    // Capturar la salida
    ob_start();
    
    // Incluir el archivo AJAX
    include 'ajax/productos.php';
    
    $output = ob_get_clean();
    
    echo "Salida del archivo AJAX: $output\n";
    
    // Decodificar JSON
    $data = json_decode($output, true);
    
    if ($data) {
        if (isset($data['success']) && $data['success']) {
            echo "✅ Variantes obtenidas correctamente via AJAX\n";
            echo "Cantidad de variantes: " . count($data['data']) . "\n";
            foreach ($data['data'] as $variante) {
                echo "  - ID: {$variante['id']}, Talla: {$variante['talla']}, Color: {$variante['color']}, Material: {$variante['material']}\n";
            }
        } else {
            echo "❌ Error en la obtención de variantes via AJAX: " . ($data['message'] ?? 'Error desconocido') . "\n";
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
