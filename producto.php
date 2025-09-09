<?php
// Configurar headers UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Obtener ID del producto
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: productos.php');
    exit;
}

// Obtener datos del producto
$producto = getRecord('productos', $id);
if (!$producto || !$producto['activo']) {
    header('Location: productos.php');
    exit;
}

// Obtener categoría del producto
$categoria = getRecord('categorias', $producto['categoria_id']);

// Obtener variantes del producto
$variantes = readRecords('variantes_producto', ["producto_id = $id", "activo = 1"], null, 'id ASC');

// Obtener categorías para el menú
$categorias = readRecords('categorias', ['activo = 1'], null, 'nombre ASC');

$pageTitle = htmlspecialchars($producto['nombre']) . ' - DT Studio';
$pageDescription = htmlspecialchars(substr($producto['descripcion'], 0, 160));
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
                <img src="assets/logo/LOGO.png" alt="DT Studio" height="40">
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
                            <?php foreach ($categorias as $cat): ?>
                                <li><a class="dropdown-item" href="productos.php?categoria=<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
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
                <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
                <?php if ($categoria): ?>
                    <li class="breadcrumb-item"><a href="productos.php?categoria=<?php echo $categoria['id']; ?>">
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($producto['nombre']); ?></li>
            </ol>
        </div>
    </nav>

    <!-- Producto Detalle -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Imagen del Producto -->
                <div class="col-lg-6 mb-4">
                    <div class="product-image-container">
                        <?php if ($producto['imagen_principal']): ?>
                            <img src="uploads/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                 class="img-fluid rounded shadow" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 id="mainImage">
                        <?php else: ?>
                            <div class="no-image-placeholder d-flex align-items-center justify-content-center bg-light rounded shadow" style="height: 400px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-4x mb-3"></i>
                                    <p>Imagen no disponible</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información del Producto -->
                <div class="col-lg-6">
                    <div class="product-info">
                        <h1 class="h2 mb-3"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                        
                        <?php if ($categoria): ?>
                            <p class="text-muted mb-3">
                                <i class="fas fa-tag me-2"></i>
                                <a href="productos.php?categoria=<?php echo $categoria['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <div class="price-section mb-4">
                            <span class="h3 text-primary">$<?php echo number_format($producto['precio_venta'], 2); ?></span>
                            <small class="text-muted ms-2">SKU: <?php echo htmlspecialchars($producto['sku']); ?></small>
                        </div>

                        <div class="description mb-4">
                            <h5>Descripción</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                        </div>

                        <?php if (!empty($variantes)): ?>
                        <div class="variants mb-4">
                            <h5>Variantes Disponibles</h5>
                            <div class="row">
                                <?php foreach ($variantes as $variante): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-2"><?php echo htmlspecialchars($variante['nombre']); ?></h6>
                                            <div class="variant-details">
                                                <?php if ($variante['talla']): ?>
                                                    <small class="text-muted d-block">Talla: <?php echo htmlspecialchars($variante['talla']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($variante['color']): ?>
                                                    <small class="text-muted d-block">Color: <?php echo htmlspecialchars($variante['color']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($variante['material']): ?>
                                                    <small class="text-muted d-block">Material: <?php echo htmlspecialchars($variante['material']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($variante['precio_extra'] > 0): ?>
                                                    <small class="text-success d-block">+$<?php echo number_format($variante['precio_extra'], 2); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="product-actions">
                            <a href="cotizacion.php?producto_id=<?php echo $producto['id']; ?>" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-calculator me-2"></i>Solicitar Cotización
                            </a>
                            <a href="productos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Productos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Especificaciones Técnicas -->
    <?php if ($producto['especificaciones']): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h3 class="mb-4">Especificaciones Técnicas</h3>
                    <div class="specifications">
                        <?php echo nl2br(htmlspecialchars($producto['especificaciones'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
                        <li><i class="fas fa-envelope me-2"></i>info@dtstudio.com</li>
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
