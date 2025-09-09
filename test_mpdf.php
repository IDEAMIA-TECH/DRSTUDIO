<?php
// Archivo de prueba para verificar mPDF
require_once 'vendor/autoload.php';

try {
    // Crear una instancia de mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P'
    ]);
    
    // HTML de prueba
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test mPDF</title>
    </head>
    <body>
        <h1>Test de mPDF</h1>
        <p>Este es un PDF de prueba generado con mPDF.</p>
        <p>Fecha: ' . date('Y-m-d H:i:s') . '</p>
    </body>
    </html>';
    
    // Escribir HTML al PDF
    $mpdf->WriteHTML($html);
    
    // Configurar headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="test_mpdf.pdf"');
    
    // Generar PDF
    $mpdf->Output('test_mpdf.pdf', 'D');
    
    echo "mPDF funcionando correctamente";
    
} catch (Exception $e) {
    echo "Error con mPDF: " . $e->getMessage();
}
?>
