<?php
$pageTitle = 'Editar Gasto';
require_once 'includes/header.php';

// Verificar permisos de administrador
if (!hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: gastos.php');
    exit;
}

// Obtener datos del gasto
$gasto = getRecord('gastos', ['id' => $id]);

if (!$gasto) {
    header('Location: gastos.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $concepto = sanitizeInput($_POST['concepto'] ?? '');
    $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
    $monto = floatval($_POST['monto'] ?? 0);
    $fecha_gasto = $_POST['fecha_gasto'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
    $estado = $_POST['estado'] ?? '';
    
    // Validaciones
    if (empty($concepto)) {
        $error = 'El concepto es requerido';
    } elseif (empty($monto) || $monto <= 0) {
        $error = 'El monto debe ser mayor a 0';
    } elseif (empty($fecha_gasto)) {
        $error = 'La fecha del gasto es requerida';
    } elseif (empty($categoria)) {
        $error = 'La categoría es requerida';
    } elseif (empty($metodo_pago)) {
        $error = 'El método de pago es requerido';
    } elseif (empty($estado)) {
        $error = 'El estado es requerido';
    } else {
        // Procesar archivo de comprobante si se subió uno nuevo
        $comprobante = $gasto['comprobante']; // Mantener el comprobante actual
        
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == 0) {
            $uploadDir = '../uploads/gastos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Eliminar comprobante anterior si existe
                if ($gasto['comprobante']) {
                    $oldFilePath = $uploadDir . $gasto['comprobante'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $filePath)) {
                    $comprobante = $fileName;
                } else {
                    $error = 'Error al subir el comprobante';
                }
            } else {
                $error = 'Tipo de archivo no permitido. Use: ' . implode(', ', $allowedExtensions);
            }
        }
        
        if (empty($error)) {
            // Actualizar registro de gasto
            $gasto_data = [
                'concepto' => $concepto,
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fecha_gasto' => $fecha_gasto,
                'categoria' => $categoria,
                'metodo_pago' => $metodo_pago,
                'comprobante' => $comprobante,
                'observaciones' => $observaciones,
                'estado' => $estado
            ];
            
            // Si se cambió el estado a aprobado o rechazado, registrar quien lo aprobó
            if ($estado != 'pendiente' && $gasto['estado'] == 'pendiente') {
                $gasto_data['aprobado_por'] = $currentUser['id'];
                $gasto_data['fecha_aprobacion'] = date('Y-m-d H:i:s');
            }
            
            if (updateRecord('gastos', $gasto_data, ['id' => $id])) {
                $success = 'Gasto actualizado exitosamente';
                // Recargar datos del gasto
                $gasto = getRecord('gastos', ['id' => $id]);
            } else {
                $error = 'Error al actualizar el gasto';
            }
        }
    }
}

$categorias = ['oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'otros'];
$metodos_pago = ['efectivo', 'tarjeta', 'transferencia', 'cheque'];
$estados = ['pendiente', 'aprobado', 'rechazado'];
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Editar Gasto #<?php echo $gasto['id']; ?></h1>
            <p class="text-muted">Modifica la información del gasto</p>
        </div>
        <div class="d-flex gap-2">
            <a href="gastos_view.php?id=<?php echo $gasto['id']; ?>" class="btn btn-outline-info">
                <i class="fas fa-eye me-2"></i>Ver
            </a>
            <a href="gastos.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Información del Gasto
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
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="concepto" class="form-label">Concepto *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="concepto" 
                                           name="concepto" 
                                           value="<?php echo htmlspecialchars($gasto['concepto']); ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor ingresa el concepto del gasto.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="monto" class="form-label">Monto *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="monto" 
                                               name="monto" 
                                               step="0.01" 
                                               min="0.01" 
                                               value="<?php echo $gasto['monto']; ?>" 
                                               required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor ingresa un monto válido.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Describe detalladamente el gasto..."><?php echo htmlspecialchars($gasto['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="fecha_gasto" class="form-label">Fecha del Gasto *</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="fecha_gasto" 
                                           name="fecha_gasto" 
                                           value="<?php echo $gasto['fecha_gasto']; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor selecciona la fecha del gasto.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="">Selecciona una categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria; ?>" <?php echo $gasto['categoria'] == $categoria ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($categoria); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor selecciona una categoría.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="metodo_pago" class="form-label">Método de Pago *</label>
                                    <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                        <option value="">Selecciona un método</option>
                                        <?php foreach ($metodos_pago as $metodo): ?>
                                            <option value="<?php echo $metodo; ?>" <?php echo $gasto['metodo_pago'] == $metodo ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($metodo); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor selecciona un método de pago.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?php echo $estado; ?>" <?php echo $gasto['estado'] == $estado ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($estado); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor selecciona un estado.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comprobante" class="form-label">Comprobante</label>
                            <?php if ($gasto['comprobante']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Comprobante actual: </small>
                                    <a href="../uploads/gastos/<?php echo htmlspecialchars($gasto['comprobante']); ?>" 
                                       target="_blank" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($gasto['comprobante']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" 
                                   class="form-control" 
                                   id="comprobante" 
                                   name="comprobante" 
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <div class="form-text">
                                Formatos permitidos: JPG, PNG, PDF, DOC, DOCX (Máximo 5MB)
                                <?php if ($gasto['comprobante']): ?>
                                    <br><small class="text-warning">Subir un nuevo archivo reemplazará el actual</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" 
                                      id="observaciones" 
                                      name="observaciones" 
                                      rows="3" 
                                      placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($gasto['observaciones']); ?></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Gasto
                            </button>
                            <a href="gastos_view.php?id=<?php echo $gasto['id']; ?>" class="btn btn-outline-info">
                                <i class="fas fa-eye me-2"></i>Ver Detalles
                            </a>
                            <a href="gastos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información del Gasto
                    </h6>
                </div>
                <div class="card-body">
                    <h6>ID del Gasto</h6>
                    <p class="text-muted">#<?php echo $gasto['id']; ?></p>
                    
                    <h6>Registrado por</h6>
                    <p class="text-muted">Usuario ID: <?php echo $gasto['usuario_id']; ?></p>
                    
                    <h6>Fecha de registro</h6>
                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($gasto['created_at'])); ?></p>
                    
                    <h6>Última actualización</h6>
                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($gasto['updated_at'])); ?></p>
                    
                    <?php if ($gasto['aprobado_por']): ?>
                    <h6>Aprobado por</h6>
                    <p class="text-muted">Usuario ID: <?php echo $gasto['aprobado_por']; ?></p>
                    
                    <h6>Fecha de aprobación</h6>
                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($gasto['fecha_aprobacion'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Validación del archivo
document.getElementById('comprobante').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (file.size > maxSize) {
            alert('El archivo es demasiado grande. Máximo 5MB.');
            e.target.value = '';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de archivo no permitido. Use: JPG, PNG, PDF, DOC, DOCX');
            e.target.value = '';
            return;
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
