<?php

require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Obtener parámetros de filtrado
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d'); // Día actual
$tipo_reporte = $_GET['tipo_reporte'] ?? 'dashboard';

// Obtener datos de gastos del período
$gastos_sql = "SELECT * FROM gastos WHERE fecha_gasto BETWEEN ? AND ? ORDER BY fecha_gasto DESC";
$gastos_stmt = $conn->prepare($gastos_sql);
$gastos_stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$gastos_stmt->execute();
$gastos_result = $gastos_stmt->get_result();
$gastos = $gastos_result->fetch_all(MYSQLI_ASSOC);

// Obtener datos de cotizaciones del período
$cotizaciones_sql = "SELECT * FROM cotizaciones WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC";
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

// Obtener gastos de los últimos 6 meses para gráfico
$gastos_mensuales_sql = "SELECT 
    DATE_FORMAT(fecha_gasto, '%Y-%m') as mes,
    SUM(monto) as total
    FROM gastos 
    WHERE fecha_gasto >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND estado = 'aprobado'
    GROUP BY DATE_FORMAT(fecha_gasto, '%Y-%m')
    ORDER BY mes ASC";
$gastos_mensuales_result = $conn->query($gastos_mensuales_sql);
$gastos_mensuales = $gastos_mensuales_result->fetch_all(MYSQLI_ASSOC);

// Obtener cotizaciones de los últimos 6 meses para gráfico
$cotizaciones_mensuales_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as mes,
    COUNT(*) as total
    FROM cotizaciones 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mes ASC";
$cotizaciones_mensuales_result = $conn->query($cotizaciones_mensuales_sql);
$cotizaciones_mensuales = $cotizaciones_mensuales_result->fetch_all(MYSQLI_ASSOC);

$categorias = ['oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'otros'];

// Obtener datos de ganancias del período
$ganancias_sql = "SELECT 
    SUM(cd.subtotal) as total_ventas,
    SUM(cd.costo_total) as total_costos,
    SUM(cd.ganancia) as total_ganancia
FROM cotizacion_detalles cd
LEFT JOIN cotizaciones c ON cd.cotizacion_id = c.id
WHERE c.created_at BETWEEN ? AND ?";
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

// Obtener ganancias de los últimos 6 meses para gráfico
$ganancias_mensuales_sql = "SELECT 
    DATE_FORMAT(c.created_at, '%Y-%m') as mes,
    SUM(cd.subtotal) as total_ventas,
    SUM(cd.costo_total) as total_costos,
    SUM(cd.ganancia) as total_ganancia
FROM cotizacion_detalles cd
LEFT JOIN cotizaciones c ON cd.cotizacion_id = c.id
WHERE c.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
ORDER BY mes ASC";
$ganancias_mensuales_result = $conn->query($ganancias_mensuales_sql);
$ganancias_mensuales = $ganancias_mensuales_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php $pageTitle = 'Reportes y Análisis';?></h1>
            <p class="text-muted">Análisis financiero y métricas de la empresa</p>
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
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
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
                    <h5 class="card-title mb-0">Gastos Mensuales (Últimos 6 meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="gastosMensualesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cotizaciones Mensuales (Últimos 6 meses)</h5>
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
</script>

<?php require_once 'includes/footer.php'; ?>
