-- Tabla para reportar gastos operacionales
CREATE TABLE IF NOT EXISTS gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto VARCHAR(255) NOT NULL COMMENT 'Concepto del gasto',
    descripcion TEXT COMMENT 'Descripción detallada del gasto',
    monto DECIMAL(10,2) NOT NULL COMMENT 'Monto del gasto',
    fecha_gasto DATE NOT NULL COMMENT 'Fecha en que se realizó el gasto',
    categoria ENUM('oficina', 'marketing', 'equipos', 'servicios', 'viajes', 'otros') NOT NULL DEFAULT 'otros' COMMENT 'Categoría del gasto',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'cheque') NOT NULL DEFAULT 'efectivo' COMMENT 'Método de pago utilizado',
    comprobante VARCHAR(255) COMMENT 'Nombre del archivo del comprobante',
    observaciones TEXT COMMENT 'Observaciones adicionales',
    estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado del gasto',
    usuario_id INT NOT NULL COMMENT 'ID del usuario que reportó el gasto',
    aprobado_por INT NULL COMMENT 'ID del usuario que aprobó el gasto',
    fecha_aprobacion DATETIME NULL COMMENT 'Fecha y hora de aprobación',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_fecha_gasto (fecha_gasto),
    INDEX idx_categoria (categoria),
    INDEX idx_estado (estado),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para reportar gastos operacionales';
