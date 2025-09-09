<?php
// Script para actualizar la base de datos con los nuevos campos para el sistema de correos
require_once 'includes/config.php';

echo "Actualizando base de datos para sistema de correos...\n";

try {
    // Agregar nuevo estado al ENUM
    $sql1 = "ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM('pendiente', 'enviada', 'aceptada', 'rechazada', 'cancelada', 'en_espera_deposito') DEFAULT 'pendiente'";
    $result1 = $conn->query($sql1);
    
    if ($result1) {
        echo "✅ Estado 'en_espera_deposito' agregado exitosamente\n";
    } else {
        echo "❌ Error agregando estado: " . $conn->error . "\n";
    }
    
    // Agregar campo fecha_aceptacion
    $sql2 = "ALTER TABLE cotizaciones ADD COLUMN fecha_aceptacion TIMESTAMP NULL AFTER fecha_vencimiento";
    $result2 = $conn->query($sql2);
    
    if ($result2) {
        echo "✅ Campo 'fecha_aceptacion' agregado exitosamente\n";
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "ℹ️  Campo 'fecha_aceptacion' ya existe\n";
        } else {
            echo "❌ Error agregando campo fecha_aceptacion: " . $conn->error . "\n";
        }
    }
    
    echo "\n🎉 Actualización de base de datos completada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
