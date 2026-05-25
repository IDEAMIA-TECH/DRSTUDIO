<?php
require_once __DIR__ . '/../includes/ajax_bootstrap.php';

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
            'username' => sanitizeInput($_POST['username']),
            'email' => sanitizeInput($_POST['email']),
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'rol' => sanitizeInput($_POST['rol']),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Verificar si el usuario o email ya existe
        $existingUser = getRecord('usuarios', null, "username = '{$data['username']}' OR email = '{$data['email']}'");
        if ($existingUser) {
            echo json_encode(['success' => false, 'message' => 'El usuario o email ya existe']);
            exit;
        }
        
        if (createRecord('usuarios', $data)) {
            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el usuario']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $data = [
            'username' => sanitizeInput($_POST['username']),
            'email' => sanitizeInput($_POST['email']),
            'rol' => sanitizeInput($_POST['rol']),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Si se proporciona nueva contraseña, actualizarla
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Verificar si el usuario o email ya existe (excluyendo el usuario actual)
        $existingUser = getRecord('usuarios', null, "(username = '{$data['username']}' OR email = '{$data['email']}') AND id != $id");
        if ($existingUser) {
            echo json_encode(['success' => false, 'message' => 'El usuario o email ya existe']);
            exit;
        }
        
        if (updateRecord('usuarios', $data, $id)) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // No permitir eliminar el usuario actual
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
            exit;
        }
        
        if (deleteRecord('usuarios', $id)) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario']);
        }
        break;
        
    case 'toggle':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        // No permitir desactivar el usuario actual
        if ($id == $_SESSION['user_id'] && !$activo) {
            echo json_encode(['success' => false, 'message' => 'No puedes desactivar tu propio usuario']);
            exit;
        }
        
        if (updateRecord('usuarios', $id, ['activo' => $activo, 'updated_at' => date('Y-m-d H:i:s')])) {
            $message = $activo ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del usuario']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $usuario = getRecord('usuarios', $id);
        
        if ($usuario) {
            // No enviar la contraseña
            unset($usuario['password']);
            echo json_encode(['success' => true, 'data' => $usuario]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
