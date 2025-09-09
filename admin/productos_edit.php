<?php
$pageTitle = 'Editar Producto';
require_once 'includes/header.php';

$error = '';
$success = '';

// Obtener ID del producto
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: productos.php');
    exit;
}

// Obtener datos del producto
$producto = getRecord('productos', $id);
if (!$producto) {
    header('Location: productos.php');
    exit;
}

// Obtener variantes del producto
$variantes = readRecords('variantes_producto', ["producto_id = $id"], null, 'id ASC');

// Obtener categorías para el select
$categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');

if ($_POST) {
    $categoria_id = (int)$_POST['categoria_id'];
    $sku = sanitizeInput($_POST['sku']);
    $nombre = sanitizeInput($_POST['nombre']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $precio_venta = (float)$_POST['precio_venta'];
    $costo_fabricacion = (float)$_POST['costo_fabricacion'];
    $tiempo_entrega = (int)$_POST['tiempo_entrega'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    if (empty($sku) || empty($nombre) || $precio_venta <= 0 || $costo_fabricacion <= 0) {
        $error = 'Todos los campos requeridos deben ser completados correctamente';
    } else {
        // Verificar si ya existe otro producto con el mismo SKU
        $existing = readRecords('productos', ["sku = '$sku'", "id != $id"]);
        if (!empty($existing)) {
            $error = 'Ya existe otro producto con este SKU';
        } else {
            // Procesar imagen principal
            $imagen_principal = $producto['imagen_principal']; // Mantener imagen actual por defecto
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
                $newImagen = uploadFile($_FILES['imagen_principal'], '../uploads/productos/');
                if ($newImagen) {
                    // Eliminar imagen anterior si existe
                    if ($imagen_principal && file_exists('../' . $imagen_principal)) {
                        deleteFile('../' . $imagen_principal);
                    }
                    $imagen_principal = $newImagen;
                } else {
                    $error = 'Error al subir la imagen principal';
                }
            }
            
            if (empty($error)) {
                // Actualizar producto
                $data = [
                    'categoria_id' => $categoria_id ?: null,
                    'sku' => $sku,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio_venta' => $precio_venta,
                    'costo_fabricacion' => $costo_fabricacion,
                    'tiempo_entrega' => $tiempo_entrega,
                    'imagen_principal' => $imagen_principal,
                    'destacado' => $destacado,
                    'activo' => $activo
                ];
                
                if (updateRecord('productos', $id, $data)) {
                    // Actualizar variantes
                    if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                        // Eliminar variantes existentes
                        $conn->query("DELETE FROM variantes_producto WHERE producto_id = $id");
                        
                        // Crear nuevas variantes
                        foreach ($_POST['variantes'] as $variante) {
                            if (!empty($variante['talla']) || !empty($variante['color']) || !empty($variante['material'])) {
                                $varianteData = [
                                    'producto_id' => $id,
                                    'talla' => $variante['talla'],
                                    'color' => $variante['color'],
                                    'material' => $variante['material'],
                                    'stock' => (int)$variante['stock'],
                                    'precio_extra' => (float)$variante['precio_extra']
                                ];
                                createRecord('variantes_producto', $varianteData);
                            }
                        }
                    }
                    
                    $success = 'Producto actualizado exitosamente';
                    // Actualizar datos locales
                    $producto = array_merge($producto, $data);
                    // Recargar variantes
                    $variantes = readRecords('variantes_producto', ["producto_id = $id"], null, 'id ASC');
                } else {
                    $error = 'Error al actualizar el producto';
                }
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Producto: <?php echo htmlspecialchars($producto['nombre']); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <a href="productos.php" class="btn btn-sm btn-outline-success ms-3">Ver Productos</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="productoForm">
                    <!-- Información básica -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="sku" 
                                       name="sku" 
                                       value="<?php echo htmlspecialchars($producto['sku']); ?>" 
                                       required>
                                <div class="form-text">Código único del producto</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo $producto['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                    
                    <!-- Precios y tiempos -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="precio_venta" class="form-label">Precio de Venta *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="precio_venta" 
                                           name="precio_venta" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?php echo $producto['precio_venta']; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="costo_fabricacion" class="form-label">Costo de Fabricación *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="costo_fabricacion" 
                                           name="costo_fabricacion" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?php echo $producto['costo_fabricacion']; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tiempo_entrega" class="form-label">Tiempo de Entrega (días)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="tiempo_entrega" 
                                       name="tiempo_entrega" 
                                       min="1" 
                                       value="<?php echo $producto['tiempo_entrega']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imagen principal -->
                    <div class="mb-3">
                        <label for="imagen_principal" class="form-label">Imagen Principal</label>
                        <input type="file" 
                               class="form-control" 
                               id="imagen_principal" 
                               name="imagen_principal" 
                               accept="image/*"
                               data-preview="imagenPreview">
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                    
                    <!-- Variantes del producto -->
                    <div class="mb-4">
                        <label class="form-label">Variantes del Producto</label>
                        <div id="variantesContainer">
                            <?php foreach ($variantes as $index => $variante): ?>
                            <div class="variante-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Talla</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][talla]" value="<?php echo htmlspecialchars($variante['talla']); ?>" placeholder="S, M, L, XL">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][color]" value="<?php echo htmlspecialchars($variante['color']); ?>" placeholder="Blanco, Negro, etc.">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][material]" value="<?php echo htmlspecialchars($variante['material']); ?>" placeholder="Algodón, Poliéster, etc.">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Stock</label>
                                        <input type="number" class="form-control" name="variantes[<?php echo $index; ?>][stock]" min="0" value="<?php echo $variante['stock']; ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeVariante(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Precio Extra</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="variantes[<?php echo $index; ?>][precio_extra]" step="0.01" min="0" value="<?php echo $variante['precio_extra']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addVariante()">
                            <i class="fas fa-plus me-2"></i>Agregar Variante
                        </button>
                    </div>
                    
                    <!-- Opciones -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="destacado" 
                                       name="destacado" 
                                       <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destacado">
                                    Producto destacado
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Producto activo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Producto
                        </button>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <a href="productos_view.php?id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Ver Detalles
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Imagen Actual</h6>
            </div>
            <div class="card-body text-center">
                <?php if ($producto['imagen_principal']): ?>
                    <img id="imagenPreview" 
                         src="../<?php echo $producto['imagen_principal']; ?>" 
                         class="img-fluid rounded" 
                         style="max-height: 200px;">
                <?php else: ?>
                    <img id="imagenPreview" 
                         src="../images/no-image.svg" 
                         class="img-fluid rounded" 
                         style="max-height: 200px; display: none;">
                    <div id="noImagePlaceholder" class="text-muted">
                        <i class="fas fa-image fa-3x mb-2"></i>
                        <p>No hay imagen actual</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Producto</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>ID:</strong> <?php echo $producto['id']; ?></li>
                    <li><strong>SKU:</strong> <?php echo htmlspecialchars($producto['sku']); ?></li>
                    <li><strong>Creado:</strong> <?php echo formatDate($producto['created_at']); ?></li>
                    <li><strong>Actualizado:</strong> <?php echo formatDate($producto['updated_at']); ?></li>
                    <li><strong>Estado:</strong> 
                        <?php if ($producto['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </li>
                    <li><strong>Destacado:</strong> 
                        <?php if ($producto['destacado']): ?>
                            <span class="badge bg-warning">Sí</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">No</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let varianteCount = <?php echo count($variantes); ?>;

// Preview de imagen
document.getElementById('imagen_principal').addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('imagenPreview');
    const placeholder = document.getElementById('noImagePlaceholder');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        // Restaurar imagen original
        preview.src = '../<?php echo $producto['imagen_principal'] ?: 'images/no-image.svg'; ?>';
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    }
});

// Agregar variante
function addVariante() {
    const container = document.getElementById('variantesContainer');
    const varianteHtml = `
        <div class="variante-item border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Talla</label>
                    <input type="text" class="form-control" name="variantes[${varianteCount}][talla]" placeholder="S, M, L, XL">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control" name="variantes[${varianteCount}][color]" placeholder="Blanco, Negro, etc.">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Material</label>
                    <input type="text" class="form-control" name="variantes[${varianteCount}][material]" placeholder="Algodón, Poliéster, etc.">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control" name="variantes[${varianteCount}][stock]" min="0" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeVariante(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Precio Extra</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="variantes[${varianteCount}][precio_extra]" step="0.01" min="0" value="0">
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', varianteHtml);
    varianteCount++;
}

// Eliminar variante
function removeVariante(button) {
    button.closest('.variante-item').remove();
}

// Validación del formulario
document.getElementById('productoForm').addEventListener('submit', function(e) {
    const sku = document.getElementById('sku').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    const precioVenta = parseFloat(document.getElementById('precio_venta').value);
    const costoFabricacion = parseFloat(document.getElementById('costo_fabricacion').value);
    
    if (!sku || !nombre || precioVenta <= 0 || costoFabricacion <= 0) {
        e.preventDefault();
        showAlert('Todos los campos requeridos deben ser completados correctamente', 'danger');
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 5 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
