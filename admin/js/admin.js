// JavaScript para el panel de administración

// Funciones AJAX comunes
function ajaxRequest(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error en la petición', 'danger');
    });
}

// Mostrar mensajes
function showAlert(message, type = 'success') {
    // Crear contenedor de alertas si no existe
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '70px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '1060';
        alertContainer.style.maxWidth = '500px';
        document.body.appendChild(alertContainer);
    }
    
    // Crear alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.marginBottom = '10px';
    
    const alertId = 'alert-' + Date.now();
    alertDiv.id = alertId;
    
    // Iconos para cada tipo
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    
    alertDiv.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Agregar a contenedor
    alertContainer.appendChild(alertDiv);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 150);
        }
    }, 5000);
}

// Confirmar eliminación
function confirmDelete(message = '¿Estás seguro de eliminar este elemento?') {
    return new Promise((resolve) => {
        // Crear modal de confirmación
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Confirmar Eliminación
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta acción no se puede deshacer.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        // Event listeners
        document.getElementById('confirmDeleteBtn').onclick = () => {
            modal.hide();
            resolve(true);
        };
        
        document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('confirmModal').remove();
            resolve(false);
        });
        
        // Mostrar modal
        modal.show();
    });
}

// Validar formulario
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Limpiar validación en tiempo real
function clearValidation(input) {
    input.classList.remove('is-invalid');
}

// Cargar datos en modal
function loadDataInModal(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    Object.keys(data).forEach(key => {
        const input = modal.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = data[key];
        }
    });
}

// Subir archivo con preview
function uploadFileWithPreview(input, previewId) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Formatear número como moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(amount);
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Inicializar DataTables
function initDataTable(tableId, options = {}) {
    const defaultOptions = {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: -1 } // Deshabilitar ordenamiento en última columna (acciones)
        ]
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    return $(`#${tableId}`).DataTable(finalOptions);
}

// CRUD Functions para Productos
function createProduct(formData) {
    ajaxRequest('../ajax/productos.php', {
        action: 'create',
        ...formData
    }, function(response) {
        if (response.success) {
            showAlert(response.message);
            if (typeof loadProducts === 'function') {
                loadProducts();
            }
            // Cerrar modal si existe
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            if (modal) modal.hide();
        } else {
            showAlert(response.message, 'danger');
        }
    });
}

function updateProduct(id, formData) {
    ajaxRequest('../ajax/productos.php', {
        action: 'update',
        id: id,
        ...formData
    }, function(response) {
        if (response.success) {
            showAlert(response.message);
            if (typeof loadProducts === 'function') {
                loadProducts();
            }
            // Cerrar modal si existe
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            if (modal) modal.hide();
        } else {
            showAlert(response.message, 'danger');
        }
    });
}

function deleteProduct(id) {
    if (confirmDelete('¿Estás seguro de eliminar este producto?')) {
        ajaxRequest('../ajax/productos.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                if (typeof loadProducts === 'function') {
                    loadProducts();
                }
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}

// CRUD Functions para Categorías
function createCategory(formData) {
    ajaxRequest('ajax/categorias.php', {
        action: 'create',
        ...formData
    }, function(response) {
        if (response.success) {
            showAlert(response.message);
            if (typeof loadCategories === 'function') {
                loadCategories();
            }
        } else {
            showAlert(response.message, 'danger');
        }
    });
}

function updateCategory(id, formData) {
    ajaxRequest('ajax/categorias.php', {
        action: 'update',
        id: id,
        ...formData
    }, function(response) {
        if (response.success) {
            showAlert(response.message);
            if (typeof loadCategories === 'function') {
                loadCategories();
            }
        } else {
            showAlert(response.message, 'danger');
        }
    });
}

function deleteCategory(id) {
    if (confirmDelete('¿Estás seguro de eliminar esta categoría?')) {
        ajaxRequest('ajax/categorias.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showAlert(response.message);
                if (typeof loadCategories === 'function') {
                    loadCategories();
                }
            } else {
                showAlert(response.message, 'danger');
            }
        });
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Validación en tiempo real
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
        input.addEventListener('blur', function() {
            clearValidation(this);
        });
    });
    
    // Envío de formularios con AJAX
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm(this.id)) {
                const formData = new FormData(this);
                const action = this.dataset.action;
                const data = Object.fromEntries(formData);
                
                if (action === 'create_product') {
                    createProduct(data);
                } else if (action === 'update_product') {
                    const id = this.dataset.id;
                    updateProduct(id, data);
                } else if (action === 'create_category') {
                    createCategory(data);
                } else if (action === 'update_category') {
                    const id = this.dataset.id;
                    updateCategory(id, data);
                }
            }
        });
    });
    
    // Preview de imágenes
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            uploadFileWithPreview(this, previewId);
        });
    });
    
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Funciones de utilidad
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('Copiado al portapapeles', 'info');
    });
}

function exportTable(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
