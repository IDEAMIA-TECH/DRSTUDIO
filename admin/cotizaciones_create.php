<?php
// Incluir archivos necesarios sin output
require_once 'includes/paths.php';
require_once '../includes/cotizacion_detalles_helper.php';

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
    $notas = sanitizeInput($_POST['notas']);
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
        $productos_personalizados = [];
        
        // Procesar productos del catálogo
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if ($item['producto_id'] && $item['cantidad'] > 0) {
                    $producto = getRecord('productos', $item['producto_id']);
                    if ($producto) {
                        // Usar el precio personalizado ingresado por el usuario
                        $precio_unitario = (float)$item['precio_unitario'];
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
        
        // Procesar productos personalizados
        if (isset($_POST['productos_personalizados']) && is_array($_POST['productos_personalizados'])) {
            foreach ($_POST['productos_personalizados'] as $producto) {
                if ($producto['nombre_producto'] && $producto['cantidad'] > 0 && $producto['precio_venta'] > 0 && $producto['costo_fabricacion'] > 0) {
                    $cantidad = (int)$producto['cantidad'];
                    $precio_venta = (float)$producto['precio_venta'];
                    $costo_fabricacion = (float)$producto['costo_fabricacion'];
                    $subtotal_producto = $precio_venta * $cantidad;
                    $costo_total = $costo_fabricacion * $cantidad;
                    $ganancia = $subtotal_producto - $costo_total;
                    $margen_ganancia = $subtotal_producto > 0 ? ($ganancia / $subtotal_producto) * 100 : 0;
                    
                    $productos_personalizados[] = [
                        'nombre_producto' => sanitizeInput($producto['nombre_producto']),
                        'talla' => sanitizeInput($producto['talla']),
                        'cantidad' => $cantidad,
                        'precio_venta' => $precio_venta,
                        'costo_fabricacion' => $costo_fabricacion,
                        'subtotal' => $subtotal_producto,
                        'costo_total' => $costo_total,
                        'ganancia' => $ganancia,
                        'margen_ganancia' => $margen_ganancia
                    ];
                    
                    $subtotal += $subtotal_producto;
                }
            }
        }
        
        if (empty($items) && empty($productos_personalizados)) {
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
                'observaciones' => $observaciones,
                'notas' => $notas
            ];
            
            if (createRecord('cotizaciones', $data)) {
                $cotizacion_id = $conn->insert_id;
                
                // Crear items de la cotización (productos del catálogo)
                foreach ($items as $item) {
                    $item['cotizacion_id'] = $cotizacion_id;
                    createRecord('cotizacion_items', $item);
                }
                
                // Crear productos personalizados
                foreach ($productos_personalizados as $producto) {
                    $producto['cotizacion_id'] = $cotizacion_id;
                    createRecord('cotizacion_productos_personalizados', $producto);
                }
                
                // Crear detalles de cotización para análisis de ganancias (productos del catálogo)
                foreach ($items as $item) {
                    $producto = getRecord('productos', $item['producto_id']);
                    if ($producto) {
                        $precio_unitario = $item['precio_unitario'];
                        $costo_unitario = $producto['costo_fabricacion'];
                        $cantidad = $item['cantidad'];
                        $subtotal = $item['subtotal'];
                        $costo_total = $costo_unitario * $cantidad;
                        $ganancia = $subtotal - $costo_total;
                        $margen_ganancia = $subtotal > 0 ? ($ganancia / $subtotal) * 100 : 0;
                        
                        $detalle_data = [
                            'cotizacion_id' => $cotizacion_id,
                            'producto_id' => $item['producto_id'],
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precio_unitario,
                            'costo_unitario' => $costo_unitario,
                            'subtotal' => $subtotal,
                            'costo_total' => $costo_total,
                            'ganancia' => $ganancia,
                            'margen_ganancia' => $margen_ganancia
                        ];
                        
                        createRecord('cotizacion_detalles', $detalle_data);
                    }
                }
                
                // Crear detalles de cotización para análisis de ganancias (productos personalizados)
                foreach ($productos_personalizados as $producto) {
                    $detalle_data = [
                        'cotizacion_id' => $cotizacion_id,
                        'producto_id' => null, // No hay producto_id para productos personalizados
                        'cantidad' => $producto['cantidad'],
                        'precio_unitario' => $producto['precio_venta'],
                        'costo_unitario' => $producto['costo_fabricacion'],
                        'subtotal' => $producto['subtotal'],
                        'costo_total' => $producto['costo_total'],
                        'ganancia' => $producto['ganancia'],
                        'margen_ganancia' => $producto['margen_ganancia']
                    ];
                    
                    createRecord('cotizacion_detalles', $detalle_data);
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" 
                                          id="observaciones" 
                                          name="observaciones" 
                                          rows="3" 
                                          placeholder="Observaciones adicionales para la cotización"><?php echo $_POST['observaciones'] ?? ''; ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notas" class="form-label">Notas</label>
                                <textarea class="form-control" 
                                          id="notas" 
                                          name="notas" 
                                          rows="3" 
                                          placeholder="Notas internas para la cotización"><?php echo $_POST['notas'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Productos -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label mb-0">Productos *</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addProducto()">
                                    <i class="fas fa-plus me-2"></i>Producto del Catálogo
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addProductoPersonalizado()">
                                    <i class="fas fa-edit me-2"></i>Producto Personalizado
                                </button>
                            </div>
                        </div>
                        <div id="productosContainer">
                            <!-- Los productos se agregarán dinámicamente aquí -->
                        </div>
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
                    <li><i class="fas fa-tags text-success me-2"></i>Puedes agregar productos del catálogo</li>
                    <li><i class="fas fa-edit text-success me-2"></i>O crear productos personalizados</li>
                    <li><i class="fas fa-dollar-sign text-primary me-2"></i>Precios y costos personalizables</li>
                    <li><i class="fas fa-percentage text-primary me-2"></i>El descuento se aplica al total</li>
                    <li><i class="fas fa-sticky-note text-secondary me-2"></i>Las notas aparecerán en el PDF generado</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let productoCount = 1;
let productoPersonalizadoCount = 1;

// Cargar variantes de un producto
function cargarVariantes(select, index) {
    const productoId = select.value;
    const varianteSelect = select.closest('.producto-item').querySelector('.variante-select');
    const precioBase = select.selectedOptions[0]?.dataset.precio || 0;
    
    // Actualizar precio base en el input editable
    const precioInput = select.closest('.producto-item').querySelector('.precio-input');
    precioInput.value = parseFloat(precioBase).toFixed(2);
    
    // Limpiar variantes
    varianteSelect.innerHTML = '<option value="">Sin talla</option>';
    
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
                    option.textContent = variante.talla || 'Talla sin especificar';
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

// Actualizar precio cuando se selecciona una variante
function actualizarPrecioConVariante(select, index) {
    const productoItem = select.closest('.producto-item');
    const precioInput = productoItem.querySelector('.precio-input');
    const productoSelect = productoItem.querySelector('.producto-select');
    
    const precioBase = parseFloat(productoSelect.selectedOptions[0]?.dataset.precio || 0);
    const precioExtra = parseFloat(select.selectedOptions[0]?.dataset.precio_extra || 0);
    
    // Actualizar el precio base con el extra de la variante
    precioInput.value = (precioBase + precioExtra).toFixed(2);
    
    calcularPrecio(select, index);
}

// Calcular precio de un producto
function calcularPrecio(element, index) {
    const productoItem = element.closest('.producto-item');
    const productoSelect = productoItem.querySelector('.producto-select');
    const varianteSelect = productoItem.querySelector('.variante-select');
    const cantidadInput = productoItem.querySelector('.cantidad-input');
    const precioInput = productoItem.querySelector('.precio-input');
    const subtotalDisplay = productoItem.querySelector('.subtotal-display');
    
    const precioBase = parseFloat(precioInput.value || 0);
    const precioExtra = parseFloat(varianteSelect.selectedOptions[0]?.dataset.precio_extra || 0);
    const cantidad = parseFloat(cantidadInput.value || 0);
    
    const precioFinal = precioBase + precioExtra;
    const subtotal = precioFinal * cantidad;
    
    subtotalDisplay.value = subtotal.toFixed(2);
    
    calcularTotales();
}

// Calcular totales
function calcularTotales() {
    let subtotal = 0;
    
    // Sumar subtotales de productos del catálogo
    document.querySelectorAll('.producto-item .subtotal-display').forEach(display => {
        subtotal += parseFloat(display.value || 0);
    });
    
    // Sumar subtotales de productos personalizados
    document.querySelectorAll('.producto-personalizado-item .subtotal-display').forEach(display => {
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

// Agregar producto del catálogo
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
                    <label class="form-label">Talla</label>
                    <select class="form-select variante-select" name="items[${productoCount}][variante_id]" onchange="actualizarPrecioConVariante(this, ${productoCount})">
                        <option value="">Sin talla</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control cantidad-input" name="items[${productoCount}][cantidad]" min="1" value="1" onchange="calcularPrecio(this, ${productoCount})">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio Unitario</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               class="form-control precio-input" 
                               name="items[${productoCount}][precio_unitario]"
                               step="0.01" 
                               min="0" 
                               value="0" 
                               onchange="calcularPrecio(this, ${productoCount})"
                               placeholder="0.00">
                    </div>
                    <small class="text-muted">Precio personalizable según diseño</small>
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

// Agregar producto personalizado
function addProductoPersonalizado() {
    const container = document.getElementById('productosContainer');
    const productoHtml = `
        <div class="producto-personalizado-item border rounded p-3 mb-3" style="border-color: #28a745 !important;">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Nombre del Producto *</label>
                    <input type="text" class="form-control" name="productos_personalizados[${productoPersonalizadoCount}][nombre_producto]" 
                           placeholder="Ej: Playera personalizada" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Talla</label>
                    <input type="text" class="form-control" name="productos_personalizados[${productoPersonalizadoCount}][talla]" 
                           placeholder="S, M, L, XL">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" class="form-control cantidad-input" name="productos_personalizados[${productoPersonalizadoCount}][cantidad]" 
                           min="1" value="1" onchange="calcularProductoPersonalizado(this)" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio Venta *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control precio-venta-input" name="productos_personalizados[${productoPersonalizadoCount}][precio_venta]" 
                               step="0.01" min="0" value="0" onchange="calcularProductoPersonalizado(this)" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Costo Fabricación *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control costo-input" name="productos_personalizados[${productoPersonalizadoCount}][costo_fabricacion]" 
                               step="0.01" min="0" value="0" onchange="calcularProductoPersonalizado(this)" required>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <label class="form-label">Subtotal</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control subtotal-display" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Costo Total</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control costo-total-display" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ganancia</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control ganancia-display" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Margen %</label>
                    <div class="input-group">
                        <input type="text" class="form-control margen-display" readonly>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeProductoPersonalizado(this)">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', productoHtml);
    productoPersonalizadoCount++;
}

// Eliminar producto
function removeProducto(button) {
    button.closest('.producto-item').remove();
    calcularTotales();
}

// Eliminar producto personalizado
function removeProductoPersonalizado(button) {
    button.closest('.producto-personalizado-item').remove();
    calcularTotales();
}

// Calcular producto personalizado
function calcularProductoPersonalizado(element) {
    const item = element.closest('.producto-personalizado-item');
    const cantidad = parseFloat(item.querySelector('.cantidad-input').value || 0);
    const precioVenta = parseFloat(item.querySelector('.precio-venta-input').value || 0);
    const costo = parseFloat(item.querySelector('.costo-input').value || 0);
    
    const subtotal = precioVenta * cantidad;
    const costoTotal = costo * cantidad;
    const ganancia = subtotal - costoTotal;
    const margen = subtotal > 0 ? (ganancia / subtotal) * 100 : 0;
    
    item.querySelector('.subtotal-display').value = subtotal.toFixed(2);
    item.querySelector('.costo-total-display').value = costoTotal.toFixed(2);
    item.querySelector('.ganancia-display').value = ganancia.toFixed(2);
    item.querySelector('.margen-display').value = margen.toFixed(2);
    
    calcularTotales();
}

// Validación del formulario
document.getElementById('cotizacionForm').addEventListener('submit', function(e) {
    const clienteId = document.getElementById('cliente_id').value;
    const productos = document.querySelectorAll('.producto-select');
    const productosPersonalizados = document.querySelectorAll('.producto-personalizado-item');
    let tieneProductos = false;
    
    // Verificar productos del catálogo
    productos.forEach(select => {
        if (select.value) {
            const cantidad = select.closest('.producto-item').querySelector('.cantidad-input').value;
            if (parseInt(cantidad) > 0) {
                tieneProductos = true;
            }
        }
    });
    
    // Verificar productos personalizados
    productosPersonalizados.forEach(item => {
        const nombre = item.querySelector('input[name*="nombre_producto"]').value;
        const cantidad = item.querySelector('.cantidad-input').value;
        const precioVenta = item.querySelector('.precio-venta-input').value;
        const costo = item.querySelector('.costo-input').value;
        
        if (nombre && parseInt(cantidad) > 0 && parseFloat(precioVenta) > 0 && parseFloat(costo) > 0) {
            tieneProductos = true;
        }
    });
    
    if (!clienteId) {
        e.preventDefault();
        showAlert('Debe seleccionar un cliente', 'danger');
        return;
    }
    
    if (!tieneProductos) {
        e.preventDefault();
        showAlert('Debe agregar al menos un producto (del catálogo o personalizado) con cantidad mayor a 0', 'danger');
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
