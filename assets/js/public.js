// DR Studio - Public Site JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar animaciones
    initAnimations();
    
    // Inicializar formularios
    initForms();
    
    // Inicializar galería de productos
    initProductGallery();
    
    // Inicializar formulario de contacto
    initContactForm();
    
    // Inicializar formulario de cotización
    initQuoteForm();
});

// Animaciones al hacer scroll
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observar elementos para animación
    document.querySelectorAll('.card, .hero-section, .productos-section, .testimonios-section').forEach(el => {
        observer.observe(el);
    });
}

// Inicializar formularios
function initForms() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Inicializar galería de productos
function initProductGallery() {
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// Inicializar formulario de contacto
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitContactForm(this);
        });
    }
}

// Enviar formulario de contacto
function submitContactForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Mostrar loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    submitBtn.disabled = true;
    
    // Simular envío (aquí iría la llamada AJAX real)
    setTimeout(() => {
        showAlert('Mensaje enviado exitosamente. Te contactaremos pronto.', 'success');
        form.reset();
        form.classList.remove('was-validated');
        
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 2000);
}

// Inicializar formulario de cotización
function initQuoteForm() {
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitQuoteForm(this);
        });
    }
}

// Enviar formulario de cotización
function submitQuoteForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Mostrar loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    submitBtn.disabled = true;
    
    // Simular envío (aquí iría la llamada AJAX real)
    setTimeout(() => {
        showAlert('Cotización solicitada exitosamente. Te contactaremos en 24 horas.', 'success');
        form.reset();
        form.classList.remove('was-validated');
        
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 2000);
}

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-dismiss después de 5 segundos
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Crear contenedor de alertas si no existe
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Obtener icono según tipo de alerta
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Función para hacer peticiones AJAX
function ajaxRequest(url, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    callback({ success: false, message: 'Error al procesar la respuesta' });
                }
            } else {
                callback({ success: false, message: 'Error de conexión' });
            }
        }
    };
    
    // Convertir datos a formato URL-encoded
    const formData = new URLSearchParams();
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    xhr.send(formData);
}

// Función para validar email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Función para validar teléfono
function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Función para formatear número de teléfono
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 10) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    }
    input.value = value;
}

// Función para formatear moneda
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    if (value) {
        value = (parseInt(value) / 100).toFixed(2);
        input.value = '$' + value;
    }
}

// Función para mostrar/ocultar loading
function toggleLoading(element, show = true) {
    if (show) {
        element.classList.add('loading');
        element.style.pointerEvents = 'none';
    } else {
        element.classList.remove('loading');
        element.style.pointerEvents = 'auto';
    }
}

// Función para confirmar eliminación
function confirmDelete(message = '¿Estás seguro de eliminar este elemento?') {
    return confirm(message);
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('Copiado al portapapeles', 'success');
    }, function() {
        showAlert('Error al copiar', 'danger');
    });
}

// Función para compartir en redes sociales
function shareOnSocial(platform, url, text) {
    const shareUrls = {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
        whatsapp: `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`
    };
    
    if (shareUrls[platform]) {
        window.open(shareUrls[platform], '_blank', 'width=600,height=400');
    }
}

// Función para imprimir página
function printPage() {
    window.print();
}

// Función para ir arriba
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Botón de ir arriba
function createBackToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '<i class="fas fa-arrow-up"></i>';
    button.className = 'btn btn-primary position-fixed';
    button.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px; display: none;';
    button.onclick = scrollToTop;
    
    document.body.appendChild(button);
    
    // Mostrar/ocultar según scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            button.style.display = 'block';
        } else {
            button.style.display = 'none';
        }
    });
}

// Inicializar botón de ir arriba
createBackToTopButton();

// Función para filtrar productos
function filterProducts(category, search) {
    const products = document.querySelectorAll('.product-card');
    const categoryFilter = category || 'all';
    const searchTerm = (search || '').toLowerCase();
    
    products.forEach(product => {
        const productCategory = product.dataset.category || 'all';
        const productName = product.querySelector('.card-title').textContent.toLowerCase();
        const productDescription = product.querySelector('.card-text').textContent.toLowerCase();
        
        const matchesCategory = categoryFilter === 'all' || productCategory === categoryFilter;
        const matchesSearch = !searchTerm || 
            productName.includes(searchTerm) || 
            productDescription.includes(searchTerm);
        
        if (matchesCategory && matchesSearch) {
            product.style.display = 'block';
            product.classList.add('fade-in');
        } else {
            product.style.display = 'none';
        }
    });
}

// Función para ordenar productos
function sortProducts(sortBy) {
    const container = document.querySelector('.products-container');
    const products = Array.from(container.querySelectorAll('.product-card'));
    
    products.sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return a.querySelector('.card-title').textContent.localeCompare(b.querySelector('.card-title').textContent);
            case 'price-low':
                return parseFloat(a.querySelector('.price').textContent.replace('$', '')) - 
                       parseFloat(b.querySelector('.price').textContent.replace('$', ''));
            case 'price-high':
                return parseFloat(b.querySelector('.price').textContent.replace('$', '')) - 
                       parseFloat(a.querySelector('.price').textContent.replace('$', ''));
            case 'newest':
                return new Date(b.dataset.date) - new Date(a.dataset.date);
            default:
                return 0;
        }
    });
    
    // Reorganizar elementos
    products.forEach(product => container.appendChild(product));
}

// Función para agregar producto a favoritos
function toggleFavorite(productId) {
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    const index = favorites.indexOf(productId);
    
    if (index > -1) {
        favorites.splice(index, 1);
        showAlert('Producto removido de favoritos', 'info');
    } else {
        favorites.push(productId);
        showAlert('Producto agregado a favoritos', 'success');
    }
    
    localStorage.setItem('favorites', JSON.stringify(favorites));
    updateFavoriteButton(productId);
}

// Actualizar botón de favoritos
function updateFavoriteButton(productId) {
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    const isFavorite = favorites.includes(productId);
    
    if (button) {
        const icon = button.querySelector('i');
        if (isFavorite) {
            icon.className = 'fas fa-heart text-danger';
        } else {
            icon.className = 'far fa-heart';
        }
    }
}

// Inicializar favoritos
function initFavorites() {
    const favoriteButtons = document.querySelectorAll('[data-product-id]');
    favoriteButtons.forEach(button => {
        const productId = button.dataset.productId;
        updateFavoriteButton(productId);
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            toggleFavorite(productId);
        });
    });
}

// Inicializar favoritos al cargar la página
initFavorites();
