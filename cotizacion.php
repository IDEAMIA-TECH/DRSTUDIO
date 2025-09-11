<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Función para obtener usuarios admin
function getAdminUsers() {
    global $conn;
    $sql = "SELECT email, username FROM usuarios WHERE rol = 'admin' AND activo = 1";
    $result = $conn->query($sql);
    $admins = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
    }
    return $admins;
}

// Función para generar email elegante
function generateElegantEmail($tipo, $data) {
    $primaryColor = '#007bff';
    $secondaryColor = '#6c757d';
    $successColor = '#28a745';
    $lightColor = '#f8f9fa';
    $darkColor = '#343a40';
    
    if ($tipo === 'cliente') {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Cotización Solicitada - DT Studio</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, $primaryColor, #0056b3); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
                .content { padding: 30px; }
                .highlight { background-color: $lightColor; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid $primaryColor; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .info-item { padding: 15px; background-color: $lightColor; border-radius: 6px; }
                .info-label { font-weight: 600; color: $darkColor; margin-bottom: 5px; }
                .info-value { color: $secondaryColor; }
                .footer { background-color: $darkColor; color: white; padding: 20px; text-align: center; }
                .btn { display: inline-block; background-color: $primaryColor; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                .contact-info { background-color: $lightColor; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Gracias por tu solicitud!</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>DT Studio - Productos Promocionales</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$data['nombre']}</strong>,</p>
                    <p>Hemos recibido tu solicitud de cotización y la estamos procesando. Nuestro equipo revisará los detalles y te contactaremos en las próximas 24 horas.</p>
                    
                    <div class='highlight'>
                        <h3 style='margin-top: 0; color: $primaryColor;'>Detalles de tu solicitud</h3>
                        <div class='info-grid'>
                            <div class='info-item'>
                                <div class='info-label'>Productos de interés</div>
                                <div class='info-value'>{$data['productos_interes']}</div>
                            </div>
                            <div class='info-item'>
                                <div class='info-label'>Cantidad estimada</div>
                                <div class='info-value'>{$data['cantidad_estimada']}</div>
                            </div>
                            <div class='info-item'>
                                <div class='info-label'>Fecha de entrega</div>
                                <div class='info-value'>" . ($data['fecha_entrega'] ? date('d/m/Y', strtotime($data['fecha_entrega'])) : 'No especificada') . "</div>
                            </div>
                            <div class='info-item'>
                                <div class='info-label'>Empresa</div>
                                <div class='info-value'>" . ($data['empresa'] ?: 'No especificada') . "</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class='contact-info'>
                        <h4 style='margin-top: 0; color: $primaryColor;'>¿Tienes preguntas?</h4>
                        <p>Nuestro equipo está disponible para ayudarte:</p>
                        <p><strong>📞 Teléfono:</strong> +52 (446) 212-9198</p>
                        <p><strong>📧 Email:</strong> cotizaciones@dtstudio.com.mx</p>
                        <p><strong>🕒 Horario:</strong> Lunes - Viernes: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>© 2024 DT Studio. Todos los derechos reservados.</p>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'>Productos promocionales de alta calidad</p>
                </div>
            </div>
        </body>
        </html>";
    } else { // admin
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Nueva Solicitud de Cotización - DT Studio</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; }
                .container { max-width: 700px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, $primaryColor, #0056b3); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
                .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 6px; margin: 20px 0; }
                .content { padding: 30px; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .info-item { padding: 15px; background-color: $lightColor; border-radius: 6px; }
                .info-label { font-weight: 600; color: $darkColor; margin-bottom: 5px; }
                .info-value { color: $secondaryColor; }
                .message-box { background-color: $lightColor; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid $primaryColor; }
                .btn { display: inline-block; background-color: $successColor; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                .footer { background-color: $darkColor; color: white; padding: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nueva Solicitud de Cotización</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>ID: #{$data['cotizacion_id']}</p>
                </div>
                <div class='content'>
                    <div class='alert'>
                        <strong>⚠️ Acción Requerida:</strong> Se ha recibido una nueva solicitud de cotización que requiere tu atención.
                    </div>
                    
                    <h3 style='color: $primaryColor; margin-top: 0;'>Información del Cliente</h3>
                    <div class='info-grid'>
                        <div class='info-item'>
                            <div class='info-label'>Nombre Completo</div>
                            <div class='info-value'>{$data['nombre']}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Email</div>
                            <div class='info-value'>{$data['email']}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Teléfono</div>
                            <div class='info-value'>" . ($data['telefono'] ?: 'No proporcionado') . "</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Empresa</div>
                            <div class='info-value'>" . ($data['empresa'] ?: 'No especificada') . "</div>
                        </div>
                    </div>
                    
                    <h3 style='color: $primaryColor;'>Detalles del Proyecto</h3>
                    <div class='info-grid'>
                        <div class='info-item'>
                            <div class='info-label'>Productos de Interés</div>
                            <div class='info-value'>{$data['productos_interes']}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Cantidad Estimada</div>
                            <div class='info-value'>{$data['cantidad_estimada']}</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Fecha de Entrega Deseada</div>
                            <div class='info-value'>" . ($data['fecha_entrega'] ? date('d/m/Y', strtotime($data['fecha_entrega'])) : 'No especificada') . "</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Estado</div>
                            <div class='info-value'><span style='background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>PENDIENTE</span></div>
                        </div>
                    </div>
                    
                    <div class='message-box'>
                        <h4 style='margin-top: 0; color: $primaryColor;'>Mensaje del Cliente</h4>
                        <p style='white-space: pre-wrap; margin: 0;'>{$data['mensaje']}</p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://dtstudio.com.mx/admin/solicitudes_cotizacion.php?id={$data['cotizacion_id']}' class='btn'>
                            📋 Ver Solicitud Completa
                        </a>
                    </div>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>© 2024 DT Studio - Sistema de Administración</p>
                    <p style='margin: 5px 0 0 0; opacity: 0.8;'>Recibido el " . date('d/m/Y H:i') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
}

$pageTitle = 'Solicitar Cotización - DT Studio';
$error = '';
$success = '';

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $empresa = sanitizeInput($_POST['empresa'] ?? '');
    $mensaje = sanitizeInput($_POST['mensaje'] ?? '');
    $productos_interes = sanitizeInput($_POST['productos_interes'] ?? '');
    $cantidad_estimada = sanitizeInput($_POST['cantidad_estimada'] ?? '');
    $fecha_entrega = sanitizeInput($_POST['fecha_entrega'] ?? '');
    
    // Validar datos
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Los campos nombre, email y mensaje son requeridos';
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
    } else {
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
        
        if (createRecord('solicitudes_cotizacion', $solicitud_data)) {
            $cotizacion_id = $conn->insert_id;
            
            // Enviar email de notificación
            try {
                require_once 'includes/SimpleEmailSender.php';
                $emailSender = new SimpleEmailSender();
                
                // Preparar datos para los emails
                $email_data = [
                    'cotizacion_id' => $cotizacion_id,
                    'nombre' => $nombre,
                    'email' => $email,
                    'telefono' => $telefono,
                    'empresa' => $empresa,
                    'productos_interes' => $productos_interes,
                    'cantidad_estimada' => $cantidad_estimada,
                    'fecha_entrega' => $fecha_entrega,
                    'mensaje' => $mensaje
                ];
                
                // Email para el cliente (elegante)
                $cliente_subject = "Cotización Solicitada - DT Studio";
                $cliente_message = generateElegantEmail('cliente', $email_data);
                
                $emailSender->sendEmail($email, $cliente_subject, $cliente_message);
                
                // Emails para todos los administradores
                $admin_users = getAdminUsers();
                $admin_subject = "Nueva Solicitud de Cotización - DT Studio";
                $admin_message = generateElegantEmail('admin', $email_data);
                
                foreach ($admin_users as $admin) {
                    $emailSender->sendEmail($admin['email'], $admin_subject, $admin_message);
                }
                
            } catch (Exception $e) {
                // Error silencioso en emails
            }
            
            $success = 'Cotización solicitada exitosamente. Te contactaremos en 24 horas.';
            $_POST = [];
            
        } else {
            $error = 'Error al procesar la solicitud. Por favor intenta nuevamente.';
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
