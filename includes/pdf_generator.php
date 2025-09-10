<?php
/**
 * Generador unificado de PDFs para cotizaciones
 * Se usa tanto para correos como para exportación directa
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Genera un PDF de cotización con datos unificados
 */
function generateCotizacionPDF($cotizacionId, $outputPath = null) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Obtener datos completos de la cotización
        $cotizacion = getRecord('cotizaciones', $cotizacionId);
        if (!$cotizacion) {
            throw new Exception("Cotización no encontrada");
        }
        
        // Obtener items de la cotización
        $items = readRecords('cotizacion_items', ["cotizacion_id = $cotizacionId"], null, 'id ASC');
        
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
        if (!$cliente) {
            throw new Exception("Cliente no encontrado");
        }
        
        // Calcular subtotal
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['subtotal'];
        }
        
        // Preparar datos unificados para el PDF
        $pdfData = [
            'numero' => $cotizacion['numero_cotizacion'],
            'fecha' => date('d/m/Y H:i', strtotime($cotizacion['created_at'])),
            'cliente' => [
                'nombre' => $cliente['nombre'],
                'empresa' => $cliente['empresa'] ?? '',
                'email' => $cliente['email'] ?? '',
                'telefono' => $cliente['telefono'] ?? ''
            ],
            'items' => $items,
            'subtotal' => $subtotal,
            'descuento' => $cotizacion['descuento'] ?? 0,
            'total' => $subtotal - ($cotizacion['descuento'] ?? 0),
            'observaciones' => $cotizacion['observaciones'] ?? '',
            'estado' => $cotizacion['estado']
        ];
        
        // Generar HTML
        $html = createCotizacionHTML($pdfData);
        
        // Crear instancia de mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L', // Cambiar a landscape para acomodar imágenes grandes
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9
    ]);
        
        // Configurar metadatos
        $mpdf->SetTitle('Cotización ' . $cotizacion['numero_cotizacion']);
        $mpdf->SetAuthor('DT Studio');
        $mpdf->SetCreator('DT Studio Sistema');
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar archivo temporal si no se especifica ruta
        if (!$outputPath) {
            $outputPath = sys_get_temp_dir() . '/cotizacion_' . $cotizacion['numero_cotizacion'] . '_' . time() . '.pdf';
        }
        
        // Guardar PDF
        $mpdf->Output($outputPath, 'F');
        
        return $outputPath;
        
    } catch (Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Crea el HTML para la cotización
 */
function createCotizacionHTML($data) {
    $logoPath = '../assets/logo/LOGO.png';
    $logoExists = file_exists($logoPath);
    
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
                table-layout: fixed;
            }
            .items-table th, .items-table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
                vertical-align: middle;
            }
            .items-table th {
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
            }
        .product-image {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .no-image {
            width: 300px;
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #6c757d;
            border: 2px solid #dee2e6;
            font-weight: 500;
        }
            .items-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .totals {
                margin-left: auto;
                width: 300px;
                margin-top: 20px;
            }
            .totals table {
                width: 100%;
                border-collapse: collapse;
            }
            .totals td {
                padding: 8px;
                border: none;
            }
            .totals .total-row {
                background-color: #7B3F9F;
                color: white;
                font-weight: bold;
                font-size: 16px;
            }
            .observations {
                margin-top: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border-left: 4px solid #7B3F9F;
            }
            .observations h4 {
                margin: 0 0 10px 0;
                color: #7B3F9F;
            }
            .footer {
                margin-top: 50px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-info">
                <h1 class="company-name">DT Studio</h1>
                <p class="company-subtitle">Productos Promocionales y Merchandising</p>
            </div>
            ' . ($logoExists ? '<img src="' . $logoPath . '" alt="Logo DT Studio" class="logo">' : '') . '
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
                <p><strong>Estado:</strong> ' . ucfirst($data['estado']) . '</p>
            </div>
        </div>
        
        <table class="items-table">
        <thead>
            <tr>
                <th style="width: 320px;">Imagen</th>
                <th style="width: 200px;">Producto</th>
                <th style="width: 150px;">Variante</th>
                <th style="width: 80px;">Cantidad</th>
                <th style="width: 100px;">Precio Unit.</th>
                <th style="width: 100px;">Subtotal</th>
            </tr>
        </thead>
            <tbody>';
            
    foreach ($data['items'] as $item) {
        $varianteText = '';
        if (isset($item['variante']) && $item['variante']) {
            $varianteParts = array_filter([
                $item['variante']['talla'] ?? '',
                $item['variante']['color'] ?? '',
                $item['variante']['material'] ?? ''
            ]);
            $varianteText = implode(' - ', $varianteParts);
        }
        
        // Obtener imagen del producto
        $imagenHtml = '';
        if (!empty($item['producto']['imagen_principal'])) {
            // Usar ruta absoluta para el PDF
            $imagenPath = __DIR__ . '/../uploads/productos/' . $item['producto']['imagen_principal'];
            
            if (file_exists($imagenPath)) {
                try {
                    // Convertir imagen a base64 para incluirla en el PDF
                    $imageData = base64_encode(file_get_contents($imagenPath));
                    $imageType = pathinfo($imagenPath, PATHINFO_EXTENSION);
                    $imagenHtml = '<img src="data:image/' . $imageType . ';base64,' . $imageData . '" alt="' . htmlspecialchars($item['producto']['nombre']) . '" class="product-image">';
                } catch (Exception $e) {
                    error_log("Error convirtiendo imagen: " . $e->getMessage());
                    $imagenHtml = '<div class="no-image">Error imagen</div>';
                }
            } else {
                // Si no existe la imagen específica, usar la imagen placeholder
                $placeholderPath = __DIR__ . '/../uploads/productos/no-image.png';
                if (file_exists($placeholderPath)) {
                    try {
                        $imageData = base64_encode(file_get_contents($placeholderPath));
                        $imagenHtml = '<img src="data:image/png;base64,' . $imageData . '" alt="' . htmlspecialchars($item['producto']['nombre']) . '" class="product-image">';
                    } catch (Exception $e) {
                        $imagenHtml = '<div class="no-image">Sin imagen</div>';
                    }
                } else {
                    $imagenHtml = '<div class="no-image">Sin imagen</div>';
                }
            }
        } else {
            // Si no hay imagen definida, usar placeholder
            $placeholderPath = __DIR__ . '/../uploads/productos/no-image.png';
            if (file_exists($placeholderPath)) {
                try {
                    $imageData = base64_encode(file_get_contents($placeholderPath));
                    $imagenHtml = '<img src="data:image/png;base64,' . $imageData . '" alt="' . htmlspecialchars($item['producto']['nombre']) . '" class="product-image">';
                } catch (Exception $e) {
                    $imagenHtml = '<div class="no-image">Sin imagen</div>';
                }
            } else {
                $imagenHtml = '<div class="no-image">Sin imagen</div>';
            }
        }
        
        $html .= '
                <tr>
                    <td style="text-align: center; vertical-align: middle;">' . $imagenHtml . '</td>
                    <td>
                        <strong>' . htmlspecialchars($item['producto']['nombre']) . '</strong><br>
                        <small>SKU: ' . htmlspecialchars($item['producto']['sku']) . '</small>
                    </td>
                    <td>' . htmlspecialchars($varianteText ?: 'Sin variante') . '</td>
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
                    <td style="text-align: right; color: red;">-$' . number_format($data['descuento'], 2) . '</td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">$' . number_format($data['total'], 2) . '</td>
                </tr>
            </table>
        </div>';
        
    if ($data['observaciones']) {
        $html .= '
        <div class="observations">
            <h4>Observaciones</h4>
            <p>' . nl2br(htmlspecialchars($data['observaciones'])) . '</p>
        </div>';
    }
    
    $html .= '
        <div class="footer">
            <p>DT Studio - Productos Promocionales y Merchandising</p>
            <p>Gracias por su confianza en nuestros servicios</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
