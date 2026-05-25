<?php
$pageTitle = 'Reportes y Análisis';
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/finanzas_banco.php';
ensureFinanzasBancoTables($conn);

// Solo datos desde enero 2026
$fecha_desde = aplicarFechaMinimaFinanzas($_GET['fecha_desde'] ?? FECHA_INICIO_FINANZAS);
$fecha_hasta = aplicarFechaMinimaFinanzas($_GET['fecha_hasta'] ?? date('Y-m-d'));
if ($fecha_desde > $fecha_hasta) {
    $fecha_hasta = $fecha_desde;
}
$tipo_reporte = $_GET['tipo_reporte'] ?? 'dashboard';

$conciliacion = getConciliacionBancaria($conn, $fecha_desde, $fecha_hasta);
$resumenMensual = getResumenMensualFinanzas($conn);
$movimientosFinancieros = getMovimientosFinancieros($conn, $fecha_desde, $fecha_hasta, 25);
$historialSaldoBanco = getHistorialSaldoBanco($conn, 8);

// Obtener datos de gastos del período
$gastos_sql = "SELECT * FROM gastos WHERE DATE(fecha_gasto) BETWEEN ? AND ? ORDER BY fecha_gasto DESC";
$gastos_stmt = $conn->prepare($gastos_sql);
$gastos_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$gastos_stmt->execute();
$gastos_result = $gastos_stmt->get_result();
$gastos = $gastos_result->fetch_all(MYSQLI_ASSOC);

// Obtener datos de cotizaciones del período
$cotizaciones_sql = "SELECT * FROM cotizaciones WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC";
$cotizaciones_stmt = $conn->prepare($cotizaciones_sql);
$cotizaciones_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$cotizaciones_stmt->execute();
$cotizaciones_result = $cotizaciones_stmt->get_result();
$cotizaciones = $cotizaciones_result->fetch_all(MYSQLI_ASSOC);

// Calcular métricas de gastos
$total_gastos = 0;
$gastos_por_categoria = [];
$gastos_por_estado = ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0];

foreach ($gastos as $gasto) {
    $total_gastos += $gasto['monto'];
    
    // Por categoría
    if (!isset($gastos_por_categoria[$gasto['categoria']])) {
        $gastos_por_categoria[$gasto['categoria']] = 0;
    }
    $gastos_por_categoria[$gasto['categoria']] += $gasto['monto'];
    
    // Por estado
    $gastos_por_estado[$gasto['estado']]++;
}

// Calcular métricas de cotizaciones
$total_cotizaciones = count($cotizaciones);
$cotizaciones_por_estado = [
    'pendiente' => 0, 
    'enviada' => 0, 
    'aceptada' => 0, 
    'rechazada' => 0, 
    'cancelada' => 0, 
    'en_espera_deposito' => 0, 
    'pagada' => 0, 
    'entregada' => 0
];

foreach ($cotizaciones as $cotizacion) {
    $estado = $cotizacion['estado'];
    if (isset($cotizaciones_por_estado[$estado])) {
        $cotizaciones_por_estado[$estado]++;
    } else {
        // Si el estado no está en nuestro array, lo agregamos
        $cotizaciones_por_estado[$estado] = 1;
    }
}

// Calcular conversión (cotizaciones entregadas / total cotizaciones)
$tasa_conversion = $total_cotizaciones > 0 ? (($cotizaciones_por_estado['entregada'] ?? 0) / $total_cotizaciones) * 100 : 0;

// Obtener gastos mensuales desde enero 2026 para gráfico
$gastos_mensuales_sql = "SELECT 
    DATE_FORMAT(fecha_gasto, '%Y-%m') as mes,
    SUM(monto) as total
    FROM gastos 
    WHERE fecha_gasto >= '" . FECHA_INICIO_FINANZAS . "'
    AND estado = 'aprobado'
    GROUP BY DATE_FORMAT(fecha_gasto, '%Y-%m')
    ORDER BY mes ASC";
$gastos_mensuales_result = $conn->query($gastos_mensuales_sql);
$gastos_mensuales = $gastos_mensuales_result->fetch_all(MYSQLI_ASSOC);

// Obtener cotizaciones mensuales desde enero 2026 para gráfico
$cotizaciones_mensuales_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as mes,
    COUNT(*) as total
    FROM cotizaciones 
    WHERE DATE(created_at) >= '" . FECHA_INICIO_FINANZAS . "'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mes ASC";
$cotizaciones_mensuales_result = $conn->query($cotizaciones_mensuales_sql);
$cotizaciones_mensuales = $cotizaciones_mensuales_result->fetch_all(MYSQLI_ASSOC);

