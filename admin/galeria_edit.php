<?php
$pageTitle = 'Editar Imagen de Galería';
require_once 'includes/header.php';

// Obtener ID de la imagen
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: galeria.php');
    exit;
}

// Obtener datos de la imagen
$imagen = getRecord('galeria', $id);
if (!$imagen) {
    header('Location: galeria.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($titulo)) {
        $error = 'El título es requerido';
    } elseif (empty($orden)) {
        $error = 'El orden es requerido';
    } else {
        // Procesar imagen si se subió una nueva
        $imagenFile = $imagen['imagen']; // Mantener imagen actual
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $uploadResult = uploadFile($_FILES['imagen'], 'galeria');
            if ($uploadResult['success']) {
                // Eliminar imagen anterior si existe
                if ($imagen['imagen']) {
                    $oldImagePath = PROJECT_ROOT . '/uploads/galeria/' . $imagen['imagen'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imagenFile = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (!$error) {
            $data = [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'imagen' => $imagenFile,
                'orden' => $orden,
                'activo' => $activo,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (updateRecord('galeria', $id, $data)) {
                $success = 'Imagen actualizada exitosamente';
                // Actualizar datos de la imagen
                $imagen = getRecord('galeria', $id);
            } else {
                $error = 'Error al actualizar la imagen';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Imagen de Galería</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="galeria.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Galería
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Información de la Imagen
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="galeriaForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($imagen['titulo']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="orden" name="orden" 
                                       value="<?php echo $imagen['orden']; ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($imagen['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen</label>
                        <?php if ($imagen['imagen']): ?>
                            <div class="mb-2">
                                <img src="../uploads/galeria/<?php echo $imagen['imagen']; ?>" 
                                     class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                                <p class="text-muted small">Imagen actual</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        <div class="form-text">Dejar vacío para mantener la imagen actual. Formatos: JPG, PNG, GIF. Máx: 5MB</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?php echo $imagen['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Imagen activa
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="galeria.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Imagen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Información</h6>
            </div>
            <div class="card-body">
                <h6>Orden de Imágenes</h6>
                <p class="text-muted small">
                    Las imágenes se mostrarán en el orden especificado. 
                    Un número menor aparece primero.
                </p>
                
                <h6>Formatos Recomendados</h6>
                <ul class="text-muted small">
                    <li>JPG: Para fotografías</li>
                    <li>PNG: Para imágenes con transparencia</li>
                    <li>GIF: Para animaciones simples</li>
                </ul>
                
                <h6>Resolución</h6>
                <p class="text-muted small">
                    Se recomienda una resolución mínima de 800x600 píxeles 
                    para una buena calidad de visualización.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de imagen
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Crear preview si no existe
            let preview = document.getElementById('imagePreview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'imagePreview';
                preview.className = 'mt-2';
                document.getElementById('imagen').parentNode.appendChild(preview);
            }
            
            preview.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                <p class="text-muted small">Nueva imagen</p>
            `;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>