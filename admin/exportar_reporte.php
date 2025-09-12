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
$reporte = $_GET['reporte'] ?? 'dashboard';

// Obtener datos según el tipo de reporte
if ($reporte == 'dashboard') {
    // Datos del dashboard
    $gastos_sql = "SELECT * FROM gastos WHERE fecha_gasto BETWEEN ? AND ? ORDER BY fecha_gasto DESC";
    $gastos_stmt = $conn->prepare($gastos_sql);
    $gastos_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $gastos_stmt->execute();
    $gastos_result = $gastos_stmt->get_result();
    $gastos = $gastos_result->fetch_all(MYSQLI_ASSOC);

    $cotizaciones_sql = "SELECT * FROM solicitudes_cotizacion WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC";
    $cotizaciones_stmt = $conn->prepare($cotizaciones_sql);
    $cotizaciones_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $cotizaciones_stmt->execute();
    $cotizaciones_result = $cotizaciones_stmt->get_result();
    $cotizaciones = $cotizaciones_result->fetch_all(MYSQLI_ASSOC);

    // Calcular métricas
    $total_gastos = 0;
    $gastos_por_categoria = [];
    $gastos_por_estado = ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0];

    foreach ($gastos as $gasto) {
        $total_gastos += $gasto['monto'];
        
        if (!isset($gastos_por_categoria[$gasto['categoria']])) {
            $gastos_por_categoria[$gasto['categoria']] = 0;
        }
        $gastos_por_categoria[$gasto['categoria']] += $gasto['monto'];
        
        $gastos_por_estado[$gasto['estado']]++;
    }

    $total_cotizaciones = count($cotizaciones);
    $cotizaciones_por_estado = ['pendiente' => 0, 'enviada' => 0, 'aceptada' => 0, 'rechazada' => 0];

    foreach ($cotizaciones as $cotizacion) {
        $cotizaciones_por_estado[$cotizacion['estado']]++;
    }

    $tasa_conversion = $total_cotizaciones > 0 ? ($cotizaciones_por_estado['aceptada'] / $total_cotizaciones) * 100 : 0;
}

if ($tipo == 'pdf') {
    // Generar PDF
    require_once '../includes/pdf_generator.php';
    
    $pdf = new PDFGenerator();
    $pdf->SetTitle('Reporte Financiero - DT Studio');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE FINANCIERO - DT STUDIO', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)), 0, 1, 'C');
    $pdf->Ln(10);
    
    if ($reporte == 'dashboard') {
        // Métricas principales
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'MÉTRICAS PRINCIPALES', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(60, 8, 'Total Gastos:', 0, 0);
        $pdf->Cell(30, 8, '$' . number_format($total_gastos, 2), 0, 1);
        
        $pdf->Cell(60, 8, 'Total Cotizaciones:', 0, 0);
        $pdf->Cell(30, 8, $total_cotizaciones, 0, 1);
        
        $pdf->Cell(60, 8, 'Tasa de Conversión:', 0, 0);
        $pdf->Cell(30, 8, number_format($tasa_conversion, 1) . '%', 0, 1);
        
        $pdf->Cell(60, 8, 'Gastos Pendientes:', 0, 0);
        $pdf->Cell(30, 8, $gastos_por_estado['pendiente'], 0, 1);
        
        $pdf->Ln(10);
        
        // Gastos por categoría
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'GASTOS POR CATEGORÍA', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        foreach ($gastos_por_categoria as $categoria => $monto) {
            $pdf->Cell(60, 8, ucfirst($categoria) . ':', 0, 0);
            $pdf->Cell(30, 8, '$' . number_format($monto, 2), 0, 1);
        }
        
        $pdf->Ln(10);
        
        // Estado de cotizaciones
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'ESTADO DE COTIZACIONES', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        foreach ($cotizaciones_por_estado as $estado => $cantidad) {
            $pdf->Cell(60, 8, ucfirst($estado) . ':', 0, 0);
            $pdf->Cell(30, 8, $cantidad, 0, 1);
        }
    }
    
    $pdf->Output('D', 'reporte_financiero_' . date('Y-m-d') . '.pdf');
    
} elseif ($tipo == 'excel') {
    // Generar Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_financiero_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($reporte == 'dashboard') {
        // Header
        fputcsv($output, ['REPORTE FINANCIERO - DT STUDIO']);
        fputcsv($output, ['Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))]);
        fputcsv($output, []);
        
        // Métricas principales
        fputcsv($output, ['MÉTRICAS PRINCIPALES']);
        fputcsv($output, ['Total Gastos', '$' . number_format($total_gastos, 2)]);
        fputcsv($output, ['Total Cotizaciones', $total_cotizaciones]);
        fputcsv($output, ['Tasa de Conversión', number_format($tasa_conversion, 1) . '%']);
        fputcsv($output, ['Gastos Pendientes', $gastos_por_estado['pendiente']]);
        fputcsv($output, []);
        
        // Gastos por categoría
        fputcsv($output, ['GASTOS POR CATEGORÍA']);
        foreach ($gastos_por_categoria as $categoria => $monto) {
            fputcsv($output, [ucfirst($categoria), '$' . number_format($monto, 2)]);
        }
        fputcsv($output, []);
        
        // Estado de cotizaciones
        fputcsv($output, ['ESTADO DE COTIZACIONES']);
        foreach ($cotizaciones_por_estado as $estado => $cantidad) {
            fputcsv($output, [ucfirst($estado), $cantidad]);
        }
    }
    
    fclose($output);
    exit;
}
?>
