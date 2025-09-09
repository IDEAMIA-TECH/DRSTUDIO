<?php
$pageTitle = 'Usuarios';
require_once 'includes/header.php';

// Obtener usuarios
$usuarios = readRecords('usuarios', [], null, 'created_at DESC');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Usuarios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="usuarios_create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Usuario
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>Lista de Usuarios
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay usuarios registrados</h5>
                        <p class="text-muted">Comienza creando el primer usuario del sistema</p>
                        <a href="usuarios_create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Usuario
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="usuariosTable">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($usuario['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                                <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-1">Tú</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $rolClass = [
                                            'admin' => 'danger',
                                            'ventas' => 'success',
                                            'produccion' => 'warning',
                                            'lectura' => 'info'
                                        ];
                                        $class = $rolClass[$usuario['rol']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_acceso']): ?>
                                            <small class="text-muted">
                                                <?php echo formatDate($usuario['ultimo_acceso'], 'd/m/Y H:i'); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo formatDate($usuario['created_at'], 'd/m/Y'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="usuarios_edit.php?id=<?php echo $usuario['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-<?php echo $usuario['activo'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleUsuario(<?php echo $usuario['id']; ?>, <?php echo $usuario['activo'] ? 'false' : 'true'; ?>)"
                                                        title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fas fa-<?php echo $usuario['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="deleteUsuario(<?php echo $usuario['id']; ?>)"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
    $('#usuariosTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "order": [[5, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
});

// Toggle usuario activo/inactivo
function toggleUsuario(id, activo) {
    const action = activo ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de ${action} este usuario?`)) {
        fetch('../ajax/usuarios.php', {
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
            showAlert('Error al actualizar el usuario', 'danger');
        });
    }
}

// Eliminar usuario
function deleteUsuario(id) {
    if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
        fetch('../ajax/usuarios.php', {
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
            showAlert('Error al eliminar el usuario', 'danger');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
