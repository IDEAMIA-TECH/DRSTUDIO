<?php
$pageTitle = 'Detalles del Gasto';
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: gastos.php');
    exit;
}

// Obtener datos del gasto con información de usuarios
$sql = "SELECT g.*, 
               u.username as usuario_nombre,
               u.email as usuario_email,
               a.username as aprobado_por_nombre,
               a.email as aprobado_por_email
        FROM gastos g
        LEFT JOIN usuarios u ON g.usuario_id = u.id
        LEFT JOIN usuarios a ON g.aprobado_por = a.id
        WHERE g.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$gasto = $result->fetch_assoc();

if (!$gasto) {
    header('Location: gastos.php');
    exit;
}

// Categorías y métodos de pago
$categorias = [
    'oficina' => 'Oficina',
    'marketing' => 'Marketing',
    'equipos' => 'Equipos',
    'servicios' => 'Servicios',
    'viajes' => 'Viajes',
    'otros' => 'Otros'
];

$metodos_pago = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'cheque' => 'Cheque'
];

$estados = [
    'pendiente' => 'Pendiente',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado'
];
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gasto #<?php echo $gasto['id']; ?></h1>
            <p class="text-muted"><?php echo htmlspecialchars($gasto['concepto']); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="gastos_edit.php?id=<?php echo $gasto['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
            <a href="gastos.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Información principal -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información del Gasto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Concepto</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($gasto['concepto']); ?></p>
                            
                            <h6>Monto</h6>
                            <p class="text-success fs-4 fw-bold">$<?php echo number_format($gasto['monto'], 2); ?></p>
                            
                            <h6>Fecha del Gasto</h6>
                            <p class="text-muted"><?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Categoría</h6>
                            <p><span class="badge bg-secondary"><?php echo $categorias[$gasto['categoria']]; ?></span></p>
                            
                            <h6>Método de Pago</h6>
                            <p class="text-muted"><?php echo $metodos_pago[$gasto['metodo_pago']]; ?></p>
                            
                            <h6>Estado</h6>
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
                            <p><span class="badge <?php echo $estado_class; ?>"><?php echo $estados[$gasto['estado']]; ?></span></p>
                        </div>
                    </div>
                    
                    <?php if ($gasto['descripcion']): ?>
                    <hr>
                    <h6>Descripción</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($gasto['descripcion'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($gasto['observaciones']): ?>
                    <hr>
                    <h6>Observaciones</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($gasto['observaciones'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comprobante -->
            <?php if ($gasto['comprobante']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file me-2"></i>Comprobante
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($gasto['comprobante']); ?></h6>
                            <small class="text-muted">Archivo adjunto</small>
                        </div>
                        <div class="ms-auto">
                            <a href="../uploads/gastos/<?php echo htmlspecialchars($gasto['comprobante']); ?>" 
                               target="_blank" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-2"></i>Ver/Descargar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Información del usuario -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Información del Usuario
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Reportado por</h6>
                    <p class="text-muted"><?php echo htmlspecialchars($gasto['usuario_nombre']); ?></p>
                    <p class="text-muted small"><?php echo htmlspecialchars($gasto['usuario_email']); ?></p>
                    
                    <h6>Fecha de registro</h6>
                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($gasto['created_at'])); ?></p>
                </div>
            </div>

            <!-- Información de aprobación -->
            <?php if ($gasto['estado'] != 'pendiente'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>Información de Aprobación
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Aprobado por</h6>
                    <p class="text-muted"><?php echo htmlspecialchars($gasto['aprobado_por_nombre']); ?></p>
                    <p class="text-muted small"><?php echo htmlspecialchars($gasto['aprobado_por_email']); ?></p>
                    
                    <h6>Fecha de aprobación</h6>
                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($gasto['fecha_aprobacion'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Acciones
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="gastos_edit.php?id=<?php echo $gasto['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar Gasto
                        </a>
                        
                        <?php if ($gasto['estado'] == 'pendiente'): ?>
                            <button class="btn btn-success" onclick="aprobarGasto(<?php echo $gasto['id']; ?>)">
                                <i class="fas fa-check me-2"></i>Aprobar Gasto
                            </button>
                            <button class="btn btn-danger" onclick="rechazarGasto(<?php echo $gasto['id']; ?>)">
                                <i class="fas fa-times me-2"></i>Rechazar Gasto
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-danger" onclick="eliminarGasto(<?php echo $gasto['id']; ?>)">
                            <i class="fas fa-trash me-2"></i>Eliminar Gasto
                        </button>
                    </div>
                </div>
            </div>
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
                window.location.href = 'gastos.php';
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
