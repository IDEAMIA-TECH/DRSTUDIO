<?php
require_once 'includes/paths.php';
requireLogin();

if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Sin parámetros de fecha = todas las cotizaciones del sistema
$fecha_desde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde'] : null;
$fecha_hasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta'] : null;
$estado = $_GET['estado'] ?? '';
$exportar_todas = ($fecha_desde === null && $fecha_hasta === null);

function buildCotizacionExportWhere($estado, &$params, &$types) {
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($GLOBALS['fecha_desde_export'])) {
        $conditions[] = 'DATE(c.created_at) >= ?';
        $params[] = $GLOBALS['fecha_desde_export'];
        $types .= 's';
    }
    if (!empty($GLOBALS['fecha_hasta_export'])) {
        $conditions[] = 'DATE(c.created_at) <= ?';
        $params[] = $GLOBALS['fecha_hasta_export'];
        $types .= 's';
    }
    if ($estado) {
        $conditions[] = 'c.estado = ?';
        $params[] = $estado;
        $types .= 's';
    }

    return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
}

$GLOBALS['fecha_desde_export'] = $fecha_desde;
$GLOBALS['fecha_hasta_export'] = $fecha_hasta;

$params = [];
$types = '';
$where = buildCotizacionExportWhere($estado, $params, $types);

$baseJoin = "
FROM cotizaciones c
LEFT JOIN clientes cl ON c.cliente_id = cl.id
LEFT JOIN usuarios u ON c.usuario_id = u.id
LEFT JOIN (
    SELECT cotizacion_id, SUM(monto) AS total_pagado FROM pagos_cotizacion GROUP BY cotizacion_id
) pagos ON pagos.cotizacion_id = c.id
";

$sqlCatalogo = "SELECT c.id AS cotizacion_id, c.numero_cotizacion, c.created_at AS fecha_venta,
    c.fecha_vencimiento, c.estado, c.subtotal AS cotizacion_subtotal, c.descuento, c.total AS cotizacion_total,
    c.observaciones, c.notas, cl.nombre AS cliente_nombre, cl.empresa AS cliente_empresa,
    cl.email AS cliente_email, cl.telefono AS cliente_telefono, u.username AS creado_por,
    COALESCE(pagos.total_pagado, 0) AS total_pagado,
    (c.total - COALESCE(pagos.total_pagado, 0)) AS saldo_pendiente,
    'catalogo' AS tipo_linea, p.nombre AS producto_nombre, p.sku,
    COALESCE(v.talla, '') AS talla, ci.cantidad, ci.precio_unitario, ci.subtotal AS linea_subtotal
$baseJoin
INNER JOIN cotizacion_items ci ON ci.cotizacion_id = c.id
LEFT JOIN productos p ON ci.producto_id = p.id
LEFT JOIN variantes_producto v ON ci.variante_id = v.id
$where";

$sqlPersonalizado = "SELECT c.id, c.numero_cotizacion, c.created_at, c.fecha_vencimiento, c.estado,
    c.subtotal, c.descuento, c.total, c.observaciones, c.notas, cl.nombre, cl.empresa, cl.email, cl.telefono,
    u.username, COALESCE(pagos.total_pagado, 0), (c.total - COALESCE(pagos.total_pagado, 0)),
    'personalizado', cpp.nombre_producto, 'PERSONALIZADO', COALESCE(cpp.talla, ''),
    cpp.cantidad, cpp.precio_venta, cpp.subtotal
$baseJoin
INNER JOIN cotizacion_productos_personalizados cpp ON cpp.cotizacion_id = c.id
$where";

$filas = [];

foreach ([$sqlCatalogo, $sqlPersonalizado] as $sql) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $filas = array_merge($filas, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

usort($filas, function ($a, $b) {
    return strtotime($b['fecha_venta']) - strtotime($a['fecha_venta']);
});

$filename = $exportar_todas
    ? 'cotizaciones_todas_' . date('Y-m-d') . '.csv'
    : 'cotizaciones_' . $fecha_desde . '_' . $fecha_hasta . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($out, ['REPORTE DE COTIZACIONES - DT STUDIO']);
if ($exportar_todas) {
    fputcsv($out, ['Alcance:', 'Todas las cotizaciones del sistema (sin filtro de fechas)']);
} else {
    fputcsv($out, ['Período (fecha de venta / created_at):', $fecha_desde ?? '—', 'a', $fecha_hasta ?? '—']);
}
fputcsv($out, ['Generado:', date('d/m/Y H:i')]);
fputcsv($out, []);

fputcsv($out, [
    'ID Cotización', 'Número', 'Fecha venta', 'Fecha vencimiento', 'Estado',
    'Cliente', 'Empresa', 'Email', 'Teléfono', 'Creado por',
    'Subtotal cot.', 'Descuento', 'Total cot.', 'Total pagado', 'Saldo pendiente',
    'Tipo línea', 'Producto', 'SKU', 'Talla', 'Cantidad', 'Precio unit.', 'Subtotal línea',
    'Observaciones', 'Notas',
]);

foreach ($filas as $r) {
    fputcsv($out, [
        $r['cotizacion_id'],
        $r['numero_cotizacion'],
        date('d/m/Y H:i', strtotime($r['fecha_venta'])),
        !empty($r['fecha_vencimiento']) ? date('d/m/Y', strtotime($r['fecha_vencimiento'])) : '',
        $r['estado'],
        $r['cliente_nombre'] ?? '',
        $r['cliente_empresa'] ?? '',
        $r['cliente_email'] ?? '',
        $r['cliente_telefono'] ?? '',
        $r['creado_por'] ?? '',
        number_format($r['cotizacion_subtotal'], 2, '.', ''),
        number_format($r['descuento'], 2, '.', ''),
        number_format($r['cotizacion_total'], 2, '.', ''),
        number_format($r['total_pagado'], 2, '.', ''),
        number_format($r['saldo_pendiente'], 2, '.', ''),
        $r['tipo_linea'],
        $r['producto_nombre'],
        $r['sku'],
        $r['talla'],
        $r['cantidad'],
        number_format($r['precio_unitario'], 2, '.', ''),
        number_format($r['linea_subtotal'], 2, '.', ''),
        $r['observaciones'] ?? '',
        $r['notas'] ?? '',
    ]);
}

fclose($out);
exit;
