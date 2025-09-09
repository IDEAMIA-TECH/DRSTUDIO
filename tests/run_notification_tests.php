<?php
/**
 * Ejecutor de Tests del Módulo de Sistema de Notificaciones - DT Studio
 * Ejecuta todos los tests del módulo de notificaciones usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE NOTIFICACIONES\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'NotificationTest.php';

try {
    // Ejecutar tests de sistema de notificaciones
    echo "=== TESTS DE SISTEMA DE NOTIFICACIONES ===\n";
    $notificationTest = new NotificationTest();
    $notificationTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
