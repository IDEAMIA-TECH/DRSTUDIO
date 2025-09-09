<?php
echo "🔍 Debug de autenticación...\n";
echo "============================\n";

// Verificar si los archivos existen
$files = [
    'includes/config.php',
    'includes/functions.php', 
    'includes/auth.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe\n";
    } else {
        echo "❌ $file NO existe\n";
    }
}

echo "\n📋 Contenido de auth.php:\n";
if (file_exists('includes/auth.php')) {
    $content = file_get_contents('includes/auth.php');
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'function') !== false) {
            echo "Línea " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}

echo "\n📋 Probando inclusión directa:\n";
try {
    include 'includes/config.php';
    echo "✅ config.php incluido\n";
    
    include 'includes/auth.php';
    echo "✅ auth.php incluido\n";
    
    if (function_exists('redirectIfLoggedIn')) {
        echo "✅ redirectIfLoggedIn disponible\n";
    } else {
        echo "❌ redirectIfLoggedIn NO disponible\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
