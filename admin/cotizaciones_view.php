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

// Obtener items de la cotización
$items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');

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

// Calcular total final
$total_calculado = $subtotal_calculado - $cotizacion['descuento'];
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
                                    <?php if (isset($item['variante']) && $item['variante']): ?>
                                        <span class="badge bg-light text-dark">
                                            <?php 
                                            $variante_parts = array_filter([
                                                $item['variante']['talla'] ?? '',
                                                $item['variante']['color'] ?? '',
                                                $item['variante']['material'] ?? ''
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
                        <span>Productos:</span>
                        <strong><?php echo count($items); ?></strong>
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

<script>
// Función para cambiar estado
function cambiarEstado(estado) {
    console.log('DEBUG: Iniciando cambiarEstado desde cotizaciones_view');
    console.log('DEBUG: Estado:', estado);
    console.log('DEBUG: ID de cotización:', <?php echo $id; ?>);
    
    const estados = {
        'enviada': 'enviada',
        'aceptada': 'aceptada',
        'rechazada': 'rechazada',
        'pagada': 'pagada',
        'entregada': 'entregada'
    };
    
    const estadoTexto = estados[estado] || estado;
    console.log('DEBUG: Estado texto:', estadoTexto);
    
    if (confirm(`¿Estás seguro de marcar esta cotización como ${estadoTexto}?`)) {
        console.log('DEBUG: Usuario confirmó el cambio');
        
        const url = '../ajax/cotizaciones.php';
        const body = `action=change_status&id=<?php echo $id; ?>&estado=${estado}`;
        
        console.log('DEBUG: URL:', url);
        console.log('DEBUG: Body:', body);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => {
            console.log('DEBUG: Response status:', response.status);
            console.log('DEBUG: Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log('DEBUG: Response text:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('DEBUG: Parsed data:', data);
                
                if (data.success) {
                    console.log('DEBUG: Éxito - mostrando alerta y recargando');
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    console.log('DEBUG: Error - mostrando alerta de error');
                    showAlert(data.message, 'danger');
                }
            } catch (e) {
                console.error('DEBUG: Error parsing JSON:', e);
                console.error('DEBUG: Raw response:', text);
                showAlert('Error al procesar la respuesta del servidor', 'danger');
            }
        })
        .catch(error => {
            console.error('DEBUG: Error en fetch:', error);
            showAlert('Error al cambiar el estado de la cotización: ' + error.message, 'danger');
        });
    } else {
        console.log('DEBUG: Usuario canceló el cambio');
    }
}

// Función para imprimir
function imprimirCotizacion() {
    window.print();
}

// Función para exportar a PDF
function exportarPDF() {
    console.log('Iniciando exportación de PDF...');
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...';
    btn.disabled = true;
    
    // Crear datos para el PDF
    const cotizacionData = {
        numero: '<?php echo $cotizacion['numero_cotizacion']; ?>',
        fecha: '<?php echo formatDate($cotizacion['created_at']); ?>',
        cliente: {
            nombre: '<?php echo addslashes($cotizacion['cliente_nombre']); ?>',
            empresa: '<?php echo addslashes($cotizacion['cliente_empresa'] ?? ''); ?>',
            email: '<?php echo addslashes($cotizacion['cliente_email'] ?? ''); ?>',
            telefono: '<?php echo addslashes($cotizacion['cliente_telefono'] ?? ''); ?>'
        },
        items: <?php echo json_encode($items); ?>,
        subtotal: <?php echo $subtotal_calculado; ?>,
        descuento: <?php echo $cotizacion['descuento']; ?>,
        total: <?php echo $total_calculado; ?>,
        observaciones: '<?php echo addslashes($cotizacion['observaciones'] ?? ''); ?>',
        estado: '<?php echo $cotizacion['estado']; ?>'
    };
    
    console.log('Datos de cotización:', cotizacionData);
    
    // Enviar datos al servidor para generar PDF
    console.log('Enviando petición a ../ajax/generate_pdf.php...');
    
    fetch('../ajax/generate_pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'generate_cotizacion_pdf',
            data: cotizacionData
        })
    })
    .then(response => {
        console.log('Respuesta del servidor:', response.status, response.statusText);
        console.log('Content-Type:', response.headers.get('content-type'));
        
        if (response.ok) {
            return response.blob();
        } else {
            return response.text().then(text => {
                console.error('Error del servidor:', text);
                throw new Error('Error del servidor: ' + response.status + ' - ' + text);
            });
        }
    })
    .then(blob => {
        console.log('Blob recibido:', blob.type, blob.size, 'bytes');
        
        // Verificar si es un PDF válido
        if (blob.type === 'application/pdf' || blob.size > 0) {
            console.log('Generando enlace de descarga...');
            // Crear enlace de descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Cotizacion_${cotizacionData.numero}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            console.log('PDF descargado exitosamente');
        } else {
            console.log('No es PDF, mostrando como HTML...');
            // Si no es PDF, mostrar el contenido como HTML
            const reader = new FileReader();
            reader.onload = function(e) {
                console.log('Contenido HTML:', e.target.result);
                const newWindow = window.open('', '_blank');
                newWindow.document.write(e.target.result);
                newWindow.document.close();
            };
            reader.readAsText(blob);
        }
        
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Error generando PDF:', error);
        showAlert('Error al generar el PDF: ' + error.message, 'danger');
        
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Función para eliminar cotización
function deleteCotizacion() {
    if (confirm('¿Estás seguro de eliminar esta cotización? Esta acción no se puede deshacer.')) {
        fetch('../ajax/cotizaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=<?php echo $id; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = 'cotizaciones.php';
                }, 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al eliminar la cotización', 'danger');
        });
    }
}

