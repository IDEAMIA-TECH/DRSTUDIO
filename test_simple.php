<?php
// Script simple para probar las funciones
echo "🔍 Prueba simple...\n";

// Incluir solo lo necesario
require_once 'includes/config.php';

echo "✅ Config incluido\n";

// Verificar si getRecord existe
if (function_exists('getRecord')) {
    echo "✅ getRecord disponible\n";
} else {
    echo "❌ getRecord NO disponible\n";
}

// Incluir auth
require_once 'includes/auth.php';

echo "✅ Auth incluido\n";

// Verificar funciones de auth
if (function_exists('redirectIfLoggedIn')) {
    echo "✅ redirectIfLoggedIn disponible\n";
} else {
    echo "❌ redirectIfLoggedIn NO disponible\n";
}

if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn disponible\n";
} else {
    echo "❌ isLoggedIn NO disponible\n";
}

echo "🎉 Prueba completada\n";
?>
