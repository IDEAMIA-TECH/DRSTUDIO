<?php
// Archivo de prueba para verificar la ruta del PDF
echo "Probando ruta del PDF...\n";

// Verificar si el archivo existe
$pdfPath = 'ajax/generate_pdf.php';
if (file_exists($pdfPath)) {
    echo "✅ Archivo ajax/generate_pdf.php existe\n";
} else {
    echo "❌ Archivo ajax/generate_pdf.php NO existe\n";
}

// Verificar desde admin/
$pdfPathFromAdmin = '../ajax/generate_pdf.php';
if (file_exists($pdfPathFromAdmin)) {
    echo "✅ Archivo ../ajax/generate_pdf.php existe (desde admin/)\n";
} else {
    echo "❌ Archivo ../ajax/generate_pdf.php NO existe (desde admin/)\n";
}

// Verificar rutas absolutas
$absolutePath = __DIR__ . '/ajax/generate_pdf.php';
if (file_exists($absolutePath)) {
    echo "✅ Ruta absoluta: " . $absolutePath . " existe\n";
} else {
    echo "❌ Ruta absoluta: " . $absolutePath . " NO existe\n";
}

// Verificar contenido del archivo
if (file_exists($pdfPath)) {
    $content = file_get_contents($pdfPath);
    if (strpos($content, 'generate_cotizacion_pdf') !== false) {
        echo "✅ El archivo contiene la función generate_cotizacion_pdf\n";
    } else {
        echo "❌ El archivo NO contiene la función generate_cotizacion_pdf\n";
    }
}
?>
