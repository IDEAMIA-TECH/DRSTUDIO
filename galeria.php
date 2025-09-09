<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Galería - DR Studio';
$pageDescription = 'Galería de productos promocionales. Ve ejemplos de nuestro trabajo y la calidad de nuestros productos.';

// Obtener categorías para el menú
$categorias = readRecords('categorias', ['activo' => 1], null, 'nombre ASC');

// Obtener filtros
$categoria_id = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir condiciones de búsqueda
$conditions = ['activo = 1'];
if ($categoria_id) {
    $conditions[] = "categoria_id = $categoria_id";
}
if ($busqueda) {
    $conditions[] = "(nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
}

// Obtener productos para la galería
$productos = readRecords('productos', $conditions, null, 'created_at DESC');

// Obtener imágenes de la galería
$galeria = readRecords('galeria', ['activo' => 1], null, 'orden ASC');
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
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-gem me-2"></i>DR Studio
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
                        <a class="nav-link active" href="galeria.php">Galería</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.php">Contacto</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="cotizacion.php" class="btn btn-primary">
                        <i class="fas fa-calculator me-2"></i>Solicitar Cotización
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
                <li class="breadcrumb-item active">Galería</li>
            </ol>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Galería de Productos</h1>
                    <p class="lead mb-4">Descubre la calidad y creatividad de nuestros productos promocionales. Cada proyecto es único.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-images fa-8x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtros -->
    <section class="filters-section py-4 bg-light">
        <div class="container">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="busqueda" class="form-label">Buscar</label>
                    <input type="text" 
                           class="form-control" 
                           id="busqueda" 
                           name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Nombre o descripción">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="galeria.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Galería de Productos -->
    <section class="gallery-section py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="display-6 fw-bold mb-3">
                        <?php if ($categoria_id): ?>
                            <?php 
                            $categoria_seleccionada = getRecord('categorias', $categoria_id);
                            echo htmlspecialchars($categoria_seleccionada['nombre']);
                            ?>
                        <?php else: ?>
                            Galería de Productos
                        <?php endif; ?>
                        <span class="badge bg-primary ms-2"><?php echo count($productos); ?></span>
                    </h2>
                </div>
            </div>
            
            <?php if (!empty($productos)): ?>
            <div class="row gallery-grid">
                <?php foreach ($productos as $producto): ?>
                <div class="col-lg-4 col-md-6 mb-4" data-category="<?php echo $producto['categoria_id']; ?>">
                    <div class="card h-100 shadow-sm gallery-item">
                        <div class="gallery-image">
                            <?php if ($producto['imagen_principal']): ?>
                                <img src="uploads/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imageModal"
                                     onclick="openImageModal('<?php echo htmlspecialchars($producto['imagen_principal']); ?>', '<?php echo htmlspecialchars($producto['nombre']); ?>', '<?php echo htmlspecialchars($producto['descripcion']); ?>')">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="gallery-overlay">
                                <button type="button" 
                                        class="btn btn-light btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#imageModal"
                                        onclick="openImageModal('<?php echo htmlspecialchars($producto['imagen_principal']); ?>', '<?php echo htmlspecialchars($producto['nombre']); ?>', '<?php echo htmlspecialchars($producto['descripcion']); ?>')">
                                    <i class="fas fa-search-plus me-1"></i>Ver
                                </button>
                            </div>
                            <?php if ($producto['destacado']): ?>
                                <div class="badge bg-warning position-absolute top-0 start-0 m-2">
                                    <i class="fas fa-star me-1"></i>Destacado
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 100)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 text-primary mb-0">$<?php echo number_format($producto['precio_venta'], 2); ?></span>
                                <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-info-circle me-1"></i>Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron productos</h5>
                <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                <a href="galeria.php" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Ver Toda la Galería
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal para ver imagen -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Imagen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imageModalImage" src="" class="img-fluid rounded" alt="">
                    <p id="imageModalDescription" class="mt-3 text-muted"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="shareImage()">
                        <i class="fas fa-share me-1"></i>Compartir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-3">¿Te Gustó lo que Viste?</h2>
                    <p class="lead mb-0">Solicita una cotización personalizada y crea tu propio producto promocional.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="cotizacion.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calculator me-2"></i>Solicitar Cotización
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
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-gem me-2"></i>DR Studio
                    </h5>
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
                    <p class="text-muted mb-0">&copy; 2024 DR Studio. Todos los derechos reservados.</p>
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
    
    <script>
    // Función para abrir modal de imagen
    function openImageModal(imageSrc, title, description) {
        document.getElementById('imageModalImage').src = 'uploads/productos/' + imageSrc;
        document.getElementById('imageModalTitle').textContent = title;
        document.getElementById('imageModalDescription').textContent = description;
    }
    
    // Función para compartir imagen
    function shareImage() {
        const imageSrc = document.getElementById('imageModalImage').src;
        const title = document.getElementById('imageModalTitle').textContent;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Mira este producto de DR Studio',
                url: window.location.href
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            copyToClipboard(window.location.href);
        }
    }
    </script>
</body>
</html>
