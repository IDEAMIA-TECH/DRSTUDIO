// Admin JavaScript - DT Studio Panel de Administración
// Funcionalidades del panel administrativo con APIs reales

// Variables globales
let currentSection = 'dashboard';
let charts = {};
let currentData = {
    products: [],
    customers: [],
    quotations: [],
    orders: []
};

// Variable para evitar múltiples inicializaciones
let isInitialized = false;

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    if (isInitialized) return;
    isInitialized = true;
    
    console.log('Inicializando panel de administración...');
    
    // Verificar sesión de administrador primero
    if (typeof checkAdminSession === 'function') {
        try {
            checkAdminSession();
        } catch (error) {
            console.error('Error verificando sesión:', error);
        }
    }
    
    initializeAdmin();
    loadDashboardData();
    setupEventListeners();
});

// Inicializar el panel de administración
function initializeAdmin() {
    console.log('Panel de Administración DT Studio inicializado');
    
    // Configurar gráficos
    setupCharts();
    
    // Cargar datos iniciales
    loadInitialData();
}

// Configurar event listeners
function setupEventListeners() {
    // Formularios
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', handleProductSubmit);
    }
    
    const customerForm = document.getElementById('customerForm');
    if (customerForm) {
        customerForm.addEventListener('submit', handleCustomerSubmit);
    }
    
    // Búsqueda
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }
}

// Mostrar sección específica
function showSection(sectionId) {
    // Ocultar todas las secciones
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Mostrar la sección seleccionada
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Actualizar navegación
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    const activeNavItem = document.querySelector(`[onclick="showSection('${sectionId}')"]`).parentElement;
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    currentSection = sectionId;
    
    // Cargar datos específicos de la sección
    loadSectionData(sectionId);
}

// Cargar datos específicos de la sección
function loadSectionData(sectionId) {
    switch (sectionId) {
        case 'products':
            loadProducts();
            break;
        case 'customers':
            loadCustomers();
            break;
        case 'quotations':
            loadQuotations();
            break;
        case 'orders':
            loadOrders();
            break;
        case 'dashboard':
            loadDashboardData();
            break;
    }
}

// Cargar datos iniciales
function loadInitialData() {
    loadDashboardData();
}

// Cargar datos del dashboard
async function loadDashboardData() {
    try {
        console.log('Cargando datos del dashboard...');
        
        const response = await fetch('api/dashboard.php?action=get_stats');
        if (!response.ok) {
            console.error('Error HTTP:', response.status, response.statusText);
            return;
        }
        
        const data = await response.json();
        console.log('Datos del dashboard recibidos:', data);
        
        if (data.success) {
            updateDashboardStats(data.data);
        } else {
            console.error('Error al cargar estadísticas:', data.message);
        }
        
        // Cargar actividad reciente
        console.log('Cargando actividad reciente...');
        const activityResponse = await fetch('api/dashboard.php?action=get_recent_activity');
        if (activityResponse.ok) {
            const activityData = await activityResponse.json();
            if (activityData.success) {
                updateRecentActivity(activityData.data);
            }
        }
        
        // Cargar gráfico de ventas
        loadSalesChart();
        
    } catch (error) {
        console.error('Error de conexión en dashboard:', error);
    }
}

// Actualizar estadísticas del dashboard
function updateDashboardStats(stats) {
    // Actualizar contadores
    updateCounter('total-products', stats.total_products);
    updateCounter('total-customers', stats.total_customers);
    updateCounter('total-quotations', stats.total_quotations);
    updateCounter('total-orders', stats.total_orders);
    updateCounter('monthly-sales', stats.monthly_sales, true);
    updateCounter('sales-growth', stats.sales_growth, true, '%');
    
    // Actualizar productos más vendidos
    updateTopProducts(stats.top_products);
    
    // Actualizar clientes más activos
    updateTopCustomers(stats.top_customers);
}

// Actualizar contador
function updateCounter(elementId, value, isCurrency = false, suffix = '') {
    const element = document.getElementById(elementId);
    if (element) {
        if (isCurrency) {
            element.textContent = '$' + value.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        } else {
            element.textContent = value.toLocaleString('es-MX') + suffix;
        }
    }
}

// Actualizar productos más vendidos
function updateTopProducts(products) {
    const container = document.getElementById('top-products');
    if (!container) return;
    
    container.innerHTML = products.map((product, index) => `
        <div class="top-item">
            <span class="rank">${index + 1}</span>
            <div class="item-info">
                <h4>${product.name}</h4>
                <p>${product.total_sold} vendidos</p>
            </div>
        </div>
    `).join('');
}

