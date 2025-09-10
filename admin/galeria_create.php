<?php
$pageTitle = 'Agregar Imagen a Galería';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_POST) {
    // Debug: Log de datos recibidos
    error_log("=== GALERIA CREATE DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $categoria = sanitizeInput($_POST['categoria']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    error_log("Datos sanitizados - Título: $titulo, Descripción: $descripcion, Categoría: $categoria, Activo: $activo");
    
    // Validaciones
    if (empty($titulo)) {
        $error = 'El título es requerido';
    } elseif (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
        $error = 'Al menos una imagen es requerida';
    } else {
        $imagenesSubidas = 0;
        $errores = [];
        
        // Procesar múltiples imágenes
        $totalImagenes = count($_FILES['imagenes']['name']);
        
        for ($i = 0; $i < $totalImagenes; $i++) {
            error_log("Procesando imagen " . ($i + 1) . " de $totalImagenes");
            
            if ($_FILES['imagenes']['error'][$i] == 0) {
                // Crear array temporal para cada imagen
                $imagenTemp = [
                    'name' => $_FILES['imagenes']['name'][$i],
                    'type' => $_FILES['imagenes']['type'][$i],
                    'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                    'error' => $_FILES['imagenes']['error'][$i],
                    'size' => $_FILES['imagenes']['size'][$i]
                ];
                
                error_log("Imagen $i - Nombre: {$imagenTemp['name']}, Tipo: {$imagenTemp['type']}, Tamaño: {$imagenTemp['size']}, Error: {$imagenTemp['error']}");
                error_log("Archivo temporal existe: " . (file_exists($imagenTemp['tmp_name']) ? 'Sí' : 'No'));
                
                $uploadResult = uploadFile($imagenTemp, 'admin/uploads/galeria/');
                error_log("Resultado uploadFile: " . ($uploadResult === false ? 'false' : $uploadResult));
                if ($uploadResult !== false) {
                    $imagen = basename($uploadResult); // Solo el nombre del archivo
                    
                    // Verificar que el archivo realmente existe
                    $imagePath = 'admin/uploads/galeria/' . $imagen;
                    if (file_exists($imagePath)) {
                        // Obtener el siguiente orden
                        $ultimoOrden = $conn->query("SELECT MAX(orden) as max_orden FROM galeria")->fetch_assoc()['max_orden'] ?? 0;
                        $orden = $ultimoOrden + 1;
                        
                        $data = [
                            'titulo' => $titulo . ($totalImagenes > 1 ? " (" . ($i + 1) . ")" : ''),
                            'descripcion' => $descripcion,
                            'imagen' => $imagen,
                            'categoria' => $categoria,
                            'orden' => $orden,
                            'activo' => $activo,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        if (createRecord('galeria', $data)) {
                            $imagenesSubidas++;
                        } else {
                            $errores[] = "Error al guardar la imagen " . ($i + 1);
                            // Eliminar archivo si no se pudo guardar en BD
                            unlink($imagePath);
                        }
                    } else {
                        $errores[] = "Error al subir la imagen " . ($i + 1) . ": El archivo no se creó correctamente";
                    }
                } else {
                    $errores[] = "Error al subir la imagen " . ($i + 1) . ": Formato no válido o archivo muy grande";
                }
            }
        }
        
        if ($imagenesSubidas > 0) {
            $success = "Se subieron exitosamente $imagenesSubidas imagen(es)";
            if (!empty($errores)) {
                $success .= ". Errores: " . implode(', ', $errores);
            }
            // Limpiar formulario
            $_POST = [];
        } else {
            $error = "No se pudo subir ninguna imagen. Errores: " . implode(', ', $errores);
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Agregar Imágenes a Galería</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="galeria.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Galería
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-images me-2"></i>Información de las Imágenes
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <div class="mt-2">
                            <a href="galeria.php" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-eye me-1"></i>Ver Galería
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="galeriaForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título Base <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" required>
                                <div class="form-text">Se agregará un número automáticamente si subes múltiples imágenes</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <input type="text" class="form-control" id="categoria" name="categoria" 
                                       value="<?php echo htmlspecialchars($_POST['categoria'] ?? ''); ?>" 
                                       placeholder="Ej: Productos, Eventos, etc.">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagenes" class="form-label">Imágenes <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="imagenes" name="imagenes[]" 
                               accept="image/*" multiple required>
                        <div class="form-text">
                            <strong>Puedes seleccionar múltiples imágenes:</strong><br>
                            • Formatos permitidos: JPG, PNG, GIF<br>
                            • Tamaño máximo por imagen: 5MB<br>
                            • Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples archivos
                        </div>
                        <div id="imagenPreview" class="mt-3"></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?php echo isset($_POST['activo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">
                                Imagen activa
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="galeria.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-images me-2"></i>Agregar Imágenes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Información</h6>
            </div>
            <div class="card-body">
                <h6>Subida Múltiple</h6>
                <p class="text-muted small">
                    Puedes seleccionar múltiples imágenes a la vez. 
                    Cada imagen se guardará con un número secuencial automático.
                </p>
                
                <h6>Orden de Imágenes</h6>
                <p class="text-muted small">
                    Las imágenes se ordenarán automáticamente. 
                    Puedes cambiar el orden después desde la lista de galería.
                </p>
                
                <h6>Formatos Recomendados</h6>
                <ul class="text-muted small">
                    <li>JPG: Para fotografías</li>
                    <li>PNG: Para imágenes con transparencia</li>
                    <li>GIF: Para animaciones simples</li>
                </ul>
                
                <h6>Resolución</h6>
                <p class="text-muted small">
                    Se recomienda una resolución mínima de 800x600 píxeles 
                    para una buena calidad de visualización.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de múltiples imágenes
document.getElementById('imagenes').addEventListener('change', function(e) {
    const files = e.target.files;
    const preview = document.getElementById('imagenPreview');
    
    console.log('Archivos seleccionados:', files.length);
    
    // Limpiar preview anterior
    preview.innerHTML = '';
    
    if (files.length > 0) {
        preview.innerHTML = `<h6>Vista previa de las imágenes seleccionadas (${files.length}):</h6>`;
        
        Array.from(files).forEach((file, index) => {
            console.log(`Archivo ${index + 1}:`, file.name, file.type, file.size);
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageContainer = document.createElement('div');
                    imageContainer.className = 'd-inline-block me-2 mb-2';
                    imageContainer.innerHTML = `
                        <div class="position-relative">
                            <img src="${e.target.result}" 
                                 class="img-thumbnail" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                            <div class="position-absolute top-0 start-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 20px; height: 20px; font-size: 12px;">
                                ${index + 1}
                            </div>
                        </div>
                        <div class="small text-muted text-center">${file.name}</div>
                    `;
                    preview.appendChild(imageContainer);
                };
                reader.readAsDataURL(file);
            } else {
                console.warn(`Archivo ${index + 1} no es una imagen:`, file.name);
            }
        });
    }
});

// Agregar debugging al formulario
document.getElementById('galeriaForm').addEventListener('submit', function(e) {
    console.log('=== FORM SUBMIT DEBUG ===');
    
    const files = document.getElementById('imagenes').files;
    console.log('Archivos seleccionados:', files.length);
    
    // Mostrar información de cada archivo
    for (let i = 0; i < files.length; i++) {
        console.log(`Archivo ${i + 1}:`, {
            name: files[i].name,
            type: files[i].type,
            size: files[i].size,
            lastModified: files[i].lastModified
        });
    }
    
    if (files.length === 0) {
        console.log('ERROR: No hay archivos seleccionados');
        e.preventDefault();
        alert('Por favor selecciona al menos una imagen');
        return false;
    }
    
    console.log('Formulario válido, enviando...');
    
    // Mostrar indicador de carga
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';
    submitBtn.disabled = true;
    
    console.log('Botón de envío deshabilitado');
    
    // Restaurar botón después de 10 segundos (por si hay algún problema)
    setTimeout(() => {
        console.log('Restaurando botón de envío');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
