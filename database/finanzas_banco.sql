-- Configuración de saldos bancarios y conciliación (desde enero 2026)
CREATE TABLE IF NOT EXISTS finanzas_config (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    saldo_inicial_monto DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo en banco al inicio del período',
    saldo_inicial_fecha DATE NOT NULL DEFAULT '2026-01-01',
    saldo_banco_actual DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo manual según estado de cuenta',
    saldo_banco_fecha DATE NULL COMMENT 'Fecha del saldo manual',
    notas TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_finanzas_config_single CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO finanzas_config (id, saldo_inicial_fecha) VALUES (1, '2026-01-01');

CREATE TABLE IF NOT EXISTS saldo_banco_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monto DECIMAL(12,2) NOT NULL,
    fecha_registro DATE NOT NULL,
    notas TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
