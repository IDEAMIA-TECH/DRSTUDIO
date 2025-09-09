# Especificación Técnica Mejorada — Sistema Web para Empresa de Promocionales

## Objetivo

Desarrollar una página web con panel de administrador para una empresa que vende productos promocionales (playeras, vasos, tazas, gorras, lonas, etc.). El sistema debe ser sencillo de operar, rápido y mantenible, con operaciones CRUD completas para todas las entidades.

---

## Arquitectura del Sistema

### Estructura de Directorios
```
/
├── css/                   # Estilos CSS
├── js/                    # JavaScript del frontend
├── images/                # Imágenes del sitio
├── uploads/               # Archivos subidos por usuarios
├── admin/                 # Panel de administración
│   ├── css/               # Estilos del admin
│   ├── js/                # JavaScript del admin
│   └── includes/          # Archivos comunes del admin
├── includes/              # Archivos comunes
│   ├── config.php         # Configuración de BD
│   ├── functions.php      # Funciones auxiliares
│   └── auth.php           # Autenticación
├── ajax/                  # Procesadores AJAX
└── index.php             # Punto de entrada público
```

### Tecnologías
- **Backend**: PHP 8.0+
- **Base de datos**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), AJAX
- **Patrón**: Arquitectura simple con includes y AJAX

---

## Base de Datos - Estructura

### Tablas Principales

#### 1. usuarios
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- username (VARCHAR(50), UNIQUE)
- email (VARCHAR(100), UNIQUE)
- password (VARCHAR(255))
- rol (ENUM: 'admin', 'ventas', 'produccion', 'lectura')
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 2. categorias
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nombre (VARCHAR(100))
- descripcion (TEXT)
- imagen (VARCHAR(255))
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 3. productos
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- categoria_id (INT, FOREIGN KEY)
- sku (VARCHAR(50), UNIQUE)
- nombre (VARCHAR(200))
- descripcion (TEXT)
- precio_venta (DECIMAL(10,2))
- costo_fabricacion (DECIMAL(10,2))
- tiempo_entrega (INT) # días
- imagen_principal (VARCHAR(255))
- destacado (BOOLEAN, DEFAULT FALSE)
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 4. variantes_producto
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- producto_id (INT, FOREIGN KEY)
- talla (VARCHAR(20))
- color (VARCHAR(50))
- material (VARCHAR(100))
- stock (INT, DEFAULT 0)
- precio_extra (DECIMAL(10,2), DEFAULT 0)
- imagen (VARCHAR(255))
- activo (BOOLEAN, DEFAULT TRUE)
```

#### 5. clientes
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nombre (VARCHAR(100))
- email (VARCHAR(100))
- telefono (VARCHAR(20))
- empresa (VARCHAR(200))
- direccion (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 6. cotizaciones
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- cliente_id (INT, FOREIGN KEY)
- usuario_id (INT, FOREIGN KEY) # quien creó la cotización
- numero_cotizacion (VARCHAR(20), UNIQUE)
- subtotal (DECIMAL(10,2))
- descuento (DECIMAL(10,2), DEFAULT 0)
- total (DECIMAL(10,2))
- estado (ENUM: 'pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada')
- fecha_vencimiento (DATE)
- observaciones (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 7. cotizacion_items
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- cotizacion_id (INT, FOREIGN KEY)
- producto_id (INT, FOREIGN KEY)
- variante_id (INT, FOREIGN KEY)
- cantidad (INT)
- precio_unitario (DECIMAL(10,2))
- subtotal (DECIMAL(10,2))
```

