<?php
// Archivo de prueba para la página de configuración
session_start();

// Simular sesión de usuario administrador
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Probando página de configuración...\n";

// Simular datos POST para probar la funcionalidad
$_POST = [
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => '465',
    'smtp_username' => 'test@dtstudio.com.mx',
    'smtp_password' => 'test_password',
    'smtp_secure' => 'ssl',
    'from_email' => 'cotizaciones@dtstudio.com.mx',
    'from_name' => 'DT Studio Cotizaciones',
    'admin_email' => 'admin@dtstudio.com.mx',
    'base_url' => 'https://dtstudio.com.mx',
    'company_phone' => '(55) 1234-5678',
    'company_website' => 'www.dtstudio.com.mx'
];

echo "Datos de prueba:\n";
print_r($_POST);

// Incluir la página de configuración
ob_start();
include 'admin/configuracion.php';
$output = ob_get_clean();

echo "\nPágina generada exitosamente\n";
echo "Longitud del contenido: " . strlen($output) . " caracteres\n";

// Verificar si se creó el archivo de configuración
$configFile = 'includes/email_config.php';
if (file_exists($configFile)) {
    echo "✅ Archivo de configuración creado: $configFile\n";
    
    // Leer el contenido del archivo
    $content = file_get_contents($configFile);
    echo "Contenido del archivo:\n";
    echo $content;
    
    // Limpiar archivo de prueba
    unlink($configFile);
    echo "\n✅ Archivo de prueba eliminado\n";
} else {
    echo "❌ Archivo de configuración no se creó\n";
}

echo "\nPrueba completada\n";
?>
