<?php
/**
 * Script completo para corregir la codificación UTF-8 en la base de datos
 */

require_once 'includes/config.php';

echo "🔧 Corrigiendo codificación UTF-8 completa...\n";
echo "============================================\n";

try {
    // Verificar conexión
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    echo "✅ Conexión establecida\n";
    
    // Establecer charset UTF-8
    $conn->set_charset("utf8mb4");
    echo "✅ Charset establecido a utf8mb4\n";
    
    // Función para corregir caracteres especiales
    function fixEncoding($text) {
        // Mapeo de caracteres mal codificados
        $replacements = [
            'Ã¡' => 'á',
            'Ã©' => 'é', 
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ãº' => 'ú',
            'Ã±' => 'ñ',
            'Ã' => 'Á',
            'Ã‰' => 'É',
            'Ã' => 'Í',
            'Ã"' => 'Ó',
            'Ãš' => 'Ú',
            'Ã' => 'Ñ',
            'Ã¼' => 'ü',
            'Ã¤' => 'ä',
            'Ã¶' => 'ö',
            'ÃŸ' => 'ß',
            'Ã§' => 'ç',
            'Ã¢' => 'â',
            'Ãª' => 'ê',
            'Ã®' => 'î',
            'Ã´' => 'ô',
            'Ã»' => 'û',
            'Ã¨' => 'è',
            'Ã¬' => 'ì',
            'Ã²' => 'ò',
            'Ã¹' => 'ù'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    // Corregir productos
    echo "\n🔄 Corrigiendo productos...\n";
    $result = $conn->query("SELECT id, nombre, descripcion FROM productos");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $nombre_corregido = fixEncoding($row['nombre']);
            $descripcion_corregida = fixEncoding($row['descripcion']);
            
            $update = "UPDATE productos SET nombre = ?, descripcion = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("ssi", $nombre_corregido, $descripcion_corregida, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Producto ID {$row['id']} corregido\n";
            } else {
                echo "❌ Error en producto ID {$row['id']}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
    
    // Corregir categorías
    echo "\n🔄 Corrigiendo categorías...\n";
    $result = $conn->query("SELECT id, nombre, descripcion FROM categorias");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $nombre_corregido = fixEncoding($row['nombre']);
            $descripcion_corregida = fixEncoding($row['descripcion']);
            
            $update = "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("ssi", $nombre_corregido, $descripcion_corregida, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Categoría ID {$row['id']} corregida\n";
            } else {
                echo "❌ Error en categoría ID {$row['id']}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
    
    // Corregir variantes
    echo "\n🔄 Corrigiendo variantes...\n";
    $result = $conn->query("SELECT id, material FROM variantes_producto");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $material_corregido = fixEncoding($row['material']);
            
            $update = "UPDATE variantes_producto SET material = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("si", $material_corregido, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Variante ID {$row['id']} corregida\n";
            } else {
                echo "❌ Error en variante ID {$row['id']}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
    
    // Corregir testimonios
    echo "\n🔄 Corrigiendo testimonios...\n";
    $result = $conn->query("SELECT id, cliente_nombre, empresa, testimonio FROM testimonios");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cliente_corregido = fixEncoding($row['cliente_nombre']);
            $empresa_corregida = fixEncoding($row['empresa']);
            $testimonio_corregido = fixEncoding($row['testimonio']);
            
            $update = "UPDATE testimonios SET cliente_nombre = ?, empresa = ?, testimonio = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("sssi", $cliente_corregido, $empresa_corregida, $testimonio_corregido, $row['id']);
            
            if ($stmt->execute()) {
                echo "✅ Testimonio ID {$row['id']} corregido\n";
            } else {
                echo "❌ Error en testimonio ID {$row['id']}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
    
    // Verificar resultado
    echo "\n📋 Verificando corrección...\n";
    $result = $conn->query("SELECT id, nombre, descripcion FROM productos WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        echo "✅ Producto corregido:\n";
        echo "ID: " . $producto['id'] . "\n";
        echo "Nombre: " . $producto['nombre'] . "\n";
        echo "Descripción: " . $producto['descripcion'] . "\n";
    }
    
    echo "\n🎉 ¡Codificación UTF-8 corregida completamente!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
