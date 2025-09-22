#!/bin/bash

# Script de Instalación - DR Studio
# Este script crea las tablas de la base de datos automáticamente

echo "🚀 Instalando DR Studio..."
echo "================================"

# Configuración de la base de datos
DB_HOST="173.231.22.109"
DB_USER="dtstudio_main"
DB_PASS="m&9!9ejG!5D6A$p&"
DB_NAME="dtstudio_main"

# Verificar si MySQL está disponible
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL no está instalado o no está en el PATH"
    exit 1
fi

echo "✅ MySQL encontrado"

# Verificar conexión a la base de datos
echo "🔌 Verificando conexión a la base de datos..."
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" &> /dev/null; then
    echo "❌ No se puede conectar a la base de datos"
    echo "   Verifica las credenciales en el script"
    exit 1
fi

echo "✅ Conexión a la base de datos establecida"

# Ejecutar el script SQL
echo "📊 Creando tablas y datos de ejemplo..."
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" < database/schema.sql; then
    echo "✅ Base de datos configurada correctamente"
else
    echo "❌ Error al ejecutar el script SQL"
    exit 1
fi

# Crear directorios necesarios
echo "📁 Creando directorios necesarios..."
mkdir -p uploads/categorias
mkdir -p uploads/productos
mkdir -p uploads/banners
mkdir -p uploads/galeria
mkdir -p images

# Establecer permisos
chmod 755 uploads/
chmod 755 uploads/categorias/
chmod 755 uploads/productos/
chmod 755 uploads/banners/
chmod 755 uploads/galeria/
chmod 755 images/

echo "✅ Directorios creados con permisos correctos"

# Crear archivo de instalación completada
echo "$(date)" > INSTALLED
echo "✅ Archivo de instalación creado"

echo ""
echo "🎉 ¡Instalación Completada Exitosamente!"
echo "========================================"
echo ""
echo "📋 Credenciales de acceso:"
echo "   Usuario: admin"
echo "   Contraseña: password"
echo ""
echo "🌐 Enlaces importantes:"
echo "   Panel de Administración: http://tu-dominio.com/DRSTUDIO/admin/"
echo "   Sitio Web Público: http://tu-dominio.com/DRSTUDIO/"
echo ""
echo "📋 Próximos pasos:"
echo "   1. Configurar el servidor web"
echo "   2. Probar el acceso al sistema"
echo "   3. Personalizar la información de la empresa"
echo "   4. Subir imágenes de productos"
echo ""
echo "✨ ¡El sistema está listo para usar!"
