<?php
// Archivo de prueba simplificado para verificar la generación de PDF
require_once 'includes/config.php';
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

// Función para crear HTML de la cotización
function createCotizacionHTML($data) {
    $logoPath = 'assets/images/logo-dt-studio.svg';
    $logoExists = file_exists($logoPath);
    
    $estadoClass = [
        'pendiente' => 'warning',
        'enviada' => 'info',
        'aceptada' => 'success',
        'rechazada' => 'danger',
        'cancelada' => 'secondary'
    ];
    $estadoColor = $estadoClass[$data['estado']] ?? 'secondary';
    
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
            .logo {
                max-height: 60px;
            }
            .company-info {
                text-align: right;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #7B3F9F;
                margin: 0;
            }
            .company-subtitle {
                font-size: 14px;
                color: #666;
                margin: 5px 0;
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
            .observations {
                margin-top: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border-left: 4px solid #7B3F9F;
            }
            .observations h3 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-pendiente { background-color: #ffc107; color: #000; }
            .status-enviada { background-color: #17a2b8; color: #fff; }
            .status-aceptada { background-color: #28a745; color: #fff; }
            .status-rechazada { background-color: #dc3545; color: #fff; }
            .status-cancelada { background-color: #6c757d; color: #fff; }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                ' . ($logoExists ? '<img src="' . $logoPath . '" alt="DR Studio" class="logo">' : '') . '
            </div>
            <div class="company-info">
                <h1 class="company-name">DR Studio</h1>
                <p class="company-subtitle">Promocionales y Merchandising</p>
                <p>Tel: (55) 1234-5678</p>
                <p>Email: info@dtstudio.com.mx</p>
                <p>Web: www.dtstudio.com.mx</p>
            </div>
        </div>
        
        <h2 class="document-title">COTIZACIÓN</h2>
        
        <div class="document-info">
            <div class="client-info">
                <h3>Cliente</h3>
                <p><strong>Nombre:</strong> ' . htmlspecialchars($data['cliente']['nombre']) . '</p>
                ' . ($data['cliente']['empresa'] ? '<p><strong>Empresa:</strong> ' . htmlspecialchars($data['cliente']['empresa']) . '</p>' : '') . '
                ' . ($data['cliente']['email'] ? '<p><strong>Email:</strong> ' . htmlspecialchars($data['cliente']['email']) . '</p>' : '') . '
                ' . ($data['cliente']['telefono'] ? '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['cliente']['telefono']) . '</p>' : '') . '
            </div>
            <div class="quote-info">
                <h3>Información de Cotización</h3>
                <p><strong>Número:</strong> ' . htmlspecialchars($data['numero']) . '</p>
                <p><strong>Fecha:</strong> ' . htmlspecialchars($data['fecha']) . '</p>
                <p><strong>Estado:</strong> <span class="status-badge status-' . $data['estado'] . '">' . ucfirst($data['estado']) . '</span></p>
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
                </tr>';
    
    if ($data['descuento'] > 0) {
        $html .= '
                <tr>
                    <td>Descuento:</td>
                    <td style="text-align: right; color: #dc3545;">-$' . number_format($data['descuento'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">$' . number_format($data['total'], 2) . '</td>
                </tr>
            </table>
        </div>';
    
    if (!empty($data['observaciones'])) {
        $html .= '
        <div class="observations">
            <h3>Observaciones</h3>
            <p>' . nl2br(htmlspecialchars($data['observaciones'])) . '</p>
        </div>';
    }
    
    $html .= '
    </body>
    </html>';
    
    return $html;
}

// Generar HTML de prueba
$html = createCotizacionHTML($testData);

// Guardar en archivo para verificar
file_put_contents('test_cotizacion.html', $html);

echo "HTML generado y guardado en test_cotizacion.html\n";
echo "Puedes abrir el archivo en un navegador para ver el resultado\n";
?>