// Función para cargar saldo pendiente en el modal
function cargarSaldoPendiente() {
    const cotizacionId = <?php echo $id; ?>;
    const totalCotizacion = <?php echo $total_calculado; ?>;
    
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', cotizacionId);
    
    fetch('../ajax/pagos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let totalPagado = 0;
            if (data.pagos && data.pagos.length > 0) {
                totalPagado = data.pagos.reduce((sum, pago) => sum + parseFloat(pago.monto), 0);
            }
            
            const saldoPendiente = totalCotizacion - totalPagado;
            const montoInput = document.getElementById('monto_pago');
            const infoSaldo = document.getElementById('info_saldo_pendiente');
            
            // Actualizar información del saldo
            infoSaldo.innerHTML = `
                <strong>Total de la cotización:</strong> $${totalCotizacion.toFixed(2)}<br>
                <strong>Total pagado:</strong> $${totalPagado.toFixed(2)}<br>
                <strong class="text-warning">Saldo pendiente:</strong> $${saldoPendiente.toFixed(2)}
            `;
            
            // Configurar el input de monto
            montoInput.max = saldoPendiente;
            montoInput.value = saldoPendiente;
            
            // Agregar validación en tiempo real
            montoInput.addEventListener('input', function() {
                const montoIngresado = parseFloat(this.value);
                if (montoIngresado > saldoPendiente) {
                    this.setCustomValidity(`El monto no puede exceder el saldo pendiente de $${saldoPendiente.toFixed(2)}`);
                    this.classList.add('is-invalid');
                } else if (montoIngresado <= 0) {
                    this.setCustomValidity('El monto debe ser mayor a 0');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });
            
        } else {
            document.getElementById('info_saldo_pendiente').innerHTML = 
                '<span class="text-danger">Error al cargar la información de pagos</span>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('info_saldo_pendiente').innerHTML = 
            '<span class="text-danger">Error al cargar la información de pagos</span>';
    });
}

