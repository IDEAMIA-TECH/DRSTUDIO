<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "=== DEBUGGING UPLOAD FUNCTION ===\n\n";

// 1. Verificar constantes
echo "1. Verificando constantes...\n";
echo "  UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "  MAX_FILE_SIZE: " . MAX_FILE_SIZE . " bytes (" . (MAX_FILE_SIZE / 1024 / 1024) . " MB)\n";

// 2. Verificar directorio de uploads
echo "\n2. Verificando directorio de uploads...\n";
$uploadDir = UPLOAD_PATH . 'galeria/';
echo "  Directorio objetivo: $uploadDir\n";
echo "  Existe: " . (is_dir($uploadDir) ? 'Sí' : 'No') . "\n";
echo "  Es escribible: " . (is_writable($uploadDir) ? 'Sí' : 'No') . "\n";

if (!is_dir($uploadDir)) {
    echo "  Creando directorio...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "  ✓ Directorio creado exitosamente\n";
    } else {
        echo "  ✗ Error al crear directorio\n";
    }
}

// 3. Verificar permisos
echo "\n3. Verificando permisos...\n";
$parentDir = UPLOAD_PATH;
echo "  Directorio padre ($parentDir):\n";
echo "    Existe: " . (is_dir($parentDir) ? 'Sí' : 'No') . "\n";
echo "    Es escribible: " . (is_writable($parentDir) ? 'Sí' : 'No') . "\n";
echo "    Permisos: " . substr(sprintf('%o', fileperms($parentDir)), -4) . "\n";

// 4. Simular archivo de prueba
echo "\n4. Simulando archivo de prueba...\n";
$testFile = [
    'name' => 'test_image.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/test_upload',
    'error' => 0,
    'size' => 1024
];

// Crear archivo temporal de prueba
$testContent = "Test image content";
if (file_put_contents($testFile['tmp_name'], $testContent)) {
    echo "  ✓ Archivo temporal creado\n";
} else {
    echo "  ✗ Error al crear archivo temporal\n";
    exit;
}

// 5. Probar función uploadFile
echo "\n5. Probando función uploadFile...\n";
$result = uploadFile($testFile, $uploadDir);
echo "  Resultado: " . ($result === false ? 'false' : $result) . "\n";

if ($result !== false) {
    echo "  ✓ Upload exitoso\n";
    echo "  Archivo guardado en: $result\n";
    echo "  Existe: " . (file_exists($result) ? 'Sí' : 'No') . "\n";
    if (file_exists($result)) {
        echo "  Tamaño: " . filesize($result) . " bytes\n";
    }
} else {
    echo "  ✗ Upload falló\n";
}

// 6. Verificar directorio después del upload
echo "\n6. Verificando directorio después del upload...\n";
$files = scandir($uploadDir);
$imageFiles = array_filter($files, function($file) {
    return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
});
echo "  Archivos de imagen encontrados: " . count($imageFiles) . "\n";
foreach ($imageFiles as $file) {
    echo "    - $file\n";
}

// Limpiar archivo temporal
unlink($testFile['tmp_name']);

echo "\n=== END DEBUG ===\n";
?>
