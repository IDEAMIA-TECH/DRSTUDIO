// Portal JavaScript - DT Studio Página Pública
// Funcionalidades de la página pública

// Variables globales
let currentSlide = 0;
let currentTestimonial = 0;
let currentProducts = [];
let filteredProducts = [];
let currentPage = 1;
const productsPerPage = 12;

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initializePortal();
    loadProducts();
    setupEventListeners();
    startBannerCarousel();
    startTestimonialCarousel();
});

// Inicializar el portal
function initializePortal() {
    console.log('Portal DT Studio inicializado');
    
    // Configurar navegación suave
    setupSmoothScrolling();
    
    // Configurar menú móvil
    setupMobileMenu();
    
    // Configurar formularios
    setupForms();
}

// Configurar navegación suave
function setupSmoothScrolling() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Configurar menú móvil
function setupMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }
}

// Configurar formularios
function setupForms() {
    // Formulario de contacto
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContact);
    }
    
    // Formulario de cotización
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', handleQuote);
    }
}

// Banner rotatorio
function startBannerCarousel() {
    setInterval(() => {
        changeSlide(1);
    }, 5000); // Cambiar slide cada 5 segundos
}

function changeSlide(direction) {
    const slides = document.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.dot');
    
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    
    currentSlide += direction;
    
    if (currentSlide >= slides.length) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function currentSlide(n) {
    const slides = document.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.dot');
    
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    
    currentSlide = n - 1;
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

// Testimonios carrusel
function startTestimonialCarousel() {
    setInterval(() => {
        changeTestimonial(1);
    }, 6000); // Cambiar testimonio cada 6 segundos
}

function changeTestimonial(direction) {
    const testimonials = document.querySelectorAll('.testimonial-slide');
    
    testimonials[currentTestimonial].classList.remove('active');
    
    currentTestimonial += direction;
    
    if (currentTestimonial >= testimonials.length) {
        currentTestimonial = 0;
    } else if (currentTestimonial < 0) {
        currentTestimonial = testimonials.length - 1;
    }
    
    testimonials[currentTestimonial].classList.add('active');
}

// Cargar productos desde la API
async function loadProducts() {
    try {
        const response = await fetch('api/public.php?action=get_products');
        const data = await response.json();
        
        if (data.success) {
            currentProducts = data.data;
            filteredProducts = [...currentProducts];
            displayProducts();
            displayFeaturedProducts();
        } else {
            console.error('Error al cargar productos:', data.message);
            displayMockProducts();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockProducts();
    }
}

// Mostrar productos mock si falla la API
function displayMockProducts() {
    const mockProducts = [
        {
            id: 1,
            name: 'Camisetas Personalizadas',
            description: 'Camisetas de algodón 100% con estampado personalizado',
            price: 150.00,
            image: 'https://via.placeholder.com/300x200/667eea/ffffff?text=Camisetas',
            category: 'textiles',
            material: 'algodon',
            featured: true
        },
        {
            id: 2,
            name: 'Tazas Promocionales',
            description: 'Tazas de cerámica con logo personalizado',
            price: 80.00,
            image: 'https://via.placeholder.com/300x200/764ba2/ffffff?text=Tazas',
            category: 'oficina',
            material: 'ceramica',
            featured: true
        },
        {
            id: 3,
            name: 'Bolsas Ecológicas',
            description: 'Bolsas de tela reutilizable con diseño personalizado',
            price: 120.00,
            image: 'https://via.placeholder.com/300x200/ff6b6b/ffffff?text=Bolsas',
            category: 'textiles',
            material: 'algodon',
            featured: false
        },
        {
            id: 4,
            name: 'Power Banks',
            description: 'Baterías portátiles con logo corporativo',
            price: 300.00,
            image: 'https://via.placeholder.com/300x200/feca57/ffffff?text=Power+Banks',
            category: 'tecnologia',
            material: 'plastico',
            featured: true
        },
        {
            id: 5,
            name: 'Gorras Deportivas',
            description: 'Gorras ajustables con bordado personalizado',
            price: 200.00,
            image: 'https://via.placeholder.com/300x200/48dbfb/ffffff?text=Gorras',
            category: 'deportes',
            material: 'poliéster',
            featured: false
        },
        {
            id: 6,
            name: 'USB Personalizados',
            description: 'Memorias USB con logo y diseño personalizado',
            price: 180.00,
            image: 'https://via.placeholder.com/300x200/ff9ff3/ffffff?text=USB',
            category: 'tecnologia',
            material: 'plastico',
            featured: false
        }
    ];
    
    currentProducts = mockProducts;
    filteredProducts = [...mockProducts];
    displayProducts();
    displayFeaturedProducts();
}

// Mostrar productos destacados
function displayFeaturedProducts() {
    const featuredGrid = document.getElementById('featured-products');
    if (!featuredGrid) return;
    
    const featuredProducts = currentProducts.filter(product => product.featured);
    
    featuredGrid.innerHTML = featuredProducts.map(product => `
        <div class="product-card">
            <img src="${product.image}" alt="${product.name}" class="product-image">
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-description">${product.description}</p>
                <div class="product-price">$${product.price.toFixed(2)}</div>
                <div class="product-actions">
                    <button class="btn-add-quote" onclick="addToQuote(${product.id})">
                        <i class="fas fa-plus"></i> Cotizar
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Mostrar productos en la grilla
function displayProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;
    
    const startIndex = (currentPage - 1) * productsPerPage;
    const endIndex = startIndex + productsPerPage;
    const productsToShow = filteredProducts.slice(startIndex, endIndex);
    
    productsGrid.innerHTML = productsToShow.map(product => `
        <div class="product-card">
            <img src="${product.image}" alt="${product.name}" class="product-image">
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-description">${product.description}</p>
                <div class="product-price">$${product.price.toFixed(2)}</div>
                <div class="product-actions">
                    <button class="btn-add-quote" onclick="addToQuote(${product.id})">
                        <i class="fas fa-plus"></i> Cotizar
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    updatePagination();
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros del catálogo
    const categoryFilter = document.getElementById('category-filter');
    const materialFilter = document.getElementById('material-filter');
    const priceFilter = document.getElementById('price-filter');
    const searchInput = document.getElementById('search-input');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterProducts);
    }
    
    if (materialFilter) {
        materialFilter.addEventListener('change', filterProducts);
    }
    
    if (priceFilter) {
        priceFilter.addEventListener('change', filterProducts);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
    }
}

// Filtrar productos
function filterProducts() {
    const categoryFilter = document.getElementById('category-filter');
    const materialFilter = document.getElementById('material-filter');
    const priceFilter = document.getElementById('price-filter');
    const searchInput = document.getElementById('search-input');
    
    const selectedCategory = categoryFilter ? categoryFilter.value : '';
    const selectedMaterial = materialFilter ? materialFilter.value : '';
    const selectedPrice = priceFilter ? priceFilter.value : '';
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    
    filteredProducts = currentProducts.filter(product => {
        const matchesCategory = !selectedCategory || product.category === selectedCategory;
        const matchesMaterial = !selectedMaterial || product.material === selectedMaterial;
        const matchesSearch = !searchTerm || product.name.toLowerCase().includes(searchTerm) || 
                            product.description.toLowerCase().includes(searchTerm);
        
        let matchesPrice = true;
        if (selectedPrice) {
            const [min, max] = selectedPrice.split('-').map(p => p === '+' ? Infinity : parseInt(p));
            matchesPrice = product.price >= min && product.price <= max;
        }
        
        return matchesCategory && matchesMaterial && matchesSearch && matchesPrice;
    });
    
    currentPage = 1;
    displayProducts();
}

// Paginación
function nextPage() {
    const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        displayProducts();
    }
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        displayProducts();
    }
}

