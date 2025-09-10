<?php
// Debug script to check variants for cotización ID 15
require_once 'includes/config.php';
require_once 'includes/functions.php';

$cotizacion_id = 15;

echo "<h2>Debug: Variantes para Cotización ID $cotizacion_id</h2>";

// Get cotización items
$items = readRecords('cotizacion_items', ["cotizacion_id = $cotizacion_id"], null, 'id ASC');

echo "<h3>Items de la cotización:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Item ID</th><th>Producto ID</th><th>Variante ID</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>";

foreach ($items as $item) {
    echo "<tr>";
    echo "<td>" . $item['id'] . "</td>";
    echo "<td>" . $item['producto_id'] . "</td>";
    echo "<td>" . $item['variante_id'] . "</td>";
    echo "<td>" . $item['cantidad'] . "</td>";
    echo "<td>" . $item['precio_unitario'] . "</td>";
    echo "<td>" . $item['subtotal'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Detalles de productos y variantes:</h3>";

foreach ($items as $item) {
    echo "<h4>Item ID: " . $item['id'] . "</h4>";
    
    // Get product info
    $producto = getRecord('productos', $item['producto_id']);
    echo "<strong>Producto:</strong> " . $producto['nombre'] . " (SKU: " . $producto['sku'] . ")<br>";
    
    if ($item['variante_id']) {
        // Get variant info
        $variante = getRecord('variantes_producto', $item['variante_id']);
        echo "<strong>Variante ID:</strong> " . $item['variante_id'] . "<br>";
        echo "<strong>Talla:</strong> " . ($variante['talla'] ?? 'N/A') . "<br>";
        echo "<strong>Color:</strong> " . ($variante['color'] ?? 'N/A') . "<br>";
        echo "<strong>Material:</strong> " . ($variante['material'] ?? 'N/A') . "<br>";
        echo "<strong>Stock:</strong> " . ($variante['stock'] ?? 'N/A') . "<br>";
        echo "<strong>Precio Extra:</strong> " . ($variante['precio_extra'] ?? 'N/A') . "<br>";
        echo "<strong>Activo:</strong> " . ($variante['activo'] ? 'Sí' : 'No') . "<br>";
    } else {
        echo "<strong>Sin variante</strong><br>";
    }
    
    echo "<hr>";
}

// Let's also check all variants for the products in this cotización
echo "<h3>Todas las variantes disponibles para los productos en esta cotización:</h3>";

$producto_ids = array_unique(array_column($items, 'producto_id'));

foreach ($producto_ids as $producto_id) {
    $producto = getRecord('productos', $producto_id);
    echo "<h4>Producto: " . $producto['nombre'] . " (ID: $producto_id)</h4>";
    
    $variantes = readRecords('variantes_producto', ["producto_id = $producto_id"], null, 'id ASC');
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Talla</th><th>Color</th><th>Material</th><th>Stock</th><th>Precio Extra</th><th>Activo</th></tr>";
    
    foreach ($variantes as $variante) {
        echo "<tr>";
        echo "<td>" . $variante['id'] . "</td>";
        echo "<td>" . ($variante['talla'] ?? 'N/A') . "</td>";
        echo "<td>" . ($variante['color'] ?? 'N/A') . "</td>";
        echo "<td>" . ($variante['material'] ?? 'N/A') . "</td>";
        echo "<td>" . ($variante['stock'] ?? 'N/A') . "</td>";
        echo "<td>" . ($variante['precio_extra'] ?? 'N/A') . "</td>";
        echo "<td>" . ($variante['activo'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

?>
