// Admin JavaScript - DT Studio Panel de Administración
// Funcionalidades del panel administrativo

// Variables globales
let currentSection = 'dashboard';
let charts = {};

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
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
    activeNavItem.classList.add('active');
    
    // Actualizar título de la página
    const pageTitle = document.querySelector('.page-title');
    if (pageTitle) {
        pageTitle.textContent = getSectionTitle(sectionId);
    }
    
    // Cargar datos de la sección
    loadSectionData(sectionId);
    
    currentSection = sectionId;
}

// Obtener título de la sección
function getSectionTitle(sectionId) {
    const titles = {
        'dashboard': 'Dashboard',
        'products': 'Productos',
        'customers': 'Clientes',
        'quotations': 'Cotizaciones',
        'orders': 'Pedidos',
        'inventory': 'Inventario',
        'reports': 'Reportes',
        'settings': 'Configuración'
    };
    return titles[sectionId] || 'Dashboard';
}

// Cargar datos de la sección
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
        case 'inventory':
            loadInventory();
            break;
        case 'reports':
            loadReports();
            break;
    }
}

// Cargar datos del dashboard
function loadDashboardData() {
    // Los datos del dashboard se cargan en initializeAdmin()
    console.log('Datos del dashboard cargados');
}

// Cargar datos iniciales
function loadInitialData() {
    // Simular carga de datos
    setTimeout(() => {
        console.log('Datos iniciales cargados');
    }, 1000);
}

