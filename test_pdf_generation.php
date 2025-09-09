<?php
/**
 * Script para probar la generación de PDF
 */

echo "🔍 Probando generación de PDF...\n";
echo "================================\n";

try {
    // Incluir config y funciones
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    
    echo "✅ Archivos incluidos correctamente\n";
    
    // Probar conexión
    if ($conn && !$conn->connect_error) {
        echo "✅ Conexión a base de datos establecida\n";
    } else {
        echo "❌ Error de conexión a base de datos\n";
        exit;
    }
    
    // Datos de prueba para la cotización
    $testData = [
        'numero' => 'COT-2025-0001',
        'fecha' => '09/09/2025',
        'cliente' => [
            'nombre' => 'Juan Pérez',
            'empresa' => 'Empresa de Prueba S.A.',
            'email' => 'juan@empresa.com',
            'telefono' => '+52 1 234 567 8900'
        ],
        'items' => [
            [
                'producto' => [
                    'nombre' => 'Playera Básica Algodón',
                    'sku' => 'PLA-2025-0001'
                ],
                'variante' => [
                    'talla' => 'M',
                    'color' => 'Blanco',
                    'material' => 'Algodón 100%'
                ],
                'cantidad' => 10,
                'precio_unitario' => 150.00,
                'subtotal' => 1500.00
            ],
            [
                'producto' => [
                    'nombre' => 'Taza Cerámica',
                    'sku' => 'TAZ-2025-0001'
                ],
                'variante' => null,
                'cantidad' => 5,
                'precio_unitario' => 80.00,
                'subtotal' => 400.00
            ]
        ],
        'subtotal' => 1900.00,
        'descuento' => 100.00,
        'total' => 1800.00,
        'observaciones' => 'Esta es una cotización de prueba para verificar la funcionalidad del sistema de generación de PDF.',
        'estado' => 'pendiente'
    ];
    
    echo "\n📋 Datos de prueba creados\n";
    
    // Probar la función de generación de HTML
    if (function_exists('createCotizacionHTML')) {
        echo "✅ Función createCotizacionHTML disponible\n";
        
        $html = createCotizacionHTML($testData);
        echo "✅ HTML generado: " . strlen($html) . " caracteres\n";
        
        // Guardar HTML de prueba
        file_put_contents('test_cotizacion.html', $html);
        echo "✅ HTML guardado en test_cotizacion.html\n";
        
    } else {
        echo "❌ Función createCotizacionHTML no disponible\n";
    }
    
    // Verificar si mPDF está disponible
    if (class_exists('Mpdf\Mpdf')) {
        echo "✅ mPDF disponible - PDFs de alta calidad\n";
    } else {
        echo "⚠️  mPDF no disponible - usando HTML como fallback\n";
    }
    
    // Verificar logo
    $logoPath = 'assets/images/logo-dt-studio.svg';
    if (file_exists($logoPath)) {
        echo "✅ Logo encontrado: $logoPath\n";
    } else {
        echo "⚠️  Logo no encontrado: $logoPath\n";
    }
    
    echo "\n🎉 Prueba completada\n";
    echo "\nPara ver el resultado, abre test_cotizacion.html en tu navegador\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
