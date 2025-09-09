<?php
/**
 * Ejecutor de Tests del Módulo de Sistema de Pagos - DT Studio
 * Ejecuta todos los tests del módulo de pagos usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE SISTEMA DE PAGOS\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'PaymentTest.php';

try {
    // Ejecutar tests de sistema de pagos
    echo "=== TESTS DE SISTEMA DE PAGOS ===\n";
    $paymentTest = new PaymentTest();
    $paymentTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
