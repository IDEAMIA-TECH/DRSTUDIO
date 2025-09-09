<?php
/**
 * Script de prueba de conexión a la base de datos
 */

echo "=== PRUEBA DE CONEXIÓN A BASE DE DATOS ===\n\n";

$host = '216.18.195.84';
$db_name = 'dtstudio_main';
$username = 'dtstudio_main';
$password = 'TkC6E7#o#Ds#m??5';

echo "Intentando conectar a:\n";
echo "Host: {$host}\n";
echo "Base de datos: {$db_name}\n";
echo "Usuario: {$username}\n";
echo "Contraseña: " . str_repeat('*', strlen($password)) . "\n\n";

try {
    $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ CONEXIÓN EXITOSA!\n\n";
    
    // Probar una consulta simple
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "Versión de MySQL: " . $version['version'] . "\n";
    
    // Verificar si las tablas existen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas encontradas: " . count($tables) . "\n";
    
    if (count($tables) > 0) {
        echo "Lista de tablas:\n";
        foreach ($tables as $table) {
            echo "- {$table}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
