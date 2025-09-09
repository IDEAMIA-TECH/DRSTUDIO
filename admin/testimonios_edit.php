<?php
$pageTitle = 'Editar Testimonio';
require_once 'includes/header.php';

// Obtener ID del testimonio
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: testimonios.php');
    exit;
}

// Obtener datos del testimonio
$testimonio = getRecord('testimonios', $id);
if (!$testimonio) {
    header('Location: testimonios.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $empresa = sanitizeInput($_POST['empresa']);
    $testimonio_texto = sanitizeInput($_POST['testimonio']);
    $calificacion = (int)$_POST['calificacion'];
    $orden = (int)$_POST['orden'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } elseif (empty($testimonio_texto)) {
        $error = 'El testimonio es requerido';
    } elseif ($calificacion < 1 || $calificacion > 5) {
        $error = 'La calificación debe estar entre 1 y 5';
    } elseif (empty($orden)) {
        $error = 'El orden es requerido';
    } else {
        // Procesar imagen si se subió una nueva
        $imagen = $testimonio['imagen']; // Mantener imagen actual
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $uploadResult = uploadFile($_FILES['imagen'], 'testimonios');
            if ($uploadResult['success']) {
                // Eliminar imagen anterior si existe
                if ($testimonio['imagen']) {
                    $oldImagePath = PROJECT_ROOT . '/uploads/testimonios/' . $testimonio['imagen'];
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
                'nombre' => $nombre,
                'empresa' => $empresa,
                'testimonio' => $testimonio_texto,
                'calificacion' => $calificacion,
                'imagen' => $imagen,
                'orden' => $orden,
                'activo' => $activo,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (updateRecord('testimonios', $id, $data)) {
                $success = 'Testimonio actualizado exitosamente';
                // Actualizar datos del testimonio
                $testimonio = getRecord('testimonios', $id);
            } else {
                $error = 'Error al actualizar el testimonio';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Testimonio</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="testimonios.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Testimonios
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Información del Testimonio
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
                
                <form method="POST" enctype="multipart/form-data" id="testimonioForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Cliente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($testimonio['nombre']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa" 
                                       value="<?php echo htmlspecialchars($testimonio['empresa']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="testimonio" class="form-label">Testimonio <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="testimonio" name="testimonio" rows="4" required><?php echo htmlspecialchars($testimonio['testimonio']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="calificacion" class="form-label">Calificación <span class="text-danger">*</span></label>
                                <select class="form-select" id="calificacion" name="calificacion" required>
                                    <option value="">Seleccionar...</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $testimonio['calificacion'] == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> estrella<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="orden" name="orden" 
                                       value="<?php echo $testimonio['orden']; ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Foto del Cliente</label>
                                <?php if ($testimonio['imagen']): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/testimonios/<?php echo $testimonio['imagen']; ?>" 
                                             class="img-thumbnail rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                        <p class="text-muted small">Imagen actual</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                                <div class="form-text">Dejar vacío para mantener la imagen actual</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?php echo $testimonio['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Testimonio activo
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="testimonios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Testimonio
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
                <h6>Calificación</h6>
                <p class="text-muted small">
                    Selecciona de 1 a 5 estrellas según la satisfacción del cliente.
                </p>
                
                <h6>Orden de Testimonios</h6>
                <p class="text-muted small">
                    Los testimonios se mostrarán en el orden especificado. 
                    Un número menor aparece primero.
                </p>
                
                <h6>Foto del Cliente</h6>
                <p class="text-muted small">
                    Si no se proporciona una foto, se mostrará la inicial 
                    del nombre del cliente.
                </p>
                
                <h6>Formato de Imagen</h6>
                <p class="text-muted small">
                    Se recomienda una imagen cuadrada (1:1) para mejor 
                    visualización en el sitio web.
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
                <img src="${e.target.result}" class="img-thumbnail rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                <p class="text-muted small">Nueva imagen</p>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Mostrar estrellas según la calificación seleccionada
document.getElementById('calificacion').addEventListener('change', function() {
    const calificacion = parseInt(this.value);
    const preview = document.getElementById('starsPreview');
    
    if (!preview) {
        const div = document.createElement('div');
        div.id = 'starsPreview';
        div.className = 'mt-2';
        this.parentNode.appendChild(div);
    }
    
    if (calificacion > 0) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const classStar = i <= calificacion ? 'text-warning' : 'text-muted';
            stars += `<i class="fas fa-star ${classStar}"></i>`;
        }
        document.getElementById('starsPreview').innerHTML = stars;
    } else {
        document.getElementById('starsPreview').innerHTML = '';
    }
});

// Inicializar estrellas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const calificacion = document.getElementById('calificacion').value;
    if (calificacion) {
        document.getElementById('calificacion').dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
