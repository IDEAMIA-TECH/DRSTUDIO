# 🚀 Guía de Instalación - DR Studio

Esta guía te ayudará a instalar el sistema DR Studio en tu servidor.

## 📋 Requisitos Previos

- **PHP 8.0+** con extensiones: mysqli, gd, fileinfo
- **MySQL 8.0+** 
- **Servidor web** (Apache/Nginx)
- **Acceso SSH** al servidor (opcional)

## 🛠️ Métodos de Instalación

### Método 1: Instalación Automática (Recomendado)

#### Opción A: Script PHP
1. Sube todos los archivos al servidor
2. Accede a: `http://tu-dominio.com/DRSTUDIO/install.php`
3. El script creará automáticamente todas las tablas y datos

#### Opción B: Script de Terminal
```bash
# Hacer ejecutable el script
chmod +x install.sh

# Ejecutar instalación
./install.sh
```

### Método 2: Instalación Manual

1. **Crear la base de datos:**
```sql
CREATE DATABASE dtstudio_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Importar el schema:**
```bash
mysql -h TU_HOST -u TU_USUARIO -p TU_BASE_DE_DATOS < database/schema.sql
```

3. **Crear directorios:**
```bash
mkdir -p uploads/categorias
mkdir -p uploads/productos
mkdir -p uploads/banners
mkdir -p uploads/galeria
mkdir -p images
chmod 755 uploads/
chmod 755 images/
```

## ⚙️ Configuración

### 1. Verificar Configuración de Base de Datos

Copie las plantillas y configure credenciales **solo en el servidor** (archivos `*.local.php`, no versionados):

```bash
cp includes/config.example.php includes/config.local.php
cp includes/email_config.example.php includes/email_config.local.php
```

Consulte [SECURITY.md](SECURITY.md).

### 2. Configurar Permisos

```bash
# Permisos para directorios de uploads
chmod 755 uploads/
chmod 755 uploads/categorias/
chmod 755 uploads/productos/
chmod 755 uploads/banners/
chmod 755 uploads/galeria/
chmod 755 images/

# Permisos para archivos PHP
chmod 644 *.php
chmod 644 admin/*.php
chmod 644 ajax/*.php
chmod 644 includes/*.php
```

### 3. Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteger archivos sensibles
<Files "*.sql">
    Order Allow,Deny
    Deny from all
</Files>

<Files "install.php">
    Order Allow,Deny
    Deny from all
</Files>
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.sql$ {
    deny all;
}

location ~ install\.php$ {
    deny all;
}
```

## 🔐 Credenciales de Acceso

Después de la instalación, puedes acceder con:

- **Panel de Administración:** `http://tu-dominio.com/DRSTUDIO/admin/`
- **Usuario:** `admin`
- **Contraseña:** `password`

⚠️ **IMPORTANTE:** Cambia la contraseña después del primer acceso.

## 📊 Verificación de la Instalación

### 1. Verificar Base de Datos
```sql
-- Conectar a MySQL
mysql -h TU_HOST -u TU_USUARIO -p TU_BASE_DE_DATOS

-- Verificar tablas
SHOW TABLES;

-- Verificar datos
SELECT COUNT(*) FROM usuarios;
SELECT COUNT(*) FROM categorias;
SELECT COUNT(*) FROM productos;
```

### 2. Verificar Archivos
```bash
# Verificar que existan los directorios
ls -la uploads/
ls -la images/

# Verificar permisos
ls -la uploads/categorias/
ls -la uploads/productos/
```

### 3. Verificar Funcionamiento
1. Acceder al panel de administración
2. Verificar que se carguen las categorías
3. Probar crear un producto
4. Verificar el sitio web público

## 🐛 Solución de Problemas

### Error de Conexión a Base de Datos
- Verificar credenciales en `includes/config.php`
- Verificar que MySQL esté ejecutándose
- Verificar firewall y permisos de red

### Error de Permisos
```bash
# Corregir permisos
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

### Error de PHP
- Verificar versión de PHP (8.0+)
- Verificar extensiones: mysqli, gd, fileinfo
- Revisar logs de error de PHP

### Error 500
- Verificar permisos de archivos
- Revisar logs de error del servidor
- Verificar configuración de PHP

## 📞 Soporte

Si encuentras problemas durante la instalación:

1. Revisa los logs de error
2. Verifica los requisitos del sistema
3. Consulta la documentación técnica
4. Contacta al equipo de desarrollo

## 🎯 Próximos Pasos

Después de la instalación exitosa:

1. **Personalizar el sistema:**
   - Cambiar información de la empresa
   - Subir logo y branding
   - Configurar colores y estilos

2. **Agregar contenido:**
   - Crear categorías de productos
   - Subir productos con imágenes
   - Agregar testimonios de clientes

3. **Configurar funcionalidades:**
   - Configurar notificaciones por email
   - Personalizar formularios
   - Configurar integraciones

4. **Optimizar rendimiento:**
   - Configurar cache
   - Optimizar imágenes
   - Configurar CDN

---

**¡El sistema DR Studio está listo para usar!** 🎉
