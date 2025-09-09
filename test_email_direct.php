<?php
// Archivo de prueba directa para el sistema de correos
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Probando sistema de correos directamente...\n";

// Obtener una cotización existente
$cotizaciones = readRecords('cotizaciones', [], null, 'id ASC', 1);
if (empty($cotizaciones)) {
    echo "❌ No hay cotizaciones en la base de datos\n";
    exit;
}

$cotizacion = $cotizaciones[0];
echo "Cotización encontrada: ID {$cotizacion['id']}, Número: {$cotizacion['numero_cotizacion']}\n";

// Obtener cliente
$cliente = getRecord('clientes', $cotizacion['cliente_id']);
if (!$cliente) {
    echo "❌ Cliente no encontrado\n";
    exit;
}
echo "Cliente: {$cliente['nombre']} ({$cliente['email']})\n";

// Probar la función generatePDFForEmail directamente
echo "\nProbando generación de PDF...\n";

function generatePDFForEmailTest($cotizacion) {
    global $conn;
    
    error_log("generatePDFForEmail - Iniciando generación de PDF para cotización: " . $cotizacion['numero_cotizacion']);
    
    try {
        require_once 'vendor/autoload.php';
        error_log("generatePDFForEmail - Vendor autoload incluido");
        
        // Obtener items de la cotización
        $items = readRecords('cotizacion_items', ["cotizacion_id = {$cotizacion['id']}"], null, 'id ASC');
        error_log("generatePDFForEmail - Items obtenidos: " . count($items));
        
        foreach ($items as &$item) {
            $producto = getRecord('productos', $item['producto_id']);
            $item['producto'] = $producto;
            
            if ($item['variante_id']) {
                $variante = getRecord('variantes_producto', $item['variante_id']);
                $item['variante'] = $variante;
            }
        }
        
        // Obtener cliente
        $cliente = getRecord('clientes', $cotizacion['cliente_id']);
        error_log("generatePDFForEmail - Cliente obtenido: " . $cliente['nombre']);
        
        // Preparar datos para el PDF
        $pdfData = [
            'numero' => $cotizacion['numero_cotizacion'],
            'fecha' => date('d/m/Y H:i', strtotime($cotizacion['created_at'])),
            'cliente' => [
                'nombre' => $cliente['nombre'],
                'empresa' => $cliente['empresa'] ?? '',
                'email' => $cliente['email'],
                'telefono' => $cliente['telefono'] ?? ''
            ],
            'items' => $items,
            'subtotal' => $cotizacion['subtotal'],
            'descuento' => $cotizacion['descuento'],
            'total' => $cotizacion['total'],
            'observaciones' => $cotizacion['observaciones'] ?? '',
            'estado' => $cotizacion['estado']
        ];
        
        error_log("generatePDFForEmail - Datos preparados para PDF");
        
        // Generar HTML usando la función del generate_pdf.php
        require_once 'ajax/generate_pdf.php';
        error_log("generatePDFForEmail - generate_pdf.php incluido");
        
        $html = createCotizacionHTML($pdfData);
        error_log("generatePDFForEmail - HTML generado, longitud: " . strlen($html));
        
        // Crear instancia de mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        error_log("generatePDFForEmail - Instancia mPDF creada");
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $cotizacion['numero_cotizacion']);
        $mpdf->SetAuthor('DT Studio');
        error_log("generatePDFForEmail - Metadatos configurados");
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        error_log("generatePDFForEmail - HTML escrito en mPDF");
        
        // Generar archivo temporal
        $tempPath = sys_get_temp_dir() . '/cotizacion_' . $cotizacion['numero_cotizacion'] . '_' . time() . '.pdf';
        $mpdf->Output($tempPath, 'F');
        error_log("generatePDFForEmail - PDF generado en: $tempPath");
        
        return $tempPath;
        
    } catch (Exception $e) {
        error_log("generatePDFForEmail - Error: " . $e->getMessage());
        error_log("generatePDFForEmail - Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

$pdfPath = generatePDFForEmailTest($cotizacion);
if ($pdfPath) {
    echo "✅ PDF generado: $pdfPath\n";
    echo "Tamaño del archivo: " . filesize($pdfPath) . " bytes\n";
    
    // Limpiar archivo temporal
    unlink($pdfPath);
    echo "✅ Archivo temporal eliminado\n";
} else {
    echo "❌ Error generando PDF\n";
}

// Probar EmailSender
echo "\nProbando EmailSender...\n";
try {
    require_once 'includes/EmailSender.php';
    $emailSender = new EmailSender();
    echo "✅ EmailSender instanciado correctamente\n";
    
    // Nota: No enviamos el correo real para evitar spam
    echo "ℹ️  EmailSender configurado correctamente (no se envió correo real)\n";
    
} catch (Exception $e) {
    echo "❌ Error con EmailSender: " . $e->getMessage() . "\n";
}

echo "\nPrueba completada\n";
?>
