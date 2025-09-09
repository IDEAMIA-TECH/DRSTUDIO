<?php
// Archivo de prueba para verificar cambios en el PDF
require_once 'vendor/autoload.php';

// Datos de prueba
$testData = [
    'numero' => 'COT-2025-TEST',
    'fecha' => '09/09/2025 18:30',
    'cliente' => [
        'nombre' => 'Cliente de Prueba',
        'empresa' => 'Empresa Test',
        'email' => 'test@empresa.com',
        'telefono' => '555-1234'
    ],
    'items' => [
        [
            'producto' => [
                'nombre' => 'Producto Test',
                'sku' => 'TEST-001'
            ],
            'variante' => [
                'talla' => 'M',
                'color' => 'Azul',
                'material' => 'Algodón'
            ],
            'cantidad' => 2,
            'precio_unitario' => 100.00,
            'subtotal' => 200.00
        ]
    ],
    'subtotal' => 200.00,
    'descuento' => 10.00,
    'total' => 190.00,
    'observaciones' => 'Esta es una prueba de los cambios en el PDF',
    'estado' => 'pendiente'
];

// Función para crear HTML de prueba
function createTestHTML($data) {
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #7B3F9F;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #7B3F9F;
                margin: 0;
            }
            .document-title {
                font-size: 28px;
                font-weight: bold;
                color: #333;
                margin: 20px 0;
                text-align: center;
            }
            .document-info {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
            }
            .client-info, .quote-info {
                flex: 1;
            }
            .client-info h3, .quote-info h3 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
                font-size: 16px;
            }
            .client-info p, .quote-info p {
                margin: 5px 0;
                font-size: 12px;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .items-table th, .items-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .items-table th {
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
            }
            .items-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .totals {
                margin-left: auto;
                width: 300px;
            }
            .totals table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals td {
                padding: 5px;
                border: none;
            }
            .totals .total-row {
                font-weight: bold;
                font-size: 14px;
                background-color: #7B3F9F;
                color: white;
            }
            .totals .total-row td {
                color: white !important;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                background-color: #ffc107;
                color: #000;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                <h1 class="company-name">DT Studio</h1>
            </div>
            <div>
                <p>Tel: (55) 1234-5678</p>
                <p>Email: info@dtstudio.com.mx</p>
            </div>
        </div>
        
        <h2 class="document-title">COTIZACIÓN</h2>
        
        <div class="document-info">
            <div class="client-info">
                <h3>Cliente</h3>
                <p><strong>Nombre:</strong> ' . htmlspecialchars($data['cliente']['nombre']) . '</p>
                <p><strong>Empresa:</strong> ' . htmlspecialchars($data['cliente']['empresa']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($data['cliente']['email']) . '</p>
                <p><strong>Teléfono:</strong> ' . htmlspecialchars($data['cliente']['telefono']) . '</p>
            </div>
            <div class="quote-info">
                <h3>Información de Cotización</h3>
                <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                <p><strong>Fecha:</strong> ' . htmlspecialchars($data['fecha']) . '</p>
                <p><strong>Estado:</strong> <span class="status-badge">' . ucfirst($data['estado']) . '</span></p>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Variante</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['items'] as $item) {
        $variante = '';
        if (isset($item['variante']) && $item['variante']) {
            $variante_parts = array_filter([
                $item['variante']['talla'] ?? '',
                $item['variante']['color'] ?? '',
                $item['variante']['material'] ?? ''
            ]);
            $variante = implode(' - ', $variante_parts);
        }
        
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['producto']['nombre']) . '<br><small>SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small></td>
                    <td>' . ($variante ? htmlspecialchars($variante) : 'Sin variante') . '</td>
                    <td>' . $item['cantidad'] . '</td>
                    <td>$' . number_format($item['precio_unitario'], 2) . '</td>
                    <td>$' . number_format($item['subtotal'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td style="text-align: right;">$' . number_format($data['subtotal'], 2) . '</td>
                </tr>
                <tr>
                    <td>Descuento:</td>
                    <td style="text-align: right; color: #dc3545;">-$' . number_format($data['descuento'], 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">$' . number_format($data['total'], 2) . '</td>
                </tr>
            </table>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #7B3F9F;">
            <h3 style="margin: 0 0 10px 0; color: #7B3F9F;">Observaciones</h3>
            <p>' . htmlspecialchars($data['observaciones']) . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Generar HTML de prueba
$html = createTestHTML($testData);

// Configurar headers
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="Test_PDF_Changes.html"');

echo $html;
?>