function updatePagination() {
    const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
    const pageInfo = document.getElementById('page-info');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    
    if (pageInfo) {
        pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
    }
    
    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
    }
    
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages;
    }
}

// Agregar producto a cotización
function addToQuote(productId) {
    const product = currentProducts.find(p => p.id === productId);
    if (product) {
        showQuoteModal();
        // Aquí se podría agregar el producto al formulario de cotización
        console.log('Producto agregado a cotización:', product.name);
    }
}

// Mostrar modal de cotización
function showQuoteModal() {
    const modal = document.getElementById('quoteModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Manejar formulario de contacto
function handleContact(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Aquí se implementaría la lógica de envío de contacto
    console.log('Mensaje de contacto:', data);
    alert('Mensaje enviado exitosamente. Te responderemos pronto.');
    e.target.reset();
}

// Manejar formulario de cotización
function handleQuote(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Aquí se implementaría la lógica de envío de cotización
    console.log('Cotización enviada:', data);
    alert('Cotización enviada exitosamente. Te contactaremos pronto.');
    closeModal('quoteModal');
    e.target.reset();
}

// Ver proyecto de galería
function viewProject(projectId) {
    console.log('Ver proyecto:', projectId);
    // Aquí se implementaría la lógica para mostrar detalles del proyecto
    alert('Funcionalidad de detalles de proyecto próximamente disponible');
}

// Scroll a sección específica
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Cerrar modales al hacer clic fuera
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Efectos de scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.style.background = 'rgba(255, 255, 255, 0.98)';
    } else {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
    }
});

// Lazy loading de imágenes
function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Inicializar lazy loading
document.addEventListener('DOMContentLoaded', setupLazyLoading);