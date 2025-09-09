<?php
$pageTitle = 'Editar Banner';
require_once 'includes/header.php';

// Obtener ID del banner
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: banners.php');
    exit;
}

// Obtener datos del banner
$banner = getRecord('banners', $id);
if (!$banner) {
    header('Location: banners.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $icono = sanitizeInput($_POST['icono']);
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($titulo)) {
        $error = 'El título es requerido';
    } elseif (empty($orden)) {
        $error = 'El orden es requerido';
    } else {
        // Procesar imagen si se subió una nueva
        $imagen = $banner['imagen']; // Mantener imagen actual
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $uploadResult = uploadFile($_FILES['imagen'], 'banners');
            if ($uploadResult['success']) {
                // Eliminar imagen anterior si existe
                if ($banner['imagen']) {
                    $oldImagePath = PROJECT_ROOT . '/uploads/banners/' . $banner['imagen'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imagen = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (!$error) {
            $data = [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'imagen' => $imagen,
                'icono' => $icono,
                'orden' => $orden,
                'activo' => $activo,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (updateRecord('banners', $id, $data)) {
                $success = 'Banner actualizado exitosamente';
                // Actualizar datos del banner
                $banner = getRecord('banners', $id);
            } else {
                $error = 'Error al actualizar el banner';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Banner</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="banners.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Banners
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Información del Banner
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
                
                <form method="POST" enctype="multipart/form-data" id="bannerForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($banner['titulo']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="orden" name="orden" 
                                       value="<?php echo $banner['orden']; ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($banner['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen</label>
                        <?php if ($banner['imagen']): ?>
                            <div class="mb-2">
                                <img src="../uploads/banners/<?php echo $banner['imagen']; ?>" 
                                     class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                                <p class="text-muted small">Imagen actual</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        <div class="form-text">Dejar vacío para mantener la imagen actual. Formatos: JPG, PNG, GIF. Máx: 5MB</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icono" class="form-label">Icono (Font Awesome)</label>
                        <input type="text" class="form-control" id="icono" name="icono" 
                               value="<?php echo htmlspecialchars($banner['icono']); ?>" 
                               placeholder="fas fa-star">
                        <div class="form-text">Ejemplo: fas fa-star, fas fa-heart, fas fa-shipping-fast</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?php echo $banner['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Banner activo
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="banners.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Banner
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
                <h6>Orden de Banners</h6>
                <p class="text-muted small">
                    Los banners se mostrarán en el orden especificado. 
                    Un número menor aparece primero.
                </p>
                
                <h6>Iconos</h6>
                <p class="text-muted small">
                    Puedes usar cualquier clase de Font Awesome. 
                    Visita <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com</a> 
                    para ver las opciones disponibles.
                </p>
                
                <h6>Imágenes</h6>
                <p class="text-muted small">
                    Las imágenes se redimensionarán automáticamente 
                    para mantener la proporción correcta.
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
                <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                <p class="text-muted small">Nueva imagen</p>
            `;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>