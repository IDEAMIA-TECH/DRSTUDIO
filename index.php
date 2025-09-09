<?php
// Configurar headers UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Inicio - DT Studio';
$pageDescription = 'Productos promocionales de alta calidad. Cotizaciones personalizadas y entrega rápida.';

// Obtener productos destacados
$productos_destacados = readRecords('productos', ['activo = 1', 'destacado = 1'], 6, 'created_at DESC');

// Obtener categorías para el menú
$categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');

// Obtener banners activos
$banners = readRecords('banners', ['activo = 1'], null, 'orden ASC');

// Obtener testimonios
$testimonios = readRecords('testimonios', ['activo = 1'], 3, 'created_at DESC');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
                <img src="assets/logo/LOGO.png" alt="DT Studio" height="40">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
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
                        <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=24&h=24&fit=crop&crop=center" 
                             alt="Calculadora" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                        Solicitar Cotización
                    </a>
                    <a href="admin/" class="btn btn-outline-secondary">
                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                             alt="Configuración" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                        Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section text-white py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-3 fw-bold mb-4" style="color: white; font-family: var(--font-display);">
                            ¡Impulsa tu Marca con 
                            <span class="text-gradient">Productos Únicos</span>
                        </h1>
                        <p class="lead mb-4" style="color: rgba(255,255,255,0.9); font-size: 1.25rem;">
                            Transformamos tus ideas en productos promocionales extraordinarios. 
                            <strong>Diseño personalizado</strong>, entrega rápida y calidad premium garantizada.
                        </p>
                        <div class="hero-stats mb-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="fw-bold text-white mb-0">500+</h3>
                                        <small class="text-white-50">Clientes Satisfechos</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="fw-bold text-white mb-0">1000+</h3>
                                        <small class="text-white-50">Productos Entregados</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="fw-bold text-white mb-0">24h</h3>
                                        <small class="text-white-50">Tiempo de Respuesta</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="productos.php" class="btn btn-light btn-lg px-4 py-3">
                                <img src="https://images.unsplash.com/photo-1555529907-2a2a4b0b0b0b?w=24&h=24&fit=crop&crop=center" 
                                     alt="Bolsa de compras" class="me-2" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                                Explorar Productos
                            </a>
                            <a href="cotizacion.php" class="btn btn-outline-light btn-lg px-4 py-3">
                                <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=24&h=24&fit=crop&crop=center" 
                                     alt="Calculadora" class="me-2" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                                Solicitar Cotización
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual text-center position-relative">
                        <div class="floating-elements">
                            <div class="floating-icon" style="top: 10%; left: 10%; animation-delay: 0s;">
                                <img src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=48&h=48&fit=crop&crop=center" 
                                     alt="Gema" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
                            </div>
                            <div class="floating-icon" style="top: 20%; right: 15%; animation-delay: 1s;">
                                <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=48&h=48&fit=crop&crop=center" 
                                     alt="Estrella" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
                            </div>
                            <div class="floating-icon" style="bottom: 30%; left: 5%; animation-delay: 2s;">
                                <img src="https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=48&h=48&fit=crop&crop=center" 
                                     alt="Corazón" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
                            </div>
                            <div class="floating-icon" style="bottom: 10%; right: 10%; animation-delay: 3s;">
                                <img src="https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=48&h=48&fit=crop&crop=center" 
                                     alt="Trofeo" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
                            </div>
                        </div>
                        <div class="main-icon">
                            <img src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=200&h=200&fit=crop&crop=center" 
                                 alt="Gema principal" style="width: 200px; height: 200px; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Banners Section -->
    <?php if (!empty($banners)): ?>
    <section class="banners-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <?php foreach ($banners as $banner): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="<?php echo htmlspecialchars($banner['icono']); ?> fa-3x text-primary mb-3"></i>
                            <h5 class="card-title"><?php echo htmlspecialchars($banner['titulo']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($banner['descripcion']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Productos Destacados -->
    <section class="productos-section py-5 position-relative">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <div class="section-header">
                        <span class="section-badge">✨ Productos Premium</span>
                        <h2 class="section-title display-4 fw-bold mb-3" style="font-family: var(--font-display);">
                            Nuestros <span class="text-gradient">Favoritos</span>
                        </h2>
                        <p class="lead text-muted fs-5">Descubre nuestra selección de productos más populares y de mayor calidad</p>
                        <div class="section-divider mx-auto"></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($productos_destacados)): ?>
            <div class="row">
                <?php foreach ($productos_destacados as $producto): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm product-card">
                        <div class="product-image">
                            <?php if ($producto['imagen_principal']): ?>
                                <img src="uploads/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=120&h=120&fit=crop&crop=center" 
                                         alt="Imagen no disponible" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; opacity: 0.5;">
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary">
                                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=20&h=20&fit=crop&crop=center" 
                                         alt="Ver detalles" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 100)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0">$<?php echo number_format($producto['precio_venta'], 2); ?></span>
                                <small class="text-muted">SKU: <?php echo htmlspecialchars($producto['sku']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="productos.php" class="btn btn-outline-primary btn-lg">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                         alt="Ver productos" class="me-2" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                    Ver Todos los Productos
                </a>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=120&h=120&fit=crop&crop=center" 
                     alt="Sin productos" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; opacity: 0.5;" class="mb-3">
                <h5 class="text-muted">No hay productos destacados disponibles</h5>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Cómo Funciona -->
    <section class="como-funciona-section py-5 bg-white">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <div class="section-header">
                        <span class="section-badge">🚀 Proceso Simple</span>
                        <h2 class="section-title display-4 fw-bold mb-3" style="font-family: var(--font-display);">
                            ¿Cómo <span class="text-gradient">Funciona</span>?
                        </h2>
                        <p class="lead text-muted fs-5">Solo 3 pasos simples para obtener tus productos promocionales</p>
                        <div class="section-divider mx-auto"></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="step-image mb-4">
                            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=300&h=300&fit=crop&crop=center" 
                                 alt="Ordena tu producto" 
                                 class="img-fluid rounded-circle step-img">
                            <div class="step-number">1</div>
                        </div>
                        <h4 class="step-title mb-3">Ordena</h4>
                        <p class="step-description text-muted">
                            Solicita tu cotización personalizada con todos los detalles de tu producto promocional.
                        </p>
                        <div class="step-features">
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Cotización gratuita</span>
                            </div>
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Diseño personalizado</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="step-image mb-4">
                            <img src="https://images.unsplash.com/photo-1556742111-a301076d9d18?w=300&h=300&fit=crop&crop=center" 
                                 alt="Deposita el pago" 
                                 class="img-fluid rounded-circle step-img">
                            <div class="step-number">2</div>
                        </div>
                        <h4 class="step-title mb-3">Deposita</h4>
                        <p class="step-description text-muted">
                            Realiza tu pago de manera segura y confirma tu pedido para comenzar la producción.
                        </p>
                        <div class="step-features">
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Pago seguro</span>
                            </div>
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Confirmación inmediata</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="step-image mb-4">
                            <img src="https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=300&h=300&fit=crop&crop=center" 
                                 alt="Recoge tu producto" 
                                 class="img-fluid rounded-circle step-img">
                            <div class="step-number">3</div>
                        </div>
                        <h4 class="step-title mb-3">Recoge</h4>
                        <p class="step-description text-muted">
                            Recibe tus productos terminados con la calidad y puntualidad que nos caracteriza.
                        </p>
                        <div class="step-features">
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Entrega puntual</span>
                            </div>
                            <div class="feature-item">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                     alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                <span>Calidad garantizada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="process-timeline">
                        <div class="timeline-line"></div>
                        <div class="timeline-dots">
                            <div class="timeline-dot active"></div>
                            <div class="timeline-dot active"></div>
                            <div class="timeline-dot active"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonios -->
    <?php if (!empty($testimonios)): ?>
    <section class="testimonios-section py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Lo que Dicen Nuestros Clientes</h2>
                    <p class="lead text-muted">Testimonios reales de empresas que confían en nosotros</p>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($testimonios as $testimonio): ?>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <div class="stars mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=20&h=20&fit=crop&crop=center" 
                                         alt="Estrella" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%; margin-right: 2px;">
                                <?php endfor; ?>
                            </div>
                            <p class="card-text">"<?php echo htmlspecialchars($testimonio['testimonio']); ?>"</p>
                            <footer class="blockquote-footer">
                                <strong><?php echo htmlspecialchars($testimonio['nombre']); ?></strong>
                                <?php if ($testimonio['empresa']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($testimonio['empresa']); ?></small>
                                <?php endif; ?>
                            </footer>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section py-5 position-relative overflow-hidden">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="cta-content">
                        <span class="cta-badge mb-3 d-inline-block">
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                 alt="Cohete" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                            ¡Comienza Ahora!
                        </span>
                        <h2 class="display-5 fw-bold mb-4" style="color: white; font-family: var(--font-display);">
                            ¿Listo para <span class="text-gradient">Impresionar</span> a tus Clientes?
                        </h2>
                        <p class="lead mb-4" style="color: rgba(255,255,255,0.9); font-size: 1.2rem;">
                            Solicita una cotización personalizada y descubre cómo podemos ayudarte a 
                            <strong>destacar tu marca</strong> con productos únicos y de calidad premium.
                        </p>
                        <div class="cta-features mb-4">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="feature-item d-flex align-items-center">
                                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                             alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                        <span class="text-white-75">Cotización Gratuita</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="feature-item d-flex align-items-center">
                                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                             alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                        <span class="text-white-75">Diseño Personalizado</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="feature-item d-flex align-items-center">
                                        <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=20&h=20&fit=crop&crop=center" 
                                             alt="Check" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 50%;">
                                        <span class="text-white-75">Entrega Rápida</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="cta-actions">
                        <div class="d-flex flex-column gap-3">
                            <a href="cotizacion.php" class="btn btn-light btn-lg px-4 py-3">
                                <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=24&h=24&fit=crop&crop=center" 
                                     alt="Calculadora" class="me-2" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                                Solicitar Cotización
                            </a>
                            <a href="admin/" class="btn btn-outline-light btn-lg px-4 py-3">
                                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                                     alt="Configuración" class="me-2" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                                Panel de Administración
                            </a>
                        </div>
                        <div class="cta-stats mt-4">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-number text-white fw-bold">24h</div>
                                    <div class="stat-label text-white-50 small">Respuesta</div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number text-white fw-bold">100%</div>
                                    <div class="stat-label text-white-50 small">Satisfacción</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Background Elements -->
        <div class="cta-bg-elements">
            <div class="cta-circle cta-circle-1"></div>
            <div class="cta-circle cta-circle-2"></div>
            <div class="cta-circle cta-circle-3"></div>
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
                    <p class="text-light">Especialistas en productos promocionales de alta calidad. Transformamos tus ideas en realidad.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3">
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                                 alt="Facebook" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                        </a>
                        <a href="#" class="text-light me-3">
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                                 alt="Instagram" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                        </a>
                        <a href="#" class="text-light me-3">
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                                 alt="LinkedIn" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                        </a>
                        <a href="#" class="text-light">
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=24&h=24&fit=crop&crop=center" 
                                 alt="Twitter" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Productos</h6>
                    <ul class="list-unstyled">
                        <li><a href="productos.php" class="text-light text-decoration-none">Todos los Productos</a></li>
                        <?php foreach (array_slice($categorias, 0, 4) as $categoria): ?>
                            <li><a href="productos.php?categoria=<?php echo $categoria['id']; ?>" class="text-light text-decoration-none">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6 class="fw-bold mb-3">Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="galeria.php" class="text-light text-decoration-none">Galería</a></li>
                        <li><a href="contacto.php" class="text-light text-decoration-none">Contacto</a></li>
                        <li><a href="cotizacion.php" class="text-light text-decoration-none">Cotización</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="fw-bold mb-3">Contacto</h6>
                    <ul class="list-unstyled text-light">
                        <li class="d-flex align-items-center mb-2">
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=20&h=20&fit=crop&crop=center" 
                                 alt="Teléfono" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                            +1 (555) 123-4567
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=20&h=20&fit=crop&crop=center" 
                                 alt="Email" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                            info@drstudio.com
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=20&h=20&fit=crop&crop=center" 
                                 alt="Ubicación" class="me-2" style="width: 20px; height: 20px; object-fit: cover; border-radius: 4px;">
                            123 Main St, City, State 12345
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0">&copy; 2024 DT Studio. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="admin/" class="text-light text-decoration-none">Panel de Administración</a>
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
