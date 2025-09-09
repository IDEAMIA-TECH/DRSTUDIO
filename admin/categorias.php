<?php
$pageTitle = 'Gestión de Categorías';
$pageActions = '<a href="categorias_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nueva Categoría</a>';
require_once 'includes/header.php';

// Obtener categorías
$categorias = readRecords('categorias', [], null, 'nombre ASC');
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tags me-2"></i>Listado de Categorías
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($categorias)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay categorías registradas</h5>
                <p class="text-muted">Comienza creando tu primera categoría</p>
                <a href="categorias_create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Primera Categoría
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover data-table" id="categoriasTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?php echo $categoria['id']; ?></td>
                            <td>
                                <?php if ($categoria['imagen']): ?>
                                    <img src="../<?php echo $categoria['imagen']; ?>" 
                                         class="img-preview" 
                                         alt="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(substr($categoria['descripcion'], 0, 50)); ?>
                                <?php if (strlen($categoria['descripcion']) > 50): ?>
                                    <span class="text-muted">...</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($categoria['activo']): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($categoria['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="categorias_view.php?id=<?php echo $categoria['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="categorias_edit.php?id=<?php echo $categoria['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteCategory(<?php echo $categoria['id']; ?>)" 
                                            data-bs-toggle="tooltip" 
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

<script>
// Función para eliminar categoría
function deleteCategory(id) {
    if (confirmDelete('¿Estás seguro de eliminar esta categoría? Esta acción no se puede deshacer.')) {
        ajaxRequest('ajax/categorias.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                location.reload();
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}

// Inicializar DataTable
$(document).ready(function() {
    $('#categoriasTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [1, 6] } // Deshabilitar ordenamiento en imagen y acciones
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
