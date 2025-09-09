<?php
$pageTitle = 'Testimonios';
require_once 'includes/header.php';

// Obtener testimonios
$testimonios = readRecords('testimonios', [], null, 'orden ASC, created_at DESC');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Testimonios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="testimonios_create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Testimonio
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-quote-left me-2"></i>Lista de Testimonios
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($testimonios)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-quote-left fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay testimonios registrados</h5>
                        <p class="text-muted">Comienza agregando testimonios de clientes satisfechos</p>
                        <a href="testimonios_create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Primer Testimonio
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="testimoniosTable">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Empresa</th>
                                    <th>Testimonio</th>
                                    <th>Calificación</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testimonios as $testimonio): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($testimonio['imagen']): ?>
                                                <img src="../uploads/testimonios/<?php echo $testimonio['imagen']; ?>" 
                                                     alt="<?php echo htmlspecialchars($testimonio['nombre']); ?>" 
                                                     class="rounded-circle me-2" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 40px; height: 40px;">
                                                    <?php echo strtoupper(substr($testimonio['nombre'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($testimonio['nombre']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($testimonio['empresa']): ?>
                                            <span class="text-muted"><?php echo htmlspecialchars($testimonio['empresa']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <p class="mb-0">
                                                "<?php echo htmlspecialchars(substr($testimonio['testimonio'], 0, 100)); ?>"
                                                <?php if (strlen($testimonio['testimonio']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $testimonio['calificacion'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2 small text-muted">(<?php echo $testimonio['calificacion']; ?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $testimonio['orden']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($testimonio['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo formatDate($testimonio['created_at'], 'd/m/Y'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="testimonios_edit.php?id=<?php echo $testimonio['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-<?php echo $testimonio['activo'] ? 'warning' : 'success'; ?>" 
                                                    onclick="toggleTestimonio(<?php echo $testimonio['id']; ?>, <?php echo $testimonio['activo'] ? 'false' : 'true'; ?>)"
                                                    title="<?php echo $testimonio['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $testimonio['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="deleteTestimonio(<?php echo $testimonio['id']; ?>)"
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
    $('#testimoniosTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "order": [[4, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [0, 7] }
        ]
    });
});

// Toggle testimonio activo/inactivo
function toggleTestimonio(id, activo) {
    const action = activo ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de ${action} este testimonio?`)) {
        fetch('../ajax/testimonios.php', {
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
            showAlert('Error al actualizar el testimonio', 'danger');
        });
    }
}

// Eliminar testimonio
function deleteTestimonio(id) {
    if (confirm('¿Estás seguro de eliminar este testimonio? Esta acción no se puede deshacer.')) {
        fetch('../ajax/testimonios.php', {
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
            showAlert('Error al eliminar el testimonio', 'danger');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
