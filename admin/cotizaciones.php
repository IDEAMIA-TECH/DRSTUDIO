<?php
// Verificar si se quiere ver solo las entregadas
$ver_entregadas = isset($_GET['ver_entregadas']) && $_GET['ver_entregadas'] == '1';

if ($ver_entregadas) {
    $pageTitle = 'Órdenes Entregadas';
    $pageActions = '<a href="cotizaciones.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Ver Cotizaciones Activas</a>';
} else {
    $pageTitle = 'Gestión de Cotizaciones';
    $pageActions = '<a href="cotizaciones_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nueva Cotización</a> <a href="cotizaciones.php?ver_entregadas=1" class="btn btn-info"><i class="fas fa-truck me-2"></i>Ver Órdenes Entregadas</a>';
}
require_once 'includes/header.php';

// Obtener filtros
$cliente_id = $_GET['cliente'] ?? '';
$estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir condiciones de búsqueda
$conditions = [];
if ($cliente_id) {
    $conditions[] = "c.cliente_id = $cliente_id";
}
if ($estado) {
    $conditions[] = "c.estado = '$estado'";
}
if ($busqueda) {
    $conditions[] = "(c.numero_cotizacion LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR cl.empresa LIKE '%$busqueda%')";
}
if ($fecha_desde) {
    $conditions[] = "DATE(c.created_at) >= '$fecha_desde'";
}
if ($fecha_hasta) {
    $conditions[] = "DATE(c.created_at) <= '$fecha_hasta'";
}

// Obtener cotizaciones con información de cliente, pagos y saldo pendiente
// Si ver_entregadas es true, solo mostrar entregadas; si no, excluir entregadas
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
if ($ver_entregadas) {
    // Solo mostrar entregadas
    if (!empty($whereClause)) {
        $whereClause .= " AND (LOWER(c.estado) = 'entregada' OR LOWER(c.estado) = 'entregado')";
    } else {
        $whereClause = "WHERE (LOWER(c.estado) = 'entregada' OR LOWER(c.estado) = 'entregado')";
    }
} else {
    // Excluir entregadas (comportamiento por defecto)
    if (!empty($whereClause)) {
        $whereClause .= " AND LOWER(c.estado) != 'entregada' AND LOWER(c.estado) != 'entregado'";
    } else {
        $whereClause = "WHERE LOWER(c.estado) != 'entregada' AND LOWER(c.estado) != 'entregado'";
    }
}

