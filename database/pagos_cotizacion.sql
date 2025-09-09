-- Tabla para registrar pagos de cotizaciones
CREATE TABLE IF NOT EXISTS pagos_cotizacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('efectivo', 'transferencia', 'tarjeta', 'cheque', 'otro') DEFAULT 'efectivo',
    referencia VARCHAR(255) NULL,
    observaciones TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Índices para mejorar rendimiento
CREATE INDEX idx_pagos_cotizacion_id ON pagos_cotizacion(cotizacion_id);
CREATE INDEX idx_pagos_fecha ON pagos_cotizacion(fecha_pago);
