<?php
/**
 * Fecha de venta = cotizaciones.created_at (solo administrador)
 */

function normalizarFechaVentaCotizacion($fechaInput) {
    $fechaInput = trim($fechaInput);
    if ($fechaInput === '') {
        return null;
    }
    $fechaInput = str_replace('T', ' ', $fechaInput);
    $timestamp = strtotime($fechaInput);
    if ($timestamp === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $timestamp);
}

function fechaVentaParaInputDatetime($createdAt) {
    return date('Y-m-d\TH:i', strtotime($createdAt));
}

function validarFechaVentaCotizacion($fechaVenta) {
    if (strtotime($fechaVenta) > time()) {
        return 'La fecha de venta no puede ser futura';
    }
    return null;
}

function actualizarFechaVentaCotizacion($conn, $cotizacionId, $fechaVenta) {
    $stmt = $conn->prepare('UPDATE cotizaciones SET created_at = ? WHERE id = ?');
    $stmt->bind_param('si', $fechaVenta, $cotizacionId);
    return $stmt->execute();
}
