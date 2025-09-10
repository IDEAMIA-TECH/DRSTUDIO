<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "=== TESTING REAL UPLOAD ===\n\n";

// Simular $_FILES múltiple
$_FILES = [
    'imagenes' => [
        'name' => ['test1.jpg', 'test2.png', 'test3.gif'],
        'type' => ['image/jpeg', 'image/png', 'image/gif'],
        'tmp_name' => ['/tmp/test1', '/tmp/test2', '/tmp/test3'],
        'error' => [0, 0, 0],
        'size' => [1024, 2048, 1536]
    ]
];

// Crear archivos temporales reales
for ($i = 0; $i < 3; $i++) {
    $tmpFile = $_FILES['imagenes']['tmp_name'][$i];
    $content = "Test image content " . ($i + 1);
    file_put_contents($tmpFile, $content);
    echo "✓ Archivo temporal $i creado: $tmpFile\n";
}

echo "\nProbando procesamiento múltiple...\n";

$totalImagenes = count($_FILES['imagenes']['name']);
$imagenesSubidas = 0;
$errores = [];

for ($i = 0; $i < $totalImagenes; $i++) {
    echo "\nProcesando imagen " . ($i + 1) . "...\n";
    
    if ($_FILES['imagenes']['error'][$i] == 0) {
        // Crear array temporal para cada imagen
        $imagenTemp = [
            'name' => $_FILES['imagenes']['name'][$i],
            'type' => $_FILES['imagenes']['type'][$i],
            'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
            'error' => $_FILES['imagenes']['error'][$i],
            'size' => $_FILES['imagenes']['size'][$i]
        ];
        
        echo "  Archivo: " . $imagenTemp['name'] . "\n";
        echo "  Tipo: " . $imagenTemp['type'] . "\n";
        echo "  Tamaño: " . $imagenTemp['size'] . "\n";
        echo "  Error: " . $imagenTemp['error'] . "\n";
        
        $uploadResult = uploadFile($imagenTemp, UPLOAD_PATH . 'galeria/');
        echo "  Resultado: " . ($uploadResult === false ? 'false' : $uploadResult) . "\n";
        
        if ($uploadResult !== false) {
            $imagen = basename($uploadResult);
            echo "  ✓ Upload exitoso: $imagen\n";
            $imagenesSubidas++;
        } else {
            echo "  ✗ Upload falló\n";
            $errores[] = "Error al subir la imagen " . ($i + 1);
        }
    }
}

echo "\nResultados:\n";
echo "  Imágenes subidas: $imagenesSubidas\n";
echo "  Errores: " . count($errores) . "\n";

// Verificar archivos en el directorio
echo "\nVerificando directorio de destino...\n";
$uploadDir = UPLOAD_PATH . 'galeria/';
$files = scandir($uploadDir);
$imageFiles = array_filter($files, function($file) {
    return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
});
echo "  Archivos encontrados: " . count($imageFiles) . "\n";
foreach ($imageFiles as $file) {
    echo "    - $file\n";
}

// Limpiar archivos temporales
foreach ($_FILES['imagenes']['tmp_name'] as $tmpFile) {
    if (file_exists($tmpFile)) {
        unlink($tmpFile);
    }
}

echo "\n=== END TEST ===\n";
?>
