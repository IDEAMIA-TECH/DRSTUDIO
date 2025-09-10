<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "=== DETAILED UPLOAD DEBUG ===\n\n";

// Función uploadFile con debugging
function uploadFileDebug($file, $targetDir = UPLOAD_PATH) {
    echo "  Iniciando uploadFile...\n";
    echo "  Archivo: " . $file['name'] . "\n";
    echo "  Directorio objetivo: $targetDir\n";
    
    if (!file_exists($targetDir)) {
        echo "  Creando directorio...\n";
        if (mkdir($targetDir, 0777, true)) {
            echo "  ✓ Directorio creado\n";
        } else {
            echo "  ✗ Error al crear directorio\n";
            return false;
        }
    } else {
        echo "  ✓ Directorio existe\n";
    }
    
    $fileName = basename($file["name"]);
    $targetFile = $targetDir . time() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    echo "  Nombre del archivo: $fileName\n";
    echo "  Archivo objetivo: $targetFile\n";
    echo "  Tipo de archivo: $fileType\n";
    
    // Verificar tipo de archivo
    $allowedTypes = array("jpg", "jpeg", "png", "gif", "webp");
    if (!in_array($fileType, $allowedTypes)) {
        echo "  ✗ Tipo de archivo no permitido: $fileType\n";
        echo "  Tipos permitidos: " . implode(', ', $allowedTypes) . "\n";
        return false;
    }
    echo "  ✓ Tipo de archivo válido\n";
    
    // Verificar tamaño
    echo "  Tamaño del archivo: " . $file["size"] . " bytes\n";
    echo "  Tamaño máximo: " . MAX_FILE_SIZE . " bytes\n";
    if ($file["size"] > MAX_FILE_SIZE) {
        echo "  ✗ Archivo demasiado grande\n";
        return false;
    }
    echo "  ✓ Tamaño válido\n";
    
    // Verificar archivo temporal
    echo "  Archivo temporal: " . $file["tmp_name"] . "\n";
    echo "  Existe: " . (file_exists($file["tmp_name"]) ? 'Sí' : 'No') . "\n";
    echo "  Es legible: " . (is_readable($file["tmp_name"]) ? 'Sí' : 'No') . "\n";
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        echo "  ✓ Archivo movido exitosamente\n";
        return $targetFile;
    } else {
        echo "  ✗ Error al mover archivo\n";
        echo "  Error: " . error_get_last()['message'] . "\n";
        return false;
    }
}

// Crear archivo temporal de prueba
$testFile = [
    'name' => 'test_image.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/test_upload_debug',
    'error' => 0,
    'size' => 1024
];

// Crear archivo temporal de prueba
$testContent = "Test image content";
if (file_put_contents($testFile['tmp_name'], $testContent)) {
    echo "✓ Archivo temporal creado\n";
} else {
    echo "✗ Error al crear archivo temporal\n";
    exit;
}

$uploadDir = UPLOAD_PATH . 'galeria/';
$result = uploadFileDebug($testFile, $uploadDir);

echo "\nResultado final: " . ($result === false ? 'false' : $result) . "\n";

// Limpiar
unlink($testFile['tmp_name']);

echo "\n=== END DEBUG ===\n";
?>
