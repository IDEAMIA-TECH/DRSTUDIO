<?php
/**
 * Ejecutor de Tests del Módulo de Productos - DT Studio
 * Ejecuta todos los tests del módulo de productos usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE PRODUCTOS\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'CategoryTest.php';
require_once 'ProductTest.php';

try {
    // Ejecutar tests de categorías
    echo "=== TESTS DE CATEGORÍAS ===\n";
    $categoryTest = new CategoryTest();
    $categoryTest->runAllTests();
    
    echo "\n";
    
    // Ejecutar tests de productos
    echo "=== TESTS DE PRODUCTOS ===\n";
    $productTest = new ProductTest();
    $productTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
