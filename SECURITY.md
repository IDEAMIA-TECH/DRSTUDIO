# Seguridad del repositorio

Este proyecto **no debe** contener contraseñas, claves API ni rutas privadas del servidor en Git.

## Configuración local (no versionada)

```bash
cp includes/config.example.php includes/config.local.php
cp includes/email_config.example.php includes/email_config.local.php
# Editar ambos archivos con credenciales reales
```

En el servidor de producción, cree esos archivos **solo en el hosting**; no los suba al repositorio público.

## Variables de entorno (instaladores CLI)

```bash
export DRSTUDIO_DB_HOST=localhost
export DRSTUDIO_DB_USER=tu_usuario
export DRSTUDIO_DB_PASS=tu_contraseña
export DRSTUDIO_DB_NAME=dtstudio_main
php install_cli.php
```

## Si el repositorio fue público con secretos

Si alguna contraseña estuvo en Git, **cámbiela de inmediato** en el panel del hosting:

1. Contraseña de MySQL (`dtstudio_main` o equivalente)
2. Contraseña del correo SMTP (`cotizaciones@...`)
3. Usuario admin del panel (`admin` — cambiar contraseña tras el primer acceso)

El historial de Git puede conservar commits antiguos; rotar credenciales es obligatorio aunque se eliminen del código actual.

## Archivos que nunca deben commitearse

- `includes/config.local.php`
- `includes/email_config.local.php`
- `.env`
- Copias de backups `*_backup.php` con datos reales
