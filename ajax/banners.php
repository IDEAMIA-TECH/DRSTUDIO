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
            'icono' => sanitizeInput($_POST['icono']),
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (createRecord('banners', $data)) {
            echo json_encode(['success' => true, 'message' => 'Banner creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el banner']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $data = [
            'titulo' => sanitizeInput($_POST['titulo']),
            'descripcion' => sanitizeInput($_POST['descripcion']),
            'imagen' => $_POST['imagen'] ?? '',
            'icono' => sanitizeInput($_POST['icono']),
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (updateRecord('banners', $data, $id)) {
            echo json_encode(['success' => true, 'message' => 'Banner actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el banner']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener banner para eliminar imagen
        $banner = getRecord('banners', $id);
        if ($banner && $banner['imagen']) {
            $imagePath = PROJECT_ROOT . '/uploads/banners/' . $banner['imagen'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if (deleteRecord('banners', $id)) {
            echo json_encode(['success' => true, 'message' => 'Banner eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el banner']);
        }
        break;
        
    case 'toggle':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        if (updateRecord('banners', $id, ['activo' => $activo, 'updated_at' => date('Y-m-d H:i:s')])) {
            $message = $activo ? 'Banner activado exitosamente' : 'Banner desactivado exitosamente';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del banner']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $banner = getRecord('banners', $id);
        
        if ($banner) {
            echo json_encode(['success' => true, 'data' => $banner]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Banner no encontrado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
