<?php
// Test de conexión a la API de productos
header('Content-Type: application/json');

echo "=== TEST DE CONEXIÓN A API DE PRODUCTOS ===\n\n";

// 1. Verificar que el archivo existe
$apiFile = 'api/products.php';
if (file_exists($apiFile)) {
    echo "✅ Archivo api/products.php existe\n";
} else {
    echo "❌ Archivo api/products.php NO existe\n";
    exit;
}

// 2. Verificar que la clase Database existe
$dbFile = 'config/database.php';
if (file_exists($dbFile)) {
    echo "✅ Archivo config/database.php existe\n";
} else {
    echo "❌ Archivo config/database.php NO existe\n";
    exit;
}

// 3. Probar la conexión a la base de datos
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "✅ Conexión a base de datos exitosa\n";
} catch (Exception $e) {
    echo "❌ Error de conexión a base de datos: " . $e->getMessage() . "\n";
    exit;
}

// 4. Probar la API directamente
echo "\n=== PROBANDO API DIRECTAMENTE ===\n";

// Simular una petición GET
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_products';

// Capturar la salida
ob_start();
include $apiFile;
$output = ob_get_clean();

echo "Salida de la API:\n";
echo $output . "\n";

// 5. Verificar si hay productos en la base de datos
try {
    $query = "SELECT COUNT(*) as total FROM products";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== INFORMACIÓN DE LA BASE DE DATOS ===\n";
    echo "Total de productos en la BD: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        echo "✅ Hay productos en la base de datos\n";
        
        // Mostrar algunos productos
        $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LIMIT 3";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nPrimeros 3 productos:\n";
        foreach ($products as $product) {
            echo "- ID: {$product['id']}, Nombre: {$product['name']}, Categoría: {$product['category_name']}\n";
        }
    } else {
        echo "⚠️ No hay productos en la base de datos\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error al consultar productos: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETADO ===\n";
?>
