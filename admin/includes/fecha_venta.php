<?php
/**
 * Fecha de venta = cotizaciones.created_at (no fecha_pago ni fecha_vencimiento)
 */

function sqlFiltroFechaVenta($alias = 'c') {
    return "DATE({$alias}.created_at)";
}

function getTotalVentasPorFechaVenta($conn, $fechaDesde, $fechaHasta) {
    $sql = "SELECT COALESCE(SUM(v.monto), 0) AS total FROM (
        SELECT cd.subtotal AS monto, c.created_at, c.estado
        FROM cotizacion_detalles cd
        INNER JOIN cotizaciones c ON c.id = cd.cotizacion_id
        WHERE DATE(c.created_at) BETWEEN ? AND ?
        UNION ALL
        SELECT cpp.subtotal AS monto, c.created_at, c.estado
        FROM cotizacion_productos_personalizados cpp
        INNER JOIN cotizaciones c ON c.id = cpp.cotizacion_id
        WHERE DATE(c.created_at) BETWEEN ? AND ?
    ) v WHERE v.estado NOT IN ('cancelada', 'rechazada')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta);
    $stmt->execute();

    return (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}

function getVentasMensualesPorFechaVenta($conn, $fechaInicio) {
    $sql = "SELECT DATE_FORMAT(v.created_at, '%Y-%m') AS mes, COALESCE(SUM(v.monto), 0) AS total
        FROM (
            SELECT cd.subtotal AS monto, c.created_at, c.estado
            FROM cotizacion_detalles cd
            INNER JOIN cotizaciones c ON c.id = cd.cotizacion_id
            WHERE DATE(c.created_at) >= ?
            UNION ALL
            SELECT cpp.subtotal AS monto, c.created_at, c.estado
            FROM cotizacion_productos_personalizados cpp
            INNER JOIN cotizaciones c ON c.id = cpp.cotizacion_id
            WHERE DATE(c.created_at) >= ?
        ) v
        WHERE v.estado NOT IN ('cancelada', 'rechazada')
        GROUP BY DATE_FORMAT(v.created_at, '%Y-%m')
        ORDER BY mes ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fechaInicio, $fechaInicio);
    $stmt->execute();

    $result = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $result[$row['mes']] = (float) $row['total'];
    }
    return $result;
}
