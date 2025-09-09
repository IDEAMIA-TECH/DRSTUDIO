# DT Studio - Sistema de Gestión de Promocionales

## 📋 Descripción del Proyecto

DT Studio es un sistema web integral para la gestión de productos promocionales con panel administrativo avanzado, diseñado para optimizar el flujo de cotizaciones, inventario y ventas. La solución incluye funcionalidades de e-commerce, CRM básico y herramientas de análisis de negocio.

## 🚀 Características Principales

### Portal Público del Cliente
- **Catálogo de Productos**: Visualización de productos con filtros avanzados
- **Sistema de Cotización**: Cotizador en tiempo real con formularios de contacto
- **Galería de Proyectos**: Portfolio de trabajos realizados
- **Diseño Responsivo**: Optimizado para dispositivos móviles y desktop

### Panel de Administración
- **Dashboard**: Métricas en tiempo real y gráficos interactivos
- **Gestión de Productos**: CRUD completo con variantes e inventario
- **Gestión de Clientes**: CRM básico con historial de compras
- **Sistema de Cotizaciones**: Creación, seguimiento y aprobación
- **Gestión de Pedidos**: Control completo del ciclo de ventas
- **Inventario**: Control de stock con alertas automáticas
- **Reportes**: Analytics avanzados y exportación de datos
- **Configuración**: Ajustes del sistema y notificaciones

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 8.1+**: Lenguaje de programación principal
- **MySQL 8.0+**: Base de datos principal
- **SQLite**: Base de datos para testing local
- **PDO**: Conexión segura a base de datos

### Frontend
- **HTML5**: Estructura semántica
- **CSS3**: Estilos modernos con Flexbox y Grid
- **JavaScript ES6+**: Funcionalidades interactivas
- **Chart.js**: Gráficos y visualizaciones
- **Font Awesome**: Iconografía

### Infraestructura
- **Servidor Web**: Apache/Nginx
- **Base de Datos**: MySQL remoto + SQLite local
- **CDN**: CloudFlare para recursos estáticos

## 📁 Estructura del Proyecto

```
DTSTUDIO/
├── api/                    # API REST endpoints
│   ├── categories.php
│   ├── customers.php
│   ├── inventory.php
│   ├── notifications.php
│   ├── orders.php
│   ├── payments.php
│   ├── products.php
│   ├── public.php
│   ├── quotations.php
│   ├── reports.php
│   └── users.php
├── config/                 # Configuración
│   ├── database.php
│   └── database_test.php
├── controllers/            # Controladores MVC
│   ├── CategoryController.php
│   ├── CustomerController.php
│   ├── InventoryController.php
│   ├── NotificationController.php
│   ├── OrderController.php
│   ├── PaymentController.php
│   ├── ProductController.php
│   ├── PublicController.php
│   ├── QuotationController.php
│   ├── ReportController.php
│   └── UserController.php
├── database/               # Esquemas de base de datos
│   ├── schema.sql         # MySQL (producción)
│   └── schema_test.sql    # SQLite (testing)
├── includes/              # Clases base
│   ├── Auth.php
│   ├── Database.php
│   └── DatabaseTest.php
├── models/                # Modelos de datos
│   ├── Analytics.php
│   ├── Banner.php
│   ├── Catalog.php
│   ├── Category.php
│   ├── Customer.php
│   ├── EmailService.php
│   ├── Inventory.php
│   ├── Notification.php
│   ├── Order.php
│   ├── Payment.php
│   ├── Product.php
│   ├── Quotation.php
│   ├── Report.php
│   ├── Setting.php
│   ├── StockMovement.php
│   ├── Supplier.php
│   └── User.php
├── tests/                 # Tests unitarios
│   ├── CategoryTest.php
│   ├── ConfigurationTest.php
│   ├── CustomerTest.php
│   ├── InventoryTest.php
│   ├── NotificationTest.php
│   ├── OrderTest.php
│   ├── PaymentTest.php
│   ├── ProductTest.php
│   ├── PublicTest.php
│   ├── QuotationTest.php
│   ├── ReportTest.php
│   ├── RoleTest.php
│   ├── UserTest.php
│   └── run_*_tests.php
├── logo/                  # Recursos gráficos
│   └── LOGO DT STUDIO.png
├── doc/                   # Documentación
│   ├── context.md
│   ├── technical-specification.md
│   └── MODULE_*_SUMMARY.md
├── admin.html             # Panel de administración
├── admin.css              # Estilos del panel admin
├── admin.js               # JavaScript del panel admin
├── portal.html            # Portal público del cliente
├── portal.css             # Estilos del portal público
├── portal.js              # JavaScript del portal público
├── index.html             # Página de inicio (redirección)
├── styles.css             # Estilos globales
└── README.md              # Este archivo
```

