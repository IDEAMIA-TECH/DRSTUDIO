<?php
$pageTitle = 'Crear Nuevo Producto';
require_once 'includes/header.php';

$error = '';
$success = '';

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
        // Verificar si ya existe un producto con el mismo SKU
        $existing = readRecords('productos', ["sku = '$sku'"]);
        if (!empty($existing)) {
            $error = 'Ya existe un producto con este SKU';
        } else {
            // Procesar imagen principal
            $imagen_principal = '';
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
                $imagen_principal = uploadFile($_FILES['imagen_principal'], '../uploads/productos/');
                if (!$imagen_principal) {
                    $error = 'Error al subir la imagen principal';
                }
            }
            
            if (empty($error)) {
                // Crear producto
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
                
                if (createRecord('productos', $data)) {
                    $producto_id = $conn->insert_id;
                    
                    // Procesar variantes si se enviaron
                    if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                        foreach ($_POST['variantes'] as $variante) {
                            if (!empty($variante['talla']) || !empty($variante['color']) || !empty($variante['material'])) {
                                $varianteData = [
                                    'producto_id' => $producto_id,
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
                    
                    $success = 'Producto creado exitosamente';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = 'Error al crear el producto';
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
                    <i class="fas fa-plus me-2"></i>Crear Nuevo Producto
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
                                       value="<?php echo $_POST['sku'] ?? ''; ?>" 
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
                                                <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
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
                               value="<?php echo $_POST['nombre'] ?? ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
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
                                           value="<?php echo $_POST['precio_venta'] ?? ''; ?>" 
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
                                           value="<?php echo $_POST['costo_fabricacion'] ?? ''; ?>" 
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
                                       value="<?php echo $_POST['tiempo_entrega'] ?? '7'; ?>">
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
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB</div>
                    </div>
                    
                    <!-- Variantes del producto -->
                    <div class="mb-4">
                        <label class="form-label">Variantes del Producto</label>
                        <div id="variantesContainer">
                            <div class="variante-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Talla</label>
                                        <input type="text" class="form-control" name="variantes[0][talla]" placeholder="S, M, L, XL">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" name="variantes[0][color]" placeholder="Blanco, Negro, etc.">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="variantes[0][material]" placeholder="Algodón, Poliéster, etc.">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Stock</label>
                                        <input type="number" class="form-control" name="variantes[0][stock]" min="0" value="0">
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
                                            <input type="number" class="form-control" name="variantes[0][precio_extra]" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                       <?php echo (isset($_POST['destacado']) || !isset($_POST['nombre'])) ? 'checked' : ''; ?>>
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
                                       <?php echo (isset($_POST['activo']) || !isset($_POST['nombre'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Producto activo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Producto
                        </button>
                        <a href="productos.php" class="btn btn-secondary">
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
                <h6 class="card-title mb-0">Vista Previa de la Imagen</h6>
            </div>
            <div class="card-body text-center">
                <img id="imagenPreview" 
                     src="../images/no-image.svg" 
                     class="img-fluid rounded" 
                     style="max-height: 200px; display: none;">
                <div id="noImagePlaceholder" class="text-muted">
                    <i class="fas fa-image fa-3x mb-2"></i>
                    <p>Selecciona una imagen para ver la vista previa</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-info-circle text-info me-2"></i>El SKU debe ser único</li>
                    <li><i class="fas fa-image text-warning me-2"></i>La imagen es opcional</li>
                    <li><i class="fas fa-tags text-success me-2"></i>Puedes agregar múltiples variantes</li>
                    <li><i class="fas fa-toggle-on text-primary me-2"></i>Puedes activar/desactivar después</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let varianteCount = 1;

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
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 5 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
