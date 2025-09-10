<?php
$pageTitle = 'Editar Categoría';
require_once 'includes/header.php';

$error = '';
$success = '';

// Obtener ID de la categoría
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: categorias.php');
    exit;
}

// Obtener datos de la categoría
$categoria = getRecord('categorias', $id);
if (!$categoria) {
    header('Location: categorias.php');
    exit;
}

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } else {
        // Verificar si ya existe otra categoría con el mismo nombre
        $existing = readRecords('categorias', ["nombre = '$nombre'", "id != $id"]);
        if (!empty($existing)) {
            $error = 'Ya existe otra categoría con este nombre';
        } else {
            // Procesar imagen
            $imagen = $categoria['imagen']; // Mantener imagen actual por defecto
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $newImagen = uploadFile($_FILES['imagen'], '../uploads/categorias/');
                if ($newImagen) {
                    // Eliminar imagen anterior si existe
                    if ($imagen && file_exists('../' . $imagen)) {
                        deleteFile('../' . $imagen);
                    }
                    $imagen = $newImagen;
                } else {
                    $error = 'Error al subir la imagen';
                }
            }
            
            if (empty($error)) {
                // Actualizar categoría
                $data = [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'imagen' => $imagen,
                    'activo' => $activo
                ];
                
                if (updateRecord('categorias', $data, $id)) {
                    $success = 'Categoría actualizada exitosamente';
                    // Actualizar datos locales
                    $categoria = array_merge($categoria, $data);
                } else {
                    $error = 'Error al actualizar la categoría';
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
                    <i class="fas fa-edit me-2"></i>Editar Categoría: <?php echo htmlspecialchars($categoria['nombre']); ?>
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
                        <a href="categorias.php" class="btn btn-sm btn-outline-success ms-3">Ver Categorías</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="categoriaForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Imagen</label>
                                <input type="file" 
                                       class="form-control" 
                                       id="imagen" 
                                       name="imagen" 
                                       accept="image/*"
                                       data-preview="imagenPreview">
                                <div class="form-text">Deja vacío para mantener la imagen actual</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="4"><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   <?php echo $categoria['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Categoría activa
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Categoría
                        </button>
                        <a href="categorias.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <a href="categorias_view.php?id=<?php echo $id; ?>" class="btn btn-info">
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
                <?php if ($categoria['imagen']): ?>
                    <img id="imagenPreview" 
                         src="../uploads/categorias/<?php echo $categoria['imagen']; ?>" 
                         class="img-fluid rounded" 
                         style="max-height: 200px;">
                <?php else: ?>
                    <img id="imagenPreview" 
                         src="../images/no-image.jpg" 
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
                <h6 class="card-title mb-0">Información de la Categoría</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>ID:</strong> <?php echo $categoria['id']; ?></li>
                    <li><strong>Creada:</strong> <?php echo formatDate($categoria['created_at']); ?></li>
                    <li><strong>Actualizada:</strong> <?php echo formatDate($categoria['updated_at']); ?></li>
                    <li><strong>Estado:</strong> 
                        <?php if ($categoria['activo']): ?>
                            <span class="badge bg-success">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactiva</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de imagen
document.getElementById('imagen').addEventListener('change', function() {
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
        preview.src = '../<?php echo $categoria['imagen'] ?: 'images/no-image.jpg'; ?>';
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    }
});

// Validación del formulario
document.getElementById('categoriaForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    
    if (!nombre) {
        e.preventDefault();
        showAlert('El nombre de la categoría es requerido', 'danger');
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 3 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
