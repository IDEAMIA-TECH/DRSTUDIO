#!/bin/bash
# Instalación DR Studio — use variables de entorno (no guardar contraseñas en este archivo)
# export DRSTUDIO_DB_HOST=localhost DRSTUDIO_DB_USER=... DRSTUDIO_DB_PASS=... DRSTUDIO_DB_NAME=dtstudio_main

set -euo pipefail

echo "🚀 Instalando DR Studio..."
echo "================================"

: "${DRSTUDIO_DB_HOST:=localhost}"
: "${DRSTUDIO_DB_USER:?Defina DRSTUDIO_DB_USER}"
: "${DRSTUDIO_DB_PASS:?Defina DRSTUDIO_DB_PASS}"
: "${DRSTUDIO_DB_NAME:=dtstudio_main}"

if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL no está instalado o no está en el PATH"
    exit 1
fi

echo "✅ MySQL encontrado"
echo "🔌 Verificando conexión a la base de datos..."

if ! mysql -h "$DRSTUDIO_DB_HOST" -u "$DRSTUDIO_DB_USER" -p"$DRSTUDIO_DB_PASS" -e "SELECT 1;" &> /dev/null; then
    echo "❌ No se puede conectar a la base de datos"
    exit 1
fi

echo "✅ Conexión a la base de datos establecida"
echo "📊 Creando tablas y datos de ejemplo..."

if mysql -h "$DRSTUDIO_DB_HOST" -u "$DRSTUDIO_DB_USER" -p"$DRSTUDIO_DB_PASS" "$DRSTUDIO_DB_NAME" < database/schema.sql; then
    echo "✅ Base de datos configurada correctamente"
else
    echo "❌ Error al ejecutar el script SQL"
    exit 1
fi

mkdir -p uploads/categorias uploads/productos uploads/banners uploads/galeria images
chmod 755 uploads uploads/categorias uploads/productos uploads/banners uploads/galeria images

echo "$(date)" > INSTALLED
echo ""
echo "🎉 Instalación completada"
echo "Configure includes/config.local.php y includes/email_config.local.php en el servidor."
echo "Usuario inicial del panel: admin (cambie la contraseña tras el primer acceso)."