$categorias = ['oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'otros'];

// Obtener datos de ganancias del período (incluyendo productos personalizados)
$ganancias_sql = "SELECT 
    SUM(COALESCE(cd.subtotal, 0) + COALESCE(cpp.subtotal, 0)) as total_ventas,
    SUM(COALESCE(cd.costo_total, 0) + COALESCE(cpp.costo_total, 0)) as total_costos,
    SUM(COALESCE(cd.ganancia, 0) + COALESCE(cpp.ganancia, 0)) as total_ganancia
FROM cotizaciones c
LEFT JOIN cotizacion_detalles cd ON c.id = cd.cotizacion_id
LEFT JOIN cotizacion_productos_personalizados cpp ON c.id = cpp.cotizacion_id
WHERE DATE(c.created_at) BETWEEN ? AND ?";
$ganancias_stmt = $conn->prepare($ganancias_sql);
$ganancias_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$ganancias_stmt->execute();
$ganancias_result = $ganancias_stmt->get_result();
$ganancias = $ganancias_result->fetch_assoc();

// Obtener gastos operacionales del período
$gastos_operacionales_sql = "SELECT SUM(monto) as total FROM gastos WHERE fecha_gasto BETWEEN ? AND ? AND estado IN ('aprobado', 'pendiente')";
$gastos_operacionales_stmt = $conn->prepare($gastos_operacionales_sql);
$gastos_operacionales_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$gastos_operacionales_stmt->execute();
$gastos_operacionales_result = $gastos_operacionales_stmt->get_result();
$gastos_operacionales = $gastos_operacionales_result->fetch_assoc();

// Calcular ganancia neta
$ganancia_neta = ($ganancias['total_ganancia'] ?? 0) - ($gastos_operacionales['total'] ?? 0);

// Obtener ganancias de los últimos 6 meses para gráfico (incluyendo productos personalizados)
$ganancias_mensuales_sql = "SELECT 
    DATE_FORMAT(c.created_at, '%Y-%m') as mes,
    SUM(COALESCE(cd.subtotal, 0) + COALESCE(cpp.subtotal, 0)) as total_ventas,
    SUM(COALESCE(cd.costo_total, 0) + COALESCE(cpp.costo_total, 0)) as total_costos,
    SUM(COALESCE(cd.ganancia, 0) + COALESCE(cpp.ganancia, 0)) as total_ganancia
FROM cotizaciones c
LEFT JOIN cotizacion_detalles cd ON c.id = cd.cotizacion_id
LEFT JOIN cotizacion_productos_personalizados cpp ON c.id = cpp.cotizacion_id
WHERE DATE(c.created_at) >= '" . FECHA_INICIO_FINANZAS . "'
GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
ORDER BY mes ASC";
$ganancias_mensuales_result = $conn->query($ganancias_mensuales_sql);
$ganancias_mensuales = $ganancias_mensuales_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reportes y Análisis</h1>
            <p class="text-muted">Análisis financiero desde enero 2026</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" onclick="exportarPDF()">
                <i class="fas fa-file-pdf me-2"></i>Exportar PDF
            </button>
            <button class="btn btn-primary" onclick="exportarExcel()">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Período
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" min="<?php echo FECHA_INICIO_FINANZAS; ?>" value="<?php echo $fecha_desde; ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" min="<?php echo FECHA_INICIO_FINANZAS; ?>" value="<?php echo $fecha_hasta; ?>">
                </div>
                <div class="col-md-3">
                    <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                    <select class="form-select" id="tipo_reporte" name="tipo_reporte">
                        <option value="dashboard" <?php echo $tipo_reporte == 'dashboard' ? 'selected' : ''; ?>>Dashboard General</option>
                        <option value="gastos" <?php echo $tipo_reporte == 'gastos' ? 'selected' : ''; ?>>Reporte de Gastos</option>
                        <option value="cotizaciones" <?php echo $tipo_reporte == 'cotizaciones' ? 'selected' : ''; ?>>Reporte de Cotizaciones</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="reportes_ganancias.php" class="btn btn-success">
                        <i class="fas fa-chart-line me-2"></i>Reporte de Ganancias
                    </a>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Generar
                    </button>
                    <a href="reportes.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Conciliación bancaria -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-university me-2"></i>Conciliación Bancaria
            </h5>
            <span class="badge bg-light text-primary">Desde <?php echo date('d/m/Y', strtotime(FECHA_INICIO_FINANZAS)); ?></span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h6 class="text-muted mb-3">Registrar saldos (manual)</h6>
                    <form id="formSaldosBanco">
                        <div class="mb-3">
                            <label for="saldo_inicial_monto" class="form-label">Saldo inicial en banco (01/01/2026)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="saldo_inicial_monto" name="saldo_inicial_monto"
                                       step="0.01" min="0" required
                                       value="<?php echo number_format($conciliacion['saldo_inicial'], 2, '.', ''); ?>">
                            </div>
                            <div class="form-text">Cuánto tenían en la cuenta al arrancar el año.</div>
                        </div>
                        <div class="mb-3">
                            <label for="saldo_banco_actual" class="form-label">Saldo actual en banco (estado de cuenta)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="saldo_banco_actual" name="saldo_banco_actual"
                                       step="0.01" min="0" required
                                       value="<?php echo number_format($conciliacion['saldo_banco'], 2, '.', ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="saldo_banco_fecha" class="form-label">Fecha del saldo en banco</label>
                            <input type="date" class="form-control" id="saldo_banco_fecha" name="saldo_banco_fecha"
                                   min="<?php echo FECHA_INICIO_FINANZAS; ?>"
                                   value="<?php echo $conciliacion['config']['saldo_banco_fecha'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="notas_banco" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas_banco" name="notas" rows="2"
                                      placeholder="Ej. BBVA cuenta principal, corte del día"><?php echo htmlspecialchars($conciliacion['config']['notas'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Guardar saldos
                        </button>
                    </form>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <small>Ingresos acumulados</small>
                                    <h4 class="mb-0">$<?php echo number_format($conciliacion['total_ingresos_acumulado'], 2); ?></h4>
                                    <small>Ventas por fecha de cotización</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white h-100">
                                <div class="card-body">
                                    <small>Egresos acumulados</small>
                                    <h4 class="mb-0">$<?php echo number_format($conciliacion['total_egresos_acumulado'], 2); ?></h4>
                                    <small>Gastos aprobados y pendientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <small>En el período filtrado</small>
                                    <h4 class="mb-0">$<?php echo number_format($conciliacion['totales_periodo']['flujo_neto'], 2); ?></h4>
                                    <small>Ingresos − egresos</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td>Saldo inicial (01/01/2026)</td>
                                    <td class="text-end fw-bold">$<?php echo number_format($conciliacion['saldo_inicial'], 2); ?></td>
                                </tr>
                                <tr class="table-success">
                                    <td>+ Ventas (fecha de venta / created_at)</td>
                                    <td class="text-end">$<?php echo number_format($conciliacion['total_ingresos_acumulado'], 2); ?></td>
                                </tr>
                                <tr class="table-danger">
                                    <td>− Egresos (gastos)</td>
                                    <td class="text-end">$<?php echo number_format($conciliacion['total_egresos_acumulado'], 2); ?></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>= Saldo según libros (calculado)</strong></td>
                                    <td class="text-end fw-bold">$<?php echo number_format($conciliacion['saldo_calculado'], 2); ?></td>
                                </tr>
                                <tr class="table-warning">
                                    <td><strong>Saldo en banco (manual)</strong></td>
                                    <td class="text-end fw-bold">$<?php echo number_format($conciliacion['saldo_banco'], 2); ?></td>
                                </tr>
                                <tr class="<?php echo $conciliacion['cuadra'] ? 'table-success' : 'table-danger'; ?>">
                                    <td><strong>Diferencia (banco − libros)</strong></td>
                                    <td class="text-end fw-bold">
                                        <?php echo $conciliacion['diferencia'] >= 0 ? '+' : ''; ?>$<?php echo number_format($conciliacion['diferencia'], 2); ?>
                                        <?php if ($conciliacion['cuadra']): ?>
                                            <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Cuadra</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger ms-2"><i class="fas fa-exclamation-triangle"></i> No cuadra</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$conciliacion['cuadra']): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Hay una diferencia de <strong>$<?php echo number_format(abs($conciliacion['diferencia']), 2); ?></strong>.
                        Revise ventas no registradas, gastos pendientes o el saldo inicial del 01/01/2026.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        El saldo en banco coincide con ingresos y egresos registrados desde enero 2026.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-4">
                <div class="col-lg-7">
                    <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Resumen mensual (2026)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Egresos</th>
                                    <th class="text-end">Neto</th>
                                    <th class="text-end">Flujo acumulado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resumenMensual)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Sin movimientos en 2026</td></tr>
                                <?php else: ?>
                                <?php foreach ($resumenMensual as $fila): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($fila['mes'] . '-01')); ?></td>
                                    <td class="text-end text-success">$<?php echo number_format($fila['ingresos'], 2); ?></td>
                                    <td class="text-end text-danger">$<?php echo number_format($fila['egresos'], 2); ?></td>
                                    <td class="text-end">$<?php echo number_format($fila['neto'], 2); ?></td>
                                    <td class="text-end fw-bold">$<?php echo number_format($fila['acumulado'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-5">
                    <h6 class="mb-3"><i class="fas fa-history me-2"></i>Últimos registros de saldo en banco</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Saldo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historialSaldoBanco)): ?>
                                <tr><td colspan="3" class="text-muted text-center">Sin historial aún</td></tr>
                                <?php else: ?>
                                <?php foreach ($historialSaldoBanco as $h): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($h['fecha_registro'])); ?></td>
                                    <td class="text-end">$<?php echo number_format($h['monto'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($h['username'] ?? '—'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <h6 class="mt-4 mb-3"><i class="fas fa-exchange-alt me-2"></i>Movimientos del período (<?php echo date('d/m/Y', strtotime($fecha_desde)); ?> – <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>)</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientosFinancieros)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No hay movimientos en este período</td></tr>
                        <?php else: ?>
                        <?php foreach ($movimientosFinancieros as $mov): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></td>
                            <td>
                                <?php if ($mov['tipo'] === 'ingreso'): ?>
                                    <span class="badge bg-success">Ingreso</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Egreso</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($mov['concepto']); ?></td>
                            <td><?php echo ucfirst($mov['metodo_pago'] ?? ''); ?></td>
                            <td class="text-end fw-bold <?php echo $mov['tipo'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $mov['tipo'] === 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($mov['monto'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($tipo_reporte == 'dashboard'): ?>
    <!-- Dashboard General -->
    <div class="row mb-4">
        <!-- Métricas Principales -->
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($total_gastos, 2); ?></h4>
                            <p class="card-text">Total Gastos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $total_cotizaciones; ?></h4>
                            <p class="card-text">Cotizaciones</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-invoice fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo number_format($tasa_conversion, 1); ?>%</h4>
                            <p class="card-text">Tasa Conversión</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $gastos_por_estado['pendiente']; ?></h4>
                            <p class="card-text">Gastos Pendientes</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($ganancias['total_ganancia'] ?? 0, 2); ?></h4>
                            <p class="card-text">Ganancia Bruta</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-pie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card <?php echo $ganancia_neta >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($ganancia_neta, 2); ?></h4>
                            <p class="card-text">Ganancia Neta</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gastos por Categoría</h5>
                </div>
                <div class="card-body">
                    <canvas id="gastosCategoriaChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Estado de Cotizaciones</h5>
                </div>
                <div class="card-body">
                    <canvas id="cotizacionesEstadoChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gastos Mensuales (desde ene 2026)</h5>
                </div>
                <div class="card-body">
                    <canvas id="gastosMensualesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cotizaciones Mensuales (desde ene 2026)</h5>
                </div>
                <div class="card-body">
                    <canvas id="cotizacionesMensualesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ganancias del Mes</h5>
                </div>
                <div class="card-body">
                    <canvas id="gananciasMensualesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tipo_reporte == 'gastos'): ?>
    <!-- Reporte de Gastos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Reporte Detallado de Gastos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Método Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gastos as $gasto): ?>
                        <tr>
                            <td>#<?php echo $gasto['id']; ?></td>
                            <td><?php echo htmlspecialchars($gasto['concepto']); ?></td>
                            <td class="text-success fw-bold">$<?php echo number_format($gasto['monto'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($gasto['categoria']); ?></span></td>
                            <td>
                                <?php
                                $estado_class = '';
                                switch ($gasto['estado']) {
                                    case 'pendiente': $estado_class = 'bg-warning'; break;
                                    case 'aprobado': $estado_class = 'bg-success'; break;
                                    case 'rechazado': $estado_class = 'bg-danger'; break;
                                }
                                ?>
                                <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($gasto['estado']); ?></span>
                            </td>
                            <td><?php echo ucfirst($gasto['metodo_pago']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tipo_reporte == 'cotizaciones'): ?>
    <!-- Reporte de Cotizaciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Reporte de Cotizaciones</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número</th>
                            <th>Cliente ID</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                        <tr>
                            <td>#<?php echo $cotizacion['id']; ?></td>
                            <td><?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></td>
                            <td><?php echo $cotizacion['cliente_id']; ?></td>
                            <td>$<?php echo number_format($cotizacion['subtotal'], 2); ?></td>
                            <td class="fw-bold">$<?php echo number_format($cotizacion['total'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cotizacion['created_at'])); ?></td>
                            <td>
                                <?php
                                $estado_class = '';
                                switch ($cotizacion['estado']) {
                                    case 'pendiente': $estado_class = 'bg-warning'; break;
                                    case 'enviada': $estado_class = 'bg-info'; break;
                                    case 'aceptada': $estado_class = 'bg-success'; break;
                                    case 'rechazada': $estado_class = 'bg-danger'; break;
                                    case 'cancelada': $estado_class = 'bg-secondary'; break;
                                    case 'en_espera_deposito': $estado_class = 'bg-warning'; break;
                                    case 'pagada': $estado_class = 'bg-success'; break;
                                    case 'entregada': $estado_class = 'bg-primary'; break;
                                    default: $estado_class = 'bg-light text-dark'; break;
                                }
                                ?>
                                <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $cotizacion['estado'])); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Datos para los gráficos
const gastosCategoriaData = <?php echo json_encode($gastos_por_categoria); ?>;
const cotizacionesEstadoData = <?php echo json_encode($cotizaciones_por_estado); ?>;
const gastosMensualesData = <?php echo json_encode($gastos_mensuales); ?>;
const cotizacionesMensualesData = <?php echo json_encode($cotizaciones_mensuales); ?>;
const gananciasMensualesData = <?php echo json_encode($ganancias_mensuales); ?>;

// Gráfico de gastos por categoría
const gastosCategoriaCtx = document.getElementById('gastosCategoriaChart').getContext('2d');
new Chart(gastosCategoriaCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(gastosCategoriaData),
        datasets: [{
            data: Object.values(gastosCategoriaData),
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de estado de cotizaciones
const cotizacionesEstadoCtx = document.getElementById('cotizacionesEstadoChart').getContext('2d');
new Chart(cotizacionesEstadoCtx, {
    type: 'pie',
    data: {
        labels: Object.keys(cotizacionesEstadoData),
        datasets: [{
            data: Object.values(cotizacionesEstadoData),
            backgroundColor: [
                '#FFC107',  // pendiente - amarillo
                '#17A2B8',  // enviada - azul
                '#28A745',  // aceptada - verde
                '#DC3545',  // rechazada - rojo
                '#6C757D',  // cancelada - gris
                '#FF6B35',  // en_espera_deposito - naranja
                '#20C997',  // pagada - verde claro
                '#6F42C1'   // entregada - morado
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de gastos mensuales
const gastosMensualesCtx = document.getElementById('gastosMensualesChart').getContext('2d');
new Chart(gastosMensualesCtx, {
    type: 'line',
    data: {
        labels: gastosMensualesData.map(item => item.mes),
        datasets: [{
            label: 'Gastos ($)',
            data: gastosMensualesData.map(item => item.total),
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de cotizaciones mensuales
const cotizacionesMensualesCtx = document.getElementById('cotizacionesMensualesChart').getContext('2d');
new Chart(cotizacionesMensualesCtx, {
    type: 'bar',
    data: {
        labels: cotizacionesMensualesData.map(item => item.mes),
        datasets: [{
            label: 'Cotizaciones',
            data: cotizacionesMensualesData.map(item => item.total),
            backgroundColor: '#28A745'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de ganancias mensuales
const gananciasMensualesCtx = document.getElementById('gananciasMensualesChart').getContext('2d');
new Chart(gananciasMensualesCtx, {
    type: 'bar',
    data: {
        labels: gananciasMensualesData.map(item => item.mes),
        datasets: [{
            label: 'Ganancia Bruta ($)',
            data: gananciasMensualesData.map(item => item.total_ganancia),
            backgroundColor: '#007BFF',
            borderColor: '#0056B3',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Ganancia: $' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});

// Funciones de exportación
function exportarPDF() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const tipoReporte = document.getElementById('tipo_reporte').value;
    
    const url = `exportar_reporte.php?tipo=pdf&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&reporte=${tipoReporte}`;
    window.open(url, '_blank');
}

function exportarExcel() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const tipoReporte = document.getElementById('tipo_reporte').value;
    
    const url = `exportar_reporte.php?tipo=excel&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&reporte=${tipoReporte}`;
    window.open(url, '_blank');
}

document.getElementById('formSaldosBanco').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'guardar_saldos');

    fetch('ajax/finanzas_banco.php', {
        method: 'POST',
        body: formData
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (typeof showAlert === 'function') {
                showAlert(data.message, data.success ? 'success' : 'danger');
            } else {
                alert(data.message);
            }
            if (data.success) {
                setTimeout(function () { location.reload(); }, 800);
            }
        })
        .catch(function () {
            if (typeof showAlert === 'function') {
                showAlert('Error al guardar los saldos', 'danger');
            }
        });
});
</script>

<?php require_once 'includes/footer.php'; ?>
