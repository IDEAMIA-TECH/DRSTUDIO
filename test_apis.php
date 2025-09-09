<?php
/**
 * Script de prueba de APIs - DT Studio
 * Verificar que todas las APIs funcionan correctamente
 */

echo "<h2>Prueba de APIs - DT Studio</h2>\n";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Lista de APIs a probar
$apis = [
    'dashboard' => 'api/dashboard.php?action=get_stats',
    'products' => 'api/products.php?action=get_products',
    'customers' => 'api/customers.php?action=get_customers',
    'quotations' => 'api/quotations.php?action=get_quotations',
    'orders' => 'api/orders.php?action=get_orders'
];

echo "<h3>Probando APIs:</h3>\n";

foreach ($apis as $name => $url) {
    echo "<h4>Probando API: $name</h4>\n";
    echo "<p><strong>URL:</strong> $url</p>\n";
    
    try {
        // Simular petición GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $url);
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
                    
                    if (isset($data['data'])) {
                        if (is_array($data['data'])) {
                            echo "<p><strong>Datos:</strong> " . count($data['data']) . " elementos</p>\n";
                        } else {
                            echo "<p><strong>Datos:</strong> " . gettype($data['data']) . "</p>\n";
                        }
                    }
                } else {
                    echo "<p style='color: red;'>❌ JSON inválido</p>\n";
                    echo "<p><strong>Respuesta:</strong> " . htmlspecialchars(substr($response, 0, 200)) . "...</p>\n";
                }
            } else {
                echo "<p style='color: red;'>❌ Sin respuesta</p>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Excepción: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
}

echo "<h3>Prueba de creación de datos:</h3>\n";

// Probar creación de un producto
echo "<h4>Probando creación de producto</h4>\n";
$productData = [
    'name' => 'Producto de Prueba',
    'description' => 'Descripción del producto de prueba',
    'price' => 100.00,
    'category' => 'test',
    'material' => 'test',
    'featured' => 0
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
    }
}

echo "<hr>\n";
echo "<p><strong>Prueba completada:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
