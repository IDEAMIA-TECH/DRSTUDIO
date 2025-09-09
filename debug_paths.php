<?php
/**
 * Script para diagnosticar la estructura de directorios en el servidor
 */

echo "🔍 Diagnosticando estructura de directorios...\n";
echo "============================================\n";

// Mostrar información del servidor
echo "📁 Información del servidor:\n";
echo "Directorio actual: " . getcwd() . "\n";
echo "Script actual: " . __FILE__ . "\n";
echo "Directorio del script: " . dirname(__FILE__) . "\n";

// Probar diferentes rutas desde admin/includes/
echo "\n📁 Probando rutas desde admin/includes/:\n";

$testPaths = [
    '../../includes/config.php',
    '../../../includes/config.php',
    '/home/dtstudio/public_html/includes/config.php',
    dirname(dirname(__DIR__)) . '/includes/config.php',
    dirname(dirname(dirname(__FILE__))) . '/includes/config.php'
];

foreach ($testPaths as $path) {
    echo "Ruta: $path\n";
    echo "Existe: " . (file_exists($path) ? '✅ SÍ' : '❌ NO') . "\n";
    echo "Ruta absoluta: " . realpath($path) . "\n";
    echo "---\n";
}

// Probar desde la raíz
echo "\n📁 Probando rutas desde la raíz:\n";
chdir('/home/dtstudio/public_html');

$rootPaths = [
    'includes/config.php',
    './includes/config.php',
    '/home/dtstudio/public_html/includes/config.php'
];

foreach ($rootPaths as $path) {
    echo "Ruta: $path\n";
    echo "Existe: " . (file_exists($path) ? '✅ SÍ' : '❌ NO') . "\n";
    echo "Ruta absoluta: " . realpath($path) . "\n";
    echo "---\n";
}

// Listar contenido de directorios
echo "\n📁 Contenido de directorios:\n";
echo "Directorio raíz:\n";
$rootFiles = scandir('/home/dtstudio/public_html');
foreach ($rootFiles as $file) {
    if ($file != '.' && $file != '..') {
        echo "  - $file\n";
    }
}

echo "\nDirectorio includes:\n";
if (is_dir('/home/dtstudio/public_html/includes')) {
    $includeFiles = scandir('/home/dtstudio/public_html/includes');
    foreach ($includeFiles as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "  ❌ Directorio includes no existe\n";
}

echo "\n🎉 Diagnóstico completado\n";
?>
