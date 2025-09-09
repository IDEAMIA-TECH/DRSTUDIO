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
        // Verificar si ya existe otro producto con el mismo SKU usando consulta preparada
        $checkSql = "SELECT id FROM productos WHERE sku = ? AND id != ?";
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt) {
            $checkStmt->bind_param("si", $sku, $id);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $checkStmt->close();
            
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
                    
                    $updateResult = updateRecord('productos', $data, $id);
                    
                    if ($updateResult) {
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
        } else {
            $error = 'Error al verificar el SKU';
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Producto
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo $categoria['id'] == $producto['categoria_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU *</label>
                                <input type="text" class="form-control" id="sku" name="sku" 
                                       value="<?php echo htmlspecialchars($producto['sku']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="precio_venta" class="form-label">Precio de Venta *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio_venta" name="precio_venta" 
                                           value="<?php echo $producto['precio_venta']; ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="costo_fabricacion" class="form-label">Costo de Fabricación *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="costo_fabricacion" name="costo_fabricacion" 
                                           value="<?php echo $producto['costo_fabricacion']; ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tiempo_entrega" class="form-label">Tiempo de Entrega (días)</label>
                                <input type="number" class="form-control" id="tiempo_entrega" name="tiempo_entrega" 
                                       value="<?php echo $producto['tiempo_entrega']; ?>" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="imagen_principal" class="form-label">Imagen Principal</label>
                        <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" 
                               accept="image/*">
                        <?php if ($producto['imagen_principal']): ?>
                            <div class="mt-2">
                                <img src="../<?php echo $producto['imagen_principal']; ?>" 
                                     alt="Imagen actual" class="img-thumbnail" style="max-width: 200px;">
                                <p class="text-muted small mt-1">Imagen actual</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="destacado" name="destacado" 
                                       <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destacado">
                                    Producto Destacado
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                       <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">
                                    Activo
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Variantes del Producto -->
                    <h6>Variantes del Producto</h6>
                    <div id="variantes-container">
                        <?php foreach ($variantes as $index => $variante): ?>
                            <div class="variante-item border p-3 mb-3 rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Talla</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][talla]" 
                                               value="<?php echo htmlspecialchars($variante['talla']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][color]" 
                                               value="<?php echo htmlspecialchars($variante['color']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="variantes[<?php echo $index; ?>][material]" 
                                               value="<?php echo htmlspecialchars($variante['material']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Stock</label>
                                        <input type="number" class="form-control" name="variantes[<?php echo $index; ?>][stock]" 
                                               value="<?php echo $variante['stock']; ?>" min="0">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Precio Extra</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="variantes[<?php echo $index; ?>][precio_extra]" 
                                                   value="<?php echo $variante['precio_extra']; ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeVariante(this)">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="addVariante()">
                        <i class="fas fa-plus"></i> Agregar Variante
                    </button>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Producto
                        </button>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Producto</h6>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo $producto['id']; ?></p>
                <p><strong>SKU:</strong> <?php echo htmlspecialchars($producto['sku']); ?></p>
                <p><strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($producto['created_at'])); ?></p>
                <p><strong>Actualizado:</strong> <?php echo date('d/m/Y H:i', strtotime($producto['updated_at'])); ?></p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-<?php echo $producto['activo'] ? 'success' : 'danger'; ?>">
                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </p>
                <?php if ($producto['destacado']): ?>
                    <p><strong>Destacado:</strong> <span class="badge bg-warning">Sí</span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let varianteIndex = <?php echo count($variantes); ?>;

function addVariante() {
    const container = document.getElementById('variantes-container');
    const varianteHtml = `
        <div class="variante-item border p-3 mb-3 rounded">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Talla</label>
                    <input type="text" class="form-control" name="variantes[${varianteIndex}][talla]">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control" name="variantes[${varianteIndex}][color]">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Material</label>
                    <input type="text" class="form-control" name="variantes[${varianteIndex}][material]">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control" name="variantes[${varianteIndex}][stock]" min="0">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Precio Extra</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="variantes[${varianteIndex}][precio_extra]" step="0.01" min="0">
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeVariante(this)">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', varianteHtml);
    varianteIndex++;
}

function removeVariante(button) {
    button.closest('.variante-item').remove();
}

// Preview de imagen
document.getElementById('imagen_principal').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'mt-2';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
                <p class="text-muted small mt-1">Vista previa</p>
            `;
            
            // Remover preview anterior
            const oldPreview = document.querySelector('.preview-container');
            if (oldPreview) oldPreview.remove();
            
            preview.className = 'preview-container mt-2';
            e.target.parentNode.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
