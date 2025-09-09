<?php
/**
 * Script de verificación UTF-8
 */

require_once 'includes/config.php';

echo "🔍 Verificando codificación UTF-8...\n";
echo "====================================\n";

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    echo "✅ Conexión establecida\n";
    
    // Establecer charset UTF-8
    $conn->set_charset("utf8mb4");
    echo "✅ Charset establecido a utf8mb4\n";
    
    // Verificar productos
    echo "\n📋 Verificando productos:\n";
    $result = $conn->query("SELECT id, nombre, descripcion FROM productos ORDER BY id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID {$row['id']}: {$row['nombre']}\n";
            echo "  Descripción: {$row['descripcion']}\n\n";
        }
    }
    
    // Verificar categorías
    echo "📋 Verificando categorías:\n";
    $result = $conn->query("SELECT id, nombre, descripcion FROM categorias ORDER BY id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID {$row['id']}: {$row['nombre']}\n";
            echo "  Descripción: {$row['descripcion']}\n\n";
        }
    }
    
    // Verificar variantes
    echo "📋 Verificando variantes:\n";
    $result = $conn->query("SELECT id, material FROM variantes_producto ORDER BY id LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "ID {$row['id']}: {$row['material']}\n";
        }
    }
    
    echo "\n🎉 ¡Verificación completada!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
