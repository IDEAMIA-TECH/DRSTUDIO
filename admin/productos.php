<?php
$pageTitle = 'Gestión de Productos';
$pageActions = '<a href="productos_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nuevo Producto</a>';
require_once 'includes/header.php';

// Obtener filtros
$categoria_id = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';

// Construir condiciones de búsqueda
$conditions = [];
if ($categoria_id) {
    $conditions[] = "p.categoria_id = $categoria_id";
}
if ($busqueda) {
    $conditions[] = "(p.nombre LIKE '%$busqueda%' OR p.sku LIKE '%$busqueda%' OR p.descripcion LIKE '%$busqueda%')";
}
if ($estado !== '') {
    $conditions[] = "p.activo = $estado";
}

// Obtener productos con información de categoría
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        $whereClause 
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
$productos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener categorías para el filtro
$categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" 
                                        <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="1" <?php echo $estado === '1' ? 'selected' : ''; ?>>Activos</option>
                            <option value="0" <?php echo $estado === '0' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" 
                               class="form-control" 
                               id="busqueda" 
                               name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Nombre, SKU o descripción">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="productos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-box me-2"></i>Listado de Productos
            <span class="badge bg-primary ms-2"><?php echo count($productos); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($productos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay productos registrados</h5>
                <p class="text-muted">Comienza creando tu primer producto</p>
                <a href="productos_create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Primer Producto
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover data-table" id="productosTable">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Costo</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <?php
                        // Obtener stock total de variantes
                        $stockTotal = $conn->query("SELECT SUM(stock) as total FROM variantes_producto WHERE producto_id = {$producto['id']} AND activo = 1")->fetch_assoc()['total'] ?? 0;
                        ?>
                        <tr>
                            <td>
                                <?php if ($producto['imagen_principal']): ?>
                                    <img src="../uploads/productos/<?php echo $producto['imagen_principal']; ?>" 
                                         class="img-preview" 
                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($producto['sku']); ?></code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                <?php if ($producto['destacado']): ?>
                                    <span class="badge bg-warning ms-1">Destacado</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)); ?>
                                    <?php if (strlen($producto['descripcion']) > 50): ?>
                                        <span class="text-muted">...</span>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($producto['categoria_nombre']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin categoría</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-success">$<?php echo number_format($producto['precio_venta'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="text-muted">$<?php echo number_format($producto['costo_fabricacion'], 2); ?></span>
                            </td>
                            <td>
                                <?php if ($stockTotal > 10): ?>
                                    <span class="badge bg-success"><?php echo $stockTotal; ?></span>
                                <?php elseif ($stockTotal > 0): ?>
                                    <span class="badge bg-warning"><?php echo $stockTotal; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo $stockTotal; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($producto['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="productos_view.php?id=<?php echo $producto['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="productos_edit.php?id=<?php echo $producto['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteProduct(<?php echo $producto['id']; ?>)" 
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
// Función para eliminar producto
function deleteProduct(id) {
    if (confirmDelete('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer.')) {
        ajaxRequest('ajax/productos.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                // Recargar la página para actualizar la tabla
                location.reload();
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}

// DataTable se inicializa automáticamente desde footer.php
</script>

<?php require_once 'includes/footer.php'; ?>
