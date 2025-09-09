<?php
// Incluir archivos necesarios sin output
require_once 'includes/paths.php';

$pageTitle = 'Crear Nueva Cotización';
$error = '';
$success = '';

// Obtener cliente preseleccionado si viene por GET
$cliente_preseleccionado = $_GET['cliente'] ?? '';

// Obtener clientes y productos para los selects
$clientes = readRecords('clientes', [], null, 'nombre ASC');
$productos = readRecords('productos', ['activo = 1'], null, 'nombre ASC');

if ($_POST) {
    $cliente_id = (int)$_POST['cliente_id'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $observaciones = sanitizeInput($_POST['observaciones']);
    $descuento = (float)$_POST['descuento'];
    
    // Validar datos
    if (!$cliente_id) {
        $error = 'Debe seleccionar un cliente';
    } else {
        // Generar número de cotización
        $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Verificar que el número no exista
        while (readRecords('cotizaciones', ["numero_cotizacion = '$numero_cotizacion'"])) {
            $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Calcular totales
        $subtotal = 0;
        $items = [];
        
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if ($item['producto_id'] && $item['cantidad'] > 0) {
                    $producto = getRecord('productos', $item['producto_id']);
                    if ($producto) {
                        $precio_unitario = $producto['precio_venta'];
                        $precio_extra = 0;
                        
                        // Si hay variante, obtener precio extra
                        if ($item['variante_id']) {
                            $variante = getRecord('variantes_producto', $item['variante_id']);
                            if ($variante) {
                                $precio_extra = $variante['precio_extra'];
                            }
                        }
                        
                        $precio_final = $precio_unitario + $precio_extra;
                        $item_subtotal = $precio_final * $item['cantidad'];
                        
                        $items[] = [
                            'producto_id' => $item['producto_id'],
                            'variante_id' => $item['variante_id'] ?: null,
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $precio_final,
                            'subtotal' => $item_subtotal
                        ];
                        
                        $subtotal += $item_subtotal;
                    }
                }
            }
        }
        
        if (empty($items)) {
            $error = 'Debe agregar al menos un producto a la cotización';
        } else {
            $total = $subtotal - $descuento;
            
            // Crear cotización
            $data = [
                'cliente_id' => $cliente_id,
                'usuario_id' => $_SESSION['user_id'],
                'numero_cotizacion' => $numero_cotizacion,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'estado' => 'pendiente',
                'fecha_vencimiento' => $fecha_vencimiento ?: null,
                'observaciones' => $observaciones
            ];
            
            if (createRecord('cotizaciones', $data)) {
                $cotizacion_id = $conn->insert_id;
                
                // Crear items de la cotización
                foreach ($items as $item) {
                    $item['cotizacion_id'] = $cotizacion_id;
                    createRecord('cotizacion_items', $item);
                }
                
                $success = 'Cotización creada exitosamente';
                // Redirigir a la vista de la cotización
                header("Location: cotizaciones_view.php?id=$cotizacion_id");
                exit;
            } else {
                $error = 'Error al crear la cotización';
            }
        }
    }
}

