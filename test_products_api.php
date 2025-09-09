<?php
/**
 * Script de prueba de API de Productos - DT Studio
 * Verificar que la API de productos funciona correctamente
 */

echo "<h2>Prueba de API de Productos - DT Studio</h2>\n";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Probar la API de productos
echo "<h3>Probando API de Productos:</h3>\n";

try {
    // Simular petición GET
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/products.php?action=get_products");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ Error cURL: $error</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Respuesta HTTP: $httpCode</p>\n";
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                echo "<p style='color: green;'>✅ JSON válido</p>\n";
                echo "<p><strong>Success:</strong> " . ($data['success'] ? 'true' : 'false') . "</p>\n";
                echo "<p><strong>Message:</strong> " . ($data['message'] ?? 'N/A') . "</p>\n";
                
                if (isset($data['data']['products'])) {
                    echo "<p><strong>Productos encontrados:</strong> " . count($data['data']['products']) . "</p>\n";
                    
                    if (count($data['data']['products']) > 0) {
                        echo "<h4>Primer producto:</h4>\n";
                        $firstProduct = $data['data']['products'][0];
                        echo "<ul>\n";
                        echo "<li><strong>ID:</strong> " . $firstProduct['id'] . "</li>\n";
                        echo "<li><strong>Nombre:</strong> " . $firstProduct['name'] . "</li>\n";
                        echo "<li><strong>Descripción:</strong> " . substr($firstProduct['description'], 0, 100) . "...</li>\n";
                        echo "<li><strong>SKU:</strong> " . $firstProduct['sku'] . "</li>\n";
                        echo "<li><strong>Estado:</strong> " . $firstProduct['status'] . "</li>\n";
                        echo "<li><strong>Categoría:</strong> " . ($firstProduct['category_name'] ?? 'N/A') . "</li>\n";
                        echo "<li><strong>Precio mínimo:</strong> $" . $firstProduct['min_price'] . "</li>\n";
                        echo "<li><strong>Precio máximo:</strong> $" . $firstProduct['max_price'] . "</li>\n";
                        echo "<li><strong>Imágenes:</strong> " . count($firstProduct['images']) . "</li>\n";
                        echo "</ul>\n";
                    }
                }
                
                if (isset($data['data']['pagination'])) {
                    echo "<h4>Paginación:</h4>\n";
                    $pagination = $data['data']['pagination'];
                    echo "<ul>\n";
                    echo "<li><strong>Página actual:</strong> " . $pagination['current_page'] . "</li>\n";
                    echo "<li><strong>Por página:</strong> " . $pagination['per_page'] . "</li>\n";
                    echo "<li><strong>Total:</strong> " . $pagination['total'] . "</li>\n";
                    echo "<li><strong>Total de páginas:</strong> " . $pagination['total_pages'] . "</li>\n";
                    echo "</ul>\n";
                }
            } else {
                echo "<p style='color: red;'>❌ JSON inválido</p>\n";
                echo "<p><strong>Respuesta:</strong> " . htmlspecialchars(substr($response, 0, 500)) . "...</p>\n";
            }
        } else {
            echo "<p style='color: red;'>❌ Sin respuesta</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Excepción: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";

// Probar creación de un producto
echo "<h3>Probando creación de producto:</h3>\n";

$productData = [
    'name' => 'Producto de Prueba API',
    'description' => 'Descripción del producto de prueba para la API',
    'category_id' => 1, // Asumiendo que existe la categoría con ID 1
    'sku' => 'TEST-' . time(),
    'price' => 150.00,
    'cost' => 100.00,
    'meta_title' => 'Producto de Prueba',
    'meta_description' => 'Descripción meta del producto de prueba'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/products.php?action=create_product");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($productData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Respuesta HTTP:</strong> $httpCode</p>\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<p style='color: " . ($data['success'] ? 'green' : 'red') . ";'>";
        echo ($data['success'] ? '✅' : '❌') . " " . $data['message'] . "</p>\n";
        if ($data['success'] && isset($data['data']['id'])) {
            echo "<p><strong>ID del producto creado:</strong> " . $data['data']['id'] . "</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Respuesta JSON inválida</p>\n";
        echo "<p><strong>Respuesta:</strong> " . htmlspecialchars(substr($response, 0, 500)) . "...</p>\n";
    }
}

echo "<hr>\n";
echo "<p><strong>Prueba completada:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
