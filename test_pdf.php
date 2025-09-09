<?php
// Archivo de prueba para verificar la generación de PDF
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Simular sesión de usuario
$_SESSION['user_id'] = 1;

// Datos de prueba
$testData = [
    'numero' => 'COT-2024-0001',
    'fecha' => '09/09/2024',
    'cliente' => [
        'nombre' => 'Juan Pérez',
        'empresa' => 'Empresa de Prueba',
        'email' => 'juan@empresa.com',
        'telefono' => '555-1234'
    ],
    'items' => [
        [
            'producto' => [
                'nombre' => 'Playera 100% algodón',
                'sku' => 'PLA-2024-0001'
            ],
            'variante' => [
                'talla' => 'M',
                'color' => 'Azul',
                'material' => 'Algodón'
            ],
            'cantidad' => 10,
            'precio_unitario' => 150.00,
            'subtotal' => 1500.00
        ],
        [
            'producto' => [
                'nombre' => 'Taza personalizada',
                'sku' => 'TZA-2024-0001'
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
    'observaciones' => 'Esta es una cotización de prueba para verificar el funcionamiento del sistema.',
    'estado' => 'pendiente'
];

// Incluir la función de generación de PDF
require_once 'ajax/generate_pdf.php';

// Generar PDF de prueba
generateCotizacionPDF($testData);
?>
