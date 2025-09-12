<?php
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

$tipo = $_GET['tipo'] ?? 'pdf';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$producto_id = $_GET['producto_id'] ?? '';
$categoria_id = $_GET['categoria_id'] ?? '';

// Construir condiciones de búsqueda
$conditions = ["c.created_at BETWEEN ? AND ?"];
$params = [$fecha_desde, $fecha_hasta];

if ($producto_id) {
    $conditions[] = "cd.producto_id = ?";
    $params[] = $producto_id;
}

if ($categoria_id) {
    $conditions[] = "p.categoria_id = ?";
    $params[] = $categoria_id;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Consulta principal para obtener detalles de ganancias
$sql = "SELECT 
    cd.*,
    c.cliente_nombre,
    c.cliente_email,
    c.estado as cotizacion_estado,
    c.created_at as fecha_cotizacion,
    p.nombre as producto_nombre,
    p.sku,
    cat.nombre as categoria_nombre
FROM cotizacion_detalles cd
LEFT JOIN solicitudes_cotizacion c ON cd.cotizacion_id = c.id
LEFT JOIN productos p ON cd.producto_id = p.id
LEFT JOIN categorias cat ON p.categoria_id = cat.id
$whereClause
ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$detalles = $result->fetch_all(MYSQLI_ASSOC);

// Calcular métricas de ganancias
$total_ventas = 0;
$total_costos = 0;
$total_ganancia = 0;
$ganancia_por_categoria = [];

foreach ($detalles as $detalle) {
    $total_ventas += $detalle['subtotal'];
    $total_costos += $detalle['costo_total'];
    $total_ganancia += $detalle['ganancia'];
    
    // Por categoría
    $categoria = $detalle['categoria_nombre'] ?: 'Sin categoría';
    if (!isset($ganancia_por_categoria[$categoria])) {
        $ganancia_por_categoria[$categoria] = [
            'ventas' => 0,
            'costos' => 0,
            'ganancia' => 0
        ];
    }
    $ganancia_por_categoria[$categoria]['ventas'] += $detalle['subtotal'];
    $ganancia_por_categoria[$categoria]['costos'] += $detalle['costo_total'];
    $ganancia_por_categoria[$categoria]['ganancia'] += $detalle['ganancia'];
}

$margen_ganancia_promedio = $total_ventas > 0 ? ($total_ganancia / $total_ventas) * 100 : 0;

// Obtener gastos operacionales del período
$gastos_sql = "SELECT * FROM gastos WHERE fecha_gasto BETWEEN ? AND ? AND estado = 'aprobado' ORDER BY fecha_gasto DESC";
$gastos_stmt = $conn->prepare($gastos_sql);
$gastos_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$gastos_stmt->execute();
$gastos_result = $gastos_stmt->get_result();
$gastos = $gastos_result->fetch_all(MYSQLI_ASSOC);

// Calcular métricas de gastos
$total_gastos_operacionales = 0;
$gastos_por_categoria = [];

foreach ($gastos as $gasto) {
    $total_gastos_operacionales += $gasto['monto'];
    
    // Por categoría de gasto
    $categoria_gasto = $gasto['categoria'] ?: 'Sin categoría';
    if (!isset($gastos_por_categoria[$categoria_gasto])) {
        $gastos_por_categoria[$categoria_gasto] = 0;
    }
    $gastos_por_categoria[$categoria_gasto] += $gasto['monto'];
}

// Calcular ganancia neta
$ganancia_neta = $total_ganancia - $total_gastos_operacionales;
$margen_neto = $total_ventas > 0 ? ($ganancia_neta / $total_ventas) * 100 : 0;

if ($tipo == 'pdf') {
    // Generar PDF
    require_once '../includes/pdf_generator.php';
    
    $pdf = new PDFGenerator();
    $pdf->SetTitle('Reporte de Ganancias - DT Studio');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE DE GANANCIAS - DT STUDIO', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Métricas principales
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'MÉTRICAS PRINCIPALES', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(60, 8, 'Total Ventas:', 0, 0);
    $pdf->Cell(30, 8, '$' . number_format($total_ventas, 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Costos Productos:', 0, 0);
    $pdf->Cell(30, 8, '$' . number_format($total_costos, 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Gastos Operacionales:', 0, 0);
    $pdf->Cell(30, 8, '$' . number_format($total_gastos_operacionales, 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Ganancia Bruta:', 0, 0);
    $pdf->Cell(30, 8, '$' . number_format($total_ganancia, 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Ganancia Neta:', 0, 0);
    $pdf->Cell(30, 8, '$' . number_format($ganancia_neta, 2), 0, 1);
    
    $pdf->Cell(60, 8, 'Margen Neto:', 0, 0);
    $pdf->Cell(30, 8, number_format($margen_neto, 1) . '%', 0, 1);
    
    $pdf->Ln(10);
    
    // Ganancias por categoría
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'GANANCIAS POR CATEGORÍA', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    foreach ($ganancia_por_categoria as $categoria => $data) {
        $pdf->Cell(60, 8, $categoria . ':', 0, 0);
        $pdf->Cell(30, 8, '$' . number_format($data['ganancia'], 2), 0, 1);
    }
    
    $pdf->Ln(10);
    
    // Gastos operacionales por categoría
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'GASTOS OPERACIONALES POR CATEGORÍA', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    foreach ($gastos_por_categoria as $categoria => $monto) {
        $pdf->Cell(60, 8, $categoria . ':', 0, 0);
        $pdf->Cell(30, 8, '$' . number_format($monto, 2), 0, 1);
    }
    
    $pdf->Ln(10);
    
    // Detalle de cotizaciones
    if (!empty($detalles)) {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'DETALLE DE COTIZACIONES', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        // Headers de tabla
        $pdf->Cell(20, 8, 'Cotización', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Cliente', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Producto', 1, 0, 'C');
        $pdf->Cell(15, 8, 'Cant.', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Ganancia', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Margen %', 1, 1, 'C');
        
        foreach ($detalles as $detalle) {
            $pdf->Cell(20, 8, '#' . $detalle['cotizacion_id'], 1, 0, 'C');
            $pdf->Cell(40, 8, substr($detalle['cliente_nombre'], 0, 20), 1, 0);
            $pdf->Cell(50, 8, substr($detalle['producto_nombre'], 0, 25), 1, 0);
            $pdf->Cell(15, 8, $detalle['cantidad'], 1, 0, 'C');
            $pdf->Cell(20, 8, '$' . number_format($detalle['ganancia'], 2), 1, 0, 'R');
            $pdf->Cell(20, 8, number_format($detalle['margen_ganancia'], 1) . '%', 1, 1, 'R');
        }
    }
    
    $pdf->Output('D', 'reporte_ganancias_' . date('Y-m-d') . '.pdf');
    
} elseif ($tipo == 'excel') {
    // Generar Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_ganancias_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['REPORTE DE GANANCIAS - DT STUDIO']);
    fputcsv($output, ['Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))]);
    fputcsv($output, []);
    
    // Métricas principales
    fputcsv($output, ['MÉTRICAS PRINCIPALES']);
    fputcsv($output, ['Total Ventas', '$' . number_format($total_ventas, 2)]);
    fputcsv($output, ['Costos Productos', '$' . number_format($total_costos, 2)]);
    fputcsv($output, ['Gastos Operacionales', '$' . number_format($total_gastos_operacionales, 2)]);
    fputcsv($output, ['Ganancia Bruta', '$' . number_format($total_ganancia, 2)]);
    fputcsv($output, ['Ganancia Neta', '$' . number_format($ganancia_neta, 2)]);
    fputcsv($output, ['Margen Neto', number_format($margen_neto, 1) . '%']);
    fputcsv($output, []);
    
    // Ganancias por categoría
    fputcsv($output, ['GANANCIAS POR CATEGORÍA']);
    foreach ($ganancia_por_categoria as $categoria => $data) {
        fputcsv($output, [$categoria, '$' . number_format($data['ganancia'], 2)]);
    }
    fputcsv($output, []);
    
    // Gastos operacionales por categoría
    fputcsv($output, ['GASTOS OPERACIONALES POR CATEGORÍA']);
    foreach ($gastos_por_categoria as $categoria => $monto) {
        fputcsv($output, [$categoria, '$' . number_format($monto, 2)]);
    }
    fputcsv($output, []);
    
    // Detalle de cotizaciones
    if (!empty($detalles)) {
        fputcsv($output, ['DETALLE DE COTIZACIONES']);
        fputcsv($output, ['Cotización', 'Cliente', 'Producto', 'SKU', 'Cantidad', 'Precio Unit.', 'Costo Unit.', 'Subtotal', 'Costo Total', 'Ganancia', 'Margen %']);
        
        foreach ($detalles as $detalle) {
            fputcsv($output, [
                '#' . $detalle['cotizacion_id'],
                $detalle['cliente_nombre'],
                $detalle['producto_nombre'],
                $detalle['sku'],
                $detalle['cantidad'],
                '$' . number_format($detalle['precio_unitario'], 2),
                '$' . number_format($detalle['costo_unitario'], 2),
                '$' . number_format($detalle['subtotal'], 2),
                '$' . number_format($detalle['costo_total'], 2),
                '$' . number_format($detalle['ganancia'], 2),
                number_format($detalle['margen_ganancia'], 1) . '%'
            ]);
        }
    }
    
    fclose($output);
    exit;
}
?>
