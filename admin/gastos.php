<?php
$pageTitle = 'Gestión de Gastos';
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

// Obtener parámetros de filtrado
$filtro_estado = $_GET['estado'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir condiciones de búsqueda
$conditions = [];
$params = [];

if ($filtro_estado) {
    $conditions[] = "g.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_categoria) {
    $conditions[] = "g.categoria = ?";
    $params[] = $filtro_categoria;
}

if ($filtro_fecha_desde) {
    $conditions[] = "g.fecha_gasto >= ?";
    $params[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $conditions[] = "g.fecha_gasto <= ?";
    $params[] = $filtro_fecha_hasta;
}

if ($busqueda) {
    $conditions[] = "(g.concepto LIKE ? OR g.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

// Construir consulta SQL
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT g.*, 
               u.username as usuario_nombre,
               a.username as aprobado_por_nombre
        FROM gastos g
        LEFT JOIN usuarios u ON g.usuario_id = u.id
        LEFT JOIN usuarios a ON g.aprobado_por = a.id
        $whereClause
        ORDER BY g.fecha_gasto DESC, g.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$gastos = $result->fetch_all(MYSQLI_ASSOC);

// Calcular totales
$total_gastos = 0;
$gastos_pendientes = 0;
$gastos_aprobados = 0;

foreach ($gastos as $gasto) {
    $total_gastos += $gasto['monto'];
    if ($gasto['estado'] == 'pendiente') $gastos_pendientes++;
    if ($gasto['estado'] == 'aprobado') $gastos_aprobados++;
}

// Categorías disponibles
$categorias = ['oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'otros'];
$estados = ['pendiente', 'aprobado', 'rechazado'];
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestión de Gastos</h1>
            <p class="text-muted">Administra los gastos operacionales de la empresa</p>
        </div>
        <a href="gastos_create.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Gasto
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $gastos_pendientes; ?></h4>
                            <p class="card-text">Pendientes</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo $gastos_aprobados; ?></h4>
                            <p class="card-text">Aprobados</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title"><?php echo count($gastos); ?></h4>
                            <p class="card-text">Total Registros</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado; ?>" <?php echo $filtro_estado == $estado ? 'selected' : ''; ?>>
                                <?php echo ucfirst($estado); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria; ?>" <?php echo $filtro_categoria == $categoria ? 'selected' : ''; ?>>
                                <?php echo ucfirst($categoria); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $filtro_fecha_desde; ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $filtro_fecha_hasta; ?>">
                </div>
                <div class="col-md-2">
                    <label for="busqueda" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" placeholder="Concepto..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                    <a href="gastos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de gastos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Gastos
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($gastos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay gastos registrados</h5>
                    <p class="text-muted">Comienza agregando un nuevo gasto</p>
                    <a href="gastos_create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Agregar Gasto
                    </a>
                </div>
            <?php else: ?>
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
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gastos as $gasto): ?>
                            <tr>
                                <td>#<?php echo $gasto['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($gasto['concepto']); ?></strong>
                                    <?php if ($gasto['descripcion']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($gasto['descripcion'], 0, 50)) . (strlen($gasto['descripcion']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-success">$<?php echo number_format($gasto['monto'], 2); ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo ucfirst($gasto['categoria']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $estado_class = '';
                                    switch ($gasto['estado']) {
                                        case 'pendiente':
                                            $estado_class = 'bg-warning';
                                            break;
                                        case 'aprobado':
                                            $estado_class = 'bg-success';
                                            break;
                                        case 'rechazado':
                                            $estado_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($gasto['estado']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($gasto['usuario_nombre']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="gastos_view.php?id=<?php echo $gasto['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="gastos_edit.php?id=<?php echo $gasto['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($gasto['estado'] == 'pendiente'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="aprobarGasto(<?php echo $gasto['id']; ?>)" title="Aprobar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="rechazarGasto(<?php echo $gasto['id']; ?>)" title="Rechazar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarGasto(<?php echo $gasto['id']; ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
</div>

<script>
function aprobarGasto(id) {
    if (confirm('¿Estás seguro de que quieres aprobar este gasto?')) {
        fetch('../ajax/gastos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=aprobar&id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function rechazarGasto(id) {
    if (confirm('¿Estás seguro de que quieres rechazar este gasto?')) {
        fetch('../ajax/gastos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=rechazar&id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function eliminarGasto(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este gasto? Esta acción no se puede deshacer.')) {
        console.log('Eliminando gasto ID:', id);
        fetch('../ajax/gastos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=eliminar&id=' + id
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión: ' + error);
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
