<?php
// Simple test to check the actual data being retrieved
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = 15;

echo "=== TESTING COTIZACIONES VIEW DATA ===\n\n";

// Get cotización data
$sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.empresa as cliente_empresa, cl.email as cliente_email, cl.telefono as cliente_telefono, u.username as creado_por
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id 
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = $id";
$result = $conn->query($sql);
$cotizacion = $result ? $result->fetch_assoc() : null;

if (!$cotizacion) {
    echo "ERROR: Cotización not found\n";
    exit;
}

echo "Cotización: " . $cotizacion['numero_cotizacion'] . "\n\n";

// Get items
$items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');

echo "Items found: " . count($items) . "\n\n";

// Process items exactly like in cotizaciones_view.php
$subtotal_calculado = 0;
foreach ($items as &$item) {
    echo "--- Processing Item ID: " . $item['id'] . " ---\n";
    echo "Product ID: " . $item['producto_id'] . "\n";
    echo "Variant ID: " . $item['variante_id'] . "\n";
    
    $producto = getRecord('productos', $item['producto_id']);
    $item['producto'] = $producto;
    echo "Product: " . $producto['nombre'] . "\n";
    
    if ($item['variante_id']) {
        $variante = getRecord('variantes_producto', $item['variante_id']);
        $item['variante'] = $variante;
        
        if ($variante) {
            echo "Variant found:\n";
            echo "  - ID: " . $variante['id'] . "\n";
            echo "  - Talla: " . ($variante['talla'] ?? 'N/A') . "\n";
            echo "  - Color: " . ($variante['color'] ?? 'N/A') . "\n";
            echo "  - Material: " . ($variante['material'] ?? 'N/A') . "\n";
            echo "  - Stock: " . ($variante['stock'] ?? 'N/A') . "\n";
            echo "  - Precio Extra: " . ($variante['precio_extra'] ?? 'N/A') . "\n";
            echo "  - Activo: " . ($variante['activo'] ? 'Sí' : 'No') . "\n";
            
            // Test the display logic
            $variante_parts = array_filter([
                $variante['talla'] ?? '',
                $variante['color'] ?? '',
                $variante['material'] ?? ''
            ]);
            $variante_display = implode(' - ', $variante_parts);
            echo "  - Display: " . $variante_display . "\n";
        } else {
            echo "ERROR: Variant not found!\n";
        }
    } else {
        echo "No variant\n";
    }
    
    echo "\n";
    
    $subtotal_calculado += $item['subtotal'];
}

echo "=== END TEST ===\n";
?>
