<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Obtener estadísticas
$stats = [];

// Total de productos
$productosCount = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1")->fetch_assoc()['total'];

// Total de clientes
$clientesCount = $conn->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];

// Total de cotizaciones
$cotizacionesCount = $conn->query("SELECT COUNT(*) as total FROM cotizaciones")->fetch_assoc()['total'];

// Cotizaciones pendientes
$cotizacionesPendientes = $conn->query("SELECT COUNT(*) as total FROM cotizaciones WHERE estado = 'pendiente'")->fetch_assoc()['total'];

// Productos con stock bajo (menos de 10)
$stockBajo = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND id IN (SELECT DISTINCT producto_id FROM variantes_producto WHERE stock < 10)")->fetch_assoc()['total'];

// Cotizaciones recientes
$cotizacionesRecientes = readRecords('cotizaciones', [], 5, 'created_at DESC');

// Productos más vendidos (simulado por ahora)
$productosDestacados = readRecords('productos', ['destacado = 1', 'activo = 1'], 5);
?>

<div class="row">
    <!-- Estadísticas principales -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Productos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $productosCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $clientesCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Cotizaciones</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cotizacionesCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cotizacionesPendientes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Cotizaciones recientes -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Cotizaciones Recientes</h6>
                <a href="cotizaciones.php" class="btn btn-sm btn-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cotizacionesRecientes as $cotizacion): ?>
                            <tr>
                                <td><?php echo $cotizacion['numero_cotizacion']; ?></td>
                                <td>
                                    <?php 
                                    $cliente = getRecord('clientes', $cotizacion['cliente_id']);
                                    echo $cliente ? $cliente['nombre'] : 'N/A';
                                    ?>
                                </td>
                                <td>$<?php echo number_format($cotizacion['total'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $cotizacion['estado'] == 'pendiente' ? 'warning' : ($cotizacion['estado'] == 'aceptada' ? 'success' : 'secondary'); ?>">
                                        <?php echo ucfirst($cotizacion['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($cotizacion['created_at']); ?></td>
                                <td>
                                    <a href="cotizaciones_view.php?id=<?php echo $cotizacion['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos destacados y alertas -->
    <div class="col-lg-4 mb-4">
        <!-- Productos destacados -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Productos Destacados</h6>
            </div>
            <div class="card-body">
                <?php foreach ($productosDestacados as $producto): ?>
                <div class="d-flex align-items-center mb-3">
                    <img src="../<?php echo $producto['imagen_principal'] ?: 'images/no-image.jpg'; ?>" 
                         class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                    <div>
                        <h6 class="mb-0"><?php echo $producto['nombre']; ?></h6>
                        <small class="text-muted">$<?php echo number_format($producto['precio_venta'], 2); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Alertas -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">Alertas</h6>
            </div>
            <div class="card-body">
                <?php if ($stockBajo > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $stockBajo; ?> productos con stock bajo
                </div>
                <?php endif; ?>
                
                <?php if ($cotizacionesPendientes > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-clock me-2"></i>
                    <?php echo $cotizacionesPendientes; ?> cotizaciones pendientes
                </div>
                <?php endif; ?>
                
                <?php if ($stockBajo == 0 && $cotizacionesPendientes == 0): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Todo en orden
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
