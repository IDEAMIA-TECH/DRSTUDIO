<?php
/**
 * Productos visibles en el sitio público (excluye ítems solo para cotizaciones internas).
 */

function productosTieneColumnaWeb($conn = null): bool {
    global $conn;
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $db = $conn;
    if (!$db) {
        return false;
    }
    $result = $db->query("SHOW COLUMNS FROM productos LIKE 'mostrar_web'");
    $cache = $result && $result->num_rows > 0;
    return $cache;
}

function condicionesProductosPublicos(): array {
    global $conn;
    $conditions = ['activo = 1'];

    if (productosTieneColumnaWeb($conn)) {
        $conditions[] = 'mostrar_web = 1';
        return $conditions;
    }

    $ids = idsCategoriasSoloSistema();
    if (!empty($ids)) {
        $conditions[] = 'categoria_id IS NULL OR categoria_id NOT IN (' . implode(',', $ids) . ')';
    }

    return $conditions;
}

function idsCategoriasSoloSistema(): array {
    global $conn;
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $ids = [];
    $result = $conn->query(
        "SELECT id FROM categorias WHERE LOWER(TRIM(nombre)) IN ('diseño', 'diseno')"
    );
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
    }
    return $ids;
}

function productoEsPublico(array $producto): bool {
    if (empty($producto['activo'])) {
        return false;
    }
    global $conn;
    if (productosTieneColumnaWeb($conn)) {
        return !empty($producto['mostrar_web']);
    }
    $catId = (int) ($producto['categoria_id'] ?? 0);
    return $catId === 0 || !in_array($catId, idsCategoriasSoloSistema(), true);
}

function getCategoriasPublicas(): array {
    global $conn;

    if (productosTieneColumnaWeb($conn)) {
        $sql = "SELECT DISTINCT c.*
                FROM categorias c
                INNER JOIN productos p ON p.categoria_id = c.id
                WHERE c.activo = 1 AND p.activo = 1 AND p.mostrar_web = 1
                ORDER BY c.nombre ASC";
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $excluir = idsCategoriasSoloSistema();
    $conditions = ['activo = 1'];
    if (!empty($excluir)) {
        $conditions[] = 'id NOT IN (' . implode(',', $excluir) . ')';
    }
    return readRecords('categorias', $conditions, null, 'nombre ASC');
}
