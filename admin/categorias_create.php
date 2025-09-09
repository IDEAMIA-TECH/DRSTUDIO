<?php
$pageTitle = 'Crear Nueva Categoría';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } else {
        // Verificar si ya existe una categoría con el mismo nombre
        $existing = readRecords('categorias', ["nombre = '$nombre'"]);
        if (!empty($existing)) {
            $error = 'Ya existe una categoría con este nombre';
        } else {
            // Procesar imagen
            $imagen = '';
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagen = uploadFile($_FILES['imagen'], '../uploads/categorias/');
                if (!$imagen) {
                    $error = 'Error al subir la imagen';
                }
            }
            
            if (empty($error)) {
                // Crear categoría
                $data = [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'imagen' => $imagen,
                    'activo' => $activo
                ];
                
                if (createRecord('categorias', $data)) {
                    $success = 'Categoría creada exitosamente';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = 'Error al crear la categoría';
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
                    <i class="fas fa-plus me-2"></i>Crear Nueva Categoría
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
                                       value="<?php echo $_POST['nombre'] ?? ''; ?>" 
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
                                <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="4"><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   <?php echo (isset($_POST['activo']) || !isset($_POST['nombre'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Categoría activa
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Categoría
                        </button>
                        <a href="categorias.php" class="btn btn-secondary">
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
                     src="../images/no-image.jpg" 
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
                    <li><i class="fas fa-info-circle text-info me-2"></i>El nombre debe ser único</li>
                    <li><i class="fas fa-image text-warning me-2"></i>La imagen es opcional</li>
                    <li><i class="fas fa-toggle-on text-success me-2"></i>Puedes activar/desactivar después</li>
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
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 3 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