// Función para registrar pago
function registrarPago() {
    const cotizacionId = document.getElementById('cotizacion_id_pago').value;
    const monto = parseFloat(document.getElementById('monto_pago').value);
    const metodoPago = document.getElementById('metodo_pago').value;
    const referencia = document.getElementById('referencia_pago').value;
    const observaciones = document.getElementById('observaciones_pago').value;
    
    // Validar monto
    if (monto <= 0) {
        showAlert('El monto debe ser mayor a 0', 'danger');
        return;
    }
    
    // Validar que no exceda el saldo pendiente
    const montoInput = document.getElementById('monto_pago');
    if (montoInput.checkValidity() === false) {
        showAlert(montoInput.validationMessage, 'danger');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registrando...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'registrar_pago');
    formData.append('cotizacion_id', cotizacionId);
    formData.append('monto', monto);
    formData.append('metodo_pago', metodoPago);
    formData.append('referencia', referencia);
    formData.append('observaciones', observaciones);
    
    fetch('../ajax/pagos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarPago'));
            modal.hide();
            // Recargar página para actualizar estado
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al registrar el pago', 'danger');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Función para cargar historial de pagos
function cargarHistorialPagos() {
    const cotizacionId = <?php echo $id; ?>;
    
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', cotizacionId);
    
    fetch('../ajax/pagos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarHistorialPagos(data.pagos);
        } else {
            document.getElementById('historial-pagos').innerHTML = 
                '<div class="text-center text-muted">No se pudo cargar el historial de pagos</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('historial-pagos').innerHTML = 
            '<div class="text-center text-danger">Error al cargar el historial de pagos</div>';
    });
}

