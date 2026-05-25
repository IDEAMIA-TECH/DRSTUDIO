<?php
$pageTitle = 'Sueldos';
require_once 'includes/header.php';
require_once 'includes/sueldos_helper.php';

if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

ensureSueldosTables($conn);

if (isset($_GET['eliminar'])) {
    $res = eliminarSueldo($conn, (int) $_GET['eliminar']);
    $_SESSION['flash_sueldo'] = $res['message'];
    header('Location: sueldos.php');
    exit;
}

$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

$sql = "SELECT s.*, e.nombre AS empleado_nombre, e.puesto, u.username AS registrado_por, g.estado AS gasto_estado
    FROM sueldos s
    INNER JOIN empleados e ON s.empleado_id = e.id
    LEFT JOIN usuarios u ON s.usuario_id = u.id
    LEFT JOIN gastos g ON s.gasto_id = g.id
    WHERE s.fecha_pago BETWEEN ? AND ?
    ORDER BY s.fecha_pago DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
$stmt->execute();
$sueldos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_column($sueldos, 'monto'));
$flash = $_SESSION['flash_sueldo'] ?? '';
unset($_SESSION['flash_sueldo']);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Sueldos y nómina</h1>
            <p class="text-muted">Cada sueldo genera un gasto aprobado en el mes que usted indique</p>
        </div>
        <div class="d-flex gap-2">
            <a href="empleados.php" class="btn btn-outline-secondary"><i class="fas fa-users me-2"></i>Empleados</a>
            <a href="sueldos_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Registrar sueldo</a>
        </div>
    </div>

    <?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success">Sueldo registrado correctamente.</div>
    <?php endif; ?>
    <?php if ($flash): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
                <div class="col-md-3 text-end">
                    <strong>Total período: $<?php echo number_format($total, 2); ?></strong>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha pago</th>
                        <th>Período</th>
                        <th>Empleado</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Gasto #</th>
                        <th>Registrado por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sueldos)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay sueldos en este período</td></tr>
                    <?php else: ?>
                    <?php foreach ($sueldos as $s): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($s['fecha_pago'])); ?></td>
                        <td><?php echo htmlspecialchars($s['periodo']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($s['empleado_nombre']); ?></strong>
                            <?php if ($s['puesto']): ?><br><small class="text-muted"><?php echo htmlspecialchars($s['puesto']); ?></small><?php endif; ?>
                        </td>
                        <td class="fw-bold">$<?php echo number_format($s['monto'], 2); ?></td>
                        <td><?php echo ucfirst($s['metodo_pago']); ?></td>
                        <td>
                            <?php if ($s['gasto_id']): ?>
                            <a href="gastos.php?busqueda=<?php echo (int) $s['gasto_id']; ?>">#<?php echo $s['gasto_id']; ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($s['registrado_por'] ?? ''); ?></td>
                        <td>
                            <a href="sueldos.php?eliminar=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar sueldo y su gasto asociado?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
