<?php

require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Obtener parámetros de filtrado
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
$ganancia_por_producto = [];

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
    
    // Por producto
    $producto = $detalle['producto_nombre'];
    if (!isset($ganancia_por_producto[$producto])) {
        $ganancia_por_producto[$producto] = [
            'ventas' => 0,
            'costos' => 0,
            'ganancia' => 0,
            'cantidad' => 0
        ];
    }
    $ganancia_por_producto[$producto]['ventas'] += $detalle['subtotal'];
    $ganancia_por_producto[$producto]['costos'] += $detalle['costo_total'];
    $ganancia_por_producto[$producto]['ganancia'] += $detalle['ganancia'];
    $ganancia_por_producto[$producto]['cantidad'] += $detalle['cantidad'];
}

$margen_ganancia_promedio = $total_ventas > 0 ? ($total_ganancia / $total_ventas) * 100 : 0;

// Obtener productos para filtro
$productos_sql = "SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre";
$productos_result = $conn->query($productos_sql);
$productos = $productos_result->fetch_all(MYSQLI_ASSOC);

// Obtener categorías para filtro
$categorias_sql = "SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre";
$categorias_result = $conn->query($categorias_sql);
$categorias = $categorias_result->fetch_all(MYSQLI_ASSOC);

// Obtener gastos operacionales del período (incluir pendientes y aprobados)
$gastos_sql = "SELECT * FROM gastos WHERE fecha_gasto BETWEEN ? AND ? AND estado IN ('aprobado', 'pendiente') ORDER BY fecha_gasto DESC";
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
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php $pageTitle = 'Reporte de Ganancias';?></h1>
            <p class="text-muted">Análisis de rentabilidad por producto y categoría</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" onclick="exportarGananciasPDF()">
                <i class="fas fa-file-pdf me-2"></i>Exportar PDF
            </button>
            <button class="btn btn-primary" onclick="exportarGananciasExcel()">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Análisis
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
                    <label for="producto_id" class="form-label">Producto</label>
                    <select class="form-select" id="producto_id" name="producto_id">
                        <option value="">Todos los productos</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>" <?php echo $producto_id == $producto['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria_id" name="categoria_id">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Generar Reporte
                    </button>
                    <a href="reportes_ganancias.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Métricas Principales -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($total_ventas, 2); ?></h4>
                            <p class="card-text">Total Ventas</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($total_costos, 2); ?></h4>
                            <p class="card-text">Costos Productos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calculator fa-2x"></i>
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
                            <h4 class="card-title">$<?php echo number_format($total_gastos_operacionales, 2); ?></h4>
                            <p class="card-text">Gastos Operacionales</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">$<?php echo number_format($total_ganancia, 2); ?></h4>
                            <p class="card-text">Ganancia Bruta</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
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
                            <i class="fas fa-chart-pie fa-2x"></i>
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
                            <h4 class="card-title"><?php echo number_format($margen_neto, 1); ?>%</h4>
                            <p class="card-text">Margen Neto</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-percentage fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ganancias por Categoría</h5>
                </div>
                <div class="card-body">
                    <canvas id="gananciasCategoriaChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gastos Operacionales</h5>
                </div>
                <div class="card-body">
                    <canvas id="gastosCategoriaChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top 10 Productos Más Rentables</h5>
                </div>
                <div class="card-body">
                    <canvas id="productosRentablesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Flujo de Caja -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Análisis de Flujo de Caja</h5>
                </div>
                <div class="card-body">
                    <canvas id="flujoCajaChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Detalles -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Detalle de Ganancias por Cotización</h5>
        </div>
        <div class="card-body">
            <?php if (empty($detalles)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay datos de ganancias</h5>
                    <p class="text-muted">Los detalles de cotización se generan automáticamente</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cotización</th>
                                <th>Cliente</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Costo Unit.</th>
                                <th>Subtotal</th>
                                <th>Costo Total</th>
                                <th>Ganancia</th>
                                <th>Margen %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td>#<?php echo $detalle['cotizacion_id']; ?></td>
                                <td><?php echo htmlspecialchars($detalle['cliente_nombre']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($detalle['producto_nombre']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($detalle['sku']); ?></small>
                                </td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td class="text-success">$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                <td class="text-danger">$<?php echo number_format($detalle['costo_unitario'], 2); ?></td>
                                <td class="text-success fw-bold">$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                                <td class="text-danger">$<?php echo number_format($detalle['costo_total'], 2); ?></td>
                                <td class="text-primary fw-bold">$<?php echo number_format($detalle['ganancia'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $detalle['margen_ganancia'] >= 30 ? 'bg-success' : ($detalle['margen_ganancia'] >= 15 ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo number_format($detalle['margen_ganancia'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de Gastos Operacionales -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Gastos Operacionales del Período</h5>
            <small class="text-muted">Incluye gastos aprobados y pendientes de aprobación</small>
        </div>
        <div class="card-body">
            <?php if (empty($gastos)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
                    <h6 class="text-muted">No hay gastos operacionales en este período</h6>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th>Monto</th>
                                <th>Método de Pago</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gastos as $gasto): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($gasto['concepto']); ?></strong>
                                    <?php if ($gasto['descripcion']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($gasto['descripcion'], 0, 50)) . (strlen($gasto['descripcion']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($gasto['categoria']); ?></span>
                                </td>
                                <td class="text-danger fw-bold">$<?php echo number_format($gasto['monto'], 2); ?></td>
                                <td><?php echo htmlspecialchars($gasto['metodo_pago']); ?></td>
                                <td>
                                    <?php 
                                    $estado_class = '';
                                    switch($gasto['estado']) {
                                        case 'aprobado':
                                            $estado_class = 'bg-success';
                                            break;
                                        case 'pendiente':
                                            $estado_class = 'bg-warning';
                                            break;
                                        case 'rechazado':
                                            $estado_class = 'bg-danger';
                                            break;
                                        default:
                                            $estado_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($gasto['estado']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-warning">
                                <th colspan="3">Total Gastos Operacionales</th>
                                <th class="text-danger">$<?php echo number_format($total_gastos_operacionales, 2); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Datos para los gráficos
const gananciasCategoriaData = <?php echo json_encode($ganancia_por_categoria); ?>;
const gastosCategoriaData = <?php echo json_encode($gastos_por_categoria); ?>;
const productosRentablesData = <?php echo json_encode(array_slice($ganancia_por_producto, 0, 10, true)); ?>;

// Gráfico de ganancias por categoría
const gananciasCategoriaCtx = document.getElementById('gananciasCategoriaChart').getContext('2d');
new Chart(gananciasCategoriaCtx, {
    type: 'bar',
    data: {
        labels: Object.keys(gananciasCategoriaData),
        datasets: [{
            label: 'Ganancia ($)',
            data: Object.values(gananciasCategoriaData).map(item => item.ganancia),
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

// Gráfico de productos más rentables
const productosRentablesCtx = document.getElementById('productosRentablesChart').getContext('2d');
new Chart(productosRentablesCtx, {
    type: 'horizontalBar',
    data: {
        labels: Object.keys(productosRentablesData),
        datasets: [{
            label: 'Ganancia ($)',
            data: Object.values(productosRentablesData).map(item => item.ganancia),
            backgroundColor: '#007BFF'
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de gastos por categoría
const gastosCategoriaCtx = document.getElementById('gastosCategoriaChart').getContext('2d');
new Chart(gastosCategoriaCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(gastosCategoriaData),
        datasets: [{
            label: 'Gastos ($)',
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

// Gráfico de flujo de caja
const flujoCajaCtx = document.getElementById('flujoCajaChart').getContext('2d');
new Chart(flujoCajaCtx, {
    type: 'bar',
    data: {
        labels: ['Ventas', 'Costos Productos', 'Gastos Operacionales', 'Ganancia Bruta', 'Ganancia Neta'],
        datasets: [{
            label: 'Ingresos',
            data: [<?php echo $total_ventas; ?>, 0, 0, 0, 0],
            backgroundColor: '#28A745'
        }, {
            label: 'Costos',
            data: [0, <?php echo $total_costos; ?>, <?php echo $total_gastos_operacionales; ?>, 0, 0],
            backgroundColor: '#DC3545'
        }, {
            label: 'Ganancias',
            data: [0, 0, 0, <?php echo $total_ganancia; ?>, <?php echo $ganancia_neta; ?>],
            backgroundColor: ['#007BFF', <?php echo $ganancia_neta >= 0 ? "'#28A745'" : "'#DC3545'"; ?>]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top'
            }
        }
    }
});

// Funciones de exportación
function exportarGananciasPDF() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const productoId = document.getElementById('producto_id').value;
    const categoriaId = document.getElementById('categoria_id').value;
    
    const url = `exportar_ganancias.php?tipo=pdf&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&producto_id=${productoId}&categoria_id=${categoriaId}`;
    window.open(url, '_blank');
}

function exportarGananciasExcel() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const productoId = document.getElementById('producto_id').value;
    const categoriaId = document.getElementById('categoria_id').value;
    
    const url = `exportar_ganancias.php?tipo=excel&fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&producto_id=${productoId}&categoria_id=${categoriaId}`;
    window.open(url, '_blank');
}
</script>

<?php require_once 'includes/footer.php'; ?>
