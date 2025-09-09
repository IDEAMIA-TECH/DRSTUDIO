<?php
// Archivo de prueba para simular la edición de productos
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Probando edición de productos...\n";

// Obtener un producto existente
$productos = readRecords('productos', [], null, 'id ASC', 1);
if (empty($productos)) {
    echo "❌ No hay productos en la base de datos\n";
    exit;
}

$producto = $productos[0];
$id = $producto['id'];
echo "Producto encontrado: ID $id, Nombre: {$producto['nombre']}\n";

// Simular datos POST
$_POST = [
    'categoria_id' => $producto['categoria_id'],
    'sku' => $producto['sku'],
    'nombre' => $producto['nombre'] . ' - TEST EDIT',
    'descripcion' => $producto['descripcion'],
    'precio_venta' => $producto['precio_venta'] + 5.00,
    'costo_fabricacion' => $producto['costo_fabricacion'] + 2.00,
    'tiempo_entrega' => $producto['tiempo_entrega'],
    'destacado' => $producto['destacado'],
    'activo' => $producto['activo']
];

echo "Datos POST simulados:\n";
print_r($_POST);

// Procesar datos como en el archivo original
$categoria_id = (int)$_POST['categoria_id'];
$sku = sanitizeInput($_POST['sku']);
$nombre = sanitizeInput($_POST['nombre']);
$descripcion = sanitizeInput($_POST['descripcion']);
$precio_venta = (float)$_POST['precio_venta'];
$costo_fabricacion = (float)$_POST['costo_fabricacion'];
$tiempo_entrega = (int)$_POST['tiempo_entrega'];
$destacado = isset($_POST['destacado']) ? 1 : 0;
$activo = isset($_POST['activo']) ? 1 : 0;

echo "\nDatos procesados:\n";
echo "SKU: $sku\n";
echo "Nombre: $nombre\n";
echo "Precio: $precio_venta\n";
echo "Costo: $costo_fabricacion\n";

// Validar datos
if (empty($sku) || empty($nombre) || $precio_venta <= 0 || $costo_fabricacion <= 0) {
    echo "❌ Error de validación: Todos los campos requeridos deben ser completados correctamente\n";
    exit;
}

// Verificar SKU usando consulta preparada
$checkSql = "SELECT id FROM productos WHERE sku = ? AND id != ?";
$checkStmt = $conn->prepare($checkSql);
if ($checkStmt) {
    $checkStmt->bind_param("si", $sku, $id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $checkStmt->close();
    
    if (!empty($existing)) {
        echo "❌ Error: Ya existe otro producto con este SKU\n";
        exit;
    }
    
    echo "✅ Validación de SKU pasada\n";
    
    // Preparar datos para actualización
    $data = [
        'categoria_id' => $categoria_id ?: null,
        'sku' => $sku,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'precio_venta' => $precio_venta,
        'costo_fabricacion' => $costo_fabricacion,
        'tiempo_entrega' => $tiempo_entrega,
        'imagen_principal' => $producto['imagen_principal'],
        'destacado' => $destacado,
        'activo' => $activo
    ];
    
    echo "\nDatos para actualización:\n";
    print_r($data);
    
    // Intentar actualizar
    echo "\nIntentando actualizar...\n";
    
    // Habilitar logging de errores
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', '/tmp/php_errors.log');
    
    echo "Llamando a updateRecord...\n";
    $result = updateRecord('productos', $data, $id);
    echo "Resultado de updateRecord: " . ($result ? 'true' : 'false') . "\n";
    
    if ($result) {
        echo "✅ Producto actualizado exitosamente\n";
        
        // Verificar actualización
        $productoActualizado = getRecord('productos', $id);
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
        updateRecord('productos', $restoreData, $id);
        echo "\n✅ Datos restaurados\n";
        
    } else {
        echo "❌ Error al actualizar el producto\n";
    }
    
} else {
    echo "❌ Error preparando consulta de verificación de SKU\n";
}

echo "\nPrueba completada\n";
?>
