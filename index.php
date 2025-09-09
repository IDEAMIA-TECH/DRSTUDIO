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
                        <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                    </a>
                    <a href="admin/" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4" style="color: white;">Productos Promocionales de Calidad</h1>
                    <p class="lead mb-4" style="color: rgba(255,255,255,0.9);">Transformamos tus ideas en productos promocionales únicos. Cotizaciones personalizadas, entrega rápida y calidad garantizada.</p>
                    <div class="d-flex gap-3">
                        <a href="productos.php" class="btn btn-light btn-lg" style="color: var(--primary-color); font-weight: 600;">
                            <i class="fas fa-shopping-bag me-2"></i>Ver Productos
                        </a>
                        <a href="cotizacion.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <i class="fas fa-gem fa-10x opacity-75"></i>
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
    <section class="productos-section py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Productos Destacados</h2>
                    <p class="lead text-muted">Nuestros productos más populares y de mayor calidad</p>
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
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>Ver Detalles
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
                    <i class="fas fa-th me-2"></i>Ver Todos los Productos
                </a>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay productos destacados disponibles</h5>
            </div>
            <?php endif; ?>
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
                                    <i class="fas fa-star text-warning"></i>
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
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-3" style="color: var(--primary-color);">¿Listo para Impresionar a tus Clientes?</h2>
                    <p class="lead mb-0">Solicita una cotización personalizada y descubre cómo podemos ayudarte a destacar tu marca.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex flex-column gap-2">
                        <a href="cotizacion.php" class="btn btn-light btn-lg">
                            <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                        </a>
                        <a href="admin/" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-cog me-2"></i>Panel de Administración
                        </a>
                    </div>
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