// Incluir header después del procesamiento
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>Crear Nueva Cotización
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="cotizacionForm">
                    <!-- Información básica -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cliente_id" class="form-label">Cliente *</label>
                                <select class="form-select" id="cliente_id" name="cliente_id" required>
                                    <option value="">Seleccionar cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo $cliente['id']; ?>" 
                                                <?php echo ($cliente_preseleccionado == $cliente['id'] || (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['nombre']); ?>
                                            <?php if ($cliente['empresa']): ?>
                                                - <?php echo htmlspecialchars($cliente['empresa']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_vencimiento" 
                                       name="fecha_vencimiento" 
                                       value="<?php echo $_POST['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" 
                                  id="observaciones" 
                                  name="observaciones" 
                                  rows="3" 
                                  placeholder="Observaciones adicionales para la cotización"><?php echo $_POST['observaciones'] ?? ''; ?></textarea>
                    </div>
                    
                    <!-- Productos -->
                    <div class="mb-4">
                        <label class="form-label">Productos *</label>
                        <div id="productosContainer">
                            <div class="producto-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="form-label">Producto</label>
                                        <select class="form-select producto-select" name="items[0][producto_id]" onchange="cargarVariantes(this, 0)">
                                            <option value="">Seleccionar producto</option>
                                            <?php foreach ($productos as $producto): ?>
                                                <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio_venta']; ?>">
                                                    <?php echo htmlspecialchars($producto['nombre']); ?> - $<?php echo number_format($producto['precio_venta'], 2); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Variante</label>
                                        <select class="form-select variante-select" name="items[0][variante_id]" onchange="calcularPrecio(this, 0)">
                                            <option value="">Sin variante</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" class="form-control cantidad-input" name="items[0][cantidad]" min="1" value="1" onchange="calcularPrecio(this, 0)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Precio</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control precio-display" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Subtotal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control subtotal-display" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeProducto(this)">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addProducto()">
                            <i class="fas fa-plus me-2"></i>Agregar Producto
                        </button>
                    </div>
                    
                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="descuento" class="form-label">Descuento</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="descuento" 
                                           name="descuento" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?php echo $_POST['descuento'] ?? '0'; ?>" 
                                           onchange="calcularTotales()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subtotal</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="subtotal" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="total" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Cotización
                        </button>
                        <a href="cotizaciones.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Resumen de la Cotización</h6>
            </div>
            <div class="card-body">
                <div id="resumenCotizacion">
                    <p class="text-muted">Agrega productos para ver el resumen</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-info-circle text-info me-2"></i>El número de cotización se genera automáticamente</li>
                    <li><i class="fas fa-calendar text-warning me-2"></i>La fecha de vencimiento es opcional</li>
                    <li><i class="fas fa-tags text-success me-2"></i>Puedes agregar múltiples productos</li>
                    <li><i class="fas fa-percentage text-primary me-2"></i>El descuento se aplica al total</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let productoCount = 1;

// Cargar variantes de un producto
function cargarVariantes(select, index) {
    const productoId = select.value;
    const varianteSelect = select.closest('.producto-item').querySelector('.variante-select');
    const precioBase = select.selectedOptions[0]?.dataset.precio || 0;
    
    // Actualizar precio base
    const precioDisplay = select.closest('.producto-item').querySelector('.precio-display');
    precioDisplay.value = parseFloat(precioBase).toFixed(2);
    
    // Limpiar variantes
    varianteSelect.innerHTML = '<option value="">Sin variante</option>';
    
    if (productoId) {
        // Cargar variantes via AJAX
        fetch('../ajax/productos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_variantes&producto_id=' + productoId
        })
        .then(response => response.json())
        .then(data => {
            console.log('Variantes recibidas:', data);
            if (data.success) {
                data.data.forEach(variante => {
                    const option = document.createElement('option');
                    option.value = variante.id;
                    option.dataset.precio_extra = variante.precio_extra;
                    option.textContent = `${variante.talla || ''} ${variante.color || ''} ${variante.material || ''}`.trim() || 'Variante sin nombre';
                    varianteSelect.appendChild(option);
                });
            } else {
                console.error('Error cargando variantes:', data.message);
            }
        })
        .catch(error => {
            console.error('Error de red:', error);
        });
    }
    
    calcularPrecio(select, index);
}

// Calcular precio de un producto
function calcularPrecio(element, index) {
    const productoItem = element.closest('.producto-item');
    const productoSelect = productoItem.querySelector('.producto-select');
    const varianteSelect = productoItem.querySelector('.variante-select');
    const cantidadInput = productoItem.querySelector('.cantidad-input');
    const precioDisplay = productoItem.querySelector('.precio-display');
    const subtotalDisplay = productoItem.querySelector('.subtotal-display');
    
    const precioBase = parseFloat(productoSelect.selectedOptions[0]?.dataset.precio || 0);
    const precioExtra = parseFloat(varianteSelect.selectedOptions[0]?.dataset.precio_extra || 0);
    const cantidad = parseFloat(cantidadInput.value || 0);
    
    const precioFinal = precioBase + precioExtra;
    const subtotal = precioFinal * cantidad;
    
    precioDisplay.value = precioFinal.toFixed(2);
    subtotalDisplay.value = subtotal.toFixed(2);
    
    calcularTotales();
}

// Calcular totales
function calcularTotales() {
    let subtotal = 0;
    
    document.querySelectorAll('.subtotal-display').forEach(display => {
        subtotal += parseFloat(display.value || 0);
    });
    
    const descuento = parseFloat(document.getElementById('descuento').value || 0);
    const total = subtotal - descuento;
    
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('total').value = total.toFixed(2);
    
    // Actualizar resumen
    actualizarResumen();
}

// Actualizar resumen
function actualizarResumen() {
    const resumen = document.getElementById('resumenCotizacion');
    const subtotal = parseFloat(document.getElementById('subtotal').value || 0);
    const descuento = parseFloat(document.getElementById('descuento').value || 0);
    const total = parseFloat(document.getElementById('total').value || 0);
    
    if (subtotal > 0) {
        resumen.innerHTML = `
            <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <strong>$${subtotal.toFixed(2)}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Descuento:</span>
                <span class="text-danger">-$${descuento.toFixed(2)}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span><strong>Total:</strong></span>
                <strong class="text-success">$${total.toFixed(2)}</strong>
            </div>
        `;
    } else {
        resumen.innerHTML = '<p class="text-muted">Agrega productos para ver el resumen</p>';
    }
}

// Agregar producto
function addProducto() {
    const container = document.getElementById('productosContainer');
    const productoHtml = `
        <div class="producto-item border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Producto</label>
                    <select class="form-select producto-select" name="items[${productoCount}][producto_id]" onchange="cargarVariantes(this, ${productoCount})">
                        <option value="">Seleccionar producto</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio_venta']; ?>">
                                <?php echo htmlspecialchars($producto['nombre']); ?> - $<?php echo number_format($producto['precio_venta'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Variante</label>
                    <select class="form-select variante-select" name="items[${productoCount}][variante_id]" onchange="calcularPrecio(this, ${productoCount})">
                        <option value="">Sin variante</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control cantidad-input" name="items[${productoCount}][cantidad]" min="1" value="1" onchange="calcularPrecio(this, ${productoCount})">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control precio-display" readonly>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Subtotal</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control subtotal-display" readonly>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeProducto(this)">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', productoHtml);
    productoCount++;
}

// Eliminar producto
function removeProducto(button) {
    button.closest('.producto-item').remove();
    calcularTotales();
}

// Validación del formulario
document.getElementById('cotizacionForm').addEventListener('submit', function(e) {
    const clienteId = document.getElementById('cliente_id').value;
    const productos = document.querySelectorAll('.producto-select');
    let tieneProductos = false;
    
    productos.forEach(select => {
        if (select.value) {
            const cantidad = select.closest('.producto-item').querySelector('.cantidad-input').value;
            if (parseInt(cantidad) > 0) {
                tieneProductos = true;
            }
        }
    });
    
    if (!clienteId) {
        e.preventDefault();
        showAlert('Debe seleccionar un cliente', 'danger');
        return;
    }
    
    if (!tieneProductos) {
        e.preventDefault();
        showAlert('Debe agregar al menos un producto con cantidad mayor a 0', 'danger');
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 5 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    calcularTotales();
});
</script>

<?php require_once 'includes/footer.php'; ?>
