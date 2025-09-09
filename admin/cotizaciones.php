<?php
$pageTitle = 'Gestión de Cotizaciones';
$pageActions = '<a href="cotizaciones_create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nueva Cotización</a>';
require_once 'includes/header.php';

// Obtener filtros
$cliente_id = $_GET['cliente'] ?? '';
$estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir condiciones de búsqueda
$conditions = [];
if ($cliente_id) {
    $conditions[] = "c.cliente_id = $cliente_id";
}
if ($estado) {
    $conditions[] = "c.estado = '$estado'";
}
if ($busqueda) {
    $conditions[] = "(c.numero_cotizacion LIKE '%$busqueda%' OR cl.nombre LIKE '%$busqueda%' OR cl.empresa LIKE '%$busqueda%')";
}
if ($fecha_desde) {
    $conditions[] = "DATE(c.created_at) >= '$fecha_desde'";
}
if ($fecha_hasta) {
    $conditions[] = "DATE(c.created_at) <= '$fecha_hasta'";
}

// Obtener cotizaciones con información de cliente
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.empresa as cliente_empresa, u.username as creado_por
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id 
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        $whereClause 
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$cotizaciones = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener clientes para el filtro
$clientes = readRecords('clientes', [], null, 'nombre ASC');
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <select class="form-select" id="cliente" name="cliente">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                        <?php echo $cliente_id == $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                                    <?php if ($cliente['empresa']): ?>
                                        - <?php echo htmlspecialchars($cliente['empresa']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="enviada" <?php echo $estado === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                            <option value="aceptada" <?php echo $estado === 'aceptada' ? 'selected' : ''; ?>>Aceptada</option>
                            <option value="en_espera_deposito" <?php echo $estado === 'en_espera_deposito' ? 'selected' : ''; ?>>En Espera de Depósito</option>
                            <option value="rechazada" <?php echo $estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                            <option value="cancelada" <?php echo $estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_desde" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" 
                               class="form-control" 
                               id="busqueda" 
                               name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Número, cliente...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="cotizaciones.php" class="btn btn-secondary">
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
            <i class="fas fa-file-invoice me-2"></i>Listado de Cotizaciones
            <span class="badge bg-primary ms-2"><?php echo count($cotizaciones); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($cotizaciones)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay cotizaciones registradas</h5>
                <p class="text-muted">Comienza creando tu primera cotización</p>
                <a href="cotizaciones_create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Primera Cotización
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover data-table" id="cotizacionesTable">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Vencimiento</th>
                            <th>Creado por</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                        <tr>
                            <td>
                                <code><?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></code>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></strong>
                                    <?php if ($cotizacion['cliente_empresa']): ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($cotizacion['cliente_empresa']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">$<?php echo number_format($cotizacion['total'], 2); ?></strong>
                                <?php if ($cotizacion['descuento'] > 0): ?>
                                    <br>
                                    <small class="text-muted">Desc: $<?php echo number_format($cotizacion['descuento'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $estadoClass = [
                                    'pendiente' => 'warning',
                                    'enviada' => 'info',
                                    'aceptada' => 'success',
                                    'en_espera_deposito' => 'primary',
                                    'rechazada' => 'danger',
                                    'cancelada' => 'secondary'
                                ];
                                $class = $estadoClass[$cotizacion['estado']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $class; ?>">
                                    <?php 
                                    $estadoTexto = [
                                        'pendiente' => 'Pendiente',
                                        'enviada' => 'Enviada',
                                        'aceptada' => 'Aceptada',
                                        'en_espera_deposito' => 'En Espera de Depósito',
                                        'rechazada' => 'Rechazada',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    echo $estadoTexto[$cotizacion['estado']] ?? ucfirst($cotizacion['estado']);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($cotizacion['fecha_vencimiento']): ?>
                                    <?php
                                    $vencimiento = new DateTime($cotizacion['fecha_vencimiento']);
                                    $hoy = new DateTime();
                                    $dias = $hoy->diff($vencimiento)->days;
                                    
                                    if ($vencimiento < $hoy) {
                                        $class = 'danger';
                                        $texto = 'Vencida';
                                    } elseif ($dias <= 3) {
                                        $class = 'warning';
                                        $texto = 'Próxima a vencer';
                                    } else {
                                        $class = 'success';
                                        $texto = 'Vigente';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>">
                                        <?php echo $texto; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo formatDate($cotizacion['fecha_vencimiento'], 'd/m/Y'); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Sin vencimiento</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($cotizacion['creado_por']); ?></small>
                            </td>
                            <td><?php echo formatDate($cotizacion['created_at']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="cotizaciones_view.php?id=<?php echo $cotizacion['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="cotizaciones_edit.php?id=<?php echo $cotizacion['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-sm btn-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" 
                                                title="Acciones">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?php echo $cotizacion['id']; ?>, 'enviada')">
                                                <i class="fas fa-paper-plane me-2"></i>Marcar como Enviada
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?php echo $cotizacion['id']; ?>, 'aceptada')">
                                                <i class="fas fa-check me-2"></i>Marcar como Aceptada
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?php echo $cotizacion['id']; ?>, 'rechazada')">
                                                <i class="fas fa-times me-2"></i>Marcar como Rechazada
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCotizacion(<?php echo $cotizacion['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>Eliminar
                                            </a></li>
                                        </ul>
                                    </div>
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
/* Corregir z-index del dropdown de acciones */
.dropdown-menu {
    z-index: 1050 !important;
    min-width: 200px !important;
    max-height: none !important;
    overflow: visible !important;
}

/* Asegurar que el dropdown se muestre correctamente en tablas */
.table-responsive .dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
    transform: translateY(0) !important;
}

/* Mejorar la visibilidad del dropdown */
.dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border: 1px solid rgba(0, 0, 0, 0.15) !important;
    background: white !important;
}

/* Asegurar que el contenedor de la tabla no corte el dropdown */
.table-responsive {
    overflow: visible !important;
    margin-bottom: 100px !important; /* Espacio extra para el dropdown */
}

/* Ajustar el contenedor de la tabla para evitar overflow */
.card-body {
    overflow: visible !important;
    padding-bottom: 50px !important; /* Espacio extra para el dropdown */
}

/* Asegurar que el dropdown se muestre completamente */
.dropdown {
    position: relative !important;
}

.dropdown-menu.show {
    display: block !important;
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    z-index: 1050 !important;
}
</style>

<script>
// Función para cambiar estado de cotización
function cambiarEstado(id, estado) {
    console.log('DEBUG: Iniciando cambiarEstado');
    console.log('DEBUG: ID:', id, 'Estado:', estado);
    
    const estados = {
        'enviada': 'enviada',
        'aceptada': 'aceptada',
        'rechazada': 'rechazada'
    };
    
    const estadoTexto = estados[estado] || estado;
    console.log('DEBUG: Estado texto:', estadoTexto);
    
    if (confirm(`¿Estás seguro de marcar esta cotización como ${estadoTexto}?`)) {
        console.log('DEBUG: Usuario confirmó el cambio');
        
        const url = '../ajax/cotizaciones.php';
        const body = `action=change_status&id=${id}&estado=${estado}`;
        
        console.log('DEBUG: URL:', url);
        console.log('DEBUG: Body:', body);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => {
            console.log('DEBUG: Response status:', response.status);
            console.log('DEBUG: Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log('DEBUG: Response text:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('DEBUG: Parsed data:', data);
                
                if (data.success) {
                    console.log('DEBUG: Éxito - mostrando alerta y recargando');
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    console.log('DEBUG: Error - mostrando alerta de error');
                    showAlert(data.message, 'danger');
                }
            } catch (e) {
                console.error('DEBUG: Error parsing JSON:', e);
                console.error('DEBUG: Raw response:', text);
                showAlert('Error al procesar la respuesta del servidor', 'danger');
            }
        })
        .catch(error => {
            console.error('DEBUG: Error en fetch:', error);
            showAlert('Error al cambiar el estado de la cotización: ' + error.message, 'danger');
        });
    } else {
        console.log('DEBUG: Usuario canceló el cambio');
    }
}

// Función para eliminar cotización
function deleteCotizacion(id) {
    if (confirm('¿Estás seguro de eliminar esta cotización? Esta acción no se puede deshacer.')) {
        fetch('../ajax/cotizaciones.php', {
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
            showAlert('Error al eliminar la cotización', 'danger');
        });
    }
}

// Inicializar DataTable
$(document).ready(function() {
    $('#cotizacionesTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [7] } // Deshabilitar ordenamiento en acciones
        ],
        dom: 'rtip', // Cambiar el layout para evitar problemas con dropdowns
        scrollX: true // Permitir scroll horizontal si es necesario
    });
    
    // Asegurar que los dropdowns se muestren correctamente
    $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Cerrar otros dropdowns abiertos
        $('.dropdown-menu').not($(this).next('.dropdown-menu')).removeClass('show').hide();
        
        // Toggle el dropdown actual
        const dropdown = $(this).next('.dropdown-menu');
        dropdown.toggleClass('show');
        
        if (dropdown.hasClass('show')) {
            dropdown.show();
            // Asegurar que el dropdown esté visible
            dropdown.css({
                'display': 'block',
                'position': 'absolute',
                'z-index': '1050',
                'top': '100%',
                'left': '0'
            });
        } else {
            dropdown.hide();
        }
    });
    
    // Cerrar dropdowns al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show').hide();
        }
    });
    
    // Prevenir que el dropdown se cierre al hacer clic en él
    $('.dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
