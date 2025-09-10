<?php
// Configurar headers UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Galería - DT Studio';
$pageDescription = 'Galería de productos promocionales. Ve ejemplos de nuestro trabajo y la calidad de nuestros productos.';

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

// Incluir header compartido
require_once 'includes/public_header.php';
?>

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
                    <h1 class="display-4 fw-bold mb-4" style="color: var(--primary-color);">Galería de Productos</h1>
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

    <!-- Galería de Imágenes -->
    <?php if (!empty($galeria)): ?>
    <section class="gallery-images-section py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="display-6 fw-bold mb-3 text-center">
                        Galería de Imágenes
                        <span class="badge bg-primary ms-2"><?php echo count($galeria); ?></span>
                    </h2>
                </div>
            </div>
            
            <div class="row gallery-grid">
                <?php foreach ($galeria as $imagen): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm gallery-item">
                        <div class="gallery-image">
                            <img src="uploads/galeria/<?php echo htmlspecialchars($imagen['imagen']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                                 style="height: 200px; object-fit: cover;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#imageModal"
                                 onclick="openImageModal('<?php echo htmlspecialchars($imagen['imagen']); ?>', '<?php echo htmlspecialchars($imagen['titulo']); ?>', '<?php echo htmlspecialchars($imagen['descripcion']); ?>', 'galeria')">
                            <div class="gallery-overlay">
                                <button type="button" 
                                        class="btn btn-light btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#imageModal"
                                        onclick="openImageModal('<?php echo htmlspecialchars($imagen['imagen']); ?>', '<?php echo htmlspecialchars($imagen['titulo']); ?>', '<?php echo htmlspecialchars($imagen['descripcion']); ?>', 'galeria')">
                                    <i class="fas fa-search-plus me-1"></i>Ver
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($imagen['titulo']); ?></h6>
                            <?php if ($imagen['descripcion']): ?>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars(substr($imagen['descripcion'], 0, 80)); ?>
                                    <?php if (strlen($imagen['descripcion']) > 80): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($imagen['categoria']): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($imagen['categoria']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

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

<?php require_once 'includes/public_footer.php'; ?>
    
    <script>
    // Función para abrir modal de imagen
    function openImageModal(imageSrc, title, description, type = 'productos') {
        let imagePath = '';
        if (type === 'galeria') {
            imagePath = 'uploads/galeria/' + imageSrc;
        } else {
            imagePath = 'uploads/productos/' + imageSrc;
        }
        
        document.getElementById('imageModalImage').src = imagePath;
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
                text: 'Mira este producto de DT Studio',
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
