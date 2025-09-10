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
                            <img src="/admin/uploads/galeria/<?php echo htmlspecialchars($imagen['imagen']); ?>" 
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
            imagePath = '/admin/uploads/galeria/' + imageSrc;
        } else {
            imagePath = '/admin/uploads/productos/' + imageSrc;
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
