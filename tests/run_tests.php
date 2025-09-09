<?php
/**
 * Ejecutor de Tests - DT Studio
 * Ejecuta todos los tests del sistema
 */

echo "========================================\n";
echo "    TESTS DEL SISTEMA DT STUDIO\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'UserTest.php';
require_once 'RoleTest.php';

try {
    // Ejecutar tests de usuarios
    $userTest = new UserTest();
    $userTest->runAllTests();
    
    echo "\n";
    
    // Ejecutar tests de roles
    $roleTest = new RoleTest();
    $roleTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
