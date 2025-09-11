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
            
            <!-- Grid de categorías con una imagen representativa cada una -->
            <div class="row gallery-grid">
                <?php foreach ($galeria_por_categoria as $categoria_nombre => $imagenes_categoria): ?>
                <?php $imagen_representativa = $imagenes_categoria[0]; // Primera imagen de la categoría ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm gallery-item">
                        <div class="gallery-image">
                            <img src="/admin/uploads/galeria/<?php echo htmlspecialchars($imagen_representativa['imagen']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($categoria_nombre); ?>"
                                 style="height: 250px; object-fit: cover;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#imageModal"
                                 onclick="openImageModal('<?php echo htmlspecialchars($imagen_representativa['imagen']); ?>', '<?php echo htmlspecialchars($imagen_representativa['titulo']); ?>', '<?php echo htmlspecialchars($imagen_representativa['descripcion']); ?>', 'galeria', '<?php echo htmlspecialchars($categoria_nombre); ?>')">
                            <div class="gallery-overlay">
                                <button type="button" 
                                        class="btn btn-light btn-lg" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#imageModal"
                                        onclick="openImageModal('<?php echo htmlspecialchars($imagen_representativa['imagen']); ?>', '<?php echo htmlspecialchars($imagen_representativa['titulo']); ?>', '<?php echo htmlspecialchars($imagen_representativa['descripcion']); ?>', 'galeria', '<?php echo htmlspecialchars($categoria_nombre); ?>')">
                                    <i class="fas fa-images me-2"></i>Ver Galería
                                </button>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($categoria_nombre); ?>
                            </h5>
                            <p class="card-text text-muted">
                                <span class="badge bg-primary"><?php echo count($imagenes_categoria); ?> imágenes</span>
                            </p>
                            <p class="card-text small text-muted">
                                Haz clic para ver todas las imágenes de esta categoría
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Modal para ver imagen con carrusel -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Galería de Imágenes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Carrusel dentro del modal -->
                    <div id="modalCarousel" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner" id="modalCarouselInner">
                            <!-- Las imágenes se cargarán dinámicamente aquí -->
                        </div>
                        
                        <!-- Controles del carrusel -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                        
                        <!-- Indicadores del carrusel -->
                        <div class="carousel-indicators" id="modalCarouselIndicators">
                            <!-- Los indicadores se cargarán dinámicamente aquí -->
                        </div>
                    </div>
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
    
    /* Estilos específicos para las tarjetas de categoría */
    .gallery-item .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--bs-primary);
    }
    
    .gallery-item .gallery-overlay button {
        font-size: 1rem;
        padding: 0.75rem 1.5rem;
        border-radius: 2rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .gallery-item .gallery-overlay button:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }
    
    .gallery-item .badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    </style>
    
    <script>
    // Datos de la galería para JavaScript
    const galeriaData = <?php echo json_encode($galeria_por_categoria); ?>;
    
    // Función para abrir modal de imagen con carrusel
    function openImageModal(imageSrc, title, description, type = 'productos', categoria = '') {
        if (type === 'galeria' && categoria && galeriaData[categoria]) {
            // Crear carrusel con todas las imágenes de la categoría
            createModalCarousel(galeriaData[categoria], imageSrc);
            document.getElementById('imageModalTitle').textContent = categoria;
        } else {
            // Mostrar imagen individual
            showSingleImage(imageSrc, title, description, type);
        }
    }
    
    // Función para crear carrusel en el modal
    function createModalCarousel(imagenes, currentImageSrc) {
        const carouselInner = document.getElementById('modalCarouselInner');
        const carouselIndicators = document.getElementById('modalCarouselIndicators');
        
        // Limpiar contenido anterior
        carouselInner.innerHTML = '';
        carouselIndicators.innerHTML = '';
        
        // Encontrar el índice de la imagen actual
        let currentIndex = 0;
        imagenes.forEach((imagen, index) => {
            if (imagen.imagen === currentImageSrc) {
                currentIndex = index;
            }
        });
        
        // Crear slides del carrusel
        imagenes.forEach((imagen, index) => {
            const isActive = index === currentIndex ? 'active' : '';
            
            const slide = document.createElement('div');
            slide.className = `carousel-item ${isActive}`;
            slide.innerHTML = `
                <div class="text-center">
                    <img src="/admin/uploads/galeria/${imagen.imagen}" 
                         class="img-fluid rounded" 
                         alt="${imagen.titulo}"
                         style="max-height: 70vh; object-fit: contain;">
                    <div class="mt-3">
                        <h5>${imagen.titulo}</h5>
                        ${imagen.descripcion ? `<p class="text-muted">${imagen.descripcion}</p>` : ''}
                    </div>
                </div>
            `;
            carouselInner.appendChild(slide);
            
            // Crear indicador
            const indicator = document.createElement('button');
            indicator.type = 'button';
            indicator.setAttribute('data-bs-target', '#modalCarousel');
            indicator.setAttribute('data-bs-slide-to', index);
            indicator.className = isActive ? 'active' : '';
            indicator.setAttribute('aria-current', isActive ? 'true' : 'false');
            indicator.setAttribute('aria-label', `Imagen ${index + 1}`);
            carouselIndicators.appendChild(indicator);
        });
        
        // Reinicializar el carrusel
        const carousel = new bootstrap.Carousel(document.getElementById('modalCarousel'), {
            interval: false, // Desactivar auto-play
            wrap: true,
            touch: true, // Habilitar navegación táctil
            keyboard: true // Habilitar navegación por teclado
        });
        
        // Ir a la imagen actual
        carousel.to(currentIndex);
    }
    
    // Función para mostrar imagen individual
    function showSingleImage(imageSrc, title, description, type) {
        let imagePath = '';
        if (type === 'galeria') {
            imagePath = '/admin/uploads/galeria/' + imageSrc;
        } else {
            imagePath = '/admin/uploads/productos/' + imageSrc;
        }
        
        const carouselInner = document.getElementById('modalCarouselInner');
        carouselInner.innerHTML = `
            <div class="carousel-item active">
                <div class="text-center">
                    <img src="${imagePath}" 
                         class="img-fluid rounded" 
                         alt="${title}"
                         style="max-height: 70vh; object-fit: contain;">
                    <div class="mt-3">
                        <h5>${title}</h5>
                        ${description ? `<p class="text-muted">${description}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('imageModalTitle').textContent = title;
    }
    
    // Función para compartir imagen
    function shareImage() {
        const activeSlide = document.querySelector('#modalCarousel .carousel-item.active img');
        const imageSrc = activeSlide ? activeSlide.src : '';
        const title = document.getElementById('imageModalTitle').textContent;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Mira este producto de DT Studio',
                url: window.location.href
            });
        } else {
            // Fallback para navegadores que no soportan Web Share API
            if (imageSrc) {
                copyToClipboard(imageSrc);
            } else {
                copyToClipboard(window.location.href);
            }
        }
    }
    
    // Función auxiliar para copiar al portapapeles
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Enlace copiado al portapapeles');
        }, function(err) {
            console.error('Error al copiar: ', err);
        });
    }
    
    // Inicializar funcionalidades
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Galería cargada correctamente');
        console.log('Datos de galería:', galeriaData);
    });
    </script>
</body>
</html>
