<?php
/**
 * Funciones de conciliación bancaria (desde enero 2026)
 * Ingresos por fecha de venta (cotizaciones.created_at)
 */

require_once __DIR__ . '/fecha_venta.php';

const FECHA_INICIO_FINANZAS = '2026-01-01';

function ensureFinanzasBancoTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS finanzas_config (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
        saldo_inicial_monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_inicial_fecha DATE NOT NULL DEFAULT '2026-01-01',
        saldo_banco_actual DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_banco_fecha DATE NULL,
        notas TEXT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("INSERT IGNORE INTO finanzas_config (id, saldo_inicial_fecha) VALUES (1, '2026-01-01')");

    $conn->query("CREATE TABLE IF NOT EXISTS saldo_banco_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        monto DECIMAL(12,2) NOT NULL,
        fecha_registro DATE NOT NULL,
        notas TEXT NULL,
        usuario_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha_registro (fecha_registro)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function aplicarFechaMinimaFinanzas($fecha) {
    return ($fecha < FECHA_INICIO_FINANZAS) ? FECHA_INICIO_FINANZAS : $fecha;
}

function getFinanzasConfig($conn) {
    $result = $conn->query("SELECT * FROM finanzas_config WHERE id = 1 LIMIT 1");
    $config = $result ? $result->fetch_assoc() : null;

    if (!$config) {
        return [
            'saldo_inicial_monto' => 0,
            'saldo_inicial_fecha' => FECHA_INICIO_FINANZAS,
            'saldo_banco_actual' => 0,
            'saldo_banco_fecha' => date('Y-m-d'),
            'notas' => '',
        ];
    }

    return $config;
}

function getTotalesFinancieros($conn, $fechaDesde, $fechaHasta) {
    $fechaDesde = aplicarFechaMinimaFinanzas($fechaDesde);
    $fechaHasta = aplicarFechaMinimaFinanzas($fechaHasta);

    $totalIngresos = getTotalVentasPorFechaVenta($conn, $fechaDesde, $fechaHasta);

    $egresosSql = "SELECT COALESCE(SUM(monto), 0) AS total
        FROM gastos
        WHERE fecha_gasto BETWEEN ? AND ?
        AND estado IN ('aprobado', 'pendiente')";
    $egresosStmt = $conn->prepare($egresosSql);
    $egresosStmt->bind_param('ss', $fechaDesde, $fechaHasta);
    $egresosStmt->execute();
    $totalEgresos = (float) ($egresosStmt->get_result()->fetch_assoc()['total'] ?? 0);

    return [
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
        'total_ingresos' => $totalIngresos,
        'total_egresos' => $totalEgresos,
        'flujo_neto' => $totalIngresos - $totalEgresos,
    ];
}

function getResumenMensualFinanzas($conn) {
    $resumen = [];
    $fechaInicio = FECHA_INICIO_FINANZAS;

    $ventasMensuales = getVentasMensualesPorFechaVenta($conn, $fechaInicio);
    foreach ($ventasMensuales as $mes => $total) {
        $resumen[$mes]['ingresos'] = $total;
    }

    $egresosSql = "SELECT DATE_FORMAT(fecha_gasto, '%Y-%m') AS mes, COALESCE(SUM(monto), 0) AS total
        FROM gastos
        WHERE fecha_gasto >= ?
        AND estado IN ('aprobado', 'pendiente')
        GROUP BY DATE_FORMAT(fecha_gasto, '%Y-%m')
        ORDER BY mes ASC";
    $stmt = $conn->prepare($egresosSql);
    $stmt->bind_param('s', $fechaInicio);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $resumen[$row['mes']]['egresos'] = (float) $row['total'];
    }

    ksort($resumen);

    $acumulado = 0;
    $filas = [];
    foreach ($resumen as $mes => $datos) {
        $ingresos = $datos['ingresos'] ?? 0;
        $egresos = $datos['egresos'] ?? 0;
        $neto = $ingresos - $egresos;
        $acumulado += $neto;
        $filas[] = [
            'mes' => $mes,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'neto' => $neto,
            'acumulado' => $acumulado,
        ];
    }

    return $filas;
}

function getMovimientosFinancieros($conn, $fechaDesde, $fechaHasta, $limite = 30) {
    $fechaDesde = aplicarFechaMinimaFinanzas($fechaDesde);
    $fechaHasta = aplicarFechaMinimaFinanzas($fechaHasta);

    $sql = "(
        SELECT 'ingreso' AS tipo, c.total AS monto, c.created_at AS fecha, '' AS metodo_pago,
            CONCAT('Venta ', c.numero_cotizacion) AS concepto,
            c.estado AS referencia
        FROM cotizaciones c
        WHERE DATE(c.created_at) BETWEEN ? AND ?
        AND c.estado NOT IN ('cancelada', 'rechazada')
    ) UNION ALL (
        SELECT 'egreso' AS tipo, g.monto, g.fecha_gasto AS fecha, g.metodo_pago,
            g.concepto, g.estado AS referencia
        FROM gastos g
        WHERE g.fecha_gasto BETWEEN ? AND ?
        AND g.estado IN ('aprobado', 'pendiente')
    )
    ORDER BY fecha DESC
    LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta, $limite);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getHistorialSaldoBanco($conn, $limite = 10) {
    $sql = "SELECT h.*, u.username
        FROM saldo_banco_historial h
        LEFT JOIN usuarios u ON u.id = h.usuario_id
        ORDER BY h.created_at DESC
        LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getConciliacionBancaria($conn, $fechaDesde, $fechaHasta) {
    $config = getFinanzasConfig($conn);
    $totales = getTotalesFinancieros($conn, FECHA_INICIO_FINANZAS, $fechaHasta);

    $saldoInicial = (float) ($config['saldo_inicial_monto'] ?? 0);
    $saldoCalculado = $saldoInicial + $totales['total_ingresos'] - $totales['total_egresos'];
    $saldoBanco = (float) ($config['saldo_banco_actual'] ?? 0);
    $diferencia = $saldoBanco - $saldoCalculado;
    $cuadra = abs($diferencia) < 0.01;

    $totalesPeriodo = getTotalesFinancieros($conn, $fechaDesde, $fechaHasta);

    $fechaAcumuladoDesde = FECHA_INICIO_FINANZAS;
    $fechaAcumuladoHasta = aplicarFechaMinimaFinanzas($fechaHasta);
    $fechaCorteBanco = $config['saldo_banco_fecha'] ?? null;
    if (empty($fechaCorteBanco) || $fechaCorteBanco === '0000-00-00') {
        $fechaCorteBanco = null;
    }

    return [
        'config' => $config,
        'saldo_inicial' => $saldoInicial,
        'saldo_inicial_fecha' => $config['saldo_inicial_fecha'] ?? FECHA_INICIO_FINANZAS,
        'total_ingresos_acumulado' => $totales['total_ingresos'],
        'total_egresos_acumulado' => $totales['total_egresos'],
        'saldo_calculado' => $saldoCalculado,
        'saldo_banco' => $saldoBanco,
        'saldo_banco_fecha' => $fechaCorteBanco,
        'diferencia' => $diferencia,
        'cuadra' => $cuadra,
        'totales_periodo' => $totalesPeriodo,
        'fecha_acumulado_desde' => $fechaAcumuladoDesde,
        'fecha_acumulado_hasta' => $fechaAcumuladoHasta,
        'fecha_periodo_desde' => $totalesPeriodo['fecha_desde'],
        'fecha_periodo_hasta' => $totalesPeriodo['fecha_hasta'],
    ];
}