$sql = "SELECT c.*, 
               cl.nombre as cliente_nombre, 
               cl.empresa as cliente_empresa, 
               u.username as creado_por,
               COALESCE(SUM(p.monto), 0) as total_pagado,
               (c.total - COALESCE(SUM(p.monto), 0)) as saldo_pendiente
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id 
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        LEFT JOIN pagos_cotizacion p ON c.id = p.cotizacion_id
        $whereClause 
        GROUP BY c.id
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$cotizaciones = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener clientes para el filtro
$clientes = readRecords('clientes', [], null, 'nombre ASC');
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <select class="form-select" id="cliente" name="cliente">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                        <?php echo $cliente_id == $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                                    <?php if ($cliente['empresa']): ?>
                                        - <?php echo htmlspecialchars($cliente['empresa']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            <?php if (!$ver_entregadas): ?>
                                <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="enviada" <?php echo $estado === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                                <option value="aceptada" <?php echo $estado === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
                                <option value="en_espera_deposito" <?php echo $estado === 'en_espera_deposito' ? 'selected' : ''; ?>>En Espera de Depósito</option>
                                <option value="rechazada" <?php echo $estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                                <option value="cancelada" <?php echo $estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <?php else: ?>
                                <option value="entregada" <?php echo $estado === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if ($ver_entregadas): ?>
                        <input type="hidden" name="ver_entregadas" value="1">
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label for="fecha_desde" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" 
                               class="form-control" 
                               id="busqueda" 
                               name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Número, cliente...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="cotizaciones.php<?php echo $ver_entregadas ? '?ver_entregadas=1' : ''; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-<?php echo $ver_entregadas ? 'truck' : 'file-invoice'; ?> me-2"></i>
            <?php echo $ver_entregadas ? 'Órdenes Entregadas' : 'Listado de Cotizaciones'; ?>
            <span class="badge bg-<?php echo $ver_entregadas ? 'success' : 'primary'; ?> ms-2"><?php echo count($cotizaciones); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($cotizaciones)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay cotizaciones registradas</h5>
                <p class="text-muted">Comienza creando tu primera cotización</p>
                <a href="cotizaciones_create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Primera Cotización
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover data-table" id="cotizacionesTable">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Pago / Saldo</th>
                            <th>Vencimiento</th>
                            <th>Creado por</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): 
                            // Calcular estado de pago primero para determinar si resaltar la fila
                            $total_cotizacion = floatval($cotizacion['total'] ?? 0);
                            $total_pagado = floatval($cotizacion['total_pagado'] ?? 0);
                            
                            // Recalcular saldo pendiente para asegurar precisión
                            $saldo_pendiente = $total_cotizacion - $total_pagado;
                            
                            // Determinar estado del pago
                            $es_parcial = false;
                            if ($total_cotizacion > 0) {
                                if ($total_pagado >= $total_cotizacion) {
                                    $estado_pago = 'Pagado';
                                    $estado_pago_class = 'success';
                                    $estado_pago_icon = 'fa-check-circle';
                                    $es_parcial = false;
                                } elseif ($total_pagado > 0) {
                                    $estado_pago = 'Parcial';
                                    $estado_pago_class = 'warning';
                                    $estado_pago_icon = 'fa-clock';
                                    $es_parcial = true;
                                } else {
                                    $estado_pago = 'Pendiente';
                                    $estado_pago_class = 'danger';
                                    $estado_pago_icon = 'fa-exclamation-circle';
                                    $es_parcial = false;
                                }
                            } else {
                                $estado_pago = 'Pendiente';
                                $estado_pago_class = 'danger';
                                $estado_pago_icon = 'fa-exclamation-circle';
                                $es_parcial = false;
                            }
                            
                            // Resaltar fila si es parcial y no está en vista de entregadas
                            $row_class = ($es_parcial && !$ver_entregadas) ? 'table-warning' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>" <?php if ($es_parcial && !$ver_entregadas): ?>style="border-left: 4px solid #ffc107;"<?php endif; ?>>
                            <td>
                                <code><?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></code>
                                <?php if ($es_parcial && !$ver_entregadas): ?>
                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Pago Parcial</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></strong>
                                    <?php if ($cotizacion['cliente_empresa']): ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($cotizacion['cliente_empresa']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                                <?php if ($cotizacion['descuento'] > 0): ?>
                                    <br>
                                    <small class="text-muted">Desc: $<?php echo number_format($cotizacion['descuento'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $estadoClass = [
                                    'pendiente' => 'warning',
                                    'enviada' => 'info',
                                    'aceptada' => 'success',
                                    'en_espera_deposito' => 'primary',
                                    'rechazada' => 'danger',
                                    'cancelada' => 'secondary'
                                ];
                                $class = $estadoClass[$cotizacion['estado']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $class; ?>">
                                    <?php 
                                    $estadoTexto = [
                                        'pendiente' => 'Pendiente',
                                        'enviada' => 'Enviada',
                                        'aceptada' => 'Aceptada',
                                        'en_espera_deposito' => 'En Espera de Depósito',
                                        'rechazada' => 'Rechazada',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    echo $estadoTexto[$cotizacion['estado']] ?? ucfirst($cotizacion['estado']);
                                    ?>
                                </span>
                                <?php if ($es_parcial && !$ver_entregadas): ?>
                                    <br>
                                    <span class="badge bg-warning mt-1">
                                        <i class="fas fa-money-bill-wave me-1"></i>Pago Parcial
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-<?php echo $estado_pago_class; ?> mb-1">
                                        <i class="fas <?php echo $estado_pago_icon; ?> me-1"></i>
                                        <?php echo $estado_pago; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        Pagado: <strong class="text-success">$<?php echo number_format($total_pagado, 2); ?></strong>
                                    </small>
                                    <br>
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <small class="text-danger">
                                            Pendiente: <strong>$<?php echo number_format($saldo_pendiente, 2); ?></strong>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-success">
                                            <strong>Sin saldo pendiente</strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($cotizacion['fecha_vencimiento']): ?>
                                    <?php
                                    $vencimiento = new DateTime($cotizacion['fecha_vencimiento']);
                                    $hoy = new DateTime();
                                    $dias = $hoy->diff($vencimiento)->days;
                                    
                                    if ($vencimiento < $hoy) {
                                        $class = 'danger';
                                        $texto = 'Vencida';
                                    } elseif ($dias <= 3) {
                                        $class = 'warning';
                                        $texto = 'Próxima a vencer';
                                    } else {
                                        $class = 'success';
                                        $texto = 'Vigente';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>">
                                        <?php echo $texto; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo formatDate($cotizacion['fecha_vencimiento'], 'd/m/Y'); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Sin vencimiento</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($cotizacion['creado_por']); ?></small>
                            </td>
                            <td><?php echo formatDate($cotizacion['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="cotizaciones_view.php?id=<?php echo $cotizacion['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="cotizaciones_edit.php?id=<?php echo $cotizacion['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
