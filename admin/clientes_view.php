<?php
$pageTitle = 'Ver Cliente';
require_once 'includes/header.php';

// Obtener ID del cliente
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: clientes.php');
    exit;
}

// Obtener datos del cliente
$cliente = getRecord('clientes', $id);
if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

// Obtener estadísticas del cliente
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_cotizaciones,
        SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END) as total_ventas,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as cotizaciones_pendientes,
        SUM(CASE WHEN estado = 'enviada' THEN 1 ELSE 0 END) as cotizaciones_enviadas,
        SUM(CASE WHEN estado = 'aceptada' THEN 1 ELSE 0 END) as cotizaciones_aceptadas,
        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as cotizaciones_rechazadas
    FROM cotizaciones 
    WHERE cliente_id = $id
")->fetch_assoc();

// Obtener cotizaciones recientes del cliente
$cotizaciones = readRecords('cotizaciones', ["cliente_id = $id"], 10, 'created_at DESC');

// Obtener cotizaciones por estado
$cotizacionesPorEstado = $conn->query("
    SELECT estado, COUNT(*) as cantidad, SUM(total) as total
    FROM cotizaciones 
    WHERE cliente_id = $id 
    GROUP BY estado
    ORDER BY cantidad DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($cliente['nombre']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 24px;">
                            <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h6>Información de Contacto</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Nombre:</strong>
                                <span class="ms-2"><?php echo htmlspecialchars($cliente['nombre']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Empresa:</strong>
                                <span class="ms-2">
                                    <?php if ($cliente['empresa']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($cliente['empresa']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">No especificada</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <strong>Email:</strong>
                                <span class="ms-2">
                                    <?php if ($cliente['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($cliente['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Teléfono:</strong>
                                <span class="ms-2">
                                    <?php if ($cliente['telefono']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($cliente['telefono']); ?>" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo htmlspecialchars($cliente['telefono']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($cliente['direccion']): ?>
                        <div class="row mt-2">
                            <div class="col-12">
                                <strong>Dirección:</strong>
                                <span class="ms-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo nl2br(htmlspecialchars($cliente['direccion'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas de cotizaciones -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Estadísticas de Cotizaciones
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-primary"><?php echo $stats['total_cotizaciones']; ?></h4>
                            <small class="text-muted">Total Cotizaciones</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-success">$<?php echo number_format($stats['total_ventas'], 2); ?></h4>
                            <small class="text-muted">Total Ventas</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-warning"><?php echo $stats['cotizaciones_pendientes']; ?></h4>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border rounded p-3">
                            <h4 class="text-info"><?php echo $stats['cotizaciones_aceptadas']; ?></h4>
                            <small class="text-muted">Aceptadas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cotizaciones recientes -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Cotizaciones Recientes
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($cotizaciones)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay cotizaciones para este cliente</p>
                        <a href="cotizaciones_create.php?cliente=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Cotización
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotizaciones as $cotizacion): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></code>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoClass = [
                                            'pendiente' => 'warning',
                                            'enviada' => 'info',
                                            'aceptada' => 'success',
                                            'rechazada' => 'danger',
                                            'cancelada' => 'secondary'
                                        ];
                                        $class = $estadoClass[$cotizacion['estado']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($cotizacion['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($cotizacion['created_at']); ?></td>
                                    <td>
                                        <a href="cotizaciones_view.php?id=<?php echo $cotizacion['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver cotización">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Acciones</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="clientes_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar Cliente
                    </a>
                    <a href="cotizaciones_create.php?cliente=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nueva Cotización
                    </a>
                    <a href="clientes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Clientes
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Sistema</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>ID:</strong> <?php echo $cliente['id']; ?></li>
                    <li><strong>Creado:</strong> <?php echo formatDate($cliente['created_at']); ?></li>
                    <li><strong>Actualizado:</strong> <?php echo formatDate($cliente['updated_at']); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Cotizaciones por Estado</h6>
            </div>
            <div class="card-body">
                <?php if (empty($cotizacionesPorEstado)): ?>
                    <p class="text-muted small">No hay cotizaciones</p>
                <?php else: ?>
                    <?php foreach ($cotizacionesPorEstado as $estado): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-capitalize"><?php echo $estado['estado']; ?></span>
                        <div>
                            <span class="badge bg-secondary me-1"><?php echo $estado['cantidad']; ?></span>
                            <small class="text-muted">$<?php echo number_format($estado['total'], 2); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Eliminar Cliente</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Esta acción eliminará el cliente y todas sus cotizaciones de forma permanente.
                </p>
                <button type="button" 
                        class="btn btn-danger btn-sm w-100" 
                        onclick="deleteCliente(<?php echo $id; ?>)">
                    <i class="fas fa-trash me-2"></i>Eliminar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para eliminar cliente
function deleteCliente(id) {
    if (confirmDelete('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer y eliminará todas las cotizaciones asociadas.')) {
        ajaxRequest('ajax/clientes.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                setTimeout(() => {
                    window.location.href = 'clientes.php';
                }, 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
