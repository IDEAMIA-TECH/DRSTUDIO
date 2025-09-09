<?php
$pageTitle = 'Configuración del Sistema';
require_once 'includes/header.php';

$error = '';
$success = '';

// Archivo de configuración
$configFile = '../includes/email_config.php';

// Cargar configuración actual
$currentConfig = [
    'smtp_host' => '',
    'smtp_port' => '465',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_secure' => 'ssl',
    'from_email' => '',
    'from_name' => '',
    'admin_email' => '',
    'base_url' => '',
    'company_phone' => '',
    'company_website' => ''
];

if (file_exists($configFile)) {
    include $configFile;
    $currentConfig = [
        'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : '',
        'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : '465',
        'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
        'smtp_password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
        'smtp_secure' => defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl',
        'from_email' => defined('FROM_EMAIL') ? FROM_EMAIL : '',
        'from_name' => defined('FROM_NAME') ? FROM_NAME : '',
        'admin_email' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '',
        'base_url' => defined('BASE_URL') ? BASE_URL : '',
        'company_phone' => defined('COMPANY_PHONE') ? COMPANY_PHONE : '',
        'company_website' => defined('COMPANY_WEBSITE') ? COMPANY_WEBSITE : ''
    ];
}

// Procesar formulario
if ($_POST) {
    $smtp_host = sanitizeInput($_POST['smtp_host']);
    $smtp_port = (int)$_POST['smtp_port'];
    $smtp_username = sanitizeInput($_POST['smtp_username']);
    $smtp_password = $_POST['smtp_password'];
    $smtp_secure = $_POST['smtp_secure'];
    $from_email = sanitizeInput($_POST['from_email']);
    $from_name = sanitizeInput($_POST['from_name']);
    $admin_email = sanitizeInput($_POST['admin_email']);
    $base_url = sanitizeInput($_POST['base_url']);
    $company_phone = sanitizeInput($_POST['company_phone']);
    $company_website = sanitizeInput($_POST['company_website']);
    
    // Validar datos
    if (empty($smtp_host) || empty($smtp_username) || empty($from_email) || empty($admin_email)) {
        $error = 'Los campos marcados con * son obligatorios';
    } elseif (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email de remitente no es válido';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email del administrador no es válido';
    } else {
        // Crear contenido del archivo de configuración
        $configContent = '<?php
// Configuración de PHPMailer
define(\'SMTP_HOST\', \'' . addslashes($smtp_host) . '\'); // Servidor SMTP
define(\'SMTP_PORT\', ' . $smtp_port . '); // Puerto SMTP (465 para SSL, 587 para TLS)
define(\'SMTP_USERNAME\', \'' . addslashes($smtp_username) . '\'); // Usuario SMTP
define(\'SMTP_PASSWORD\', \'' . addslashes($smtp_password) . '\'); // Contraseña SMTP
define(\'SMTP_SECURE\', \'' . $smtp_secure . '\'); // \'ssl\' o \'tls\'

// Información del remitente
define(\'FROM_EMAIL\', \'' . addslashes($from_email) . '\');
define(\'FROM_NAME\', \'' . addslashes($from_name) . '\');

// Correo del administrador para copias
define(\'ADMIN_EMAIL\', \'' . addslashes($admin_email) . '\');

// URL base del sitio para enlaces de aceptación
define(\'BASE_URL\', \'' . addslashes($base_url) . '\');

// Información de la empresa para el pie de página del correo
define(\'COMPANY_PHONE\', \'' . addslashes($company_phone) . '\');
define(\'COMPANY_WEBSITE\', \'' . addslashes($company_website) . '\');
?>';

        // Guardar archivo
        if (file_put_contents($configFile, $configContent)) {
            $success = 'Configuración guardada exitosamente';
            // Recargar configuración actual
            $currentConfig = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_secure' => $smtp_secure,
                'from_email' => $from_email,
                'from_name' => $from_name,
                'admin_email' => $admin_email,
                'base_url' => $base_url,
                'company_phone' => $company_phone,
                'company_website' => $company_website
            ];
        } else {
            $error = 'Error al guardar la configuración. Verifica los permisos del archivo.';
        }
    }
}

// Probar configuración de correo
$testResult = '';
if (isset($_POST['test_email'])) {
    try {
        require_once '../includes/EmailSender.php';
        $emailSender = new EmailSender();
        $testResult = '✅ Configuración de correo válida - PHPMailer inicializado correctamente';
    } catch (Exception $e) {
        $testResult = '❌ Error en la configuración: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cog me-2"></i>
                    Configuración del Sistema
                </h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($testResult): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo $testResult; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <!-- Configuración SMTP -->
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="fas fa-server me-2"></i>
                                Configuración SMTP
                            </h5>
                            
                            <div class="mb-3">
                                <label for="smtp_host" class="form-label">Servidor SMTP *</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($currentConfig['smtp_host']); ?>" 
                                       placeholder="smtp.hostinger.com" required>
                                <div class="form-text">Ejemplo: smtp.hostinger.com, smtp.gmail.com</div>
                            </div>

                            <div class="mb-3">
                                <label for="smtp_port" class="form-label">Puerto SMTP *</label>
                                <select class="form-select" id="smtp_port" name="smtp_port" required>
                                    <option value="465" <?php echo $currentConfig['smtp_port'] == 465 ? 'selected' : ''; ?>>465 (SSL)</option>
                                    <option value="587" <?php echo $currentConfig['smtp_port'] == 587 ? 'selected' : ''; ?>>587 (TLS)</option>
                                    <option value="25" <?php echo $currentConfig['smtp_port'] == 25 ? 'selected' : ''; ?>>25 (Sin encriptación)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="smtp_username" class="form-label">Usuario SMTP *</label>
                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                       value="<?php echo htmlspecialchars($currentConfig['smtp_username']); ?>" 
                                       placeholder="tu-email@dtstudio.com.mx" required>
                            </div>

                            <div class="mb-3">
                                <label for="smtp_password" class="form-label">Contraseña SMTP *</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                       value="<?php echo htmlspecialchars($currentConfig['smtp_password']); ?>" 
                                       placeholder="Tu contraseña de aplicación" required>
                                <div class="form-text">Usa contraseñas de aplicación para mayor seguridad</div>
                            </div>

                            <div class="mb-3">
                                <label for="smtp_secure" class="form-label">Tipo de Encriptación *</label>
                                <select class="form-select" id="smtp_secure" name="smtp_secure" required>
                                    <option value="ssl" <?php echo $currentConfig['smtp_secure'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo $currentConfig['smtp_secure'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                        </div>

                        <!-- Configuración de Correos -->
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="fas fa-envelope me-2"></i>
                                Configuración de Correos
                            </h5>
                            
                            <div class="mb-3">
                                <label for="from_email" class="form-label">Email Remitente *</label>
                                <input type="email" class="form-control" id="from_email" name="from_email" 
                                       value="<?php echo htmlspecialchars($currentConfig['from_email']); ?>" 
                                       placeholder="cotizaciones@dtstudio.com.mx" required>
                            </div>

                            <div class="mb-3">
                                <label for="from_name" class="form-label">Nombre Remitente</label>
                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                       value="<?php echo htmlspecialchars($currentConfig['from_name']); ?>" 
                                       placeholder="DT Studio Cotizaciones">
                            </div>

                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email Administrador *</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?php echo htmlspecialchars($currentConfig['admin_email']); ?>" 
                                       placeholder="admin@dtstudio.com.mx" required>
                                <div class="form-text">Recibirá copia de todos los correos</div>
                            </div>

                            <div class="mb-3">
                                <label for="base_url" class="form-label">URL Base del Sitio *</label>
                                <input type="url" class="form-control" id="base_url" name="base_url" 
                                       value="<?php echo htmlspecialchars($currentConfig['base_url']); ?>" 
                                       placeholder="https://dtstudio.com.mx" required>
                            </div>
                        </div>
                    </div>

                    <!-- Información de la Empresa -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-building me-2"></i>
                                Información de la Empresa
                            </h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                       value="<?php echo htmlspecialchars($currentConfig['company_phone']); ?>" 
                                       placeholder="(55) 1234-5678">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_website" class="form-label">Sitio Web</label>
                                <input type="text" class="form-control" id="company_website" name="company_website" 
                                       value="<?php echo htmlspecialchars($currentConfig['company_website']); ?>" 
                                       placeholder="www.dtstudio.com.mx">
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Configuración
                                </button>
                                
                                <button type="submit" name="test_email" class="btn btn-info">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Probar Configuración
                                </button>
                                
                                <a href="cotizaciones.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver a Cotizaciones
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Información de ayuda -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-question-circle me-2"></i>
                    Información de Ayuda
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Configuración SMTP Común:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Gmail:</strong> smtp.gmail.com:587 (TLS)</li>
                            <li><strong>Outlook:</strong> smtp-mail.outlook.com:587 (TLS)</li>
                            <li><strong>Hostinger:</strong> smtp.hostinger.com:465 (SSL)</li>
                            <li><strong>cPanel:</strong> mail.tudominio.com:587 (TLS)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Recomendaciones de Seguridad:</h6>
                        <ul class="list-unstyled">
                            <li>• Usa contraseñas de aplicación</li>
                            <li>• No uses contraseñas principales</li>
                            <li>• Verifica la configuración antes de guardar</li>
                            <li>• Mantén las credenciales seguras</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-actualizar tipo de encriptación según el puerto
document.getElementById('smtp_port').addEventListener('change', function() {
    const port = this.value;
    const secure = document.getElementById('smtp_secure');
    
    if (port == '465') {
        secure.value = 'ssl';
    } else if (port == '587') {
        secure.value = 'tls';
    }
});

// Validación en tiempo real
document.getElementById('from_email').addEventListener('blur', function() {
    const email = this.value;
    if (email && !email.includes('@')) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});

document.getElementById('admin_email').addEventListener('blur', function() {
    const email = this.value;
    if (email && !email.includes('@')) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
