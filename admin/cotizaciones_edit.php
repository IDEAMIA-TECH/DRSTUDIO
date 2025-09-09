<?php
// Incluir archivos necesarios sin output
require_once 'includes/paths.php';

$pageTitle = 'Editar Cotización';
$error = '';
$success = '';

// Obtener ID de la cotización
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: cotizaciones.php');
    exit;
}

// Obtener datos de la cotización
$cotizacion = getRecord('cotizaciones', $id);
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
        // Calcular totales
        $subtotal = 0;
        $items_data = [];
        
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
                        $subtotal_item = $precio_final * $item['cantidad'];
                        
                        $items_data[] = [
                            'producto_id' => $item['producto_id'],
                            'variante_id' => $item['variante_id'] ?: null,
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $precio_final,
                            'subtotal' => $subtotal_item
                        ];
                        
                        $subtotal += $subtotal_item;
                    }
                }
            }
        }
        
        $total = $subtotal - $descuento;
        
        // Actualizar cotización
        $cotizacion_data = [
            'cliente_id' => $cliente_id,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'fecha_vencimiento' => $fecha_vencimiento ?: null,
            'observaciones' => $observaciones
        ];
        
        if (updateRecord('cotizaciones', $cotizacion_data, $id)) {
            // Eliminar items existentes
            $conn->query("DELETE FROM cotizacion_items WHERE cotizacion_id = $id");
            
            // Insertar nuevos items
            foreach ($items_data as $item_data) {
                $item_data['cotizacion_id'] = $id;
                createRecord('cotizacion_items', $item_data);
            }
            
            $success = 'Cotización actualizada exitosamente';
            // Recargar datos actualizados
            $cotizacion = getRecord('cotizaciones', $id);
            $items = readRecords('cotizacion_items', ["cotizacion_id = $id"], null, 'id ASC');
            
            // Obtener información de productos para los items actualizados
            foreach ($items as &$item) {
                $producto = getRecord('productos', $item['producto_id']);
                $item['producto'] = $producto;
                
                if ($item['variante_id']) {
                    $variante = getRecord('variantes_producto', $item['variante_id']);
                    $item['variante'] = $variante;
                }
            }
        } else {
            $error = 'Error al actualizar la cotización';
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
                    <i class="fas fa-edit me-2"></i>Editar Cotización <?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" id="cotizacionForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>" 
                                            <?php echo ($cliente['id'] == $cotizacion['cliente_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente['nombre']); ?>
                                        <?php if ($cliente['empresa']): ?>
                                            - <?php echo htmlspecialchars($cliente['empresa']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" 
                                   value="<?php echo $cotizacion['fecha_vencimiento']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($cotizacion['observaciones']); ?></textarea>
                    </div>
                    
                    <!-- Items de la cotización -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6>Productos</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="agregarItem()">
                                <i class="fas fa-plus me-1"></i>Agregar Producto
                            </button>
                        </div>
                        
                        <div id="itemsContainer">
                            <?php foreach ($items as $index => $item): ?>
                                <div class="item-row border p-3 mb-3 rounded">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Producto</label>
                                            <select class="form-select producto-select" name="items[<?php echo $index; ?>][producto_id]" required>
                                                <option value="">Seleccionar producto</option>
                                                <?php foreach ($productos as $producto): ?>
                                                    <option value="<?php echo $producto['id']; ?>" 
                                                            <?php echo ($producto['id'] == $item['producto_id']) ? 'selected' : ''; ?>
                                                            data-precio="<?php echo $producto['precio_venta']; ?>">
                                                        <?php echo htmlspecialchars($producto['nombre']); ?> - $<?php echo number_format($producto['precio_venta'], 2); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Variante</label>
                                            <select class="form-select variante-select" name="items[<?php echo $index; ?>][variante_id]">
                                                <option value="">Sin variante</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Cantidad</label>
                                            <input type="number" class="form-control cantidad-input" name="items[<?php echo $index; ?>][cantidad]" 
                                                   value="<?php echo $item['cantidad']; ?>" min="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Precio Unit.</label>
                                            <input type="text" class="form-control precio-input" readonly 
                                                   value="$<?php echo number_format($item['precio_unitario'], 2); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-sm btn-danger d-block" onclick="eliminarItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($item['producto']['sku']); ?></small>
                                        </div>
                                        <div class="col-md-8 text-end">
                                            <strong>Subtotal: $<span class="subtotal-display"><?php echo number_format($item['subtotal'], 2); ?></span></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Totales -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="descuento" class="form-label">Descuento ($)</label>
                            <input type="number" class="form-control" id="descuento" name="descuento" 
                                   value="<?php echo $cotizacion['descuento']; ?>" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <span id="subtotalDisplay">$<?php echo number_format($cotizacion['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Descuento:</span>
                                        <span id="descuentoDisplay">-$<?php echo number_format($cotizacion['descuento'], 2); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="totalDisplay">$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Cotización
                        </button>
                        <a href="cotizaciones_view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-eye me-2"></i>Ver Cotización
                        </a>
                        <a href="cotizaciones.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let itemIndex = <?php echo count($items); ?>;

// Función para agregar un nuevo item
function agregarItem() {
    const container = document.getElementById('itemsContainer');
    const newItem = document.createElement('div');
    newItem.className = 'item-row border p-3 mb-3 rounded';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <select class="form-select producto-select" name="items[${itemIndex}][producto_id]" required>
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
                <select class="form-select variante-select" name="items[${itemIndex}][variante_id]">
                    <option value="">Sin variante</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control cantidad-input" name="items[${itemIndex}][cantidad]" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Precio Unit.</label>
                <input type="text" class="form-control precio-input" readonly value="$0.00">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-danger d-block" onclick="eliminarItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4">
                <small class="text-muted">SKU: -</small>
            </div>
            <div class="col-md-8 text-end">
                <strong>Subtotal: $<span class="subtotal-display">0.00</span></strong>
            </div>
        </div>
    `;
    
    container.appendChild(newItem);
    itemIndex++;
    
    // Agregar event listeners
    addItemEventListeners(newItem);
}

// Función para eliminar un item
function eliminarItem(button) {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        button.closest('.item-row').remove();
        calcularTotales();
    }
}

// Función para agregar event listeners a un item
function addItemEventListeners(itemRow) {
    const productoSelect = itemRow.querySelector('.producto-select');
    const varianteSelect = itemRow.querySelector('.variante-select');
    const cantidadInput = itemRow.querySelector('.cantidad-input');
    const precioInput = itemRow.querySelector('.precio-input');
    const subtotalDisplay = itemRow.querySelector('.subtotal-display');
    
    // Event listener para cambio de producto
    productoSelect.addEventListener('change', function() {
        const precio = parseFloat(this.selectedOptions[0]?.dataset.precio || 0);
        precioInput.value = '$' + precio.toFixed(2);
        cargarVariantes(this.value, varianteSelect);
        calcularSubtotal(itemRow);
    });
    
    // Event listener para cambio de variante
    varianteSelect.addEventListener('change', function() {
        calcularSubtotal(itemRow);
    });
    
    // Event listener para cambio de cantidad
    cantidadInput.addEventListener('input', function() {
        calcularSubtotal(itemRow);
    });
}

// Función para cargar variantes de un producto
function cargarVariantes(productoId, varianteSelect) {
    if (!productoId) {
        varianteSelect.innerHTML = '<option value="">Sin variante</option>';
        return;
    }
    
    fetch('../ajax/productos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_variantes&producto_id=${productoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            varianteSelect.innerHTML = '<option value="">Sin variante</option>';
            data.data.forEach(variante => {
                const option = document.createElement('option');
                option.value = variante.id;
                option.textContent = `${variante.talla} - ${variante.color} - ${variante.material} (+$${parseFloat(variante.precio_extra).toFixed(2)})`;
                option.dataset.precioExtra = variante.precio_extra;
                varianteSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error cargando variantes:', error);
    });
}

// Función para calcular subtotal de un item
function calcularSubtotal(itemRow) {
    const productoSelect = itemRow.querySelector('.producto-select');
    const varianteSelect = itemRow.querySelector('.variante-select');
    const cantidadInput = itemRow.querySelector('.cantidad-input');
    const precioInput = itemRow.querySelector('.precio-input');
    const subtotalDisplay = itemRow.querySelector('.subtotal-display');
    
    const precioBase = parseFloat(productoSelect.selectedOptions[0]?.dataset.precio || 0);
    const precioExtra = parseFloat(varianteSelect.selectedOptions[0]?.dataset.precioExtra || 0);
    const cantidad = parseFloat(cantidadInput.value || 0);
    
    const precioTotal = precioBase + precioExtra;
    const subtotal = precioTotal * cantidad;
    
    precioInput.value = '$' + precioTotal.toFixed(2);
    subtotalDisplay.textContent = subtotal.toFixed(2);
    
    calcularTotales();
}

// Función para calcular totales generales
function calcularTotales() {
    let subtotal = 0;
    
    document.querySelectorAll('.item-row').forEach(itemRow => {
        const subtotalDisplay = itemRow.querySelector('.subtotal-display');
        subtotal += parseFloat(subtotalDisplay.textContent || 0);
    });
    
    const descuento = parseFloat(document.getElementById('descuento').value || 0);
    const total = subtotal - descuento;
    
    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('descuentoDisplay').textContent = '-$' + descuento.toFixed(2);
    document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
}

// Event listener para descuento
document.getElementById('descuento').addEventListener('input', calcularTotales);

// Agregar event listeners a items existentes
document.querySelectorAll('.item-row').forEach(itemRow => {
    addItemEventListeners(itemRow);
});

// Cargar variantes para items existentes
document.querySelectorAll('.producto-select').forEach(select => {
    if (select.value) {
        const varianteSelect = select.closest('.item-row').querySelector('.variante-select');
        cargarVariantes(select.value, varianteSelect);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
