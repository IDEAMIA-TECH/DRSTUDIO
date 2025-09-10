<?php
// Funciones auxiliares para el sistema

// Función para crear registros
function createRecord($table, $data) {
    global $conn;
    
    $fields = implode(',', array_keys($data));
    $placeholders = str_repeat('?,', count($data) - 1) . '?';
    
    $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Detectar tipos de datos automáticamente
        $types = '';
        $values = array_values($data);
        
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }
    return false;
}

// Función para leer registros
function readRecords($table, $conditions = [], $limit = null, $orderBy = null) {
    global $conn;
    
    $sql = "SELECT * FROM $table";
    
    if (!empty($conditions)) {
        $whereClause = implode(' AND ', $conditions);
        $sql .= " WHERE $whereClause";
    }
    
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Función para obtener un registro específico
function getRecord($table, $id) {
    global $conn;
    
    $sql = "SELECT * FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Función para actualizar registros
function updateRecord($table, $data, $id) {
    global $conn;
    
    error_log("updateRecord - Iniciando actualización");
    error_log("updateRecord - Tabla: $table");
    error_log("updateRecord - ID: $id");
    error_log("updateRecord - Datos: " . print_r($data, true));
    
    if (empty($data) || !is_array($data)) {
        error_log("updateRecord - ERROR: Datos vacíos o no es array");
        return false;
    }
    
    $setClause = [];
    foreach ($data as $key => $value) {
        $setClause[] = "$key = ?";
    }
    $setClause = implode(', ', $setClause);
    
    $sql = "UPDATE $table SET $setClause WHERE id = ?";
    error_log("updateRecord - SQL: $sql");
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("updateRecord - ERROR preparando consulta: " . $conn->error);
        error_log("updateRecord - SQL: " . $sql);
        return false;
    }
    
    // Detectar tipos de datos automáticamente
    $types = '';
    $values = array_values($data);
    
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    // Agregar el ID al final (siempre integer)
    $types .= 'i';
    $values[] = $id;
    
    error_log("updateRecord - Tipos: $types");
    error_log("updateRecord - Valores: " . print_r($values, true));
    
    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    
    error_log("updateRecord - Resultado execute: " . ($result ? 'TRUE' : 'FALSE'));
    
    if (!$result) {
        $errorMsg = "Error ejecutando UPDATE: " . $stmt->error;
        $errorMsg .= "\nSQL: " . $sql;
        $errorMsg .= "\nTipos: " . $types;
        $errorMsg .= "\nValores: " . print_r($values, true);
        error_log("updateRecord - ERROR: " . $errorMsg);
    } else {
        error_log("updateRecord - Filas afectadas: " . $stmt->affected_rows);
    }
    
    $stmt->close();
    return $result;
}

// Función para eliminar registros (soft delete)
function deleteRecord($table, $id, $soft = true) {
    global $conn;
    
    if ($soft) {
        $sql = "UPDATE $table SET activo = 0 WHERE id = ?";
    } else {
        $sql = "DELETE FROM $table WHERE id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparando consulta DELETE: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Error ejecutando DELETE: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

// Función para sanitizar datos
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para subir archivos
function uploadFile($file, $targetDir = UPLOAD_PATH) {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFile = $targetDir . time() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Verificar tipo de archivo
    $allowedTypes = array("jpg", "jpeg", "png", "gif", "webp");
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    // Verificar tamaño
    if ($file["size"] > MAX_FILE_SIZE) {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    }
    
    // Fallback: usar copy si move_uploaded_file falla (útil para testing)
    if (copy($file["tmp_name"], $targetFile)) {
        return $targetFile;
    }
    
    return false;
}

// Función para eliminar archivo
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

// Función para formatear fecha
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Función para generar paginación
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $pagination = '<nav><ul class="pagination">';
    
    // Botón anterior
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Anterior</a></li>';
    }
    
    // Números de página
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Botón siguiente
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Siguiente</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}
?>
