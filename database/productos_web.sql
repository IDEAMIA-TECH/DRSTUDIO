-- Visibilidad de productos en el catálogo público (productos.php, inicio, etc.)
-- Ejecutar una vez en la base de datos de producción.

ALTER TABLE productos
    ADD COLUMN mostrar_web TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = visible en sitio público, 0 = solo uso interno/cotizaciones'
    AFTER activo;

-- Ocultar por defecto servicios de diseño (uso interno)
UPDATE productos p
INNER JOIN categorias c ON p.categoria_id = c.id
SET p.mostrar_web = 0
WHERE LOWER(TRIM(c.nombre)) IN ('diseño', 'diseno');

-- Servicios / insumos típicos de cotización interna (ajustar según su catálogo)
UPDATE productos SET mostrar_web = 0
WHERE LOWER(nombre) LIKE '%corte y planchado%'
   OR LOWER(nombre) LIKE '%vinil fut%'
   OR LOWER(nombre) LIKE 'etiquetas vinil adhesivo%'
   OR LOWER(nombre) LIKE 'diseño tarjetas%'
   OR LOWER(nombre) LIKE 'diseno tarjetas%';

-- Revisar en Admin > Productos los que sigan visibles y desmarcar "Mostrar en sitio web"
