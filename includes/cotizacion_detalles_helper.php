<?php
/**
 * Helper para manejar detalles de cotización y cálculos de ganancia
 */

/**
 * Crear detalles de cotización basados en productos mencionados
 */
function crearDetallesCotizacion($cotizacion_id, $productos_interes, $cantidad_estimada) {
    global $conn;
    
    // Buscar productos mencionados en el texto
    $productos_sql = "SELECT id, nombre, precio_venta, costo_fabricacion FROM productos WHERE activo = 1";
    $productos_result = $conn->query($productos_sql);
    $productos = $productos_result->fetch_all(MYSQLI_ASSOC);
    
    $detalles_creados = 0;
    $cantidad_base = is_numeric($cantidad_estimada) ? intval($cantidad_estimada) : 10;
    
    foreach ($productos as $producto) {
        // Buscar si el producto está mencionado en el texto de interés
        if (stripos($productos_interes, $producto['nombre']) !== false || 
            stripos($productos_interes, $producto['id']) !== false) {
            
            // Calcular cantidad (puede variar por producto)
            $cantidad = $cantidad_base + rand(-5, 10);
            $cantidad = max(1, $cantidad); // Mínimo 1
            
            $precio_unitario = $producto['precio_venta'];
            $costo_unitario = $producto['costo_fabricacion'];
            $subtotal = $precio_unitario * $cantidad;
            $costo_total = $costo_unitario * $cantidad;
            $ganancia = $subtotal - $costo_total;
            $margen_ganancia = $subtotal > 0 ? ($ganancia / $subtotal) * 100 : 0;
            
            $insert_sql = "INSERT INTO cotizacion_detalles 
                          (cotizacion_id, producto_id, cantidad, precio_unitario, costo_unitario, 
                           subtotal, costo_total, ganancia, margen_ganancia) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('iiidddddd', 
                $cotizacion_id, 
                $producto['id'], 
                $cantidad, 
                $precio_unitario, 
                $costo_unitario, 
                $subtotal, 
                $costo_total, 
                $ganancia, 
                $margen_ganancia
            );
            
            if ($stmt->execute()) {
                $detalles_creados++;
            }
        }
    }
    
    return $detalles_creados;
}

/**
 * Obtener resumen de ganancias para una cotización
 */
function obtenerResumenGanancias($cotizacion_id) {
    global $conn;
    
    $sql = "SELECT 
        COUNT(*) as total_productos,
        SUM(cantidad) as total_cantidad,
        SUM(subtotal) as total_ventas,
        SUM(costo_total) as total_costos,
        SUM(ganancia) as total_ganancia,
        AVG(margen_ganancia) as margen_promedio
    FROM cotizacion_detalles 
    WHERE cotizacion_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $cotizacion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Obtener productos más rentables
 */
function obtenerProductosMasRentables($limite = 10) {
    global $conn;
    
    $sql = "SELECT 
        p.nombre as producto_nombre,
        p.sku,
        SUM(cd.cantidad) as total_cantidad,
        SUM(cd.subtotal) as total_ventas,
        SUM(cd.costo_total) as total_costos,
        SUM(cd.ganancia) as total_ganancia,
        AVG(cd.margen_ganancia) as margen_promedio
    FROM cotizacion_detalles cd
    LEFT JOIN productos p ON cd.producto_id = p.id
    GROUP BY p.id, p.nombre, p.sku
    ORDER BY total_ganancia DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtener ganancias por período
 */
function obtenerGananciasPorPeriodo($fecha_desde, $fecha_hasta) {
    global $conn;
    
    $sql = "SELECT 
        DATE(c.created_at) as fecha,
        COUNT(DISTINCT cd.cotizacion_id) as cotizaciones,
        SUM(cd.cantidad) as total_cantidad,
        SUM(cd.subtotal) as total_ventas,
        SUM(cd.costo_total) as total_costos,
        SUM(cd.ganancia) as total_ganancia
    FROM cotizacion_detalles cd
    LEFT JOIN solicitudes_cotizacion c ON cd.cotizacion_id = c.id
    WHERE c.created_at BETWEEN ? AND ?
    GROUP BY DATE(c.created_at)
    ORDER BY fecha ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
