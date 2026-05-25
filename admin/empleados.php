<?php
$pageTitle = 'Empleados';
require_once 'includes/header.php';
require_once 'includes/sueldos_helper.php';

if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

ensureSueldosTables($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'crear') {
        $nombre = sanitizeInput($_POST['nombre'] ?? '');
        $puesto = sanitizeInput($_POST['puesto'] ?? '');
        if ($nombre) {
            createRecord('empleados', ['nombre' => $nombre, 'puesto' => $puesto, 'activo' => 1]);
        }
    } elseif ($_POST['action'] === 'toggle' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];
        $emp = getRecord('empleados', $id);
        if ($emp) {
            updateRecord('empleados', ['activo' => $emp['activo'] ? 0 : 1], $id);
        }
    }
    header('Location: empleados.php');
    exit;
}

$empleados = readRecords('empleados', [], null, 'nombre ASC');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Empleados</h1>
            <p class="text-muted">Catálogo para registro de sueldos</p>
        </div>
        <a href="sueldos.php" class="btn btn-outline-primary"><i class="fas fa-money-check-alt me-2"></i>Sueldos</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Nuevo empleado</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="crear">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Puesto</label>
                            <input type="text" name="puesto" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Nombre</th><th>Puesto</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $e): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($e['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($e['puesto'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $e['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $e['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <?php echo $e['activo'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
