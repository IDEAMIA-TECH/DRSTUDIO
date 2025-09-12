<?php
$pageTitle = 'Crear Usuario';
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rol = sanitizeInput($_POST['rol']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($username)) {
        $error = 'El nombre de usuario es requerido';
    } elseif (empty($email)) {
        $error = 'El email es requerido';
    } elseif (!validateEmail($email)) {
        $error = 'El email no es válido';
    } elseif (empty($password)) {
        $error = 'La contraseña es requerida';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (empty($rol)) {
        $error = 'El rol es requerido';
    } else {
        // Verificar si el usuario o email ya existe
        $existingUser = getRecord('usuarios', null, "username = '$username' OR email = '$email'");
        if ($existingUser) {
            $error = 'El nombre de usuario o email ya existe';
        } else {
            $data = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'rol' => $rol,
                'activo' => $activo,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (createRecord('usuarios', $data)) {
                $success = 'Usuario creado exitosamente';
                // Limpiar formulario
                $_POST = [];
            } else {
                $error = 'Error al crear el usuario';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Crear Usuario</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="usuarios.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Usuarios
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>Información del Usuario
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
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="usuarioForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="">Seleccionar rol...</option>
                                    <option value="admin" <?php echo ($_POST['rol'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="ventas" <?php echo ($_POST['rol'] ?? '') == 'ventas' ? 'selected' : ''; ?>>Ventas</option>
                                    <option value="produccion" <?php echo ($_POST['rol'] ?? '') == 'produccion' ? 'selected' : ''; ?>>Producción</option>
                                    <option value="lectura" <?php echo ($_POST['rol'] ?? '') == 'lectura' ? 'selected' : ''; ?>>Solo Lectura</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                           <?php echo isset($_POST['activo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        Usuario activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="usuarios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Usuario
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
                <h6>Roles del Sistema</h6>
                <ul class="text-muted small">
                    <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                    <li><strong>Ventas:</strong> Gestión de clientes y cotizaciones</li>
                    <li><strong>Producción:</strong> Gestión de productos e inventario</li>
                    <li><strong>Solo Lectura:</strong> Solo visualización de datos</li>
                </ul>
                
                <h6>Seguridad</h6>
                <p class="text-muted small">
                    La contraseña debe tener al menos 6 caracteres. 
                    Se almacena de forma segura en la base de datos.
                </p>
                
                <h6>Estado del Usuario</h6>
                <p class="text-muted small">
                    Los usuarios inactivos no podrán iniciar sesión 
                    en el sistema.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
