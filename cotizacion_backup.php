<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Solicitar Cotización - DT Studio';
$pageDescription = 'Solicita una cotización personalizada para tus productos promocionales. Respuesta rápida y precios competitivos.';

// Obtener producto preseleccionado si viene por GET
$producto_preseleccionado = $_GET['producto'] ?? '';
$producto_info = null;

if ($producto_preseleccionado) {
    $producto_info = getRecord('productos', $producto_preseleccionado);
    if (!$producto_info || !$producto_info['activo']) {
        $producto_info = null;
        $producto_preseleccionado = '';
    }
}

$error = '';
$success = '';

if ($_POST) {
    // Log detallado del proceso
    error_log("=== COTIZACION FORM DEBUG (WEB) ===");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    error_log("POST data recibido: " . print_r($_POST, true));
    
    $nombre = sanitizeInput($_POST['nombre']);
    $email = sanitizeInput($_POST['email']);
    $telefono = sanitizeInput($_POST['telefono']);
    $empresa = sanitizeInput($_POST['empresa']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    $productos_interes = sanitizeInput($_POST['productos_interes']);
    $cantidad_estimada = sanitizeInput($_POST['cantidad_estimada']);
    $fecha_entrega = sanitizeInput($_POST['fecha_entrega']);
    
    error_log("Datos sanitizados - Nombre: $nombre, Email: $email, Mensaje: $mensaje");
    
    // Validar datos
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Los campos nombre, email y mensaje son requeridos';
        error_log("ERROR: Campos requeridos faltantes - Nombre: '$nombre', Email: '$email', Mensaje: '$mensaje'");
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
        error_log("ERROR: Email inválido - '$email'");
    } else {
        error_log("✓ Validación pasada correctamente");
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
        
        // Insertar solicitud en la base de datos
        error_log("Intentando insertar en BD - Datos: " . print_r($solicitud_data, true));
        error_log("Conexión BD: " . ($conn ? 'OK' : 'ERROR'));
        
        if (createRecord('solicitudes_cotizacion', $solicitud_data)) {
            // Obtener el ID de la cotización recién creada
            $cotizacion_id = $conn->insert_id;
            error_log("✓ INSERCIÓN EXITOSA - ID: $cotizacion_id");
            
            // Enviar email de notificación
            try {
                error_log("=== INICIANDO ENVÍO DE EMAILS ===");
                require_once 'includes/SimpleEmailSender.php';
                $emailSender = new SimpleEmailSender();
                error_log("✓ SimpleEmailSender instanciado correctamente");
                
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
                
                error_log("Enviando email al cliente: $email");
                $cliente_result = $emailSender->sendEmail($email, $cliente_subject, $cliente_message);
                error_log("Resultado email cliente: " . ($cliente_result ? 'ÉXITO' : 'ERROR'));
                
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
                
                error_log("Enviando email al administrador: cotizaciones@dtstudio.com.mx");
                $admin_result = $emailSender->sendEmail('cotizaciones@dtstudio.com.mx', $admin_subject, $admin_message);
                error_log("Resultado email administrador: " . ($admin_result ? 'ÉXITO' : 'ERROR'));
                
                error_log("=== FIN ENVÍO DE EMAILS ===");
                
            } catch (Exception $e) {
                // Si falla el email, aún así mostrar éxito pero logear el error
                error_log("ERROR EN ENVÍO DE EMAILS: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            $success = 'Cotización solicitada exitosamente. Te contactaremos en 24 horas.';
            
            // Limpiar formulario
            $_POST = [];
        } else {
            $error = 'Error al procesar la solicitud. Por favor intenta nuevamente.';
            error_log("✗ ERROR EN INSERCIÓN - MySQL Error: " . $conn->error);
            error_log("✗ ERROR EN INSERCIÓN - MySQL Errno: " . $conn->errno);
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
                            
                            <?php if ($producto_info): ?>
                            <!-- Producto Preseleccionado -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <h5 class="mb-0">Producto de Interés</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="mb-2"><?php echo htmlspecialchars($producto_info['nombre']); ?></h6>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($producto_info['descripcion'], 0, 150)); ?>...</p>
                                        <div class="d-flex align-items-center">
                                            <span class="h6 text-primary mb-0 me-3">$<?php echo number_format($producto_info['precio_venta'], 2); ?></span>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($producto_info['sku']); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <a href="producto.php?id=<?php echo $producto_info['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="quoteForm" class="needs-validation" novalidate onsubmit="return handleFormSubmit(event)">
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
                                            <div class="invalid-feedback">
                                                Por favor ingresa tu nombre completo.
                                            </div>
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
                                            <div class="invalid-feedback">
                                                Por favor ingresa un email válido.
                                            </div>
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
                                                   value="<?php echo $_POST['telefono'] ?? ''; ?>"
                                                   oninput="formatPhone(this)">
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
                                              placeholder="Describe los productos que te interesan..."><?php 
                                              if ($_POST['productos_interes'] ?? '') {
                                                  echo $_POST['productos_interes'];
                                              } elseif ($producto_info) {
                                                  echo "Interesado en: " . htmlspecialchars($producto_info['nombre']) . " (SKU: " . htmlspecialchars($producto_info['sku']) . ")\n";
                                                  echo "Precio: $" . number_format($producto_info['precio_venta'], 2) . "\n";
                                                  echo "Descripción: " . htmlspecialchars(substr($producto_info['descripcion'], 0, 200));
                                              }
                                              ?></textarea>
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
                                    <div class="invalid-feedback">
                                        Por favor ingresa un mensaje.
                                    </div>
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
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>¿Por qué elegirnos?
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Respuesta Rápida</strong><br>
                                    <small class="text-muted">Cotizaciones en 24 horas</small>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Precios Competitivos</strong><br>
                                    <small class="text-muted">Mejores precios del mercado</small>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Calidad Garantizada</strong><br>
                                    <small class="text-muted">Productos de alta calidad</small>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Entrega Puntual</strong><br>
                                    <small class="text-muted">Cumplimos con los tiempos</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-phone me-2"></i>Contacto Directo
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <a href="tel:+524462129198" class="text-decoration-none">+52 (446) 212-9198</a>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <a href="mailto:cotizaciones@dtstudio.com.mx" class="text-decoration-none">cotizaciones@dtstudio.com.mx</a>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock text-primary me-2"></i>
                                Lunes - Viernes: 9:00 AM - 6:00 PM
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h3 class="display-6 fw-bold mb-3">Nuestro Proceso</h3>
                    <p class="lead text-muted">Así es como trabajamos contigo</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="process-step bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="h4 mb-0">1</span>
                        </div>
                        <h5>Solicita Cotización</h5>
                        <p class="text-muted">Completa nuestro formulario con los detalles de tu proyecto</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="process-step bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="h4 mb-0">2</span>
                        </div>
                        <h5>Revisión y Análisis</h5>
                        <p class="text-muted">Nuestro equipo revisa tu solicitud y analiza los requerimientos</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="process-step bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="h4 mb-0">3</span>
                        </div>
                        <h5>Cotización Personalizada</h5>
                        <p class="text-muted">Te enviamos una cotización detallada con precios y tiempos</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="process-step bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="h4 mb-0">4</span>
                        </div>
                        <h5>Producción y Entrega</h5>
                        <p class="text-muted">Producimos tus productos y los entregamos a tiempo</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-3">¿Tienes Preguntas?</h2>
                    <p class="lead mb-0">Nuestro equipo está listo para ayudarte. Contáctanos directamente.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="contacto.php" class="btn btn-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contactar
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/public_footer.php'; ?>

<script>
// Función para manejar el envío del formulario con logging
function handleFormSubmit(event) {
    console.log("=== FORMULARIO COTIZACIÓN - INICIO ===");
    console.log("Timestamp:", new Date().toISOString());
    
    // Obtener datos del formulario
    const formData = new FormData(event.target);
    const formObject = {};
    
    console.log("Datos del formulario:");
    for (let [key, value] of formData.entries()) {
        formObject[key] = value;
        console.log(`  ${key}: ${value}`);
    }
    
    // Validar campos requeridos
    const nombre = formData.get('nombre');
    const email = formData.get('email');
    const mensaje = formData.get('mensaje');
    
    console.log("Validación de campos requeridos:");
    console.log(`  Nombre: "${nombre}" (${nombre ? 'OK' : 'FALTA'})`);
    console.log(`  Email: "${email}" (${email ? 'OK' : 'FALTA'})`);
    console.log(`  Mensaje: "${mensaje}" (${mensaje ? 'OK' : 'FALTA'})`);
    
    if (!nombre || !email || !mensaje) {
        console.error("✗ ERROR: Campos requeridos faltantes");
        return true; // Dejar que el navegador maneje la validación
    }
    
    console.log("✓ Validación de campos pasada");
    console.log("Enviando formulario...");
    
    // Mostrar indicador de carga
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    submitBtn.disabled = true;
    
    // Log cuando se complete el envío
    setTimeout(() => {
        console.log("Formulario enviado, esperando respuesta...");
    }, 100);
    
    return true; // Permitir el envío del formulario
}

// Log cuando se carga la página
console.log("=== PÁGINA COTIZACIÓN CARGADA ===");
console.log("Timestamp:", new Date().toISOString());
console.log("URL:", window.location.href);
console.log("User Agent:", navigator.userAgent);

// Log de errores de JavaScript
window.addEventListener('error', function(e) {
    console.error("JavaScript Error:", e.error);
    console.error("File:", e.filename, "Line:", e.lineno);
});

// Log de errores de red
window.addEventListener('unhandledrejection', function(e) {
    console.error("Unhandled Promise Rejection:", e.reason);
});
</script>
