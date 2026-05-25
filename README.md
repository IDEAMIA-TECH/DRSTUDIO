# DT Studio - Sistema de Promocionales

Sistema web completo para empresa de productos promocionales con panel de administración y sitio público.

## Características

- ✅ **Panel de Administración** con autenticación
- ✅ **Gestión de Categorías** (CRUD completo)
- ✅ **Gestión de Productos** (CRUD completo)
- ✅ **Gestión de Clientes** (CRUD completo)
- ✅ **Sistema de Cotizaciones** (CRUD completo)
- ✅ **Sitio Web Público** (completo)
- ✅ **Diseño con Colores del Logo** (Púrpura #7B3F9F y Gris #333333)

## Tecnologías

- **Backend**: PHP 8.0+
- **Base de datos**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), AJAX
- **Framework CSS**: Bootstrap 5
- **Iconos**: Font Awesome 6

## Paleta de Colores

El sistema utiliza los colores oficiales del logo DT Studio:

- **Púrpura Principal**: `#7B3F9F` - Color principal para botones, enlaces y elementos destacados
- **Púrpura Oscuro**: `#5A2D73` - Para estados hover y elementos activos
- **Púrpura Claro**: `#9B5BB8` - Para elementos secundarios
- **Gris Oscuro**: `#333333` - Para texto principal y elementos de navegación
- **Gris Claro**: `#555555` - Para texto secundario
- **Gris Muy Oscuro**: `#1a1a1a` - Para texto de alto contraste

## Instalación ✅ COMPLETADA

### 🎉 Sistema Instalado Exitosamente

El sistema DT Studio ha sido instalado y configurado correctamente. Todas las tablas de la base de datos han sido creadas.

**Credenciales de Acceso:**
- **Usuario:** `admin`
- **Contraseña:** `password`
- **Panel Admin:** `http://tu-dominio.com/DRSTUDIO/admin/`
- **Sitio Público:** `http://tu-dominio.com/DRSTUDIO/`

### Scripts de Instalación Disponibles

- **`install.php`** - Instalación web con interfaz gráfica
- **`install.sh`** - Instalación por terminal (Linux/Mac)
- **`database/schema.sql`** - Schema SQL para instalación manual

### Requisitos del Servidor

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: mysqli, gd, fileinfo

### Configuración de la Base de Datos

**No suba credenciales a Git.** En el servidor:

```bash
cp includes/config.example.php includes/config.local.php
cp includes/email_config.example.php includes/email_config.local.php
# Editar ambos con host, usuario, contraseña y URL reales
```

Ver [SECURITY.md](SECURITY.md) para buenas prácticas y rotación de contraseñas.

4. Crear las carpetas necesarias:
```bash
mkdir -p uploads/categorias
mkdir -p uploads/productos
mkdir -p images
chmod 755 uploads/
chmod 755 images/
```

### 4. Acceso al Sistema

- **Panel de Administración**: `http://tu-dominio.com/DRSTUDIO/admin/`
- **Usuario por defecto**: admin
- **Contraseña por defecto**: password

## Estructura del Proyecto

```
DRSTUDIO/
├── admin/                 # Panel de administración
│   ├── includes/         # Archivos comunes del admin
│   ├── css/              # Estilos del admin
│   ├── js/               # JavaScript del admin
│   ├── categorias.php    # Listado de categorías
│   ├── categorias_create.php
│   ├── categorias_edit.php
│   ├── categorias_view.php
│   ├── dashboard.php     # Dashboard principal
│   └── login.php         # Página de login
├── ajax/                 # Procesadores AJAX
│   └── categorias.php    # Procesador de categorías
├── includes/             # Archivos comunes
│   ├── config.php        # Configuración
│   ├── functions.php     # Funciones auxiliares
│   └── auth.php          # Sistema de autenticación
├── uploads/              # Archivos subidos
│   └── categorias/       # Imágenes de categorías
├── images/               # Imágenes del sitio
├── database/             # Scripts de base de datos
│   └── schema.sql        # Esquema de la BD
└── README.md
```

## Módulos Implementados

### 1. Sistema de Autenticación
- Login/logout
- Control de sesiones
- Roles de usuario (admin, ventas, producción, lectura)
- Protección de rutas

### 2. Gestión de Categorías
- **Listar**: Vista con tabla paginada y filtros
- **Crear**: Formulario con validación y subida de imágenes
- **Editar**: Formulario pre-poblado con actualización
- **Ver**: Vista detallada con productos asociados
- **Eliminar**: Eliminación con confirmación

#### Características:
- Subida de imágenes con preview
- Validación en tiempo real
- Operaciones AJAX
- Soft delete
- Relaciones con productos

