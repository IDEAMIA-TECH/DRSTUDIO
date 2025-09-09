<?php
// Archivo de generación de PDF mejorado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir dependencias
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf_generator.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$action = $data['action'];
$cotizacionData = $data['data'] ?? [];

// Procesar acción
if ($action === 'generate_cotizacion_pdf') {
    if (empty($cotizacionData)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron datos de cotización']);
        exit;
    }
    
    try {
        // Obtener ID de la cotización desde los datos
        $cotizacionId = null;
        if (isset($cotizacionData['numero'])) {
            // Buscar por número de cotización
            $stmt = $conn->prepare("SELECT id FROM cotizaciones WHERE numero_cotizacion = ?");
            $stmt->bind_param("s", $cotizacionData['numero']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $cotizacionId = $row['id'] ?? null;
            $stmt->close();
        }
        
        if (!$cotizacionId) {
            throw new Exception("No se pudo encontrar la cotización");
        }
        
        // Generar PDF usando la función unificada
        $pdfPath = generateCotizacionPDF($cotizacionId);
        
        // Enviar el PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Cotizacion_' . $cotizacionData['numero'] . '.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        
        readfile($pdfPath);
        
        // Limpiar archivo temporal
        unlink($pdfPath);
        
    } catch (Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        
        // Fallback: mostrar HTML
        header('Content-Type: text/html; charset=UTF-8');
        echo createCotizacionHTML($cotizacionData);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
}
?>
