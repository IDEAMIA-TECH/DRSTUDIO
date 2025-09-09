<?php
/**
 * Ejecutor de Tests del Módulo de Sistema de Inventario - DT Studio
 * Ejecuta todos los tests del módulo de inventario usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE INVENTARIO\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'InventoryTest.php';

try {
    // Ejecutar tests de sistema de inventario
    echo "=== TESTS DE SISTEMA DE INVENTARIO ===\n";
    $inventoryTest = new InventoryTest();
    $inventoryTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
