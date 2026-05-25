-- Empleados y sueldos (generan gasto automático en categoría sueldos)

CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    puesto VARCHAR(100) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sueldos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha_pago DATE NOT NULL COMMENT 'Fecha contable del gasto',
    periodo VARCHAR(7) NOT NULL COMMENT 'YYYY-MM del período pagado',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'cheque') NOT NULL DEFAULT 'transferencia',
    observaciones TEXT NULL,
    gasto_id INT NULL COMMENT 'Gasto generado en tabla gastos',
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE RESTRICT,
    FOREIGN KEY (gasto_id) REFERENCES gastos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar categoría sueldos (ejecutar si la columna ya existe)
ALTER TABLE gastos
    MODIFY COLUMN categoria ENUM('oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'sueldos', 'otros')
    NOT NULL DEFAULT 'otros';
