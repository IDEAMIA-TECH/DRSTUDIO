<?php
// Página de perfil del usuario
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Mi Perfil';
$error = '';
$success = '';

// Obtener datos del usuario actual
$user_id = $_SESSION['user_id'];
$usuario = getRecord('usuarios', $user_id);

if (!$usuario) {
    header('Location: login.php');
    exit;
}

// Procesar formulario de actualización de perfil
if ($_POST) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            // Actualizar perfil
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            
            // Validar datos
            if (empty($username) || empty($email)) {
                $error = 'Todos los campos son obligatorios';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email no es válido';
            } else {
                // Verificar si el username o email ya existen en otro usuario
                $existing_user = $conn->query("SELECT id FROM usuarios WHERE (username = '$username' OR email = '$email') AND id != $user_id");
                
                if ($existing_user->num_rows > 0) {
                    $error = 'El nombre de usuario o email ya está en uso';
                } else {
                    // Actualizar perfil
                    $data = [
                        'username' => $username,
                        'email' => $email
                    ];
                    
                    if (updateRecord('usuarios', $data, $user_id)) {
                        $success = 'Perfil actualizado exitosamente';
                        // Recargar datos del usuario
                        $usuario = getRecord('usuarios', $user_id);
                    } else {
                        $error = 'Error al actualizar el perfil';
                    }
                }
            }
        } elseif ($action === 'change_password') {
            // Cambiar contraseña
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validar datos
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'Todos los campos de contraseña son obligatorios';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Las contraseñas nuevas no coinciden';
            } elseif (strlen($new_password) < 6) {
                $error = 'La nueva contraseña debe tener al menos 6 caracteres';
            } elseif (!password_verify($current_password, $usuario['password'])) {
                $error = 'La contraseña actual es incorrecta';
            } else {
                // Actualizar contraseña
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $data = ['password' => $hashed_password];
                
                if (updateRecord('usuarios', $data, $user_id)) {
                    $success = 'Contraseña actualizada exitosamente';
                } else {
                    $error = 'Error al actualizar la contraseña';
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>Mi Perfil
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Información del perfil -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Información Personal</h6>
                        <p class="mb-1"><strong>Nombre de usuario:</strong> <?php echo htmlspecialchars($usuario['username']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                        <p class="mb-1"><strong>Rol:</strong> 
                            <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : ($usuario['rol'] === 'ventas' ? 'primary' : 'secondary'); ?>">
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Estado:</strong> 
                            <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'danger'; ?>">
                                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Miembro desde:</strong> <?php echo formatDate($usuario['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Estadísticas</h6>
                        <?php
                        // Obtener estadísticas del usuario
                        $cotizaciones_count = $conn->query("SELECT COUNT(*) as total FROM cotizaciones WHERE usuario_id = $user_id")->fetch_assoc()['total'];
                        $ultima_actividad = $usuario['updated_at'];
                        ?>
                        <p class="mb-1"><strong>Cotizaciones creadas:</strong> <?php echo $cotizaciones_count; ?></p>
                        <p class="mb-1"><strong>Última actualización:</strong> <?php echo formatDate($ultima_actividad); ?></p>
                    </div>
                </div>
                
                <!-- Formulario de actualización de perfil -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Actualizar Información
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nombre de Usuario *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="username" 
                                               name="username" 
                                               value="<?php echo htmlspecialchars($usuario['username']); ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Actualizar Perfil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Formulario de cambio de contraseña -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Contraseña Actual *</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nueva Contraseña *</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password" 
                                               minlength="6"
                                               required>
                                        <div class="form-text">Mínimo 6 caracteres</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña *</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               minlength="6"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Sistema</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        <strong>Seguridad:</strong> Tu contraseña está encriptada
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-user-shield text-info me-2"></i>
                        <strong>Rol:</strong> <?php echo ucfirst($usuario['rol']); ?>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <strong>Última sesión:</strong> <?php echo formatDate($usuario['updated_at']); ?>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-calendar text-primary me-2"></i>
                        <strong>Miembro desde:</strong> <?php echo formatDate($usuario['created_at']); ?>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Acciones Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Ir al Dashboard
                    </a>
                    <a href="cotizaciones.php" class="btn btn-outline-success">
                        <i class="fas fa-file-invoice me-2"></i>Ver Cotizaciones
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación de contraseñas
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
