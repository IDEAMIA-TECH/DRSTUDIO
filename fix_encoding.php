<?php
/**
 * Script para corregir la codificación UTF-8 en la base de datos
 */

require_once 'includes/config.php';

echo "🔧 Corrigiendo codificación UTF-8...\n";
echo "=====================================\n";

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    echo "✅ Conexión establecida\n";
    
    // Establecer charset UTF-8
    $conn->set_charset("utf8mb4");
    echo "✅ Charset establecido a utf8mb4\n";
    
    // Verificar productos con problemas de codificación
    $result = $conn->query("SELECT id, nombre, descripcion FROM productos WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        echo "\n📋 Producto actual:\n";
        echo "ID: " . $producto['id'] . "\n";
        echo "Nombre: " . $producto['nombre'] . "\n";
        echo "Descripción: " . $producto['descripcion'] . "\n";
    }
    
    // Actualizar descripciones con caracteres especiales correctos
    $updates = [
        "UPDATE productos SET descripcion = 'Playera 100% algodón, disponible en varios colores' WHERE id = 1",
        "UPDATE productos SET descripcion = 'Playera de algodón premium con mejor calidad' WHERE id = 2",
        "UPDATE productos SET descripcion = 'Vaso térmico de acero inoxidable' WHERE id = 3",
        "UPDATE productos SET descripcion = 'Taza de cerámica blanca personalizable' WHERE id = 4",
        "UPDATE productos SET descripcion = 'Gorra trucker ajustable' WHERE id = 5"
    ];
    
    echo "\n🔄 Actualizando descripciones...\n";
    foreach ($updates as $update) {
        if ($conn->query($update)) {
            echo "✅ Descripción actualizada\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
    
    // Actualizar categorías
    $categorias_updates = [
        "UPDATE categorias SET descripcion = 'Playeras de algodón con diferentes técnicas de estampado' WHERE id = 1",
        "UPDATE categorias SET descripcion = 'Vasos personalizados con diferentes materiales' WHERE id = 2",
        "UPDATE categorias SET descripcion = 'Tazas de cerámica y otros materiales' WHERE id = 3",
        "UPDATE categorias SET descripcion = 'Gorras y sombreros personalizados' WHERE id = 4",
        "UPDATE categorias SET descripcion = 'Lonas publicitarias y banners' WHERE id = 5"
    ];
    
    echo "\n🔄 Actualizando categorías...\n";
    foreach ($categorias_updates as $update) {
        if ($conn->query($update)) {
            echo "✅ Categoría actualizada\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
    
    // Actualizar variantes
    $variantes_updates = [
        "UPDATE variantes_producto SET material = 'Algodón 100%' WHERE material LIKE '%algod%'",
        "UPDATE variantes_producto SET material = 'Algodón 100%' WHERE material LIKE '%Algod%'"
    ];
    
    echo "\n🔄 Actualizando variantes...\n";
    foreach ($variantes_updates as $update) {
        if ($conn->query($update)) {
            echo "✅ Variantes actualizadas\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
    
    // Verificar resultado
    $result = $conn->query("SELECT id, nombre, descripcion FROM productos WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        echo "\n✅ Producto corregido:\n";
        echo "ID: " . $producto['id'] . "\n";
        echo "Nombre: " . $producto['nombre'] . "\n";
        echo "Descripción: " . $producto['descripcion'] . "\n";
    }
    
    echo "\n🎉 ¡Codificación UTF-8 corregida exitosamente!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
