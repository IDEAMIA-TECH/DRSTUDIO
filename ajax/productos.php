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
        $categoria_id = (int)$_POST['categoria_id'];
        $sku = sanitizeInput($_POST['sku']);
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $precio_venta = (float)$_POST['precio_venta'];
        $costo_fabricacion = (float)$_POST['costo_fabricacion'];
        $tiempo_entrega = (int)$_POST['tiempo_entrega'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validar datos
        if (empty($sku) || empty($nombre) || $precio_venta <= 0 || $costo_fabricacion <= 0) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados correctamente']);
            exit;
        }
        
        // Verificar si ya existe un producto con el mismo SKU
        $existing = readRecords('productos', ["sku = '$sku'"]);
        if (!empty($existing)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un producto con este SKU']);
            exit;
        }
        
        // Procesar imagen principal
        $imagen_principal = '';
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
            $imagen_principal = uploadFile($_FILES['imagen_principal'], '../uploads/productos/');
            if (!$imagen_principal) {
                echo json_encode(['success' => false, 'message' => 'Error al subir la imagen principal']);
                exit;
            }
        }
        
        // Crear producto
        $data = [
            'categoria_id' => $categoria_id ?: null,
            'sku' => $sku,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_venta' => $precio_venta,
            'costo_fabricacion' => $costo_fabricacion,
            'tiempo_entrega' => $tiempo_entrega,
            'imagen_principal' => $imagen_principal,
            'destacado' => $destacado,
            'activo' => $activo
        ];
        
        if (createRecord('productos', $data)) {
            $producto_id = $conn->insert_id;
            
            // Procesar variantes si se enviaron
            if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                foreach ($_POST['variantes'] as $variante) {
                    if (!empty($variante['talla']) || !empty($variante['color']) || !empty($variante['material'])) {
                        $varianteData = [
                            'producto_id' => $producto_id,
                            'talla' => $variante['talla'],
                            'color' => $variante['color'],
                            'material' => $variante['material'],
                            'stock' => (int)$variante['stock'],
                            'precio_extra' => (float)$variante['precio_extra']
                        ];
                        createRecord('variantes_producto', $varianteData);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente', 'id' => $producto_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el producto']);
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $categoria_id = (int)$_POST['categoria_id'];
        $sku = sanitizeInput($_POST['sku']);
        $nombre = sanitizeInput($_POST['nombre']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $precio_venta = (float)$_POST['precio_venta'];
        $costo_fabricacion = (float)$_POST['costo_fabricacion'];
        $tiempo_entrega = (int)$_POST['tiempo_entrega'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validar datos
        if (empty($sku) || empty($nombre) || $precio_venta <= 0 || $costo_fabricacion <= 0) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados correctamente']);
            exit;
        }
        
        // Verificar si ya existe otro producto con el mismo SKU
        $existing = readRecords('productos', ["sku = '$sku'", "id != $id"]);
        if (!empty($existing)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro producto con este SKU']);
            exit;
        }
        
        // Obtener producto actual
        $producto = getRecord('productos', $id);
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }
        
        // Procesar imagen principal
        $imagen_principal = $producto['imagen_principal']; // Mantener imagen actual por defecto
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
            $newImagen = uploadFile($_FILES['imagen_principal'], '../uploads/productos/');
            if ($newImagen) {
                // Eliminar imagen anterior si existe
                if ($imagen_principal && file_exists('../' . $imagen_principal)) {
                    deleteFile('../' . $imagen_principal);
                }
                $imagen_principal = $newImagen;
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al subir la imagen principal']);
                exit;
            }
        }
        
        // Actualizar producto
        $data = [
            'categoria_id' => $categoria_id ?: null,
            'sku' => $sku,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_venta' => $precio_venta,
            'costo_fabricacion' => $costo_fabricacion,
            'tiempo_entrega' => $tiempo_entrega,
            'imagen_principal' => $imagen_principal,
            'destacado' => $destacado,
            'activo' => $activo
        ];
        
        if (updateRecord('productos', $data, $id)) {
            // Actualizar variantes
            if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                // Eliminar variantes existentes
                $conn->query("DELETE FROM variantes_producto WHERE producto_id = $id");
                
                // Crear nuevas variantes
                foreach ($_POST['variantes'] as $variante) {
                    if (!empty($variante['talla']) || !empty($variante['color']) || !empty($variante['material'])) {
                        $varianteData = [
                            'producto_id' => $id,
                            'talla' => $variante['talla'],
                            'color' => $variante['color'],
                            'material' => $variante['material'],
                            'stock' => (int)$variante['stock'],
                            'precio_extra' => (float)$variante['precio_extra']
                        ];
                        createRecord('variantes_producto', $varianteData);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Obtener producto para eliminar imagen
        $producto = getRecord('productos', $id);
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }
        
        // Eliminar imagen principal si existe
        if ($producto['imagen_principal'] && file_exists('../' . $producto['imagen_principal'])) {
            deleteFile('../' . $producto['imagen_principal']);
        }
        
        // Eliminar variantes (se eliminan automáticamente por CASCADE)
        $conn->query("DELETE FROM variantes_producto WHERE producto_id = $id");
        
        // Eliminar producto
        if (deleteRecord('productos', $id, false)) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto']);
        }
        break;
        
    case 'toggle_status':
        $id = (int)$_POST['id'];
        $activo = (int)$_POST['activo'];
        
        $data = ['activo' => $activo];
        if (updateRecord('productos', $data, $id)) {
            $status = $activo ? 'activado' : 'desactivado';
            echo json_encode(['success' => true, 'message' => "Producto $status exitosamente"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
        }
        break;
        
    case 'toggle_destacado':
        $id = (int)$_POST['id'];
        $destacado = (int)$_POST['destacado'];
        
        $data = ['destacado' => $destacado];
        if (updateRecord('productos', $data, $id)) {
            $status = $destacado ? 'marcado como destacado' : 'desmarcado como destacado';
            echo json_encode(['success' => true, 'message' => "Producto $status exitosamente"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado de destacado']);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $producto = getRecord('productos', $id);
        
        if ($producto) {
            // Obtener variantes
            $variantes = readRecords('variantes_producto', ["producto_id = $id"], null, 'id ASC');
            $producto['variantes'] = $variantes;
            
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }
        break;
        
    case 'list':
        $categoria_id = $_POST['categoria_id'] ?? '';
        $busqueda = $_POST['busqueda'] ?? '';
        $estado = $_POST['estado'] ?? '';
        
        // Construir condiciones de búsqueda
        $conditions = [];
        if ($categoria_id) {
            $conditions[] = "p.categoria_id = $categoria_id";
        }
        if ($busqueda) {
            $conditions[] = "(p.nombre LIKE '%$busqueda%' OR p.sku LIKE '%$busqueda%' OR p.descripcion LIKE '%$busqueda%')";
        }
        if ($estado !== '') {
            $conditions[] = "p.activo = $estado";
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT p.*, c.nombre as categoria_nombre 
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                $whereClause 
                ORDER BY p.created_at DESC";
        
        $result = $conn->query($sql);
        $productos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        echo json_encode(['success' => true, 'data' => $productos]);
        break;
        
    case 'update_stock':
        $variante_id = (int)$_POST['variante_id'];
        $stock = (int)$_POST['stock'];
        
        $data = ['stock' => $stock];
        if (updateRecord('variantes_producto', $data, $variante_id)) {
            echo json_encode(['success' => true, 'message' => 'Stock actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el stock']);
        }
        break;
        
    case 'get_variantes':
        $producto_id = (int)$_POST['producto_id'];
        $variantes = readRecords('variantes_producto', ["producto_id = $producto_id", "activo = 1"], null, 'id ASC');
        echo json_encode(['success' => true, 'data' => $variantes]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
