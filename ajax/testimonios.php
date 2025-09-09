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
            'nombre' => sanitizeInput($_POST['nombre']),
            'empresa' => sanitizeInput($_POST['empresa']),
            'testimonio' => sanitizeInput($_POST['testimonio']),
            'calificacion' => (int)$_POST['calificacion'],
            'imagen' => $_POST['imagen'] ?? '',
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (createRecord('testimonios', $data)) {
            echo json_encode(['success' => true, 'message' => 'Testimonio creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el testimonio']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $data = [
            'nombre' => sanitizeInput($_POST['nombre']),
            'empresa' => sanitizeInput($_POST['empresa']),
            'testimonio' => sanitizeInput($_POST['testimonio']),
            'calificacion' => (int)$_POST['calificacion'],
            'imagen' => $_POST['imagen'] ?? '',
            'orden' => (int)$_POST['orden'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (updateRecord('testimonios', $data, $id)) {
            echo json_encode(['success' => true, 'message' => 'Testimonio actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el testimonio']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener testimonio para eliminar imagen
        $testimonio = getRecord('testimonios', $id);
        if ($testimonio && $testimonio['imagen']) {
            $imagePath = PROJECT_ROOT . '/uploads/testimonios/' . $testimonio['imagen'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if (deleteRecord('testimonios', $id)) {
            echo json_encode(['success' => true, 'message' => 'Testimonio eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el testimonio']);
        }
        break;
        
    case 'toggle':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        if (updateRecord('testimonios', $id, ['activo' => $activo, 'updated_at' => date('Y-m-d H:i:s')])) {
            $message = $activo ? 'Testimonio activado exitosamente' : 'Testimonio desactivado exitosamente';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del testimonio']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $testimonio = getRecord('testimonios', $id);
        
        if ($testimonio) {
            echo json_encode(['success' => true, 'data' => $testimonio]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Testimonio no encontrado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
