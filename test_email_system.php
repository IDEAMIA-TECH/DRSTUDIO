<?php
// Archivo de prueba para verificar el sistema de correos
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Probando sistema de correos...\n";

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

// Probar la función generatePDFForEmail
echo "\nProbando generación de PDF...\n";
require_once 'ajax/cotizaciones.php';

$pdfPath = generatePDFForEmail($cotizacion);
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
