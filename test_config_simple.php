<?php
// Prueba simple de la página de configuración
echo "Probando página de configuración...\n";

// Verificar que el archivo existe
if (file_exists('admin/configuracion.php')) {
    echo "✅ Archivo configuracion.php existe\n";
} else {
    echo "❌ Archivo configuracion.php no existe\n";
}

// Verificar que el archivo de configuración de email existe
if (file_exists('includes/email_config.php')) {
    echo "✅ Archivo email_config.php existe\n";
    
    // Leer el contenido
    $content = file_get_contents('includes/email_config.php');
    echo "Contenido actual:\n";
    echo $content;
} else {
    echo "ℹ️  Archivo email_config.php no existe (se creará al configurar)\n";
}

echo "\nPrueba completada\n";
?>
