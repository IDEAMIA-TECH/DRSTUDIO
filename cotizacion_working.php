<?php
// Versión completamente simple que funciona
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'cotizacion_working.log');

echo "=== COTIZACION WORKING DEBUG ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data: " . print_r($_POST, true) . "\n";

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Solicitar Cotización - DT Studio';
$error = '';
$success = '';

if ($_POST) {
    echo "=== PROCESANDO POST ===\n";
    
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $empresa = sanitizeInput($_POST['empresa'] ?? '');
    $mensaje = sanitizeInput($_POST['mensaje'] ?? '');
    $productos_interes = sanitizeInput($_POST['productos_interes'] ?? '');
    $cantidad_estimada = sanitizeInput($_POST['cantidad_estimada'] ?? '');
    $fecha_entrega = sanitizeInput($_POST['fecha_entrega'] ?? '');
    
    echo "Datos sanitizados - Nombre: '$nombre', Email: '$email', Mensaje: '$mensaje'\n";
    
    // Validar datos
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Los campos nombre, email y mensaje son requeridos';
        echo "ERROR: Campos requeridos faltantes\n";
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
        echo "ERROR: Email inválido\n";
    } else {
        echo "✓ Validación pasada\n";
        
        // Crear registro de solicitud de cotización en la base de datos
        $solicitud_data = [
            'cliente_nombre' => $nombre,
            'cliente_email' => $email,
            'cliente_telefono' => $telefono,
            'cliente_empresa' => $empresa,
            'productos_interes' => $productos_interes,
            'cantidad_estimada' => $cantidad_estimada,
            'fecha_entrega_deseada' => $fecha_entrega,
            'mensaje' => $mensaje,
            'estado' => 'pendiente'
        ];
        
        echo "Intentando insertar: " . print_r($solicitud_data, true) . "\n";
        
        if (createRecord('solicitudes_cotizacion', $solicitud_data)) {
            $cotizacion_id = $conn->insert_id;
            echo "✓ INSERCIÓN EXITOSA - ID: $cotizacion_id\n";
            
            // Enviar email de notificación
            try {
                echo "Iniciando envío de emails\n";
                require_once 'includes/SimpleEmailSender.php';
                $emailSender = new SimpleEmailSender();
                
                // Email para el cliente
                $cliente_subject = "Cotización Solicitada - DT Studio";
                $cliente_message = "
                    <h2>¡Gracias por tu solicitud de cotización!</h2>
                    <p>Hola <strong>$nombre</strong>,</p>
                    <p>Hemos recibido tu solicitud de cotización y la estamos procesando.</p>
                    <p><strong>Detalles de tu solicitud:</strong></p>
                    <ul>
                        <li><strong>Productos de interés:</strong> $productos_interes</li>
                        <li><strong>Cantidad estimada:</strong> $cantidad_estimada</li>
                        <li><strong>Fecha de entrega deseada:</strong> " . ($fecha_entrega ? date('d/m/Y', strtotime($fecha_entrega)) : 'No especificada') . "</li>
                    </ul>
                    <p>Nuestro equipo revisará tu solicitud y te contactaremos en las próximas 24 horas.</p>
                    <p>Si tienes alguna pregunta, no dudes en contactarnos al +52 (446) 212-9198</p>
                    <p>Saludos,<br>Equipo DT Studio</p>
                ";
                
                $result_cliente = $emailSender->sendEmail($email, $cliente_subject, $cliente_message);
                echo "Email al cliente: " . ($result_cliente ? 'ENVIADO' : 'ERROR') . "\n";
                
                // Email para el administrador
                $admin_subject = "Nueva Solicitud de Cotización - DT Studio";
                $admin_message = "
                    <h2>Nueva Solicitud de Cotización</h2>
                    <p><strong>ID de Cotización:</strong> $cotizacion_id</p>
                    <p><strong>Cliente:</strong> $nombre</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Teléfono:</strong> $telefono</p>
                    <p><strong>Empresa:</strong> $empresa</p>
                    <p><strong>Productos de interés:</strong> $productos_interes</p>
                    <p><strong>Cantidad estimada:</strong> $cantidad_estimada</p>
                    <p><strong>Fecha de entrega deseada:</strong> " . ($fecha_entrega ? date('d/m/Y', strtotime($fecha_entrega)) : 'No especificada') . "</p>
                    <p><strong>Mensaje:</strong> $mensaje</p>
                    <p><a href='https://dtstudio.com.mx/admin/solicitudes_cotizacion.php?id=$cotizacion_id'>Ver solicitud completa</a></p>
                ";
                
                $result_admin = $emailSender->sendEmail('cotizaciones@dtstudio.com.mx', $admin_subject, $admin_message);
                echo "Email al administrador: " . ($result_admin ? 'ENVIADO' : 'ERROR') . "\n";
                
            } catch (Exception $e) {
                echo "ERROR EN ENVÍO DE EMAILS: " . $e->getMessage() . "\n";
            }
            
            $success = 'Cotización solicitada exitosamente. Te contactaremos en 24 horas.';
            $_POST = [];
            
        } else {
            $error = 'Error al procesar la solicitud. Por favor intenta nuevamente.';
            echo "ERROR EN INSERCIÓN: " . $conn->error . "\n";
        }
    }
}

// Incluir header compartido
require_once 'includes/public_header.php';
?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active">Solicitar Cotización</li>
            </ol>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4" style="color: var(--primary-color);">Solicitar Cotización</h1>
                    <p class="lead mb-4">Obtén una cotización personalizada para tus productos promocionales. Respuesta rápida y precios competitivos.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-calculator fa-8x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Quote Form -->
    <section class="quote-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Formulario de Cotización
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <!-- Información Personal -->
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Información Personal
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nombre" 
                                                   name="nombre" 
                                                   value="<?php echo $_POST['nombre'] ?? ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" 
                                                   class="form-control" 
                                                   id="telefono" 
                                                   name="telefono" 
                                                   value="<?php echo $_POST['telefono'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="empresa" class="form-label">Empresa</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="empresa" 
                                                   name="empresa" 
                                                   value="<?php echo $_POST['empresa'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Información del Proyecto -->
                                <hr class="my-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-project-diagram me-2"></i>Información del Proyecto
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="productos_interes" class="form-label">Productos de Interés</label>
                                    <textarea class="form-control" 
                                              id="productos_interes" 
                                              name="productos_interes" 
                                              rows="3" 
                                              placeholder="Describe los productos que te interesan..."><?php echo $_POST['productos_interes'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cantidad_estimada" class="form-label">Cantidad Estimada</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="cantidad_estimada" 
                                                   name="cantidad_estimada" 
                                                   value="<?php echo $_POST['cantidad_estimada'] ?? ''; ?>"
                                                   placeholder="Ej: 100, 500, 1000+">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_entrega" class="form-label">Fecha de Entrega Deseada</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_entrega" 
                                                   name="fecha_entrega" 
                                                   value="<?php echo $_POST['fecha_entrega'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mensaje" class="form-label">Mensaje Adicional *</label>
                                    <textarea class="form-control" 
                                              id="mensaje" 
                                              name="mensaje" 
                                              rows="4" 
                                              required 
                                              placeholder="Cuéntanos más detalles sobre tu proyecto..."><?php echo $_POST['mensaje'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Solicitar Cotización
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-2"></i>Limpiar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/public_footer.php'; ?>
