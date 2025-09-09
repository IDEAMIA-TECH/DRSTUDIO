<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validar datos
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }
        
        // Verificar si ya existe
        $existing = readRecords('categorias', ["nombre = '$nombre'"]);
        if (!empty($existing)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una categoría con este nombre']);
            exit;
        }
        
        // Procesar imagen
        $imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $imagen = uploadFile($_FILES['imagen'], '../uploads/categorias/');
            if (!$imagen) {
                echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
                exit;
            }
        }
        
        // Crear categoría
        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'imagen' => $imagen,
            'activo' => $activo
        ];
        
        if (createRecord('categorias', $data)) {
            echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la categoría']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validar datos
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }
        
        // Verificar si ya existe otra categoría con el mismo nombre
        $existing = readRecords('categorias', ["nombre = '$nombre'", "id != $id"]);
        if (!empty($existing)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra categoría con este nombre']);
            exit;
        }
        
        // Obtener categoría actual
        $categoria = getRecord('categorias', $id);
        if (!$categoria) {
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
            exit;
        }
        
        // Procesar imagen
        $imagen = $categoria['imagen']; // Mantener imagen actual por defecto
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $newImagen = uploadFile($_FILES['imagen'], '../uploads/categorias/');
            if ($newImagen) {
                // Eliminar imagen anterior si existe
                if ($imagen && file_exists('../' . $imagen)) {
                    deleteFile('../' . $imagen);
                }
                $imagen = $newImagen;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
                exit;
            }
        }
        
        // Actualizar categoría
        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'imagen' => $imagen,
            'activo' => $activo
        ];
        
        if (updateRecord('categorias', $data, $id)) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la categoría']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Verificar si la categoría tiene productos
        $productos = readRecords('productos', ["categoria_id = $id"]);
        if (!empty($productos)) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar la categoría porque tiene productos asociados']);
            exit;
        }
        
        // Obtener categoría para eliminar imagen
        $categoria = getRecord('categorias', $id);
        if (!$categoria) {
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
            exit;
        }
        
        // Eliminar imagen si existe
        if ($categoria['imagen'] && file_exists('../' . $categoria['imagen'])) {
            deleteFile('../' . $categoria['imagen']);
        }
        
        // Eliminar categoría
        if (deleteRecord('categorias', $id, false)) {
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la categoría']);
        }
        break;
        
    case 'toggle_status':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        $data = ['activo' => $activo];
        if (updateRecord('categorias', $data, $id)) {
            $status = $activo ? 'activada' : 'desactivada';
            echo json_encode(['success' => true, 'message' => "Categoría $status exitosamente"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $categoria = getRecord('categorias', $id);
        
        if ($categoria) {
            echo json_encode(['success' => true, 'data' => $categoria]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        }
        break;
        
    case 'list':
        $categorias = readRecords('categorias', [], null, 'nombre ASC');
        echo json_encode(['success' => true, 'data' => $categorias]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
