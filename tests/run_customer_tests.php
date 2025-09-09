<?php
/**
 * Ejecutor de Tests del Módulo de Clientes - DT Studio
 * Ejecuta todos los tests del módulo de clientes usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE CLIENTES\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'CustomerTest.php';

try {
    // Ejecutar tests de clientes
    echo "=== TESTS DE CLIENTES ===\n";
    $customerTest = new CustomerTest();
    $customerTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
