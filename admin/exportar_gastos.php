<?php
require_once 'includes/paths.php';
require_once 'includes/sueldos_helper.php';
requireLogin();

if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

ensureSueldosTables($conn);

// Sin fechas en la URL = todos los gastos del sistema
$fecha_desde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde'] : null;
$fecha_hasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta'] : null;
$estado = $_GET['estado'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$exportar_todos = ($fecha_desde === null && $fecha_hasta === null);

$conditions = [];
$params = [];
$types = '';

if ($fecha_desde !== null) {
    $conditions[] = 'g.fecha_gasto >= ?';
    $params[] = $fecha_desde;
    $types .= 's';
}
if ($fecha_hasta !== null) {
    $conditions[] = 'g.fecha_gasto <= ?';
    $params[] = $fecha_hasta;
    $types .= 's';
}
if ($estado) {
    $conditions[] = 'g.estado = ?';
    $params[] = $estado;
    $types .= 's';
}
if ($categoria) {
    $conditions[] = 'g.categoria = ?';
    $params[] = $categoria;
    $types .= 's';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT g.*,
        u.username AS usuario_nombre,
        a.username AS aprobado_por_nombre,
        s.id AS sueldo_id,
        e.nombre AS empleado_nombre,
        s.periodo AS sueldo_periodo
    FROM gastos g
    LEFT JOIN usuarios u ON g.usuario_id = u.id
    LEFT JOIN usuarios a ON g.aprobado_por = a.id
    LEFT JOIN sueldos s ON s.gasto_id = g.id
    LEFT JOIN empleados e ON s.empleado_id = e.id
    $where
    ORDER BY g.fecha_gasto ASC, g.id ASC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$filename = $exportar_todos
    ? 'gastos_todos_' . date('Y-m-d') . '.csv'
    : 'gastos_' . $fecha_desde . '_' . $fecha_hasta . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($out, ['REPORTE DE GASTOS - DT STUDIO']);
if ($exportar_todos) {
    fputcsv($out, ['Alcance:', 'Todos los gastos del sistema (sin filtro de fechas)']);
} else {
    fputcsv($out, ['Período (fecha_gasto):', $fecha_desde ?? '—', 'a', $fecha_hasta ?? '—']);
}
fputcsv($out, ['Generado:', date('d/m/Y H:i')]);
fputcsv($out, []);

$total = 0;
fputcsv($out, [
    'ID', 'Fecha gasto', 'Concepto', 'Descripción', 'Monto', 'Categoría', 'Método pago',
    'Estado', 'Usuario', 'Aprobado por', 'Fecha aprobación',
    'Empleado (sueldo)', 'Período nómina', 'ID Sueldo', 'Observaciones', 'Comprobante',
]);

foreach ($gastos as $g) {
    $total += (float) $g['monto'];
    fputcsv($out, [
        $g['id'],
        date('d/m/Y', strtotime($g['fecha_gasto'])),
        $g['concepto'],
        $g['descripcion'],
        number_format($g['monto'], 2, '.', ''),
        $g['categoria'],
        $g['metodo_pago'],
        $g['estado'],
        $g['usuario_nombre'],
        $g['aprobado_por_nombre'],
        $g['fecha_aprobacion'] ? date('d/m/Y H:i', strtotime($g['fecha_aprobacion'])) : '',
        $g['empleado_nombre'] ?? '',
        $g['sueldo_periodo'] ?? '',
        $g['sueldo_id'] ?? '',
        $g['observaciones'],
        $g['comprobante'],
    ]);
}

fputcsv($out, []);
fputcsv($out, ['TOTAL GASTOS', '', '', '', number_format($total, 2, '.', '')]);

fclose($out);
exit;
