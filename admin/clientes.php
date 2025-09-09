<?php
$pageTitle = 'Gestión de Clientes';
$pageActions = '<a href="clientes_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nuevo Cliente</a>';
require_once 'includes/header.php';

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';

// Construir condiciones de búsqueda
$conditions = [];
if ($busqueda) {
    $conditions[] = "(nombre LIKE '%$busqueda%' OR email LIKE '%$busqueda%' OR empresa LIKE '%$busqueda%' OR telefono LIKE '%$busqueda%')";
}

// Obtener clientes
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT c.*, 
        COUNT(cot.id) as total_cotizaciones,
        SUM(CASE WHEN cot.estado = 'aceptada' THEN cot.total ELSE 0 END) as total_ventas
        FROM clientes c 
        LEFT JOIN cotizaciones cot ON c.id = cot.cliente_id 
        $whereClause 
        GROUP BY c.id 
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$clientes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <label for="busqueda" class="form-label">Buscar Cliente</label>
                        <input type="text" 
                               class="form-control" 
                               id="busqueda" 
                               name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Nombre, email, empresa o teléfono">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="clientes.php" class="btn btn-secondary">
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
            <i class="fas fa-users me-2"></i>Listado de Clientes
            <span class="badge bg-primary ms-2"><?php echo count($clientes); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($clientes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay clientes registrados</h5>
                <p class="text-muted">Comienza agregando tu primer cliente</p>
                <a href="clientes_create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Agregar Primer Cliente
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover data-table" id="clientesTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Empresa</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Cotizaciones</th>
                            <th>Total Ventas</th>
                            <th>Última Cotización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3">
                                        <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong>
                                        <?php if ($cliente['direccion']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars(substr($cliente['direccion'], 0, 30)); ?>
                                                <?php if (strlen($cliente['direccion']) > 30): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($cliente['empresa']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($cliente['empresa']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cliente['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($cliente['email']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cliente['telefono']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($cliente['telefono']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($cliente['telefono']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $cliente['total_cotizaciones']; ?></span>
                            </td>
                            <td>
                                <?php if ($cliente['total_ventas'] > 0): ?>
                                    <strong class="text-success">$<?php echo number_format($cliente['total_ventas'], 2); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">$0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                // Obtener última cotización
                                $ultimaCotizacion = $conn->query("SELECT created_at FROM cotizaciones WHERE cliente_id = {$cliente['id']} ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
                                if ($ultimaCotizacion) {
                                    echo formatDate($ultimaCotizacion['created_at']);
                                } else {
                                    echo '<span class="text-muted">Nunca</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="clientes_view.php?id=<?php echo $cliente['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="clientes_edit.php?id=<?php echo $cliente['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteCliente(<?php echo $cliente['id']; ?>)" 
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

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}
</style>

<script>
// Función para eliminar cliente
function deleteCliente(id) {
    if (confirmDelete('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.')) {
        ajaxRequest('ajax/clientes.php', {
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
    $('#clientesTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [7] } // Deshabilitar ordenamiento en acciones
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