## 🚀 Instalación y Configuración

### Requisitos del Sistema
- PHP 8.1 o superior
- MySQL 8.0 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL, JSON

### Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd DTSTUDIO
   ```

2. **Configurar la base de datos**
   - Editar `config/database.php` con las credenciales de tu servidor MySQL
   - Ejecutar `database/schema.sql` en tu servidor MySQL

3. **Configurar el servidor web**
   - Apuntar el document root a la carpeta del proyecto
   - Configurar URL rewriting si es necesario

4. **Verificar la instalación**
   - Acceder a `http://tu-dominio.com`
   - Verificar que el portal público carga correctamente
   - Acceder a `http://tu-dominio.com/admin.html` para el panel de administración

## 🧪 Testing

### Ejecutar Tests Locales
```bash
# Tests de usuarios y roles
php tests/run_tests.php

# Tests de productos
php tests/run_product_tests.php

# Tests de clientes
php tests/run_customer_tests.php

# Tests de cotizaciones
php tests/run_quotation_tests.php

# Tests de pedidos
php tests/run_order_tests.php

# Tests de reportes
php tests/run_report_tests.php

# Tests del portal público
php tests/run_public_tests.php

# Tests del sistema de pagos
php tests/run_payment_tests.php

# Tests del sistema de notificaciones
php tests/run_notification_tests.php

# Tests del sistema de configuración
php tests/run_configuration_tests.php

# Tests del sistema de inventario
php tests/run_inventory_tests.php
```

## 📊 Módulos Implementados

### ✅ Módulos Completados
1. **Usuarios y Roles** - Sistema de autenticación y autorización
2. **Productos** - Gestión completa de productos y variantes
3. **Clientes** - CRM básico con historial de compras
4. **Cotizaciones** - Sistema de cotización con seguimiento
5. **Pedidos** - Gestión completa del ciclo de ventas
6. **Reportes y Analytics** - Dashboard con métricas en tiempo real
7. **Portal Público** - Catálogo y cotizador para clientes
8. **Sistema de Pagos** - Integración con pasarelas de pago
9. **Sistema de Notificaciones** - Email y SMS automáticos
10. **Sistema de Configuración** - Ajustes del sistema
11. **Sistema de Inventario** - Control de stock y proveedores

### 🔄 Módulos en Desarrollo
- **Sistema de Facturación** - Integración con Enlace Fiscal para CFDI

## 🌐 Acceso al Sistema

### Portal Público
- **URL**: `http://tu-dominio.com/portal.html`
- **Descripción**: Portal para clientes con catálogo de productos y sistema de cotización

### Panel de Administración
- **URL**: `http://tu-dominio.com/admin.html`
- **Descripción**: Panel administrativo completo para gestión del sistema

### Página de Inicio
- **URL**: `http://tu-dominio.com/index.html`
- **Descripción**: Página de redirección que lleva al portal público

## 🔧 Configuración de la Base de Datos

### Servidor de Producción
- **Host**: 216.18.195.84
- **Base de datos**: dtstudio_main
- **Usuario**: dtstudio_main
- **Puerto**: 3306

### Base de Datos Local (Testing)
- **Tipo**: SQLite
- **Archivo**: `database/test.db`
- **Configuración**: `config/database_test.php`

## 📈 Características Técnicas

### Seguridad
- Autenticación basada en roles
- Validación de datos en frontend y backend
- Protección contra inyección SQL con PDO
- Sanitización de inputs

### Rendimiento
- Consultas optimizadas con índices
- Carga asíncrona de datos
- Compresión de recursos estáticos
- Cache de consultas frecuentes

### Escalabilidad
- Arquitectura modular
- API REST para integraciones
- Base de datos normalizada
- Separación de responsabilidades

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- **Email**: info@dtstudio.com
- **Teléfono**: +52 (55) 1234-5678

## 🎯 Roadmap

### Versión 2.0 (Próximamente)
- [ ] Sistema de facturación con Enlace Fiscal
- [ ] App móvil nativa
- [ ] Integración con WhatsApp Business
- [ ] Dashboard avanzado con más métricas

### Versión 3.0 (Futuro)
- [ ] Multi-tenant
- [ ] API pública
- [ ] Integración con redes sociales
- [ ] Analytics avanzados

---

**Desarrollado con ❤️ por el equipo de DT Studio**