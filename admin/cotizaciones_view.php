<?php
// Incluir configuración de base de datos
require_once '../includes/config.php';
require_once '../includes/functions.php';

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

$pageTitle = 'Ver Cotización';
require_once 'includes/header.php';

// Obtener items de la cotización (productos del catálogo)
$items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');

// Obtener productos personalizados
$productos_personalizados = readRecords('cotizacion_productos_personalizados', ["cotizacion_id = $id"], null, 'id ASC');

// Obtener información de productos para los items y calcular totales
$subtotal_calculado = 0;
foreach ($items as &$item) {
    $producto = getRecord('productos', $item['producto_id']);
    $item['producto'] = $producto;
    
    if ($item['variante_id']) {
        $variante = getRecord('variantes_producto', $item['variante_id']);
        $item['variante'] = $variante;
    }
    
    // Sumar al subtotal calculado
    $subtotal_calculado += $item['subtotal'];
}
// Limpiar la referencia para evitar problemas en bucles posteriores
unset($item);

// Sumar productos personalizados al subtotal
foreach ($productos_personalizados as $producto_personalizado) {
    $subtotal_calculado += $producto_personalizado['subtotal'];
}


// Calcular total final
$total_calculado = $subtotal_calculado - $cotizacion['descuento'];

// Datos para exportar PDF (codificación segura para JavaScript)
$cotizacionPdfData = [
    'numero' => $cotizacion['numero_cotizacion'],
    'fecha' => formatDate($cotizacion['created_at']),
    'cliente' => [
        'nombre' => $cotizacion['cliente_nombre'],
        'empresa' => $cotizacion['cliente_empresa'] ?? '',
        'email' => $cotizacion['cliente_email'] ?? '',
        'telefono' => $cotizacion['cliente_telefono'] ?? '',
    ],
    'items' => $items,
    'productos_personalizados' => $productos_personalizados,
    'subtotal' => $subtotal_calculado,
    'descuento' => (float) $cotizacion['descuento'],
    'total' => $total_calculado,
    'observaciones' => $cotizacion['observaciones'] ?? '',
    'estado' => $cotizacion['estado'],
];
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$cargarHistorialPagos = ($cotizacion['estado'] === 'pagada' || $cotizacion['estado'] === 'entregada');
?>

<div id="cotizacion-view-config" class="d-none"
    data-id="<?php echo (int) $id; ?>"
    data-estado="<?php echo htmlspecialchars($cotizacion['estado'], ENT_QUOTES, 'UTF-8'); ?>"
    data-total="<?php echo (float) $total_calculado; ?>"
    data-cargar-historial="<?php echo $cargarHistorialPagos ? '1' : '0'; ?>"></div>
