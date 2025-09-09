# Configuración del Sistema de Correos

## 📧 Configuración Requerida

Para que el sistema de correos funcione correctamente, necesitas configurar los siguientes archivos:

### 1. Configuración SMTP (`includes/email_config.php`)

```php
// Configuración del servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com'); // Cambiar por tu servidor SMTP
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu-email@dtstudio.com.mx'); // Cambiar por tu email
define('SMTP_PASSWORD', 'tu-password-app'); // Cambiar por tu contraseña de aplicación
define('SMTP_ENCRYPTION', 'tls');

// Configuración del remitente
define('FROM_EMAIL', 'cotizaciones@dtstudio.com.mx');
define('FROM_NAME', 'DT Studio');
define('ADMIN_EMAIL', 'admin@dtstudio.com.mx'); // Email del administrador
```

### 2. Configuración de Gmail (Recomendado)

1. **Habilitar autenticación de 2 factores** en tu cuenta de Gmail
2. **Generar una contraseña de aplicación**:
   - Ve a Configuración de Google > Seguridad
   - Busca "Contraseñas de aplicaciones"
   - Genera una nueva contraseña para "Correo"
   - Usa esta contraseña en `SMTP_PASSWORD`

### 3. Configuración de Dominio (Opcional)

Si tienes tu propio dominio, configura:
- Un servidor SMTP propio
- Un email `cotizaciones@tudominio.com`
- Un email `admin@tudominio.com`

## 🚀 Funcionalidades Implementadas

### ✅ Sistema de Envío de Correos
- **PHPMailer** instalado y configurado
- **Plantillas HTML** elegantes para correos
- **Adjuntos PDF** automáticos
- **Copia al administrador** en todos los correos

### ✅ Página de Aceptación
- **URL pública**: `https://dtstudio.com.mx/aceptar-cotizacion.php`
- **Token de seguridad** para acceso
- **Interfaz moderna** con Bootstrap
- **Confirmación automática** por correo

### ✅ Estados de Cotización
- **Pendiente**: Cotización creada
- **Enviada**: Correo enviado al cliente
- **Aceptada**: Cliente aceptó la cotización
- **En Espera de Depósito**: Cliente aceptó, esperando pago
- **Rechazada**: Cliente rechazó la cotización
- **Cancelada**: Cotización cancelada

## 📋 Flujo de Trabajo

1. **Crear cotización** en el admin
2. **Marcar como "Enviada"** → Se envía correo con PDF
3. **Cliente recibe correo** con botón de aceptación
4. **Cliente hace clic** en el botón de aceptación
5. **Estado cambia** a "En Espera de Depósito"
6. **Se envía confirmación** por correo

## 🔧 Archivos Modificados

- `includes/email_config.php` - Configuración de correo
- `includes/EmailSender.php` - Clase para envío de correos
- `aceptar-cotizacion.php` - Página pública de aceptación
- `ajax/cotizaciones.php` - Lógica de envío de correos
- `admin/cotizaciones.php` - Interfaz actualizada
- `database/schema.sql` - Nuevos estados y campos

## ⚠️ Importante

1. **Configura las credenciales** antes de usar el sistema
2. **Prueba el envío** con una cotización de prueba
3. **Verifica que los correos** lleguen correctamente
4. **Revisa los logs** si hay errores

## 🐛 Solución de Problemas

### Error de autenticación SMTP
- Verifica las credenciales en `email_config.php`
- Asegúrate de usar contraseña de aplicación para Gmail
- Verifica que la autenticación de 2 factores esté habilitada

### Correos no se envían
- Revisa los logs del servidor
- Verifica la configuración del servidor SMTP
- Asegúrate de que el servidor permita conexiones SMTP

### PDF no se adjunta
- Verifica que mPDF esté instalado correctamente
- Revisa los permisos de escritura en el directorio temporal
- Verifica que la función `createCotizacionHTML` funcione

## 📞 Soporte

Si tienes problemas con la configuración, revisa:
1. Los logs de error de PHP
2. Los logs de PHPMailer
3. La configuración del servidor SMTP
