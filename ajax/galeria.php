<?php
// Detectar la ruta correcta del proyecto
$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/includes/config.php';
$authPath = $projectRoot . '/includes/auth.php';
$functionsPath = $projectRoot . '/includes/functions.php';

// Verificar si los archivos existen, si no, probar rutas alternativas
if (!file_exists($configPath)) {
    // Probar ruta absoluta del servidor
    $configPath = '/home/dtstudio/public_html/includes/config.php';
    $authPath = '/home/dtstudio/public_html/includes/auth.php';
    $functionsPath = '/home/dtstudio/public_html/includes/functions.php';
}

// Incluir archivos
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die('Error: No se pudo encontrar config.php');
}

if (file_exists($authPath)) {
    require_once $authPath;
} else {
    die('Error: No se pudo encontrar auth.php');
}

if (file_exists($functionsPath)) {
    require_once $functionsPath;
} else {
    die('Error: No se pudo encontrar functions.php');
}

header('Content-Type: application/json');

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $data = [
            'titulo' => sanitizeInput($_POST['titulo']),
            'descripcion' => sanitizeInput($_POST['descripcion']),
            'imagen' => $_POST['imagen'] ?? '',
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (createRecord('galeria', $data)) {
            echo json_encode(['success' => true, 'message' => 'Imagen agregada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar la imagen']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $data = [
            'titulo' => sanitizeInput($_POST['titulo']),
            'descripcion' => sanitizeInput($_POST['descripcion']),
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (updateRecord('galeria', $data, $id)) {
            echo json_encode(['success' => true, 'message' => 'Imagen actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la imagen']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener imagen para eliminar archivo
        $imagen = getRecord('galeria', $id);
        if ($imagen && $imagen['imagen']) {
            $imagePath = 'uploads/galeria/' . $imagen['imagen'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if (deleteRecord('galeria', $id, false)) {
            echo json_encode(['success' => true, 'message' => 'Imagen eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la imagen']);
        }
        break;
        
    case 'toggle':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        if (updateRecord('galeria', ['activo' => $activo, 'updated_at' => date('Y-m-d H:i:s')], $id)) {
            $message = $activo ? 'Imagen activada exitosamente' : 'Imagen desactivada exitosamente';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de la imagen']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $imagen = getRecord('galeria', $id);
        
        if ($imagen) {
            echo json_encode(['success' => true, 'data' => $imagen]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
