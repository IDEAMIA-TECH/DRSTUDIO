<?php
// Archivo de debugging para generate_pdf.php
header('Content-Type: application/json');

// Log de debugging
error_log("PDF Debug - Archivo accedido correctamente");

// Verificar si es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("PDF Debug - Error: Método no es POST");
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

error_log("PDF Debug - Action recibida: " . $action);
error_log("PDF Debug - Input data: " . print_r($input, true));

if ($action === 'test') {
    echo json_encode([
        'success' => true,
        'message' => 'Archivo generate_pdf.php accesible correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action
    ]);
    exit;
}

if ($action === 'generate_cotizacion_pdf') {
    $data = $input['data'] ?? [];
    
    if (empty($data)) {
        error_log("PDF Debug - Error: Datos vacíos");
        echo json_encode(['success' => false, 'message' => 'Datos de cotización requeridos']);
        exit;
    }
    
    error_log("PDF Debug - Datos recibidos correctamente, generando PDF...");
    
    // Simular generación de PDF (por ahora solo devolver HTML)
    $html = '<h1>Cotización ' . htmlspecialchars($data['numero']) . '</h1>';
    $html .= '<p>Cliente: ' . htmlspecialchars($data['cliente']['nombre']) . '</p>';
    $html .= '<p>Total: $' . number_format($data['total'], 2) . '</p>';
    
    // Devolver como HTML por ahora
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
?>
