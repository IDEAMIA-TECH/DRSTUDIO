<?php
/**
 * Empleados, sueldos y registro automático como gasto
 */

function ensureSueldosTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS empleados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        puesto VARCHAR(100) NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS sueldos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empleado_id INT NOT NULL,
        monto DECIMAL(12,2) NOT NULL,
        fecha_pago DATE NOT NULL,
        periodo VARCHAR(7) NOT NULL,
        metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'cheque') NOT NULL DEFAULT 'transferencia',
        observaciones TEXT NULL,
        gasto_id INT NULL,
        usuario_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha_pago (fecha_pago),
        INDEX idx_periodo (periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    @$conn->query("ALTER TABLE gastos MODIFY COLUMN categoria
        ENUM('oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'sueldos', 'otros')
        NOT NULL DEFAULT 'otros'");
}

function registrarSueldoConGasto($conn, $data, $usuarioId) {
    ensureSueldosTables($conn);

    $empleadoId = (int) $data['empleado_id'];
    $monto = (float) $data['monto'];
    $fechaPago = $data['fecha_pago'];
    $periodo = $data['periodo'];
    $metodoPago = $data['metodo_pago'];
    $observaciones = $data['observaciones'] ?? '';

    $emp = getRecord('empleados', $empleadoId);
    if (!$emp) {
        return ['success' => false, 'message' => 'Empleado no encontrado'];
    }

    $concepto = 'Sueldo - ' . $emp['nombre'] . ' (' . $periodo . ')';
    $descripcion = 'Pago de nómina correspondiente al período ' . $periodo;
    if ($observaciones) {
        $descripcion .= '. ' . $observaciones;
    }

    $gastoData = [
        'concepto' => $concepto,
        'descripcion' => $descripcion,
        'monto' => $monto,
        'fecha_gasto' => $fechaPago,
        'categoria' => 'sueldos',
        'metodo_pago' => $metodoPago,
        'observaciones' => $observaciones,
        'estado' => 'aprobado',
        'usuario_id' => $usuarioId,
        'aprobado_por' => $usuarioId,
        'fecha_aprobacion' => date('Y-m-d H:i:s'),
    ];

    if (!createRecord('gastos', $gastoData)) {
        return ['success' => false, 'message' => 'Error al crear el gasto del sueldo'];
    }

    $gastoId = $conn->insert_id;

    $sql = "INSERT INTO sueldos (empleado_id, monto, fecha_pago, periodo, metodo_pago, observaciones, gasto_id, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('idssssii', $empleadoId, $monto, $fechaPago, $periodo, $metodoPago, $observaciones, $gastoId, $usuarioId);

    if (!$stmt->execute()) {
        $conn->query("DELETE FROM gastos WHERE id = $gastoId");
        return ['success' => false, 'message' => 'Error al registrar el sueldo'];
    }

    return [
        'success' => true,
        'message' => 'Sueldo registrado y reflejado como gasto (aprobado)',
        'sueldo_id' => $conn->insert_id,
        'gasto_id' => $gastoId,
    ];
}

function eliminarSueldo($conn, $sueldoId) {
    $sueldo = getRecord('sueldos', $sueldoId);
    if (!$sueldo) {
        return ['success' => false, 'message' => 'Sueldo no encontrado'];
    }

    if ($sueldo['gasto_id']) {
        $conn->query('DELETE FROM gastos WHERE id = ' . (int) $sueldo['gasto_id']);
    }
    $conn->query('DELETE FROM sueldos WHERE id = ' . (int) $sueldoId);

    return ['success' => true, 'message' => 'Sueldo y gasto asociado eliminados'];
}
