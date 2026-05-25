<?php
header('Content-Type: application/json');
require_once '../includes/paths.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes']);
    exit;
}

const FECHA_MINIMA_FINANZAS = '2026-01-01';

function ensureFinanzasTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS finanzas_config (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
        saldo_inicial_monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_inicial_fecha DATE NOT NULL DEFAULT '2026-01-01',
        saldo_banco_actual DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        saldo_banco_fecha DATE NULL,
        notas TEXT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("INSERT IGNORE INTO finanzas_config (id, saldo_inicial_fecha) VALUES (1, '2026-01-01')");

    $conn->query("CREATE TABLE IF NOT EXISTS saldo_banco_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        monto DECIMAL(12,2) NOT NULL,
        fecha_registro DATE NOT NULL,
        notas TEXT NULL,
        usuario_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fecha_registro (fecha_registro)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensureFinanzasTables($conn);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'guardar_saldos':
        guardarSaldos();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function guardarSaldos() {
    global $conn;

    $saldoInicial = isset($_POST['saldo_inicial_monto']) ? (float) $_POST['saldo_inicial_monto'] : null;
    $saldoBanco = isset($_POST['saldo_banco_actual']) ? (float) $_POST['saldo_banco_actual'] : null;
    $fechaBanco = $_POST['saldo_banco_fecha'] ?? date('Y-m-d');
    $notas = trim($_POST['notas'] ?? '');

    if ($saldoInicial === null || $saldoBanco === null) {
        echo json_encode(['success' => false, 'message' => 'Los montos son requeridos']);
        return;
    }

    if ($saldoInicial < 0 || $saldoBanco < 0) {
        echo json_encode(['success' => false, 'message' => 'Los montos no pueden ser negativos']);
        return;
    }

    if ($fechaBanco < FECHA_MINIMA_FINANZAS) {
        echo json_encode(['success' => false, 'message' => 'La fecha del saldo debe ser desde enero 2026']);
        return;
    }

    $usuarioId = (int) $_SESSION['user_id'];

    $sql = "UPDATE finanzas_config SET
        saldo_inicial_monto = ?,
        saldo_inicial_fecha = ?,
        saldo_banco_actual = ?,
        saldo_banco_fecha = ?,
        notas = ?,
        updated_by = ?
        WHERE id = 1";

    $stmt = $conn->prepare($sql);
    $fechaInicio = FECHA_MINIMA_FINANZAS;
    $stmt->bind_param('ddsdsi', $saldoInicial, $fechaInicio, $saldoBanco, $fechaBanco, $notas, $usuarioId);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la configuración']);
        return;
    }

    $histSql = "INSERT INTO saldo_banco_historial (monto, fecha_registro, notas, usuario_id) VALUES (?, ?, ?, ?)";
    $histStmt = $conn->prepare($histSql);
    $histStmt->bind_param('dssi', $saldoBanco, $fechaBanco, $notas, $usuarioId);
    $histStmt->execute();

    echo json_encode(['success' => true, 'message' => 'Saldos bancarios guardados correctamente']);
}
