<?php
$pageTitle = 'Ver Categoría';
require_once 'includes/header.php';

// Obtener ID de la categoría
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: categorias.php');
    exit;
}

// Obtener datos de la categoría
$categoria = getRecord('categorias', $id);
if (!$categoria) {
    header('Location: categorias.php');
    exit;
}

// Obtener productos de esta categoría
$productos = readRecords('productos', ["categoria_id = $id"], null, 'nombre ASC');
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($categoria['nombre']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <?php if ($categoria['imagen']): ?>
                            <img src="../<?php echo $categoria['imagen']; ?>" 
                                 class="img-fluid rounded" 
                                 alt="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h6>Descripción</h6>
                        <p class="text-muted">
                            <?php echo $categoria['descripcion'] ? nl2br(htmlspecialchars($categoria['descripcion'])) : 'Sin descripción'; ?>
                        </p>
                        
                        <div class="row mt-3">
                            <div class="col-sm-6">
                                <strong>Estado:</strong>
                                <?php if ($categoria['activo']): ?>
                                    <span class="badge bg-success ms-2">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Inactiva</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Productos:</strong>
                                <span class="badge bg-info ms-2"><?php echo count($productos); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-sm-6">
                                <strong>Creada:</strong>
                                <span class="text-muted ms-2"><?php echo formatDate($categoria['created_at']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Actualizada:</strong>
                                <span class="text-muted ms-2"><?php echo formatDate($categoria['updated_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Productos de esta categoría -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>Productos en esta Categoría (<?php echo count($productos); ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($productos)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay productos en esta categoría</p>
                        <a href="productos_create.php?categoria=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Producto
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <?php if ($producto['imagen_principal']): ?>
                                            <img src="../<?php echo $producto['imagen_principal']; ?>" 
                                                 class="img-preview" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted">SKU: <?php echo $producto['sku']; ?></small>
                                    </td>
                                    <td>$<?php echo number_format($producto['precio_venta'], 2); ?></td>
                                    <td>
                                        <?php if ($producto['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="productos_view.php?id=<?php echo $producto['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver producto">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="productos_edit.php?id=<?php echo $producto['id']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar producto">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
                    <a href="categorias_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar Categoría
                    </a>
                    <a href="productos_create.php?categoria=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear Producto
                    </a>
                    <a href="categorias.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Categorías
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
                        <span>Total Productos:</span>
                        <strong><?php echo count($productos); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Productos Activos:</span>
                        <strong><?php echo count(array_filter($productos, function($p) { return $p['activo']; })); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Productos Inactivos:</span>
                        <strong><?php echo count(array_filter($productos, function($p) { return !$p['activo']; })); ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Eliminar Categoría</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php if (count($productos) > 0): ?>
                        No puedes eliminar esta categoría porque tiene <?php echo count($productos); ?> producto(s) asociado(s).
                    <?php else: ?>
                        Esta categoría no tiene productos asociados y puede ser eliminada.
                    <?php endif; ?>
                </p>
                <?php if (count($productos) == 0): ?>
                    <button type="button" 
                            class="btn btn-danger btn-sm w-100" 
                            onclick="deleteCategory(<?php echo $id; ?>)">
                        <i class="fas fa-trash me-2"></i>Eliminar Categoría
                    </button>
                <?php endif; ?>
            </div>
        </div>
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
                setTimeout(() => {
                    window.location.href = 'categorias.php';
                }, 1500);
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
