<?php
$pageTitle = 'Galería';
require_once 'includes/header.php';

// Obtener imágenes de la galería
$galeria = readRecords('galeria', [], null, 'orden ASC, created_at DESC');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Galería</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="galeria_create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Imagen
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-images me-2"></i>Galería de Imágenes
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($galeria)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay imágenes en la galería</h5>
                        <p class="text-muted">Comienza subiendo imágenes para mostrar en el sitio web</p>
                        <a href="galeria_create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Subir Primera Imagen
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row" id="galeriaGrid">
                        <?php foreach ($galeria as $imagen): ?>
                        <div class="col-md-4 col-lg-3 mb-4" data-id="<?php echo $imagen['id']; ?>">
                            <div class="card h-100">
                                <div class="position-relative">
                                    <img src="../uploads/galeria/<?php echo $imagen['imagen']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <?php if ($imagen['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo htmlspecialchars($imagen['titulo']); ?></h6>
                                    <?php if ($imagen['descripcion']): ?>
                                        <p class="card-text small text-muted">
                                            <?php echo htmlspecialchars(substr($imagen['descripcion'], 0, 80)); ?>
                                            <?php if (strlen($imagen['descripcion']) > 80): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Orden: <span class="badge bg-secondary"><?php echo $imagen['orden']; ?></span>
                                            </small>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" 
                                                        class="btn btn-outline-primary" 
                                                        onclick="editImagen(<?php echo $imagen['id']; ?>)"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-<?php echo $imagen['activo'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleImagen(<?php echo $imagen['id']; ?>, <?php echo $imagen['activo'] ? 'false' : 'true'; ?>)"
                                                        title="<?php echo $imagen['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fas fa-<?php echo $imagen['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="deleteImagen(<?php echo $imagen['id']; ?>)"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar imagen -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId" name="id">
                    <div class="mb-3">
                        <label for="editTitulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="editTitulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editDescripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOrden" class="form-label">Orden</label>
                        <input type="number" class="form-control" id="editOrden" name="orden" min="1" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editActivo" name="activo">
                            <label class="form-check-label" for="editActivo">
                                Imagen activa
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveImagen()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Editar imagen
function editImagen(id) {
    // Obtener datos de la imagen
    fetch('../ajax/galeria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editId').value = data.data.id;
            document.getElementById('editTitulo').value = data.data.titulo;
            document.getElementById('editDescripcion').value = data.data.descripcion || '';
            document.getElementById('editOrden').value = data.data.orden;
            document.getElementById('editActivo').checked = data.data.activo == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al cargar los datos de la imagen', 'danger');
    });
}

// Guardar imagen editada
function saveImagen() {
    const formData = new FormData(document.getElementById('editForm'));
    formData.append('action', 'update');
    
    fetch('../ajax/galeria.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            location.reload();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al guardar la imagen', 'danger');
    });
}

// Toggle imagen activa/inactiva
function toggleImagen(id, activo) {
    const action = activo ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de ${action} esta imagen?`)) {
        fetch('../ajax/galeria.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle&id=${id}&activo=${activo ? 1 : 0}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                location.reload();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al actualizar la imagen', 'danger');
        });
    }
}

// Eliminar imagen
function deleteImagen(id) {
    if (confirm('¿Estás seguro de eliminar esta imagen? Esta acción no se puede deshacer.')) {
        fetch('../ajax/galeria.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                location.reload();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al eliminar la imagen', 'danger');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