<script type="application/json" id="cotizacion-pdf-data"><?php echo json_encode($cotizacionPdfData, $jsonFlags); ?></script>

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
                        'cancelada' => 'secondary',
                        'en_espera_deposito' => 'primary',
                        'pagada' => 'success',
                        'entregada' => 'dark'
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
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th>Talla</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Productos del catálogo -->
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary">Catálogo</span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['producto']['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($item['producto']['sku']); ?></small>
                                </td>
                                <td>
                                    <?php if (isset($item['variante']) && $item['variante']): ?>
                                        <span class="badge bg-light text-dark">
                                            <?php 
                                            $variante_parts = array_filter([
                                                $item['variante']['talla'] ?? '',
                                                $item['variante']['color'] ?? '',
                                                $item['variante']['material'] ?? ''
                                            ]);
                                            $variante_display = implode(' - ', $variante_parts);
                                            echo htmlspecialchars($variante_display);
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
                            
                            <!-- Productos personalizados -->
                            <?php foreach ($productos_personalizados as $producto): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-success">Personalizado</span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($producto['nombre_producto']); ?></strong>
                                    <br>
                                    <small class="text-muted">Producto personalizado</small>
                                </td>
                                <td>
                                    <?php if ($producto['talla']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($producto['talla']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin talla</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $producto['cantidad']; ?></td>
                                <td class="text-end">$<?php echo number_format($producto['precio_venta'], 2); ?></td>
                                <td class="text-end"><strong>$<?php echo number_format($producto['subtotal'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($subtotal_calculado, 2); ?></strong></td>
                            </tr>
                            <?php if ($cotizacion['descuento'] > 0): ?>
                            <tr>
                                <td colspan="4" class="text-end text-danger"><strong>Descuento:</strong></td>
                                <td class="text-end text-danger"><strong>-$<?php echo number_format($cotizacion['descuento'], 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($total_calculado, 2); ?></strong></td>
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
                
                <?php if ($cotizacion['notas']): ?>
                <div class="mt-3">
                    <h6>Notas</h6>
                    <div class="alert alert-warning">
                        <i class="fas fa-sticky-note me-2"></i>
                        <?php echo nl2br(htmlspecialchars($cotizacion['notas'])); ?>
                    </div>
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
                    
                    <?php if ($cotizacion['estado'] == 'en_espera_deposito'): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">
                            <i class="fas fa-credit-card me-2"></i>Registrar Pago
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($cotizacion['estado'] == 'pagada' || $cotizacion['estado'] == 'en_espera_deposito'): ?>
                        <button type="button" class="btn btn-primary" onclick="cambiarEstado('entregada')">
                            <i class="fas fa-truck me-2"></i>Marcar como Entregada
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-primary" onclick="imprimirCotizacion()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    
                    <button type="button" class="btn btn-success" onclick="exportarPDF()">
                        <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                    </button>
                    
                    <button type="button" class="btn btn-danger" onclick="deleteCotizacion()">
                        <i class="fas fa-trash me-2"></i>Eliminar Cotización
                    </button>
                    
                    <a href="cotizaciones.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Cotizaciones
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Módulo de Pagos -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-credit-card me-2"></i>Control de Pagos
                </h6>
            </div>
            <div class="card-body">
                <div id="modulo-pagos">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando información de pagos...
                    </div>
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
                        <span>Productos del Catálogo:</span>
                        <strong><?php echo count($items); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Productos Personalizados:</span>
                        <strong><?php echo count($productos_personalizados); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Total Productos:</span>
                        <strong><?php echo count($items) + count($productos_personalizados); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <strong>$<?php echo number_format($subtotal_calculado, 2); ?></strong>
                    </li>
                    <?php if ($cotizacion['descuento'] > 0): ?>
                    <li class="d-flex justify-content-between">
                        <span>Descuento:</span>
                        <span class="text-danger">-$<?php echo number_format($cotizacion['descuento'], 2); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="d-flex justify-content-between">
                        <span><strong>Total:</strong></span>
                        <strong class="text-success">$<?php echo number_format($total_calculado, 2); ?></strong>
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

<!-- Modal para registrar pago -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card me-2"></i>Registrar Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formRegistrarPago">
                    <input type="hidden" id="cotizacion_id_pago" value="<?php echo $id; ?>">
                    
                    <div class="mb-3">
                        <label for="monto_pago" class="form-label">Monto del Pago *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="monto_pago" 
                                   step="0.01" min="0.01" required>
                        </div>
                        <div class="form-text">
                            <span id="info_saldo_pendiente">Cargando información...</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="metodo_pago" class="form-label">Método de Pago *</label>
                        <select class="form-select" id="metodo_pago" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="cheque">Cheque</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="referencia_pago" class="form-label">Referencia</label>
                        <input type="text" class="form-control" id="referencia_pago" 
                               placeholder="Número de referencia, folio, etc.">
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones_pago" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_pago" rows="3" 
                                  placeholder="Notas adicionales sobre el pago"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="registrarPago()">
                    <i class="fas fa-save me-2"></i>Registrar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Historial de Pagos -->
<?php if ($cotizacion['estado'] == 'pagada' || $cotizacion['estado'] == 'entregada'): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Historial de Pagos
                </h6>
            </div>
            <div class="card-body">
                <div id="historial-pagos">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando historial de pagos...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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

/* Estilos para validación de monto */
#monto_pago.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

#monto_pago.is-valid {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

#info_saldo_pendiente {
    font-size: 0.875rem;
    line-height: 1.4;
}
</style>

<?php
$pageScripts = '<script src="js/cotizaciones_view.js?v=3"></script>';
require_once 'includes/footer.php';
?>
