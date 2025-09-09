<?php
/**
 * Ejecutor de Tests del Módulo de Portal Público - DT Studio
 * Ejecuta todos los tests del módulo de portal público usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE PORTAL PÚBLICO\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'PublicTest.php';

try {
    // Ejecutar tests de portal público
    echo "=== TESTS DE PORTAL PÚBLICO ===\n";
    $publicTest = new PublicTest();
    $publicTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