### 3. Gestión de Productos
- **Listar**: Vista con tabla paginada, filtros y búsqueda
- **Crear**: Formulario completo con variantes e imágenes
- **Editar**: Formulario pre-poblado con gestión de variantes
- **Ver**: Vista detallada con estadísticas y variantes
- **Eliminar**: Eliminación con confirmación

#### Características:
- Gestión de variantes (talla, color, material, stock)
- Subida de imágenes con preview
- Cálculo automático de precios y márgenes
- Control de inventario por variantes
- Filtros por categoría, estado y búsqueda
- Validación en tiempo real
- Operaciones AJAX
- Relaciones con categorías

### 4. Gestión de Clientes
- **Listar**: Vista con tabla paginada y búsqueda
- **Crear**: Formulario con validación y vista previa
- **Editar**: Formulario pre-poblado con actualización
- **Ver**: Vista detallada con estadísticas y cotizaciones
- **Eliminar**: Eliminación con validaciones

#### Características:
- Vista previa con avatar generado automáticamente
- Búsqueda por nombre, email, empresa o teléfono
- Estadísticas de cotizaciones y ventas
- Validación de email único
- Relaciones con cotizaciones
- Información de contacto completa

### 5. Sistema de Cotizaciones
- **Listar**: Vista con filtros avanzados y estados
- **Crear**: Formulario dinámico con productos y variantes
- **Editar**: Actualización de cotizaciones existentes
- **Ver**: Vista detallada con cálculos y estados
- **Eliminar**: Eliminación con confirmación

#### Características:
- Cálculo automático de precios y totales
- Gestión de variantes de productos
- Estados de cotización (pendiente, enviada, aceptada, etc.)
- Filtros por cliente, estado y fechas
- Generación automática de números de cotización
- Sistema de descuentos
- Historial de estados
- Integración con clientes y productos

### 6. Sitio Web Público
- **Página de Inicio**: Hero section, productos destacados, testimonios
- **Catálogo de Productos**: Filtros, búsqueda, ordenamiento
- **Galería**: Vista de productos con modal de imagen
- **Contacto**: Formulario de contacto con validación
- **Cotización**: Formulario de solicitud de cotización

#### Características:
- Diseño responsive y moderno
- Navegación intuitiva con menú desplegable
- Filtros y búsqueda avanzada
- Formularios con validación en tiempo real
- Galería de imágenes con modal
- Integración con redes sociales
- SEO optimizado
- Carga rápida y optimizada

## Uso del Sistema

### Panel de Administración

1. **Acceder**: Ir a `/admin/` y hacer login
2. **Dashboard**: Ver estadísticas generales
3. **Categorías**: Gestionar categorías de productos
4. **Productos**: Gestionar productos (próximamente)
5. **Clientes**: Gestionar clientes (próximamente)
6. **Cotizaciones**: Gestionar cotizaciones (próximamente)

### Gestión de Categorías

1. **Crear Categoría**:
   - Ir a "Categorías" → "Nueva Categoría"
   - Llenar formulario con nombre, descripción
   - Subir imagen (opcional)
   - Marcar como activa/inactiva

2. **Editar Categoría**:
   - En el listado, hacer clic en "Editar"
   - Modificar datos necesarios
   - Cambiar imagen si es necesario

3. **Ver Categoría**:
   - Hacer clic en "Ver" para detalles completos
   - Ver productos asociados
   - Ver estadísticas

4. **Eliminar Categoría**:
   - Solo si no tiene productos asociados
   - Confirmar eliminación

## Características Técnicas

### Seguridad
- Validación de datos en servidor y cliente
- Sanitización de inputs
- Protección CSRF
- Escape de outputs (XSS)
- Autenticación de usuarios
- Control de permisos por rol

### Rendimiento
- Paginación en listados
- Optimización de consultas SQL
- Compresión de imágenes
- Cache de consultas frecuentes

### UX/UI
- Diseño responsivo (Bootstrap 5)
- Confirmaciones antes de eliminar
- Mensajes de éxito/error
- Loading states en AJAX
- Validación en tiempo real
- Preview de imágenes

## Próximas Funcionalidades

- [ ] Módulo de Productos (CRUD completo)
- [ ] Módulo de Clientes
- [ ] Sistema de Cotizaciones
- [ ] Sitio Web Público
- [ ] Gestión de Banners
- [ ] Galería de Proyectos
- [ ] Testimonios
- [ ] Reportes y Estadísticas
- [ ] Notificaciones por Email

## Soporte

Para soporte técnico o consultas sobre el sistema, contactar al equipo de desarrollo.

## Licencia

Sistema desarrollado para DT Studio - Todos los derechos reservados.
