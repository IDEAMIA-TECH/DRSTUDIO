<?php
$pageTitle = 'Ver Producto';
require_once 'includes/header.php';

// Obtener ID del producto
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: productos.php');
    exit;
}

// Obtener datos del producto con información de categoría
$sql = "SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.id = $id";
$result = $conn->query($sql);
$producto = $result ? $result->fetch_assoc() : null;

if (!$producto) {
    header('Location: productos.php');
    exit;
}

// Obtener variantes del producto
$variantes = readRecords('variantes_producto', ["producto_id = $id"], null, 'id ASC');

// Calcular estadísticas
$stockTotal = array_sum(array_column($variantes, 'stock'));
$variantesActivas = count(array_filter($variantes, function($v) { return $v['activo']; }));
$margenGanancia = $producto['precio_venta'] - $producto['costo_fabricacion'];
$porcentajeMargen = $producto['costo_fabricacion'] > 0 ? ($margenGanancia / $producto['costo_fabricacion']) * 100 : 0;
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($producto['nombre']); ?>
                    <?php if ($producto['destacado']): ?>
                        <span class="badge bg-warning ms-2">Destacado</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?php if ($producto['imagen_principal']): ?>
                            <img src="../uploads/productos/<?php echo $producto['imagen_principal']; ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h6>Información del Producto</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>SKU:</strong>
                                <code class="ms-2"><?php echo htmlspecialchars($producto['sku']); ?></code>
                            </div>
                            <div class="col-sm-6">
                                <strong>Categoría:</strong>
                                <?php if ($producto['categoria_nombre']): ?>
                                    <span class="badge bg-info ms-2"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted ms-2">Sin categoría</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <strong>Precio de Venta:</strong>
                                <span class="text-success ms-2">$<?php echo number_format($producto['precio_venta'], 2); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Costo de Fabricación:</strong>
                                <span class="text-muted ms-2">$<?php echo number_format($producto['costo_fabricacion'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <strong>Margen de Ganancia:</strong>
                                <span class="text-primary ms-2">$<?php echo number_format($margenGanancia, 2); ?> (<?php echo number_format($porcentajeMargen, 1); ?>%)</span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Tiempo de Entrega:</strong>
                                <span class="text-info ms-2"><?php echo $producto['tiempo_entrega']; ?> días</span>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <strong>Estado:</strong>
                                <?php if ($producto['activo']): ?>
                                    <span class="badge bg-success ms-2">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Inactivo</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Stock Total:</strong>
                                <?php if ($stockTotal > 10): ?>
                                    <span class="badge bg-success ms-2"><?php echo $stockTotal; ?></span>
                                <?php elseif ($stockTotal > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo $stockTotal; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger ms-2"><?php echo $stockTotal; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($producto['descripcion']): ?>
                        <div class="mt-3">
                            <h6>Descripción</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Variantes del producto -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-tags me-2"></i>Variantes del Producto (<?php echo count($variantes); ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($variantes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay variantes definidas para este producto</p>
                        <a href="productos_edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar Producto
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Talla</th>
                                    <th>Color</th>
                                    <th>Material</th>
                                    <th>Stock</th>
                                    <th>Precio Extra</th>
                                    <th>Precio Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variantes as $variante): ?>
                                <tr>
                                    <td>
                                        <?php if ($variante['talla']): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($variante['talla']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($variante['color']): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($variante['color']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($variante['material']): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($variante['material']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($variante['stock'] > 10): ?>
                                            <span class="badge bg-success"><?php echo $variante['stock']; ?></span>
                                        <?php elseif ($variante['stock'] > 0): ?>
                                            <span class="badge bg-warning"><?php echo $variante['stock']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $variante['stock']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($variante['precio_extra'] > 0): ?>
                                            <span class="text-success">+$<?php echo number_format($variante['precio_extra'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-primary">
                                            $<?php echo number_format($producto['precio_venta'] + $variante['precio_extra'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($variante['activo']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
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
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Acciones</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="productos_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </a>
                    <a href="productos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Productos
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Estadísticas</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between">
                        <span>Variantes Totales:</span>
                        <strong><?php echo count($variantes); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Variantes Activas:</span>
                        <strong><?php echo $variantesActivas; ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Stock Total:</span>
                        <strong><?php echo $stockTotal; ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Margen de Ganancia:</span>
                        <strong class="text-success"><?php echo number_format($porcentajeMargen, 1); ?>%</strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Sistema</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>ID:</strong> <?php echo $producto['id']; ?></li>
                    <li><strong>Creado:</strong> <?php echo formatDate($producto['created_at']); ?></li>
                    <li><strong>Actualizado:</strong> <?php echo formatDate($producto['updated_at']); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Eliminar Producto</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Esta acción eliminará el producto y todas sus variantes de forma permanente.
                </p>
                <button type="button" 
                        class="btn btn-danger btn-sm w-100" 
                        onclick="deleteProduct(<?php echo $id; ?>)">
                    <i class="fas fa-trash me-2"></i>Eliminar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para eliminar producto
function deleteProduct(id) {
    if (confirmDelete('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer y eliminará todas las variantes asociadas.')) {
        ajaxRequest('../ajax/productos.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                setTimeout(() => {
                    window.location.href = 'productos.php';
                }, 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
