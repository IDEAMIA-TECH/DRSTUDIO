<?php
/**
 * Ejecutor de Tests del Módulo de Pedidos - DT Studio
 * Ejecuta todos los tests del módulo de pedidos usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE PEDIDOS\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'OrderTest.php';

try {
    // Ejecutar tests de pedidos
    echo "=== TESTS DE PEDIDOS ===\n";
    $orderTest = new OrderTest();
    $orderTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
