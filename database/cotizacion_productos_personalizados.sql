-- Tabla para productos personalizados en cotizaciones
CREATE TABLE IF NOT EXISTS `cotizacion_productos_personalizados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int(11) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `talla` varchar(50) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_venta` decimal(10,2) NOT NULL,
  `costo_fabricacion` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `costo_total` decimal(10,2) NOT NULL,
  `ganancia` decimal(10,2) NOT NULL,
  `margen_ganancia` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cotizacion` (`cotizacion_id`),
  CONSTRAINT `cotizacion_productos_personalizados_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Productos personalizados ingresados directamente en cotizaciones';
