<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/EmailSender.php';

$error = '';
$success = '';
$cotizacion = null;

// Verificar si se proporcionó un token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = 'Token de acceso no válido.';
} else {
    $token = $_GET['token'];
    
    // Buscar cotización por token
    $stmt = $conn->prepare("SELECT id FROM cotizaciones WHERE token_aceptacion = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $cotizacionId = $result->fetch_assoc()['id'] ?? null;
    $stmt->close();
    
    if (!$cotizacionId) {
        $error = 'Token de acceso inválido o expirado.';
    } else {
        // Obtener información de la cotización
        $cotizacion = getRecord('cotizaciones', $cotizacionId);
        
        if (!$cotizacion) {
            $error = 'Cotización no encontrada.';
        } else {
            // Obtener información del cliente
            $cliente = getRecord('clientes', $cotizacion['cliente_id']);
            
            // Obtener items de la cotización (productos del catálogo)
            $items = readRecords('cotizacion_items', ["cotizacion_id = $cotizacionId"], null, 'id ASC');
            foreach ($items as &$item) {
                $producto = getRecord('productos', $item['producto_id']);
                $item['producto'] = $producto;
                
                if ($item['variante_id']) {
                    $variante = getRecord('variantes_producto', $item['variante_id']);
                    $item['variante'] = $variante;
                }
            }
            // Limpiar la referencia para evitar problemas en bucles posteriores
            unset($item);
            
            // Obtener productos personalizados
            $productos_personalizados = readRecords('cotizacion_productos_personalizados', ["cotizacion_id = $cotizacionId"], null, 'id ASC');
            
            // Procesar aceptación si se envió el formulario
            if ($_POST && isset($_POST['accept_quote'])) {
                // Actualizar estado a "en espera de depósito"
                $updateData = [
                    'estado' => 'en_espera_deposito',
                    'fecha_aceptacion' => date('Y-m-d H:i:s')
                ];
                
                if (updateRecord('cotizaciones', $updateData, $cotizacionId)) {
                    $success = '¡Cotización aceptada exitosamente! Nos pondremos en contacto con usted para coordinar el pago y la producción.';
                    
                    // Enviar confirmación por correo al cliente
                    $emailSender = new EmailSender();
                    $emailSender->sendAcceptanceConfirmation($cotizacion, $cliente);
                } else {
                    $error = 'Error al procesar la aceptación. Por favor, intente nuevamente.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar Cotización - DT Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #7B3F9F 0%, #9B59B6 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        .container {
            padding-top: 50px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #7B3F9F 0%, #9B59B6 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 30px;
        }
        .btn-accept {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .quote-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .items-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .items-table th {
            background: #7B3F9F;
            color: white;
            border: none;
        }
        .total-section {
            background: linear-gradient(135deg, #7B3F9F 0%, #9B59B6 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-check-circle me-3"></i>Aceptar Cotización</h1>
                        <p class="mb-0">DT Studio - Productos Promocionales</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php elseif ($cotizacion): ?>
                            <div class="quote-details">
                                <h3><i class="fas fa-file-invoice me-2"></i>Detalles de la Cotización</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Número:</strong> <?php echo htmlspecialchars($cotizacion['numero_cotizacion']); ?></p>
                                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cotizacion['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente['nombre']); ?></p>
                                        <p><strong>Empresa:</strong> <?php echo htmlspecialchars($cliente['empresa'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <h4><i class="fas fa-list me-2"></i>Productos Cotizados</h4>
                            <div class="table-responsive">
                                <table class="table items-table">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Producto</th>
                                            <th>Talla</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Productos del catálogo -->
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">Catálogo</span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['producto']['nombre']); ?></strong><br>
                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['producto']['sku']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($item['variante']): ?>
                                                    <?php 
                                                    $variante_parts = array_filter([
                                                        $item['variante']['talla'] ?? '',
                                                        $item['variante']['color'] ?? '',
                                                        $item['variante']['material'] ?? ''
                                                    ]);
                                                    $variante_display = implode(' - ', $variante_parts);
                                                    echo htmlspecialchars($variante_display);
                                                    ?>
                                                <?php else: ?>
                                                    Sin variante
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['cantidad']; ?></td>
                                            <td>$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <!-- Productos personalizados -->
                                        <?php foreach ($productos_personalizados as $producto): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success">Personalizado</span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre_producto']); ?></strong><br>
                                                <small class="text-muted">Producto personalizado</small>
                                            </td>
                                            <td>
                                                <?php if ($producto['talla']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($producto['talla']); ?></span>
                                                <?php else: ?>
                                                    Sin talla
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $producto['cantidad']; ?></td>
                                            <td>$<?php echo number_format($producto['precio_venta'], 2); ?></td>
                                            <td>$<?php echo number_format($producto['subtotal'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="total-section">
                                <h3>Total de la Cotización</h3>
                                <h2>$<?php echo number_format($cotizacion['total'], 2); ?></h2>
                                <?php if ($cotizacion['descuento'] > 0): ?>
                                    <p>Incluye descuento de $<?php echo number_format($cotizacion['descuento'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($cotizacion['observaciones']): ?>
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
                                    <p><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <form method="POST">
                                    <button type="submit" name="accept_quote" class="btn btn-accept btn-lg">
                                        <i class="fas fa-check me-2"></i>ACEPTAR COTIZACIÓN
                                    </button>
                                </form>
                                <p class="mt-3 text-muted">
                                    Al aceptar esta cotización, confirma que está de acuerdo con los términos y condiciones.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
