<?php
/**
 * Ejecutor de Tests Local - DT Studio
 * Ejecuta todos los tests del sistema usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL SISTEMA DT STUDIO (LOCAL)\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'UserTestLocal.php';

try {
    // Ejecutar tests de usuarios
    $userTest = new UserTestLocal();
    $userTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
