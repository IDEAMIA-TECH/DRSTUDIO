<?php
// Detailed debug script to check variants retrieval
require_once 'includes/config.php';
require_once 'includes/functions.php';

$cotizacion_id = 15;

echo "<h2>Debug Detallado: Variantes para Cotización ID $cotizacion_id</h2>";

// Simulate the exact same logic as in cotizaciones_view.php
$items = readRecords('cotizacion_items', ["cotizacion_id = $cotizacion_id"], null, 'id ASC');

echo "<h3>Simulando la lógica de cotizaciones_view.php:</h3>";

foreach ($items as &$item) {
    echo "<h4>Procesando Item ID: " . $item['id'] . "</h4>";
    echo "Variante ID en el item: " . $item['variante_id'] . "<br>";
    
    $producto = getRecord('productos', $item['producto_id']);
    $item['producto'] = $producto;
    echo "Producto: " . $producto['nombre'] . "<br>";
    
    if ($item['variante_id']) {
        echo "Obteniendo variante con ID: " . $item['variante_id'] . "<br>";
        $variante = getRecord('variantes_producto', $item['variante_id']);
        $item['variante'] = $variante;
        
        if ($variante) {
            echo "Variante encontrada:<br>";
            echo "- ID: " . $variante['id'] . "<br>";
            echo "- Talla: " . ($variante['talla'] ?? 'N/A') . "<br>";
            echo "- Color: " . ($variante['color'] ?? 'N/A') . "<br>";
            echo "- Material: " . ($variante['material'] ?? 'N/A') . "<br>";
            echo "- Stock: " . ($variante['stock'] ?? 'N/A') . "<br>";
            echo "- Precio Extra: " . ($variante['precio_extra'] ?? 'N/A') . "<br>";
            echo "- Activo: " . ($variante['activo'] ? 'Sí' : 'No') . "<br>";
            
            // Simulate the display logic from cotizaciones_view.php
            $variante_parts = array_filter([
                $variante['talla'] ?? '',
                $variante['color'] ?? '',
                $variante['material'] ?? ''
            ]);
            $variante_display = implode(' - ', $variante_parts);
            echo "<strong>Display result: " . htmlspecialchars($variante_display) . "</strong><br>";
        } else {
            echo "ERROR: No se encontró la variante con ID " . $item['variante_id'] . "<br>";
        }
    } else {
        echo "Sin variante<br>";
    }
    
    echo "<hr>";
}

// Let's also check if there are any issues with the database connection or caching
echo "<h3>Verificación directa de la base de datos:</h3>";

$sql = "SELECT * FROM variantes_producto WHERE id IN (13, 14)";
$result = $conn->query($sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Producto ID</th><th>Talla</th><th>Color</th><th>Material</th><th>Stock</th><th>Precio Extra</th><th>Activo</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['producto_id'] . "</td>";
    echo "<td>" . ($row['talla'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['color'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['material'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['stock'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['precio_extra'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

?>
