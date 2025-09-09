<?php
/**
 * Ejecutor de Tests del Módulo de Cotizaciones - DT Studio
 * Ejecuta todos los tests del módulo de cotizaciones usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE COTIZACIONES\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'QuotationTest.php';

try {
    // Ejecutar tests de cotizaciones
    echo "=== TESTS DE COTIZACIONES ===\n";
    $quotationTest = new QuotationTest();
    $quotationTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
