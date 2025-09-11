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

// Obtener imágenes de la galería agrupadas por categoría
$galeria = readRecords('galeria', ['activo' => 1], null, 'categoria ASC, orden ASC');

// Agrupar imágenes por categoría
$galeria_por_categoria = [];
foreach ($galeria as $imagen) {
    $categoria = $imagen['categoria'] ?: 'Sin categoría';
    if (!isset($galeria_por_categoria[$categoria])) {
        $galeria_por_categoria[$categoria] = [];
    }
    $galeria_por_categoria[$categoria][] = $imagen;
}

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





    <!-- Galería de Imágenes por Categoría -->
    <?php if (!empty($galeria_por_categoria)): ?>
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
            
            <?php foreach ($galeria_por_categoria as $categoria_nombre => $imagenes_categoria): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="h4 mb-4 text-center">
                        <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($categoria_nombre); ?>
                        <span class="badge bg-secondary ms-2"><?php echo count($imagenes_categoria); ?> imágenes</span>
                    </h3>
                    
                    <!-- Carrusel para esta categoría -->
                    <div id="carousel-<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $categoria_nombre); ?>" 
                         class="carousel slide" 
                         data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($imagenes_categoria as $index => $imagen): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8 col-md-10">
                                        <div class="card shadow-lg">
                                            <div class="gallery-image position-relative">
                                                <img src="/admin/uploads/galeria/<?php echo htmlspecialchars($imagen['imagen']); ?>" 
                                                     class="card-img-top" 
                                                     alt="<?php echo htmlspecialchars($imagen['titulo']); ?>"
                                                     style="height: 400px; object-fit: cover;"
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#imageModal"
                                                     onclick="openImageModal('<?php echo htmlspecialchars($imagen['imagen']); ?>', '<?php echo htmlspecialchars($imagen['titulo']); ?>', '<?php echo htmlspecialchars($imagen['descripcion']); ?>', 'galeria')">
                                                <div class="gallery-overlay">
                                                    <button type="button" 
                                                            class="btn btn-light btn-lg" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#imageModal"
                                                            onclick="openImageModal('<?php echo htmlspecialchars($imagen['imagen']); ?>', '<?php echo htmlspecialchars($imagen['titulo']); ?>', '<?php echo htmlspecialchars($imagen['descripcion']); ?>', 'galeria')">
                                                        <i class="fas fa-search-plus me-2"></i>Ver en Grande
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo htmlspecialchars($imagen['titulo']); ?></h5>
                                                <?php if ($imagen['descripcion']): ?>
                                                    <p class="card-text text-muted">
                                                        <?php echo htmlspecialchars($imagen['descripcion']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Controles del carrusel -->
                        <?php if (count($imagenes_categoria) > 1): ?>
                        <button class="carousel-control-prev" 
                                type="button" 
                                data-bs-target="#carousel-<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $categoria_nombre); ?>" 
                                data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" 
                                type="button" 
                                data-bs-target="#carousel-<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $categoria_nombre); ?>" 
                                data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                        
                        <!-- Indicadores del carrusel -->
                        <div class="carousel-indicators">
                            <?php foreach ($imagenes_categoria as $index => $imagen): ?>
                            <button type="button" 
                                    data-bs-target="#carousel-<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $categoria_nombre); ?>" 
                                    data-bs-slide-to="<?php echo $index; ?>" 
                                    class="<?php echo $index === 0 ? 'active' : ''; ?>" 
                                    aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                    aria-label="Imagen <?php echo $index + 1; ?>">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
    
    <style>
    /* Estilos personalizados para los carruseles */
    .gallery-image {
        position: relative;
        overflow: hidden;
        border-radius: 0.375rem;
    }
    
    .gallery-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 0.375rem;
    }
    
    .gallery-image:hover .gallery-overlay {
        opacity: 1;
    }
    
    .carousel-control-prev,
    .carousel-control-next {
        width: 5%;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 0.375rem;
        margin: 0 10px;
    }
    
    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background: rgba(0, 0, 0, 0.5);
    }
    
    .carousel-indicators {
        margin-bottom: -2rem;
    }
    
    .carousel-indicators button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin: 0 5px;
    }
    
    .carousel-item {
        transition: transform 0.6s ease-in-out;
    }
    
    .card {
        border: none;
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    </style>
    
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
    
    // Inicializar carruseles con configuración personalizada
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar todos los carruseles
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(function(carousel) {
            // Pausar carrusel al hacer hover
            carousel.addEventListener('mouseenter', function() {
                const bsCarousel = bootstrap.Carousel.getInstance(carousel);
                if (bsCarousel) {
                    bsCarousel.pause();
                }
            });
            
            // Reanudar carrusel al quitar hover
            carousel.addEventListener('mouseleave', function() {
                const bsCarousel = bootstrap.Carousel.getInstance(carousel);
                if (bsCarousel) {
                    bsCarousel.cycle();
                }
            });
            
            // Configurar intervalo de cambio automático (5 segundos)
            const bsCarousel = new bootstrap.Carousel(carousel, {
                interval: 5000,
                wrap: true
            });
        });
        
        // Agregar animación suave a los indicadores
        const indicators = document.querySelectorAll('.carousel-indicators button');
        indicators.forEach(function(indicator) {
            indicator.addEventListener('click', function() {
                // Remover clase active de todos los indicadores
                indicators.forEach(function(ind) {
                    ind.classList.remove('active');
                    ind.setAttribute('aria-current', 'false');
                });
                
                // Agregar clase active al indicador clickeado
                this.classList.add('active');
                this.setAttribute('aria-current', 'true');
            });
        });
    });
    </script>
</body>
</html>