// Actualizar clientes más activos
function updateTopCustomers(customers) {
    const container = document.getElementById('top-customers');
    if (!container) return;
    
    container.innerHTML = customers.map((customer, index) => `
        <div class="top-item">
            <span class="rank">${index + 1}</span>
            <div class="item-info">
                <h4>${customer.name}</h4>
                <p>${customer.company || 'Sin empresa'}</p>
                <small>$${customer.total_spent.toLocaleString('es-MX')} gastado</small>
            </div>
        </div>
    `).join('');
}

// Actualizar actividad reciente
function updateRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    if (!container) return;
    
    container.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon ${activity.type}">
                <i class="fas fa-${activity.type === 'quotation' ? 'file-invoice' : 'shopping-cart'}"></i>
            </div>
            <div class="activity-content">
                <h4>${activity.title}</h4>
                <p>${activity.description}</p>
                <span class="activity-date">${formatDate(activity.date)}</span>
            </div>
            <div class="activity-amount">
                $${activity.amount.toLocaleString('es-MX', { minimumFractionDigits: 2 })}
            </div>
        </div>
    `).join('');
}

// Cargar productos
async function loadProducts() {
    try {
        const response = await fetch('api/products.php?action=get_products');
        const data = await response.json();
        
        if (data.success) {
            currentData.products = data.data.products;
            displayProducts(data.data.products);
            updatePagination('products', data.data.pagination);
        } else {
            console.error('Error al cargar productos:', data.message);
            showError('Error al cargar productos: ' + data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showError('Error de conexión al cargar productos');
    }
}

// Mostrar productos
function displayProducts(products) {
    const container = document.getElementById('products-list');
    if (!container) return;
    
    container.innerHTML = products.map(product => `
        <div class="data-item">
            <div class="item-image">
                <img src="${product.images[0] || 'https://via.placeholder.com/100x100'}" alt="${product.name}">
            </div>
            <div class="item-info">
                <h3>${product.name}</h3>
                <p>${product.description}</p>
                <div class="item-meta">
                    <span class="category">${product.category}</span>
                    <span class="material">${product.material}</span>
                    <span class="featured ${product.featured ? 'yes' : 'no'}">
                        ${product.featured ? 'Destacado' : 'Normal'}
                    </span>
                </div>
            </div>
            <div class="item-actions">
                <span class="price">$${product.price.toLocaleString('es-MX', { minimumFractionDigits: 2 })}</span>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="editProduct(${product.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" onclick="deleteProduct(${product.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Cargar clientes
