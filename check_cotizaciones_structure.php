<?php
require_once 'includes/config.php';

echo "=== ESTRUCTURA DE LA TABLA SOLICITUDES_COTIZACION ===\n";

$result = $conn->query("DESCRIBE solicitudes_cotizacion");
if ($result) {
    echo "Campos disponibles:\n";
    echo "----------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'], 
            $row['Extra']
        );
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== MUESTRA DE DATOS ===\n";
$result = $conn->query("SELECT id, productos_interes, cantidad_estimada, estado FROM solicitudes_cotizacion LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Productos: {$row['productos_interes']}, Cantidad: {$row['cantidad_estimada']}, Estado: {$row['estado']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
