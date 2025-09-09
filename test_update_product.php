<?php
// Archivo de prueba para verificar la actualización de productos
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Probando actualización de productos...\n";

// Obtener un producto existente
$productos = readRecords('productos', [], null, 'id ASC', 1);
if (empty($productos)) {
    echo "❌ No hay productos en la base de datos\n";
    exit;
}

$producto = $productos[0];
echo "Producto encontrado: ID {$producto['id']}, Nombre: {$producto['nombre']}\n";

// Datos de prueba para actualizar
$testData = [
    'nombre' => $producto['nombre'] . ' - TEST UPDATE',
    'precio_venta' => $producto['precio_venta'] + 10.00,
    'costo_fabricacion' => $producto['costo_fabricacion'] + 5.00
];

echo "Datos de prueba:\n";
print_r($testData);

// Intentar actualizar
echo "\nIntentando actualizar...\n";
$result = updateRecord('productos', $testData, $producto['id']);

if ($result) {
    echo "✅ Actualización exitosa\n";
    
    // Verificar que se actualizó
    $productoActualizado = getRecord('productos', $producto['id']);
    echo "Producto actualizado:\n";
    echo "Nombre: {$productoActualizado['nombre']}\n";
    echo "Precio: {$productoActualizado['precio_venta']}\n";
    echo "Costo: {$productoActualizado['costo_fabricacion']}\n";
    
    // Restaurar datos originales
    $restoreData = [
        'nombre' => $producto['nombre'],
        'precio_venta' => $producto['precio_venta'],
        'costo_fabricacion' => $producto['costo_fabricacion']
    ];
    updateRecord('productos', $restoreData, $producto['id']);
    echo "\n✅ Datos restaurados\n";
    
} else {
    echo "❌ Error en la actualización\n";
    echo "Revisa los logs de error para más detalles\n";
}

echo "\nPrueba completada\n";
?>