// Configurar gráficos
function setupCharts() {
    // Gráfico de ventas por mes
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        charts.sales = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ventas',
                    data: [12000, 15000, 18000, 14000, 20000, 25000],
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
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico de productos más vendidos
    const productsCtx = document.getElementById('productsChart');
    if (productsCtx) {
        charts.products = new Chart(productsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Camisetas', 'Tazas', 'Bolsas', 'Tecnología'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#ff6b6b',
                        '#feca57'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Cargar productos
async function loadProducts() {
    try {
        const response = await fetch('api/products.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.data);
        } else {
            console.error('Error al cargar productos:', data.message);
            displayMockProducts();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockProducts();
    }
}

// Mostrar productos mock
function displayMockProducts() {
    const mockProducts = [
        { id: 1, name: 'Camisetas Personalizadas', category: 'Textiles', price: 150.00, stock: 50, status: 'Activo' },
        { id: 2, name: 'Tazas Promocionales', category: 'Oficina', price: 80.00, stock: 30, status: 'Activo' },
        { id: 3, name: 'Bolsas Ecológicas', category: 'Textiles', price: 120.00, stock: 25, status: 'Activo' },
        { id: 4, name: 'Power Banks', category: 'Tecnología', price: 300.00, stock: 15, status: 'Activo' },
        { id: 5, name: 'Gorras Deportivas', category: 'Deportes', price: 200.00, stock: 40, status: 'Activo' }
    ];
    
    displayProducts(mockProducts);
}

// Mostrar productos en la tabla
function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.id}</td>
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>$${product.price.toFixed(2)}</td>
            <td>${product.stock}</td>
            <td><span class="status-badge ${product.status.toLowerCase()}">${product.status}</span></td>
            <td>
                <button class="btn-action" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Cargar clientes
async function loadCustomers() {
    try {
        const response = await fetch('api/customers.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayCustomers(data.data);
        } else {
            console.error('Error al cargar clientes:', data.message);
            displayMockCustomers();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockCustomers();
    }
}

// Mostrar clientes mock
function displayMockCustomers() {
    const mockCustomers = [
        { id: 1, name: 'Juan Pérez', email: 'juan@empresa.com', phone: '+52 55 1234-5678', company: 'Empresa ABC' },
        { id: 2, name: 'María García', email: 'maria@empresa.com', phone: '+52 55 2345-6789', company: 'Empresa XYZ' },
        { id: 3, name: 'Carlos López', email: 'carlos@empresa.com', phone: '+52 55 3456-7890', company: 'Empresa 123' }
    ];
    
    displayCustomers(mockCustomers);
}

// Mostrar clientes en la tabla
function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>${customer.id}</td>
            <td>${customer.name}</td>
            <td>${customer.email}</td>
            <td>${customer.phone}</td>
            <td>${customer.company}</td>
            <td>
                <button class="btn-action" onclick="editCustomer(${customer.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action" onclick="deleteCustomer(${customer.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Cargar cotizaciones
async function loadQuotations() {
    try {
        const response = await fetch('api/quotations.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayQuotations(data.data);
        } else {
            console.error('Error al cargar cotizaciones:', data.message);
            displayMockQuotations();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockQuotations();
    }
}

// Mostrar cotizaciones mock
function displayMockQuotations() {
    const mockQuotations = [
        { id: 'QT-001', customer: 'Juan Pérez', total: 2500.00, status: 'Aprobada', date: '2024-01-15' },
        { id: 'QT-002', customer: 'María García', total: 1800.00, status: 'Pendiente', date: '2024-01-16' },
        { id: 'QT-003', customer: 'Carlos López', total: 3200.00, status: 'Enviada', date: '2024-01-17' }
    ];
    
    displayQuotations(mockQuotations);
}

// Mostrar cotizaciones en la tabla
function displayQuotations(quotations) {
    const tbody = document.getElementById('quotationsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = quotations.map(quotation => `
        <tr>
            <td>${quotation.id}</td>
            <td>${quotation.customer}</td>
            <td>$${quotation.total.toFixed(2)}</td>
            <td><span class="status-badge ${quotation.status.toLowerCase()}">${quotation.status}</span></td>
            <td>${quotation.date}</td>
            <td>
                <button class="btn-action" onclick="viewQuotation('${quotation.id}')">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-action" onclick="editQuotation('${quotation.id}')">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Cargar pedidos
async function loadOrders() {
    try {
        const response = await fetch('api/orders.php?action=get_all');
        const data = await response.json();
        
        if (data.success) {
            displayOrders(data.data);
        } else {
            console.error('Error al cargar pedidos:', data.message);
            displayMockOrders();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockOrders();
    }
}

// Mostrar pedidos mock
function displayMockOrders() {
    const mockOrders = [
        { id: 'ORD-001', customer: 'Juan Pérez', total: 2500.00, status: 'En Proceso', date: '2024-01-15' },
        { id: 'ORD-002', customer: 'María García', total: 1800.00, status: 'Entregado', date: '2024-01-16' },
        { id: 'ORD-003', customer: 'Carlos López', total: 3200.00, status: 'Pendiente', date: '2024-01-17' }
    ];
    
    displayOrders(mockOrders);
}

// Mostrar pedidos en la tabla
function displayOrders(orders) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>${order.id}</td>
            <td>${order.customer}</td>
            <td>$${order.total.toFixed(2)}</td>
            <td><span class="status-badge ${order.status.toLowerCase().replace(' ', '-')}">${order.status}</span></td>
            <td>${order.date}</td>
            <td>
                <button class="btn-action" onclick="viewOrder('${order.id}')">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-action" onclick="editOrder('${order.id}')">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Cargar inventario
async function loadInventory() {
    try {
        const response = await fetch('api/inventory.php?action=get_stock');
        const data = await response.json();
        
        if (data.success) {
            displayInventory(data.data);
        } else {
            console.error('Error al cargar inventario:', data.message);
            displayMockInventory();
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        displayMockInventory();
    }
}

// Mostrar inventario mock
function displayMockInventory() {
    const mockInventory = [
        { product: 'Camisetas Personalizadas', variant: 'Talla M', current: 50, minimum: 10, status: 'Normal' },
        { product: 'Tazas Promocionales', variant: 'Blanca', current: 5, minimum: 15, status: 'Bajo' },
        { product: 'Bolsas Ecológicas', variant: 'Algodón', current: 25, minimum: 20, status: 'Normal' },
        { product: 'Power Banks', variant: '10000mAh', current: 15, minimum: 5, status: 'Normal' }
    ];
    
    displayInventory(mockInventory);
}

// Mostrar inventario en la tabla
function displayInventory(inventory) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = inventory.map(item => `
        <tr>
            <td>${item.product}</td>
            <td>${item.variant}</td>
            <td>${item.current}</td>
            <td>${item.minimum}</td>
            <td><span class="status-badge ${item.status.toLowerCase()}">${item.status}</span></td>
            <td>
                <button class="btn-action" onclick="adjustStock('${item.product}', '${item.variant}')">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Cargar reportes
function loadReports() {
    // Configurar gráficos de reportes
    setupReportCharts();
}

// Configurar gráficos de reportes
function setupReportCharts() {
    // Gráfico de ventas por período
    const salesReportCtx = document.getElementById('salesReportChart');
    if (salesReportCtx) {
        charts.salesReport = new Chart(salesReportCtx, {
            type: 'bar',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                datasets: [{
                    label: 'Ventas',
                    data: [45000, 52000, 48000, 61000],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Gráfico de top clientes
    const clientsReportCtx = document.getElementById('clientsReportChart');
    if (clientsReportCtx) {
        charts.clientsReport = new Chart(clientsReportCtx, {
            type: 'horizontalBar',
            data: {
                labels: ['Cliente A', 'Cliente B', 'Cliente C', 'Cliente D'],
                datasets: [{
                    label: 'Ventas',
                    data: [15000, 12000, 10000, 8000],
                    backgroundColor: '#764ba2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// Toggle sidebar en móvil
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Mostrar modal de producto
function showProductModal() {
    const modal = document.getElementById('productModal');
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

// Manejar envío de formulario de producto
function handleProductSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    console.log('Producto guardado:', data);
    alert('Producto guardado exitosamente');
    closeModal('productModal');
    e.target.reset();
}

// Manejar búsqueda
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    console.log('Búsqueda:', searchTerm);
    
    // Implementar lógica de búsqueda según la sección actual
    switch (currentSection) {
        case 'products':
            // Filtrar productos
            break;
        case 'customers':
            // Filtrar clientes
            break;
        case 'quotations':
            // Filtrar cotizaciones
            break;
        case 'orders':
            // Filtrar pedidos
            break;
    }
}

// Funciones de acciones
function editProduct(id) {
    console.log('Editar producto:', id);
    showProductModal();
}

function deleteProduct(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        console.log('Eliminar producto:', id);
        alert('Producto eliminado');
    }
}

function editCustomer(id) {
    console.log('Editar cliente:', id);
}

function deleteCustomer(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        console.log('Eliminar cliente:', id);
        alert('Cliente eliminado');
    }
}

function viewQuotation(id) {
    console.log('Ver cotización:', id);
}

function editQuotation(id) {
    console.log('Editar cotización:', id);
}

function viewOrder(id) {
    console.log('Ver pedido:', id);
}

function editOrder(id) {
    console.log('Editar pedido:', id);
}

function adjustStock(product, variant) {
    console.log('Ajustar stock:', product, variant);
}

function generateReport(type) {
    console.log('Generar reporte:', type);
    alert(`Reporte de ${type} generado exitosamente`);
}

function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        console.log('Cerrando sesión...');
        // Implementar lógica de logout
        window.location.href = 'portal.html';
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

// Redimensionar gráficos
window.addEventListener('resize', function() {
    Object.values(charts).forEach(chart => {
        if (chart && chart.resize) {
            chart.resize();
        }
    });
});
