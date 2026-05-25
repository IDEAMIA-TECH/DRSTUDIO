<?php
require_once __DIR__ . '/../includes/ajax_bootstrap.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Configurar headers para PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="cotizacion_dtf.pdf"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos: ' . json_last_error_msg()]);
    exit;
}

// Generar PDF usando mPDF
try {
    require_once $projectRoot . '/vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9,
    ]);

    // Configurar metadatos
    $mpdf->SetTitle('Cotización DTF - DT Studio');
    $mpdf->SetAuthor('DT Studio');
    $mpdf->SetSubject('Cotización de Playeras con Estampado DTF');
    $mpdf->SetKeywords('DTF, Playeras, Cotización, Estampado');

    // Generar HTML del PDF
    $html = generarHTMLCotizacion($data);
    
    // Escribir HTML al PDF
    $mpdf->WriteHTML($html);
    
    // Generar y enviar PDF
    $mpdf->Output('cotizacion_dtf.pdf', 'D');
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar PDF: ' . $e->getMessage()]);
    exit;
}

function generarHTMLCotizacion($data) {
    $fecha = $data['fecha'] ?? date('d/m/Y');
    $hora = $data['hora'] ?? date('H:i:s');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Cotización DTF - DT Studio</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #667eea;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .logo {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 10px;
            }
            .title {
                font-size: 20px;
                color: #333;
                margin-bottom: 5px;
            }
            .subtitle {
                font-size: 14px;
                color: #666;
            }
            .info-section {
                margin-bottom: 25px;
            }
            .section-title {
                background-color: #667eea;
                color: white;
                padding: 8px 12px;
                font-weight: bold;
                font-size: 14px;
                margin-bottom: 10px;
            }
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }
            .data-table td {
                padding: 6px 8px;
                border-bottom: 1px solid #ddd;
            }
            .data-table td:first-child {
                font-weight: bold;
                width: 40%;
            }
            .data-table td:last-child {
                text-align: right;
                width: 60%;
            }
            .highlight-row {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            .price-section {
                background-color: #e8f5e8;
                border: 2px solid #28a745;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
            }
            .price-title {
                font-size: 16px;
                font-weight: bold;
                color: #28a745;
                text-align: center;
                margin-bottom: 15px;
            }
            .price-grid {
                display: table;
                width: 100%;
            }
            .price-item {
                display: table-cell;
                text-align: center;
                padding: 10px;
            }
            .price-value {
                font-size: 18px;
                font-weight: bold;
                color: #333;
            }
            .price-label {
                font-size: 11px;
                color: #666;
                margin-top: 5px;
            }
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                font-size: 10px;
                color: #666;
            }
            .calculation-details {
                background-color: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 15px;
                margin: 15px 0;
            }
            .calculation-title {
                font-weight: bold;
                color: #667eea;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo">DT STUDIO</div>
            <div class="title">COTIZACIÓN DTF - PLAYERAS</div>
            <div class="subtitle">Herramienta Interna de Cálculo de Precios</div>
        </div>

        <div class="info-section">
            <div class="section-title">📅 Información General</div>
            <table class="data-table">
                <tr>
                    <td>Fecha de Cotización:</td>
                    <td>' . $fecha . '</td>
                </tr>
                <tr>
                    <td>Hora:</td>
                    <td>' . $hora . '</td>
                </tr>
                <tr>
                    <td>Generado por:</td>
                    <td>Sistema Interno DT Studio</td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <div class="section-title">🎨 Información del Diseño</div>
            <table class="data-table">
                <tr>
                    <td>Ancho del Diseño:</td>
                    <td>' . number_format($data['diseno']['ancho'], 1) . ' cm</td>
                </tr>
                <tr>
                    <td>Alto del Diseño:</td>
                    <td>' . number_format($data['diseno']['alto'], 1) . ' cm</td>
                </tr>
                <tr class="highlight-row">
                    <td>Área Total:</td>
                    <td>' . number_format($data['diseno']['area'], 1) . ' cm²</td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <div class="section-title">🖨️ Configuración DTF</div>
            <table class="data-table">
                <tr>
                    <td>Costo por Metro Lineal:</td>
                    <td>$' . number_format($data['dtf']['costo_metro_lineal'], 2) . '</td>
                </tr>
                <tr>
                    <td>Ancho del Film:</td>
                    <td>' . number_format($data['dtf']['ancho_film'], 1) . ' cm</td>
                </tr>
                <tr>
                    <td>Costo por cm²:</td>
                    <td>$' . number_format($data['dtf']['costo_por_cm2'], 4) . '</td>
                </tr>
                <tr class="highlight-row">
                    <td>Costo DTF Total:</td>
                    <td>$' . number_format($data['dtf']['costo_dtf'], 2) . '</td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <div class="section-title">👕 Información de la Playera</div>
            <table class="data-table">
                <tr>
                    <td>Tipo de Playera:</td>
                    <td>' . htmlspecialchars($data['playera']['tipo']) . '</td>
                </tr>
                <tr class="highlight-row">
                    <td>Costo de la Playera:</td>
                    <td>$' . number_format($data['playera']['costo'], 2) . '</td>
                </tr>
            </table>
        </div>

        <div class="calculation-details">
            <div class="calculation-title">📊 Desglose de Costos (por unidad)</div>
            <table class="data-table">
                <tr>
                    <td>Costo de la Playera:</td>
                    <td>$' . number_format($data['playera']['costo'], 2) . '</td>
                </tr>
                <tr>
                    <td>Costo DTF:</td>
                    <td>$' . number_format($data['dtf']['costo_dtf'], 2) . '</td>
                </tr>
                <tr>
                    <td>Mano de Obra:</td>
                    <td>$' . number_format($data['costos']['mano_obra'], 2) . '</td>
                </tr>
                <tr class="highlight-row">
                    <td>Subtotal:</td>
                    <td>$' . number_format($data['costos']['subtotal'], 2) . '</td>
                </tr>
            </table>
        </div>

        <div class="price-section">
            <div class="price-title">💰 PRECIO FINAL</div>
            <div class="price-grid">
                <div class="price-item">
                    <div class="price-value">$' . number_format($data['precios']['unitario'], 2) . '</div>
                    <div class="price-label">Precio Unitario</div>
                </div>
                <div class="price-item">
                    <div class="price-value">' . $data['precios']['cantidad'] . '</div>
                    <div class="price-label">Cantidad</div>
                </div>
                <div class="price-item">
                    <div class="price-value">$' . number_format($data['precios']['total'], 2) . '</div>
                    <div class="price-label">Total</div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 15px; font-size: 11px; color: #666;">
                Margen de Ganancia: ' . number_format($data['costos']['margen_ganancia'], 1) . '% | 
                Ganancia por Unidad: $' . number_format($data['costos']['ganancia_por_unidad'], 2) . '
            </div>
        </div>

        <div class="footer">
            <p><strong>DT Studio</strong> - Sistema de Cotización DTF</p>
            <p>Este documento es generado automáticamente por el sistema interno de DT Studio</p>
            <p>Fecha de generación: ' . $fecha . ' ' . $hora . '</p>
        </div>
    </body>
    </html>';

    return $html;
}
?>
