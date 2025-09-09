<?php
$pageTitle = 'Ver Cotización';
require_once 'includes/header.php';

// Obtener ID de la cotización
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: cotizaciones.php');
    exit;
}

// Obtener datos de la cotización con información de cliente
$sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.empresa as cliente_empresa, cl.email as cliente_email, cl.telefono as cliente_telefono, u.username as creado_por
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id 
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = $id";
$result = $conn->query($sql);
$cotizacion = $result ? $result->fetch_assoc() : null;

if (!$cotizacion) {
    header('Location: cotizaciones.php');
    exit;
}

// Obtener items de la cotización
$items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');

// Obtener información de productos para los items
foreach ($items as &$item) {
    $producto = getRecord('productos', $item['producto_id']);
    $item['producto'] = $producto;
    
    if ($item['variante_id']) {
        $variante = getRecord('variantes_producto', $item['variante_id']);
        $item['variante'] = $variante;
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Cotización <?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?>
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
                    <span class="badge bg-<?php echo $class; ?> ms-2">
                        <?php echo ucfirst($cotizacion['estado']); ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <!-- Información del cliente -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Información del Cliente</h6>
                        <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></p>
                        <?php if ($cotizacion['cliente_empresa']): ?>
                            <p class="mb-1"><strong>Empresa:</strong> <?php echo htmlspecialchars($cotizacion['cliente_empresa']); ?></p>
                        <?php endif; ?>
                        <?php if ($cotizacion['cliente_email']): ?>
                            <p class="mb-1"><strong>Email:</strong> 
                                <a href="mailto:<?php echo htmlspecialchars($cotizacion['cliente_email']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($cotizacion['cliente_email']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($cotizacion['cliente_telefono']): ?>
                            <p class="mb-1"><strong>Teléfono:</strong> 
                                <a href="tel:<?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Información de la Cotización</h6>
                        <p class="mb-1"><strong>Número:</strong> <code><?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></code></p>
                        <p class="mb-1"><strong>Fecha:</strong> <?php echo formatDate($cotizacion['created_at']); ?></p>
                        <p class="mb-1"><strong>Creado por:</strong> <?php echo htmlspecialchars($cotizacion['creado_por']); ?></p>
                        <?php if ($cotizacion['fecha_vencimiento']): ?>
                            <p class="mb-1"><strong>Vencimiento:</strong> <?php echo formatDate($cotizacion['fecha_vencimiento'], 'd/m/Y'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Items de la cotización -->
                <h6>Productos Cotizados</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Variante</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['producto']['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($item['producto']['sku']); ?></small>
                                </td>
                                <td>
                                    <?php if ($item['variante']): ?>
                                        <span class="badge bg-light text-dark">
                                            <?php 
                                            $variante_parts = array_filter([
                                                $item['variante']['talla'],
                                                $item['variante']['color'],
                                                $item['variante']['material']
                                            ]);
                                            echo htmlspecialchars(implode(' - ', $variante_parts));
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin variante</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-end">$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                                <td class="text-end"><strong>$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($cotizacion['subtotal'], 2); ?></strong></td>
                            </tr>
                            <?php if ($cotizacion['descuento'] > 0): ?>
                            <tr>
                                <td colspan="4" class="text-end text-danger"><strong>Descuento:</strong></td>
                                <td class="text-end text-danger"><strong>-$<?php echo number_format($cotizacion['descuento'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($cotizacion['total'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if ($cotizacion['observaciones']): ?>
                <div class="mt-3">
                    <h6>Observaciones</h6>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></p>
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
                    <a href="cotizaciones_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar Cotización
                    </a>
                    
                    <?php if ($cotizacion['estado'] == 'pendiente'): ?>
                        <button type="button" class="btn btn-info" onclick="cambiarEstado('enviada')">
                            <i class="fas fa-paper-plane me-2"></i>Marcar como Enviada
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($cotizacion['estado'] == 'enviada'): ?>
                        <button type="button" class="btn btn-success" onclick="cambiarEstado('aceptada')">
                            <i class="fas fa-check me-2"></i>Marcar como Aceptada
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cambiarEstado('rechazada')">
                            <i class="fas fa-times me-2"></i>Marcar como Rechazada
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-primary" onclick="imprimirCotizacion()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    
                    <a href="cotizaciones.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Cotizaciones
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Resumen</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between">
                        <span>Productos:</span>
                        <strong><?php echo count($items); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <strong>$<?php echo number_format($cotizacion['subtotal'], 2); ?></strong>
                    </li>
                    <?php if ($cotizacion['descuento'] > 0): ?>
                    <li class="d-flex justify-content-between">
                        <span>Descuento:</span>
                        <span class="text-danger">-$<?php echo number_format($cotizacion['descuento'], 2); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="d-flex justify-content-between">
                        <span><strong>Total:</strong></span>
                        <strong class="text-success">$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Historial de Estados</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Cotización Creada</h6>
                            <p class="timeline-text"><?php echo formatDate($cotizacion['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($cotizacion['estado'] != 'pendiente'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-<?php echo $class; ?>"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Estado: <?php echo ucfirst($cotizacion['estado']); ?></h6>
                            <p class="timeline-text"><?php echo formatDate($cotizacion['updated_at']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}
</style>

<script>
// Función para cambiar estado
function cambiarEstado(estado) {
    const estados = {
        'enviada': 'enviada',
        'aceptada': 'aceptada',
        'rechazada': 'rechazada'
    };
    
    const estadoTexto = estados[estado] || estado;
    
    if (confirm(`¿Estás seguro de marcar esta cotización como ${estadoTexto}?`)) {
        ajaxRequest('ajax/cotizaciones.php', {
            action: 'change_status',
            id: <?php echo $id; ?>,
            estado: estado
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                location.reload();
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}

// Función para imprimir
function imprimirCotizacion() {
    window.print();
}

// Función para eliminar cotización
function deleteCotizacion() {
    if (confirmDelete('¿Estás seguro de eliminar esta cotización? Esta acción no se puede deshacer.')) {
        ajaxRequest('ajax/cotizaciones.php', {
            action: 'delete',
            id: <?php echo $id; ?>
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                setTimeout(() => {
                    window.location.href = 'cotizaciones.php';
                }, 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