// Función para mostrar historial de pagos
function mostrarHistorialPagos(pagos) {
    const container = document.getElementById('historial-pagos');
    
    if (pagos.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No hay pagos registrados</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr>';
    html += '<th>Fecha</th>';
    html += '<th>Monto</th>';
    html += '<th>Método</th>';
    html += '<th>Referencia</th>';
    html += '<th>Registrado por</th>';
    html += '<th>Observaciones</th>';
    html += '</tr></thead><tbody>';
    
    let totalPagado = 0;
    
    pagos.forEach(pago => {
        totalPagado += parseFloat(pago.monto);
        
        html += '<tr>';
        html += `<td>${new Date(pago.fecha_pago).toLocaleDateString('es-ES')}</td>`;
        html += `<td class="text-end"><strong>$${parseFloat(pago.monto).toFixed(2)}</strong></td>`;
        html += `<td><span class="badge bg-info">${pago.metodo_pago}</span></td>`;
        html += `<td>${pago.referencia || '-'}</td>`;
        html += `<td>${pago.usuario_nombre || 'Sistema'}</td>`;
        html += `<td>${pago.observaciones || '-'}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    // Agregar resumen
    const totalCotizacion = <?php echo $total_calculado; ?>;
    const pendiente = totalCotizacion - totalPagado;
    
    html += '<div class="row mt-3">';
    html += '<div class="col-md-4">';
    html += '<div class="card bg-light">';
    html += '<div class="card-body text-center">';
    html += '<h6 class="card-title">Total Cotización</h6>';
    html += `<h4 class="text-primary">$${totalCotizacion.toFixed(2)}</h4>`;
    html += '</div></div></div>';
    
    html += '<div class="col-md-4">';
    html += '<div class="card bg-success text-white">';
    html += '<div class="card-body text-center">';
    html += '<h6 class="card-title">Total Pagado</h6>';
    html += `<h4>$${totalPagado.toFixed(2)}</h4>`;
    html += '</div></div></div>';
    
    html += '<div class="col-md-4">';
    html += '<div class="card bg-warning text-dark">';
    html += '<div class="card-body text-center">';
    html += '<h6 class="card-title">Pendiente</h6>';
    html += `<h4>$${pendiente.toFixed(2)}</h4>`;
    html += '</div></div></div>';
    
    html += '</div>';
    
    container.innerHTML = html;
}

// Función para cargar módulo de pagos
function cargarModuloPagos() {
    const cotizacionId = <?php echo $id; ?>;
    
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', cotizacionId);
    
    fetch('../ajax/pagos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('DEBUG: Respuesta de pagos:', data);
        if (data.success) {
            console.log('DEBUG: Pagos recibidos:', data.pagos);
            mostrarModuloPagos(data.pagos);
        } else {
            console.log('DEBUG: Error en respuesta:', data.message);
            document.getElementById('modulo-pagos').innerHTML = 
                '<div class="text-center text-muted">No se pudo cargar la información de pagos: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modulo-pagos').innerHTML = 
            '<div class="text-center text-danger">Error al cargar la información de pagos</div>';
    });
}

// Función para mostrar módulo de pagos
function mostrarModuloPagos(pagos) {
    console.log('DEBUG: Mostrando módulo de pagos con datos:', pagos);
    const container = document.getElementById('modulo-pagos');
    const totalCotizacion = <?php echo $total_calculado; ?>;
    
    let totalPagado = 0;
    if (pagos && pagos.length > 0) {
        totalPagado = pagos.reduce((sum, pago) => sum + parseFloat(pago.monto), 0);
        console.log('DEBUG: Total pagado calculado:', totalPagado);
    } else {
        console.log('DEBUG: No hay pagos o array vacío');
    }
    
    const pendiente = totalCotizacion - totalPagado;
    const porcentajePagado = totalCotizacion > 0 ? (totalPagado / totalCotizacion) * 100 : 0;
    
    let html = '';
    
    // Resumen de pagos con cards
    html += '<div class="row mb-4">';
    
    // Total de la cotización
    html += '<div class="col-md-4">';
    html += '<div class="card bg-light h-100">';
    html += '<div class="card-body text-center">';
    html += '<i class="fas fa-file-invoice fa-2x text-primary mb-2"></i>';
    html += '<h6 class="card-title">Total Cotización</h6>';
    html += `<h4 class="text-primary">$${totalCotizacion.toFixed(2)}</h4>`;
    html += '</div></div></div>';
    
    // Total pagado
    html += '<div class="col-md-4">';
    html += '<div class="card bg-success text-white h-100">';
    html += '<div class="card-body text-center">';
    html += '<i class="fas fa-check-circle fa-2x mb-2"></i>';
    html += '<h6 class="card-title">Total Pagado</h6>';
    html += `<h4>$${totalPagado.toFixed(2)}</h4>`;
    html += `<small>${porcentajePagado.toFixed(1)}% completado</small>`;
    html += '</div></div></div>';
    
    // Pendiente
    html += '<div class="col-md-4">';
    const pendienteClass = pendiente > 0 ? 'bg-warning text-dark' : 'bg-success text-white';
    const pendienteIcon = pendiente > 0 ? 'fas fa-clock' : 'fas fa-check-double';
    html += `<div class="card ${pendienteClass} h-100">`;
    html += '<div class="card-body text-center">';
    html += `<i class="${pendienteIcon} fa-2x mb-2"></i>`;
    html += '<h6 class="card-title">Saldo Pendiente</h6>';
    html += `<h4>$${pendiente.toFixed(2)}</h4>`;
    if (pendiente <= 0) {
        html += '<small>¡Completamente pagado!</small>';
    } else {
        html += `<small>${(100 - porcentajePagado).toFixed(1)}% pendiente</small>`;
    }
    html += '</div></div></div>';
    
    html += '</div>';
    
    // Barra de progreso
    html += '<div class="mb-4">';
    html += '<div class="d-flex justify-content-between mb-1">';
    html += '<span>Progreso de Pago</span>';
    html += `<span>${porcentajePagado.toFixed(1)}%</span>`;
    html += '</div>';
    html += '<div class="progress" style="height: 20px;">';
    html += `<div class="progress-bar bg-success" role="progressbar" style="width: ${porcentajePagado}%" aria-valuenow="${porcentajePagado}" aria-valuemin="0" aria-valuemax="100">`;
    html += `${porcentajePagado.toFixed(1)}%`;
    html += '</div></div></div>';
    
    // Lista de pagos - siempre mostrar la tabla
    html += '<h6 class="mb-3"><i class="fas fa-list me-2"></i>Pagos Registrados</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm table-striped">';
    html += '<thead style="background-color: #f8f9fa;">';
    html += '<tr>';
    html += '<th style="color: #000000; font-weight: 600;">Fecha</th>';
    html += '<th style="color: #000000; font-weight: 600;">Monto</th>';
    html += '<th style="color: #000000; font-weight: 600;">Método</th>';
    html += '<th style="color: #000000; font-weight: 600;">Referencia</th>';
    html += '<th style="color: #000000; font-weight: 600;">Usuario</th>';
    html += '</tr>';
    html += '</thead><tbody>';
    
    if (pagos && pagos.length > 0) {
        pagos.forEach(pago => {
            html += '<tr>';
            html += `<td>${new Date(pago.fecha_pago).toLocaleDateString('es-ES')}</td>`;
            html += `<td class="text-end"><strong>$${parseFloat(pago.monto).toFixed(2)}</strong></td>`;
            html += `<td><span class="badge bg-info">${pago.metodo_pago}</span></td>`;
            html += `<td>${pago.referencia || '-'}</td>`;
            html += `<td>${pago.usuario_nombre || 'Sistema'}</td>`;
            html += '</tr>';
        });
    } else {
        html += '<tr>';
        html += '<td colspan="5" class="text-center text-muted py-3">';
        html += '<i class="fas fa-credit-card fa-2x mb-2 d-block"></i>';
        html += 'No hay pagos registrados';
        html += '</td>';
        html += '</tr>';
    }
    
    html += '</tbody></table></div>';
    
    // Botones de acción según el estado de pagos
    html += '<div class="text-center mt-3">';
    
    if (pendiente <= 0) {
        // Completamente pagado - mostrar botón de entregada
        html += '<button type="button" class="btn btn-primary" onclick="cambiarEstado(\'entregada\')">';
        html += '<i class="fas fa-truck me-2"></i>Marcar como Entregada';
        html += '</button>';
    } else if (totalPagado > 0) {
        // Parcialmente pagado - mostrar ambos botones
        html += '<button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">';
        html += '<i class="fas fa-plus me-2"></i>Registrar Pago';
        html += '</button>';
        html += '<button type="button" class="btn btn-primary" onclick="cambiarEstado(\'entregada\')">';
        html += '<i class="fas fa-truck me-2"></i>Marcar como Entregada';
        html += '</button>';
    } else {
        // Sin pagos - solo botón de registrar pago
        html += '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">';
        html += '<i class="fas fa-plus me-2"></i>Registrar Pago';
        html += '</button>';
    }
    
    html += '</div>';
    
    container.innerHTML = html;
    
    // Actualizar botones de la sección de acciones principales
    actualizarBotonesAcciones(pendiente, totalPagado);
}

// Función para actualizar botones de acciones principales
function actualizarBotonesAcciones(pendiente, totalPagado) {
    const accionesContainer = document.querySelector('.card-body .d-grid');
    if (!accionesContainer) return;
    
    // Buscar botones existentes
    const botonRegistrarPago = accionesContainer.querySelector('[data-bs-target="#modalRegistrarPago"]');
    const botonEntregada = accionesContainer.querySelector('[onclick="cambiarEstado(\'entregada\')"]');
    
    if (pendiente <= 0) {
        // Completamente pagado - ocultar botón de registrar pago, mostrar entregada
        if (botonRegistrarPago) {
            botonRegistrarPago.style.display = 'none';
        }
        if (botonEntregada) {
            botonEntregada.style.display = 'block';
        }
    } else if (totalPagado > 0) {
        // Parcialmente pagado - mostrar ambos botones
        if (botonRegistrarPago) {
            botonRegistrarPago.style.display = 'block';
        }
        if (botonEntregada) {
            botonEntregada.style.display = 'block';
        }
    } else {
        // Sin pagos - solo botón de registrar pago
        if (botonRegistrarPago) {
            botonRegistrarPago.style.display = 'block';
        }
        if (botonEntregada) {
            botonEntregada.style.display = 'none';
        }
    }
}

// Cargar módulo de pagos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarModuloPagos();
    
    <?php if ($cotizacion['estado'] == 'pagada' || $cotizacion['estado'] == 'entregada'): ?>
    cargarHistorialPagos();
    <?php endif; ?>
    
    // Cargar saldo pendiente cuando se abre el modal de registro de pago
    const modalRegistrarPago = document.getElementById('modalRegistrarPago');
    if (modalRegistrarPago) {
        modalRegistrarPago.addEventListener('show.bs.modal', function() {
            cargarSaldoPendiente();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
