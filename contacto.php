<?php
// Configurar headers UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Contacto - DT Studio';
$pageDescription = 'Ponte en contacto con nosotros. Estamos aquí para ayudarte con tus productos promocionales.';

$error = '';
$success = '';

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $email = sanitizeInput($_POST['email']);
    $telefono = sanitizeInput($_POST['telefono']);
    $empresa = sanitizeInput($_POST['empresa']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    
    // Validar datos
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Los campos nombre, email y mensaje son requeridos';
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
    } else {
        // Aquí iría la lógica para enviar el email
        // Por ahora simulamos el envío exitoso
        $success = 'Mensaje enviado exitosamente. Te contactaremos pronto.';
        
        // Limpiar formulario
        $_POST = [];
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
                <li class="breadcrumb-item active">Contacto</li>
            </ol>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4" style="color: var(--primary-color);">Contáctanos</h1>
                    <p class="lead mb-4">¿Tienes alguna pregunta? ¿Necesitas una cotización personalizada? Estamos aquí para ayudarte.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-envelope fa-8x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form and Info -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-paper-plane me-2"></i>Envíanos un Mensaje
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
                            
                            <form method="POST" id="contactForm" class="needs-validation" novalidate>
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
                                
                                <div class="mb-3">
                                    <label for="mensaje" class="form-label">Mensaje *</label>
                                    <textarea class="form-control" 
                                              id="mensaje" 
                                              name="mensaje" 
                                              rows="5" 
                                              required 
                                              placeholder="Cuéntanos sobre tu proyecto o consulta..."><?php echo $_POST['mensaje'] ?? ''; ?></textarea>
                                    <div class="invalid-feedback">
                                        Por favor ingresa tu mensaje.
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
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
                                <i class="fas fa-info-circle me-2"></i>Información de Contacto
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <div class="contact-item mb-3">
                                    <i class="fas fa-phone text-primary me-3"></i>
                                    <div>
                                        <strong>Teléfono</strong><br>
                                        <a href="tel:4462129198" class="text-decoration-none">4462129198</a>
                                    </div>
                                </div>
                                <div class="contact-item mb-3">
                                    <i class="fas fa-envelope text-primary me-3"></i>
                                    <div>
                                        <strong>Email</strong><br>
                                        <a href="mailto:cotizaciones@dtstudio.com.mx" class="text-decoration-none">cotizaciones@dtstudio.com.mx</a>
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-clock text-primary me-3"></i>
                                    <div>
                                        <strong>Horarios</strong><br>
                                        Lunes - Viernes: 9:00 AM - 6:00 PM<br>
                                        Sábados: 10:00 AM - 4:00 PM
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-share-alt me-2"></i>Síguenos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                    <i class="fab fa-facebook me-1"></i>Facebook
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                    <i class="fab fa-instagram me-1"></i>Instagram
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                    <i class="fab fa-linkedin me-1"></i>LinkedIn
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm mb-2">
                                    <i class="fab fa-twitter me-1"></i>Twitter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="text-center mb-4">Nuestra Ubicación</h3>
                    <div class="map-placeholder bg-primary text-white text-center py-5 rounded">
                        <i class="fas fa-map fa-3x mb-3"></i>
                        <h5>Mapa Interactivo</h5>
                        <p class="mb-0">Aquí se mostraría un mapa de Google Maps</p>
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
                    <h2 class="display-6 fw-bold mb-3">¿Listo para Empezar tu Proyecto?</h2>
                    <p class="lead mb-0">Solicita una cotización personalizada y descubre cómo podemos ayudarte a destacar tu marca.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="cotizacion.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/public_footer.php'; ?>
