<?php
/**
 * Script para probar las rutas de los archivos
 */

echo "🔍 Probando rutas de archivos...\n";
echo "================================\n";

// Probar desde la raíz del proyecto
echo "📁 Desde la raíz del proyecto:\n";
$root_path = __DIR__;
echo "Directorio actual: $root_path\n";

$config_path = $root_path . '/includes/config.php';
echo "Ruta config.php: $config_path\n";
echo "Existe: " . (file_exists($config_path) ? '✅ SÍ' : '❌ NO') . "\n";

$auth_path = $root_path . '/includes/auth.php';
echo "Ruta auth.php: $auth_path\n";
echo "Existe: " . (file_exists($auth_path) ? '✅ SÍ' : '❌ NO') . "\n";

// Probar desde admin/
echo "\n📁 Desde admin/:\n";
$admin_path = $root_path . '/admin';
$admin_config_path = $admin_path . '/includes/config.php';
echo "Ruta admin config: $admin_config_path\n";
echo "Existe: " . (file_exists($admin_config_path) ? '✅ SÍ' : '❌ NO') . "\n";

// Probar rutas relativas
echo "\n📁 Rutas relativas desde admin/:\n";
$relative_config = '../includes/config.php';
echo "Ruta relativa config: $relative_config\n";
echo "Existe: " . (file_exists($relative_config) ? '✅ SÍ' : '❌ NO') . "\n";

// Probar con dirname()
echo "\n📁 Usando dirname():\n";
$dirname_config = dirname(__DIR__) . '/includes/config.php';
echo "Ruta con dirname: $dirname_config\n";
echo "Existe: " . (file_exists($dirname_config) ? '✅ SÍ' : '❌ NO') . "\n";

// Probar desde admin/includes/
echo "\n📁 Desde admin/includes/:\n";
$admin_includes_path = $root_path . '/admin/includes';
$admin_includes_config = $admin_includes_path . '/../../includes/config.php';
echo "Ruta admin/includes config: $admin_includes_config\n";
echo "Existe: " . (file_exists($admin_includes_config) ? '✅ SÍ' : '❌ NO') . "\n";

// Probar con dirname() desde admin/includes/
$admin_includes_dirname = dirname(dirname(__DIR__)) . '/includes/config.php';
echo "Ruta con dirname desde admin/includes: $admin_includes_dirname\n";
echo "Existe: " . (file_exists($admin_includes_dirname) ? '✅ SÍ' : '❌ NO') . "\n";

echo "\n🎉 Prueba completada\n";
?>
