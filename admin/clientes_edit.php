<?php
$pageTitle = 'Editar Cliente';
require_once 'includes/header.php';

$error = '';
$success = '';

// Obtener ID del cliente
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: clientes.php');
    exit;
}

// Obtener datos del cliente
$cliente = getRecord('clientes', $id);
if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

if ($_POST) {
    $nombre = sanitizeInput($_POST['nombre']);
    $email = sanitizeInput($_POST['email']);
    $telefono = sanitizeInput($_POST['telefono']);
    $empresa = sanitizeInput($_POST['empresa']);
    $direccion = sanitizeInput($_POST['direccion']);
    
    // Validar datos
    if (empty($nombre)) {
        $error = 'El nombre es requerido';
    } elseif ($email && !validateEmail($email)) {
        $error = 'El email no tiene un formato válido';
    } else {
        // Verificar si ya existe otro cliente con el mismo email
        if ($email) {
            $existing = readRecords('clientes', ["email = '$email'", "id != $id"]);
            if (!empty($existing)) {
                $error = 'Ya existe otro cliente con este email';
            }
        }
        
        if (empty($error)) {
            // Actualizar cliente
            $data = [
                'nombre' => $nombre,
                'email' => $email ?: null,
                'telefono' => $telefono ?: null,
                'empresa' => $empresa ?: null,
                'direccion' => $direccion ?: null
            ];
            
            if (updateRecord('clientes', $data, $id)) {
                $success = 'Cliente actualizado exitosamente';
                // Actualizar datos locales
                $cliente = array_merge($cliente, $data);
            } else {
                $error = 'Error al actualizar el cliente';
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>Editar Cliente: <?php echo htmlspecialchars($cliente['nombre']); ?>
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
                        <a href="clientes.php" class="btn btn-sm btn-outline-success ms-3">Ver Clientes</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="clienteForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($cliente['nombre']); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="empresa" 
                                       name="empresa" 
                                       value="<?php echo htmlspecialchars($cliente['empresa']); ?>" 
                                       placeholder="Nombre de la empresa">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($cliente['email']); ?>" 
                                       placeholder="correo@ejemplo.com">
                                <div class="form-text">Opcional, pero recomendado para comunicaciones</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono" 
                                       value="<?php echo htmlspecialchars($cliente['telefono']); ?>" 
                                       placeholder="(555) 123-4567">
                                <div class="form-text">Opcional, pero recomendado para contacto</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" 
                                  id="direccion" 
                                  name="direccion" 
                                  rows="3" 
                                  placeholder="Dirección completa del cliente"><?php echo htmlspecialchars($cliente['direccion']); ?></textarea>
                        <div class="form-text">Opcional, útil para envíos</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Cliente
                        </button>
                        <a href="clientes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <a href="clientes_view.php?id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Ver Detalles
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Vista Previa</h6>
            </div>
            <div class="card-body text-center">
                <div class="avatar-circle mx-auto mb-3" id="avatarPreview">
                    <span id="avatarText"><?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?></span>
                </div>
                <h6 id="nombrePreview" class="mb-1"><?php echo htmlspecialchars($cliente['nombre']); ?></h6>
                <p class="text-muted mb-0" id="empresaPreview"><?php echo htmlspecialchars($cliente['empresa'] ?: 'Empresa'); ?></p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Información del Cliente</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>ID:</strong> <?php echo $cliente['id']; ?></li>
                    <li><strong>Creado:</strong> <?php echo formatDate($cliente['created_at']); ?></li>
                    <li><strong>Actualizado:</strong> <?php echo formatDate($cliente['updated_at']); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Actualizar vista previa en tiempo real
document.getElementById('nombre').addEventListener('input', function() {
    const nombre = this.value.trim();
    const avatarText = document.getElementById('avatarText');
    const nombrePreview = document.getElementById('nombrePreview');
    
    if (nombre) {
        const iniciales = nombre.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
        avatarText.textContent = iniciales;
        nombrePreview.textContent = nombre;
    } else {
        avatarText.textContent = '??';
        nombrePreview.textContent = 'Nombre del Cliente';
    }
});

document.getElementById('empresa').addEventListener('input', function() {
    const empresa = this.value.trim();
    const empresaPreview = document.getElementById('empresaPreview');
    
    if (empresa) {
        empresaPreview.textContent = empresa;
    } else {
        empresaPreview.textContent = 'Empresa';
    }
});

// Validación del formulario
document.getElementById('clienteForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const email = document.getElementById('email').value.trim();
    
    if (!nombre) {
        e.preventDefault();
        showAlert('El nombre es requerido', 'danger');
        return;
    }
    
    if (email && !validateEmail(email)) {
        e.preventDefault();
        showAlert('El email no tiene un formato válido', 'danger');
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    submitBtn.disabled = true;
    
    // Re-habilitar después de 3 segundos (por si hay error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});

// Función para validar email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>

<?php require_once 'includes/footer.php'; ?>
