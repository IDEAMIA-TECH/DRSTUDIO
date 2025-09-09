<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Generar token similar al de EmailSender
$cotizacionId = 3;
$data = $cotizacionId . '_' . time() . '_' . rand(1000, 9999);
$token = base64_encode($data);

echo "Token generado: " . $token . "\n";
echo "Token decodificado: " . base64_decode($token) . "\n";

// Guardar token en la base de datos
$stmt = $conn->prepare("UPDATE cotizaciones SET token_aceptacion = ? WHERE id = ?");
$stmt->bind_param("si", $token, $cotizacionId);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo "Token guardado exitosamente en la base de datos\n";
    
    // Verificar que se guardó correctamente
    $stmt = $conn->prepare("SELECT token_aceptacion FROM cotizaciones WHERE id = ?");
    $stmt->bind_param("i", $cotizacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    echo "Token en BD: " . $row['token_aceptacion'] . "\n";
    echo "URL de aceptación: https://dtstudio.com.mx/aceptar-cotizacion.php?token=" . $token . "\n";
} else {
    echo "Error guardando token\n";
}
?>
