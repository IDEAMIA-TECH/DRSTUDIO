<?php
require_once 'includes/config.php';

echo "Agregando campo token_aceptacion a la tabla cotizaciones...\n";

$sql = "ALTER TABLE cotizaciones ADD COLUMN token_aceptacion VARCHAR(255) NULL AFTER observaciones";
$result = $conn->query($sql);

if ($result) {
    echo "Campo token_aceptacion agregado exitosamente.\n";
    
    // Generar tokens para cotizaciones existentes
    $cotizaciones = $conn->query("SELECT id FROM cotizaciones WHERE token_aceptacion IS NULL");
    while ($row = $cotizaciones->fetch_assoc()) {
        $token = bin2hex(random_bytes(32));
        $updateSql = "UPDATE cotizaciones SET token_aceptacion = '$token' WHERE id = " . $row['id'];
        $conn->query($updateSql);
        echo "Token generado para cotización ID " . $row['id'] . ": $token\n";
    }
    
    echo "Proceso completado.\n";
} else {
    echo "Error agregando campo: " . $conn->error . "\n";
}
?>
