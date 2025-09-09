<?php
$pageTitle = 'Banners';
require_once 'includes/header.php';

// Obtener banners
$banners = readRecords('banners', [], null, 'orden ASC, created_at DESC');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Banners</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="banners_create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Banner
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-images me-2"></i>Lista de Banners
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($banners)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay banners registrados</h5>
                        <p class="text-muted">Comienza creando tu primer banner para el sitio web</p>
                        <a href="banners_create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Banner
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="bannersTable">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Descripción</th>
                                    <th>Icono</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                <tr>
                                    <td>
                                        <?php if ($banner['imagen']): ?>
                                            <img src="../uploads/banners/<?php echo $banner['imagen']; ?>" 
                                                 alt="<?php echo htmlspecialchars($banner['titulo']); ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 60px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 40px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($banner['titulo']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($banner['descripcion'], 0, 50)); ?>
                                            <?php if (strlen($banner['descripcion']) > 50): ?>...<?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($banner['icono']): ?>
                                            <i class="<?php echo htmlspecialchars($banner['icono']); ?>"></i>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $banner['orden']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($banner['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo formatDate($banner['created_at'], 'd/m/Y'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="banners_edit.php?id=<?php echo $banner['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-<?php echo $banner['activo'] ? 'warning' : 'success'; ?>" 
                                                    onclick="toggleBanner(<?php echo $banner['id']; ?>, <?php echo $banner['activo'] ? 'false' : 'true'; ?>)"
                                                    title="<?php echo $banner['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $banner['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="deleteBanner(<?php echo $banner['id']; ?>)"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// DataTable
$(document).ready(function() {
    $('#bannersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "order": [[4, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [0, 7] }
        ]
    });
});

// Toggle banner activo/inactivo
function toggleBanner(id, activo) {
    const action = activo ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de ${action} este banner?`)) {
        fetch('../ajax/banners.php', {
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
            showAlert('Error al actualizar el banner', 'danger');
        });
    }
}

// Eliminar banner
function deleteBanner(id) {
    if (confirm('¿Estás seguro de eliminar este banner? Esta acción no se puede deshacer.')) {
        fetch('../ajax/banners.php', {
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
            showAlert('Error al eliminar el banner', 'danger');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
