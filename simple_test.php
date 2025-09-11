<?php
// Test simple para verificar si PHP funciona desde el navegador
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'simple_test.log');

// Log inmediato
error_log("=== SIMPLE TEST - INICIO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

echo "=== SIMPLE TEST ===\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "POST data: " . print_r($_POST, true) . "\n";
echo "GET data: " . print_r($_GET, true) . "\n";

if ($_POST) {
    echo "✓ POST data presente\n";
    error_log("✓ POST data presente");
    
    // Procesar datos
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $mensaje = $_POST['mensaje'] ?? '';
    
    echo "Datos recibidos:\n";
    echo "- Nombre: $nombre\n";
    echo "- Email: $email\n";
    echo "- Mensaje: $mensaje\n";
    
    error_log("Datos recibidos - Nombre: $nombre, Email: $email, Mensaje: $mensaje");
    
    // Validar
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        echo "✗ Error: Campos requeridos faltantes\n";
        error_log("✗ Error: Campos requeridos faltantes");
    } else {
        echo "✓ Validación pasada\n";
        error_log("✓ Validación pasada");
        
        // Insertar en BD
        require_once 'includes/config.php';
        require_once 'includes/functions.php';
        
        $solicitud_data = [
            'cliente_nombre' => $nombre,
            'cliente_email' => $email,
            'cliente_telefono' => $_POST['telefono'] ?? '',
            'cliente_empresa' => $_POST['empresa'] ?? '',
            'productos_interes' => $_POST['productos_interes'] ?? '',
            'cantidad_estimada' => $_POST['cantidad_estimada'] ?? '',
            'fecha_entrega_deseada' => $_POST['fecha_entrega'] ?? '',
            'mensaje' => $mensaje,
            'estado' => 'pendiente'
        ];
        
        echo "Intentando insertar en BD...\n";
        error_log("Intentando insertar en BD: " . print_r($solicitud_data, true));
        
        if (createRecord('solicitudes_cotizacion', $solicitud_data)) {
            $cotizacion_id = $conn->insert_id;
            echo "✓ INSERCIÓN EXITOSA - ID: $cotizacion_id\n";
            error_log("✓ INSERCIÓN EXITOSA - ID: $cotizacion_id");
        } else {
            echo "✗ ERROR EN INSERCIÓN: " . $conn->error . "\n";
            error_log("✗ ERROR EN INSERCIÓN: " . $conn->error);
        }
    }
} else {
    echo "✗ No hay POST data\n";
    error_log("✗ No hay POST data");
}

error_log("=== SIMPLE TEST - FIN ===");

echo "\n=== END TEST ===\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
</head>
<body>
    <h1>Simple Test Form</h1>
    <form method="POST">
        <div>
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
        </div>
        <div>
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Teléfono:</label>
            <input type="text" name="telefono">
        </div>
        <div>
            <label>Empresa:</label>
            <input type="text" name="empresa">
        </div>
        <div>
            <label>Productos de Interés:</label>
            <textarea name="productos_interes"></textarea>
        </div>
        <div>
            <label>Cantidad Estimada:</label>
            <input type="text" name="cantidad_estimada">
        </div>
        <div>
            <label>Fecha de Entrega:</label>
            <input type="date" name="fecha_entrega">
        </div>
        <div>
            <label>Mensaje:</label>
            <textarea name="mensaje" required></textarea>
        </div>
        <div>
            <button type="submit">Enviar</button>
        </div>
    </form>
</body>
</html>
