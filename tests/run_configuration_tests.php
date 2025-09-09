<?php
/**
 * Ejecutor de Tests del Módulo de Sistema de Configuración - DT Studio
 * Ejecuta todos los tests del módulo de configuración usando SQLite
 */

echo "========================================\n";
echo "    TESTS DEL MÓDULO DE CONFIGURACIÓN\n";
echo "========================================\n\n";

// Incluir archivos de test
require_once 'ConfigurationTest.php';

try {
    // Ejecutar tests de sistema de configuración
    echo "=== TESTS DE SISTEMA DE CONFIGURACIÓN ===\n";
    $configurationTest = new ConfigurationTest();
    $configurationTest->runAllTests();
    
    echo "\n========================================\n";
    echo "    TODOS LOS TESTS COMPLETADOS\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