#### 8. banners
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- titulo (VARCHAR(200))
- descripcion (TEXT)
- imagen (VARCHAR(255))
- enlace (VARCHAR(500))
- orden (INT, DEFAULT 0)
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 9. galeria
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- titulo (VARCHAR(200))
- descripcion (TEXT)
- imagen (VARCHAR(255))
- categoria (VARCHAR(100))
- orden (INT, DEFAULT 0)
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 10. testimonios
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- cliente_nombre (VARCHAR(100))
- empresa (VARCHAR(200))
- testimonio (TEXT)
- calificacion (INT, 1-5)
- imagen (VARCHAR(255))
- activo (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

---

## Sitio Web Público

### 1. Página de Inicio (`index.php`)
- Banner rotatorio (consulta tabla `banners`)
- Productos destacados (consulta tabla `productos` WHERE destacado = 1)
- Sección de clientes (logos)
- Testimonios (consulta tabla `testimonios`)
- Misión, Visión y Valores

### 2. Página de Productos (`productos.php`)
- Listado de productos con filtros
- Paginación
- Búsqueda por nombre, categoría, precio
- Modal de cotización

### 3. Página de Galería (`galeria.php`)
- Grid de imágenes de la tabla `galeria`
- Filtros por categoría
- Modal para vista ampliada

### 4. Página de Contacto (`contacto.php`)
- Formulario de contacto
- Validación JavaScript
- Envío por AJAX

---

## Panel de Administración - Vistas CRUD

### 1. Dashboard (`admin/dashboard.php`)
- Resumen de estadísticas
- Gráficas de ingresos/egresos
- Cotizaciones recientes
- Productos con stock bajo

### 2. Gestión de Productos
- **Listar**: `admin/productos.php`
  - Tabla con productos
  - Filtros y búsqueda
  - Botones: Ver, Editar, Eliminar, Activar/Desactivar
- **Crear**: `admin/productos_create.php`
  - Formulario completo de producto
  - Subida de imágenes
  - Gestión de variantes
- **Editar**: `admin/productos_edit.php`
  - Formulario pre-poblado
  - Misma funcionalidad que crear
- **Ver**: `admin/productos_view.php`
  - Vista detallada del producto
  - Historial de variantes

### 3. Gestión de Categorías
- **Listar**: `admin/categorias.php`
- **Crear**: `admin/categorias_create.php`
- **Editar**: `admin/categorias_edit.php`
- **Eliminar**: Confirmación antes de eliminar

### 4. Gestión de Cotizaciones
- **Listar**: `admin/cotizaciones.php`
  - Filtros por estado, cliente, fecha
  - Acciones: Ver, Editar, Enviar, Aceptar/Rechazar
- **Crear**: `admin/cotizaciones_create.php`
  - Selector de cliente
  - Agregar productos con cantidades
  - Cálculo automático de totales
- **Editar**: `admin/cotizaciones_edit.php`
- **Ver**: `admin/cotizaciones_view.php`
  - Vista detallada con items
  - Botón de envío por email

### 5. Gestión de Clientes
- **Listar**: `admin/clientes.php`
- **Crear**: `admin/clientes_create.php`
- **Editar**: `admin/clientes_edit.php`
- **Ver**: `admin/clientes_view.php`
  - Historial de cotizaciones del cliente

### 6. Gestión de Banners
- **Listar**: `admin/banners.php`
- **Crear**: `admin/banners_create.php`
- **Editar**: `admin/banners_edit.php`
- **Ordenar**: Drag & drop para cambiar orden

### 7. Gestión de Galería
- **Listar**: `admin/galeria.php`
- **Crear**: `admin/galeria_create.php`
- **Editar**: `admin/galeria_edit.php`

### 8. Gestión de Testimonios
- **Listar**: `admin/testimonios.php`
- **Crear**: `admin/testimonios_create.php`
- **Editar**: `admin/testimonios_edit.php`

### 9. Gestión de Usuarios
- **Listar**: `admin/usuarios.php`
- **Crear**: `admin/usuarios_create.php`
- **Editar**: `admin/usuarios_edit.php`
- **Cambiar contraseña**: Modal separado

---

## Procesadores AJAX y Lógica de Negocio

### Estructura de Procesadores AJAX
```php
// ajax/productos.php
if ($_POST['action'] == 'create') {
    // Crear producto
} elseif ($_POST['action'] == 'update') {
    // Actualizar producto
} elseif ($_POST['action'] == 'delete') {
    // Eliminar producto
} elseif ($_POST['action'] == 'get') {
    // Obtener producto específico
}
```

### Funciones CRUD Comunes
```php
// includes/functions.php
function createRecord($table, $data) {
    global $conn;
    $fields = implode(',', array_keys($data));
    $values = "'" . implode("','", array_values($data)) . "'";
    $sql = "INSERT INTO $table ($fields) VALUES ($values)";
    return mysqli_query($conn, $sql);
}

function readRecords($table, $conditions = [], $limit = null) {
    global $conn;
    $sql = "SELECT * FROM $table";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return mysqli_query($conn, $sql);
}

function updateRecord($table, $id, $data) {
    global $conn;
    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = '$value'";
    }
    $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function deleteRecord($table, $id, $soft = true) {
    global $conn;
    if ($soft) {
        $sql = "UPDATE $table SET activo = 0 WHERE id = $id";
    } else {
        $sql = "DELETE FROM $table WHERE id = $id";
    }
    return mysqli_query($conn, $sql);
}
```

### Estructura de Archivos PHP
```php
// admin/productos.php (Listar)
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener productos
$productos = readRecords('productos', ['activo = 1']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- HTML del listado -->
    <script src="js/admin.js"></script>
</body>
</html>
```

### Procesadores AJAX
```php
// ajax/productos.php
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $data = [
            'nombre' => $_POST['nombre'],
            'precio_venta' => $_POST['precio_venta'],
            'categoria_id' => $_POST['categoria_id']
        ];
        if (createRecord('productos', $data)) {
            echo json_encode(['success' => true, 'message' => 'Producto creado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
        break;
        
    case 'update':
        $id = $_POST['id'];
        $data = [
            'nombre' => $_POST['nombre'],
            'precio_venta' => $_POST['precio_venta']
        ];
        if (updateRecord('productos', $id, $data)) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'];
        if (deleteRecord('productos', $id)) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
        break;
}
?>
```

---

## JavaScript y AJAX

### Estructura de Archivos JS
```
js/
├── admin.js              # Funciones del panel de administración
├── public.js             # Funciones del sitio público
└── common.js             # Funciones comunes
```

### Funciones AJAX Comunes
```javascript
// js/common.js
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
    .catch(error => console.error('Error:', error));
}

function showMessage(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    document.body.insertBefore(alertDiv, document.body.firstChild);
    setTimeout(() => alertDiv.remove(), 3000);
}
```

### Funciones CRUD con AJAX
```javascript
// js/admin.js
function createProduct(formData) {
    ajaxRequest('ajax/productos.php', {
        action: 'create',
        ...formData
    }, function(response) {
        if (response.success) {
            showMessage(response.message);
            loadProducts(); // Recargar tabla
        } else {
            showMessage(response.message, 'error');
        }
    });
}

function updateProduct(id, formData) {
    ajaxRequest('ajax/productos.php', {
        action: 'update',
        id: id,
        ...formData
    }, function(response) {
        if (response.success) {
            showMessage(response.message);
            loadProducts();
        } else {
            showMessage(response.message, 'error');
        }
    });
}

function deleteProduct(id) {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        ajaxRequest('ajax/productos.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                showMessage(response.message);
                loadProducts();
            } else {
                showMessage(response.message, 'error');
            }
        });
    }
}

function loadProducts() {
    ajaxRequest('ajax/productos.php', {
        action: 'list'
    }, function(response) {
        if (response.success) {
            updateProductsTable(response.data);
        }
    });
}
```

### Validación de Formularios
```javascript
// js/admin.js
function validateForm(formId) {
    const form = document.getElementById(formId);
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Validación en tiempo real
    document.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Envío de formularios
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(this.id)) {
                const formData = new FormData(this);
                const action = this.dataset.action;
                
                if (action === 'create') {
                    createProduct(Object.fromEntries(formData));
                } else if (action === 'update') {
                    const id = this.dataset.id;
                    updateProduct(id, Object.fromEntries(formData));
                }
            }
        });
    });
});
```

---

## Características Técnicas

### Seguridad
- Validación de datos en servidor y cliente
- Sanitización de inputs
- Protección CSRF con tokens
- Escape de outputs (XSS)
- Autenticación de usuarios
- Control de permisos por rol

### Rendimiento
- Paginación en todas las listas
- Optimización de consultas SQL
- Compresión de imágenes
- Cache de consultas frecuentes
- Minificación de CSS/JS

### UX/UI
- Diseño responsivo (Bootstrap 5)
- Confirmaciones antes de eliminar
- Mensajes de éxito/error
- Loading states en AJAX
- Validación en tiempo real
- Drag & drop para ordenar

### Funcionalidades Especiales
- **Cotizador**: Cálculo automático de precios
- **Notificaciones**: Email para cotizaciones
- **Reportes**: Gráficas con Chart.js
- **Subida de archivos**: Imágenes optimizadas
- **Exportación**: PDF para cotizaciones

---

## Flujo de Desarrollo

### Fase 1: Estructura Base
1. Configuración de base de datos
2. Estructura de directorios
3. Sistema de autenticación
4. Layout base del admin

### Fase 2: Módulos CRUD
1. Gestión de categorías
2. Gestión de productos
3. Gestión de clientes
4. Gestión de cotizaciones

### Fase 3: Sitio Público
1. Páginas públicas
2. Formularios de contacto
3. Sistema de cotizaciones

### Fase 4: Funcionalidades Avanzadas
1. Reportes y gráficas
2. Notificaciones
3. Optimizaciones
4. Testing

---

## Consideraciones de Mantenimiento

- Código modular y documentado
- Separación clara de responsabilidades
- Uso de constantes para configuración
- Logs de errores y actividades
- Backup automático de base de datos
- Versionado de código con Git
