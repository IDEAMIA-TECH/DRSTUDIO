<?php
require_once 'includes/paths.php';
require_once 'includes/sueldos_helper.php';

$pageTitle = 'Registrar Sueldo';
$error = '';

if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

ensureSueldosTables($conn);

$empleados = readRecords('empleados', ['activo = 1'], null, 'nombre ASC');

if ($_POST) {
    $empleado_id = (int) ($_POST['empleado_id'] ?? 0);
    $monto = (float) ($_POST['monto'] ?? 0);
    $fecha_pago = $_POST['fecha_pago'] ?? '';
    $periodo = $_POST['periodo'] ?? date('Y-m');
    $metodo_pago = $_POST['metodo_pago'] ?? 'transferencia';
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');

    if (!$empleado_id) {
        $error = 'Seleccione un empleado';
    } elseif ($monto <= 0) {
        $error = 'El monto debe ser mayor a 0';
    } elseif (!$fecha_pago) {
        $error = 'La fecha de pago es requerida';
    } else {
        $result = registrarSueldoConGasto($conn, [
            'empleado_id' => $empleado_id,
            'monto' => $monto,
            'fecha_pago' => $fecha_pago,
            'periodo' => $periodo,
            'metodo_pago' => $metodo_pago,
            'observaciones' => $observaciones,
        ], (int) $_SESSION['user_id']);

        if ($result['success']) {
            header('Location: sueldos.php?ok=1');
            exit;
        }
        $error = $result['message'];
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3">Registrar pago de sueldo</h1>
        <p class="text-muted">Se crea automáticamente un <strong>gasto aprobado</strong> en categoría Sueldos con la fecha que indique (para cuadrar libros por mes).</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($empleados)): ?>
    <div class="alert alert-warning">
        No hay empleados activos. <a href="empleados.php">Dar de alta empleados</a> primero.
    </div>
    <?php else: ?>
    <div class="card col-lg-6">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Empleado *</label>
                    <select name="empleado_id" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($empleados as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?>
                            <?php if ($e['puesto']): ?> — <?php echo htmlspecialchars($e['puesto']); ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Período de nómina * <small class="text-muted">(mes que paga)</small></label>
                    <input type="month" name="periodo" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha de pago (contable) *</label>
                    <input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    <div class="form-text">Esta fecha se usa en reportes de gastos y conciliación (fecha_gasto).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Método de pago *</label>
                    <select name="metodo_pago" class="form-select" required>
                        <option value="transferencia">Transferencia</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Registrar sueldo</button>
                <a href="sueldos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
