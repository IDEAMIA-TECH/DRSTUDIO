<?php
// Configurar headers UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Solicitar Cotización - DT Studio';
$pageDescription = 'Solicita una cotización personalizada para tus productos promocionales. Respuesta rápida y precios competitivos.';

// Obtener categorías para el menú
$categorias = readRecords('categorias', ['activo' => 1], null, 'nombre ASC');

// Obtener producto preseleccionado si viene por GET
$producto_preseleccionado = $_GET['producto'] ?? '';

$error = '';
$success = '';

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $email = sanitizeInput($_POST['email']);
    $telefono = sanitizeInput($_POST['telefono']);
    $empresa = sanitizeInput($_POST['empresa']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    $productos_interes = sanitizeInput($_POST['productos_interes']);
    $cantidad_estimada = sanitizeInput($_POST['cantidad_estimada']);
    $fecha_entrega = sanitizeInput($_POST['fecha_entrega']);
    
    // Validar datos
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $error = 'Los campos nombre, email y mensaje son requeridos';
    } elseif (!validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
    } else {
        // Aquí iría la lógica para crear la cotización
        // Por ahora simulamos el envío exitoso
        $success = 'Cotización solicitada exitosamente. Te contactaremos en 24 horas.';
        
        // Limpiar formulario
        $_POST = [];
    }
}

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/public.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="assets/logo/LOGO.png" alt="DT Studio" height="40" class="me-2">
                <span style="color: var(--primary-color) !important;">DT Studio</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="productosDropdown" role="button" data-bs-toggle="dropdown">
                            Productos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="productos.php">Todos los Productos</a></li>
                            <?php foreach ($categorias as $categoria): ?>
                                <li><a class="dropdown-item" href="productos.php?categoria=<?php echo $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="galeria.php">Galería</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.php">Contacto</a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="cotizacion.php" class="btn btn-primary">
                        <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                    </a>
                    <a href="admin/" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

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
                            
                            <form method="POST" id="quoteForm" class="needs-validation" novalidate>
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
                                <a href="tel:+15551234567" class="text-decoration-none">+1 (555) 123-4567</a>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <a href="mailto:info@drstudio.com" class="text-decoration-none">info@drstudio.com</a>
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

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/logo/LOGO.png" alt="DT Studio" height="30" class="me-2">
                        <h5 class="fw-bold mb-0">DT Studio</h5>
                    </div>
                    <p class="text-muted">Especialistas en productos promocionales de alta calidad. Transformamos tus ideas en realidad.</p>
                    <div class="social-links">
                        <a href="#" class="text-muted me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Productos</h6>
                    <ul class="list-unstyled">
                        <li><a href="productos.php" class="text-muted text-decoration-none">Todos los Productos</a></li>
                        <?php foreach (array_slice($categorias, 0, 4) as $categoria): ?>
                            <li><a href="productos.php?categoria=<?php echo $categoria['id']; ?>" class="text-muted text-decoration-none">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="galeria.php" class="text-muted text-decoration-none">Galería</a></li>
                        <li><a href="contacto.php" class="text-muted text-decoration-none">Contacto</a></li>
                        <li><a href="cotizacion.php" class="text-muted text-decoration-none">Cotización</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="fw-bold mb-3">Contacto</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-phone me-2"></i>+1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope me-2"></i>info@drstudio.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>123 Main St, City, State 12345</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 DT Studio. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="admin/" class="text-muted text-decoration-none">Panel de Administración</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/public.js"></script>
</body>
</html>
