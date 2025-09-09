<?php
/**
 * Ejecutor de Tests del Módulo de Reportes y Analytics - DT Studio
 * Ejecuta todos los tests del módulo de reportes usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE REPORTES\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'ReportTest.php';

try {
    // Ejecutar tests de reportes
    echo "=== TESTS DE REPORTES ===\n";
    $reportTest = new ReportTest();
    $reportTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
