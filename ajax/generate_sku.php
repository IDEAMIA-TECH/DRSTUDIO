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

// Función para generar SKU automático
function generateSKU($categoria_id = null) {
    global $conn;
    
    // Obtener prefijo de categoría
    $prefijo = 'PRD';
    if ($categoria_id) {
        $cat = getRecord('categorias', $categoria_id);
        if ($cat) {
            $prefijo = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cat['nombre']), 0, 3));
            if (strlen($prefijo) < 3) {
                $prefijo = 'PRD';
            }
        }
    }
    
    // Obtener el siguiente número
    $sql = "SELECT COUNT(*) as total FROM productos WHERE sku LIKE ?";
    $stmt = $conn->prepare($sql);
    $likePattern = $prefijo . '%';
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    // Generar SKU con formato: PREFIJO-YYYY-NNNN
    $year = date('Y');
    $numero = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefijo . '-' . $year . '-' . $numero;
}

try {
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $sku = generateSKU($categoria_id);
    
    echo json_encode([
        'success' => true,
        'sku' => $sku
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generando SKU: ' . $e->getMessage()
    ]);
}
?>