async function loadCustomers() {
    try {
        const response = await fetch('api/customers.php?action=get_customers');
        const data = await response.json();
        
        if (data.success) {
            currentData.customers = data.data.customers;
            displayCustomers(data.data.customers);
            updatePagination('customers', data.data.pagination);
        } else {
            console.error('Error al cargar clientes:', data.message);
            showError('Error al cargar clientes: ' + data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showError('Error de conexión al cargar clientes');
    }
}

// Mostrar clientes
function displayCustomers(customers) {
    const container = document.getElementById('customers-list');
    if (!container) return;
    
    container.innerHTML = customers.map(customer => `
        <div class="data-item">
            <div class="item-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="item-info">
                <h3>${customer.name}</h3>
                <p>${customer.email}</p>
                <div class="item-meta">
                    <span class="company">${customer.company || 'Sin empresa'}</span>
                    <span class="phone">${customer.phone || 'Sin teléfono'}</span>
                </div>
            </div>
            <div class="item-stats">
                <div class="stat">
                    <span class="number">${customer.total_quotations}</span>
                    <span class="label">Cotizaciones</span>
                </div>
                <div class="stat">
                    <span class="number">${customer.total_orders}</span>
                    <span class="label">Pedidos</span>
                </div>
            </div>
            <div class="item-actions">
                <button class="btn-edit" onclick="editCustomer(${customer.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-delete" onclick="deleteCustomer(${customer.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Cargar cotizaciones
async function loadQuotations() {
    try {
        const response = await fetch('api/quotations.php?action=get_quotations');
        const data = await response.json();
        
        if (data.success) {
            currentData.quotations = data.data.quotations;
            displayQuotations(data.data.quotations);
            updatePagination('quotations', data.data.pagination);
        } else {
            console.error('Error al cargar cotizaciones:', data.message);
            showError('Error al cargar cotizaciones: ' + data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showError('Error de conexión al cargar cotizaciones');
    }
}

// Mostrar cotizaciones
function displayQuotations(quotations) {
    const container = document.getElementById('quotations-list');
    if (!container) return;
    
    container.innerHTML = quotations.map(quotation => `
        <div class="data-item">
            <div class="item-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="item-info">
                <h3>${quotation.quotation_number}</h3>
                <p>${quotation.customer_name}</p>
                <div class="item-meta">
                    <span class="status ${quotation.status}">${getStatusText(quotation.status)}</span>
                    <span class="date">${formatDate(quotation.created_at)}</span>
                </div>
            </div>
            <div class="item-amount">
                $${quotation.total_amount.toLocaleString('es-MX', { minimumFractionDigits: 2 })}
            </div>
            <div class="item-actions">
                <button class="btn-view" onclick="viewQuotation(${quotation.id})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-edit" onclick="editQuotation(${quotation.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-delete" onclick="deleteQuotation(${quotation.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Cargar pedidos
async function loadOrders() {
    try {
        const response = await fetch('api/orders.php?action=get_orders');
        const data = await response.json();
        
        if (data.success) {
            currentData.orders = data.data.orders;
            displayOrders(data.data.orders);
            updatePagination('orders', data.data.pagination);
        } else {
            console.error('Error al cargar pedidos:', data.message);
            showError('Error al cargar pedidos: ' + data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        showError('Error de conexión al cargar pedidos');
    }
}

// Mostrar pedidos
function displayOrders(orders) {
    const container = document.getElementById('orders-list');
    if (!container) return;
    
    container.innerHTML = orders.map(order => `
        <div class="data-item">
            <div class="item-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="item-info">
                <h3>${order.order_number}</h3>
                <p>${order.customer_name}</p>
                <div class="item-meta">
                    <span class="status ${order.status}">${getStatusText(order.status)}</span>
                    <span class="date">${formatDate(order.created_at)}</span>
                </div>
            </div>
            <div class="item-amount">
                $${order.total_amount.toLocaleString('es-MX', { minimumFractionDigits: 2 })}
            </div>
            <div class="item-actions">
                <button class="btn-view" onclick="viewOrder(${order.id})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-edit" onclick="editOrder(${order.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-delete" onclick="deleteOrder(${order.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Funciones de formularios
async function handleProductSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('api/products.php?action=create_product', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Producto creado exitosamente');
            e.target.reset();
            loadProducts();
        } else {
            showError('Error al crear producto: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión al crear producto');
    }
}

async function handleCustomerSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('api/customers.php?action=create_customer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Cliente creado exitosamente');
            e.target.reset();
            loadCustomers();
        } else {
            showError('Error al crear cliente: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error de conexión al crear cliente');
    }
}

// Funciones de edición
function editProduct(id) {
    const product = currentData.products.find(p => p.id == id);
    if (product) {
        // Llenar formulario de edición
        document.getElementById('edit-product-name').value = product.name;
        document.getElementById('edit-product-description').value = product.description;
        document.getElementById('edit-product-price').value = product.price;
        document.getElementById('edit-product-category').value = product.category;
        document.getElementById('edit-product-material').value = product.material;
        document.getElementById('edit-product-featured').checked = product.featured;
        
        // Mostrar modal de edición
        showModal('editProductModal');
    }
}

function editCustomer(id) {
    const customer = currentData.customers.find(c => c.id == id);
    if (customer) {
        // Llenar formulario de edición
        document.getElementById('edit-customer-name').value = customer.name;
        document.getElementById('edit-customer-email').value = customer.email;
        document.getElementById('edit-customer-phone').value = customer.phone;
        document.getElementById('edit-customer-company').value = customer.company;
        document.getElementById('edit-customer-address').value = customer.address;
        
        // Mostrar modal de edición
        showModal('editCustomerModal');
    }
}

// Funciones de eliminación
async function deleteProduct(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        try {
            const response = await fetch(`api/products.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('Producto eliminado exitosamente');
                loadProducts();
            } else {
                showError('Error al eliminar producto: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión al eliminar producto');
        }
    }
}

async function deleteCustomer(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        try {
            const response = await fetch(`api/customers.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('Cliente eliminado exitosamente');
                loadCustomers();
            } else {
                showError('Error al eliminar cliente: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión al eliminar cliente');
        }
    }
}

// Funciones de utilidad
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'approved': 'Aprobado',
        'rejected': 'Rechazado',
        'completed': 'Completado',
        'shipped': 'Enviado',
        'cancelled': 'Cancelado'
    };
    return statusMap[status] || status;
}

function showSuccess(message) {
    // Implementar notificación de éxito
    console.log('Success:', message);
    alert(message);
}

function showError(message) {
    // Implementar notificación de error
    console.error('Error:', message);
    alert(message);
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Alias para closeModal (usado en HTML)
function closeModal(modalId) {
    hideModal(modalId);
}

function updatePagination(section, pagination) {
    const container = document.getElementById(`${section}-pagination`);
    if (!container) return;
    
    container.innerHTML = `
        <div class="pagination-info">
            Página ${pagination.current_page} de ${pagination.total_pages}
        </div>
        <div class="pagination-controls">
            <button ${pagination.current_page <= 1 ? 'disabled' : ''} 
                    onclick="changePage('${section}', ${pagination.current_page - 1})">
                Anterior
            </button>
            <button ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''} 
                    onclick="changePage('${section}', ${pagination.current_page + 1})">
                Siguiente
            </button>
        </div>
    `;
}

function changePage(section, page) {
    // Implementar cambio de página
    console.log(`Cambiar a página ${page} de ${section}`);
}

// Configurar gráficos
function setupCharts() {
    // Configuración básica de Chart.js
    Chart.defaults.font.family = 'Poppins, sans-serif';
    Chart.defaults.color = '#666';
}

// Cargar gráfico de ventas
async function loadSalesChart() {
    try {
        const response = await fetch('api/dashboard.php?action=get_sales_chart&period=month');
        const data = await response.json();
        
        if (data.success) {
            createSalesChart(data.data);
        }
    } catch (error) {
        console.error('Error al cargar gráfico de ventas:', error);
    }
}

// Crear gráfico de ventas
function createSalesChart(chartData) {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    
    if (charts.sales) {
        charts.sales.destroy();
    }
    
    charts.sales = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.data.map(item => item.label),
            datasets: [{
                label: 'Ventas',
                data: chartData.data.map(item => item.sales),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-MX');
                        }
                    }
                }
            }
        }
    });
}

// Funciones de búsqueda
function handleSearch(e) {
    const searchTerm = e.target.value;
    console.log('Búsqueda:', searchTerm);
    // Implementar búsqueda en tiempo real
}

// Funciones de modales
function showProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.style.display = 'block';
        // Limpiar formulario
        const form = document.getElementById('productForm');
        if (form) {
            form.reset();
        }
    } else {
        console.log('Modal de producto no encontrado');
    }
}

function showCustomerModal() {
    const modal = document.getElementById('customerModal');
    if (modal) {
        modal.style.display = 'block';
        // Limpiar formulario
        const form = document.getElementById('customerForm');
        if (form) {
            form.reset();
        }
    } else {
        console.log('Modal de cliente no encontrado');
    }
}

function showQuotationModal() {
    const modal = document.getElementById('quotationModal');
    if (modal) {
        modal.style.display = 'block';
        // Limpiar formulario
        const form = document.getElementById('quotationForm');
        if (form) {
            form.reset();
        }
    } else {
        console.log('Modal de cotización no encontrado');
    }
}

function showOrderModal() {
    const modal = document.getElementById('orderModal');
    if (modal) {
        modal.style.display = 'block';
        // Limpiar formulario
        const form = document.getElementById('orderForm');
        if (form) {
            form.reset();
        }
    } else {
        console.log('Modal de pedido no encontrado');
    }
}

function showInventoryModal() {
    const modal = document.getElementById('inventoryModal');
    if (modal) {
        modal.style.display = 'block';
        // Limpiar formulario
        const form = document.getElementById('inventoryForm');
        if (form) {
            form.reset();
        }
    } else {
        console.log('Modal de inventario no encontrado');
    }
}

// Funciones adicionales para modales
function viewProduct(id) {
    console.log('Ver producto:', id);
    // Implementar vista de producto
}

function viewCustomer(id) {
    console.log('Ver cliente:', id);
    // Implementar vista de cliente
}

function viewQuotation(id) {
    console.log('Ver cotización:', id);
    // Implementar vista de cotización
}

function viewOrder(id) {
    console.log('Ver pedido:', id);
    // Implementar vista de pedido
}

function deleteQuotation(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta cotización?')) {
        console.log('Eliminar cotización:', id);
        // Implementar eliminación de cotización
    }
}

function deleteOrder(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este pedido?')) {
        console.log('Eliminar pedido:', id);
        // Implementar eliminación de pedido
    }
}

function editQuotation(id) {
    console.log('Editar cotización:', id);
    // Implementar edición de cotización
}

function editOrder(id) {
    console.log('Editar pedido:', id);
    // Implementar edición de pedido
}

// Exportar funciones globales
window.showSection = showSection;
window.editProduct = editProduct;
window.editCustomer = editCustomer;
window.deleteProduct = deleteProduct;
window.deleteCustomer = deleteCustomer;
window.hideModal = hideModal;
window.closeModal = closeModal;
window.changePage = changePage;
window.showProductModal = showProductModal;
window.showCustomerModal = showCustomerModal;
window.showQuotationModal = showQuotationModal;
window.showOrderModal = showOrderModal;
window.showInventoryModal = showInventoryModal;
window.viewProduct = viewProduct;
window.viewCustomer = viewCustomer;
window.viewQuotation = viewQuotation;
window.viewOrder = viewOrder;
window.deleteQuotation = deleteQuotation;
window.deleteOrder = deleteOrder;
window.editQuotation = editQuotation;
window.editOrder = editOrder;
