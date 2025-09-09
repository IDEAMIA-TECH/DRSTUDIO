<?php
/**
 * Script para crear las tablas en el servidor MySQL - DT Studio
 */

echo "=== CONFIGURACIÓN DE BASE DE DATOS DT STUDIO ===\n\n";

$host = '216.18.195.84';
$db_name = 'dtstudio_main';
$username = 'dtstudio_main';
$password = 'TkC6E7#o#Ds#m??5';

echo "Conectando al servidor MySQL...\n";
echo "Host: {$host}\n";
echo "Base de datos: {$db_name}\n";
echo "Usuario: {$username}\n\n";

try {
    // Conectar sin especificar base de datos primero
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Conexión exitosa al servidor MySQL\n\n";
    
    // Crear base de datos si no existe
    echo "Creando base de datos si no existe...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '{$db_name}' creada/verificada\n\n";
    
    // Conectar a la base de datos específica
    $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Conectado a la base de datos '{$db_name}'\n\n";
    
    // Leer y ejecutar el esquema
    echo "Ejecutando esquema de base de datos...\n";
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Dividir el esquema en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $tableCount = 0;
    $dataCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            
            if (preg_match('/CREATE TABLE/i', $statement)) {
                $tableCount++;
                $tableName = '';
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                }
                echo "  ✅ Tabla creada: {$tableName}\n";
            } elseif (preg_match('/INSERT INTO/i', $statement)) {
                $dataCount++;
                echo "  ✅ Datos insertados\n";
            }
        } catch (PDOException $e) {
            // Ignorar errores de "table already exists" o "duplicate entry"
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "  ⚠️  Advertencia: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "✅ Tablas creadas: {$tableCount}\n";
    echo "✅ Inserción de datos: {$dataCount}\n\n";
    
    // Verificar tablas creadas
    echo "Verificando tablas creadas...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Tablas encontradas:\n";
        foreach ($tables as $table) {
            echo "  - {$table}\n";
        }
    } else {
        echo "❌ No se encontraron tablas\n";
    }
    
    // Verificar datos iniciales
    echo "\nVerificando datos iniciales...\n";
    $roles = $pdo->query("SELECT COUNT(*) as count FROM roles")->fetch();
    $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    $settings = $pdo->query("SELECT COUNT(*) as count FROM settings")->fetch();
    $categories = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch();
    
    echo "  - Roles: {$roles['count']}\n";
    echo "  - Usuarios: {$users['count']}\n";
    echo "  - Configuraciones: {$settings['count']}\n";
    echo "  - Categorías: {$categories['count']}\n";
    
    echo "\n🎉 CONFIGURACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "La base de datos está lista para usar.\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    exit(1);
}

echo "\n=== FIN DE LA CONFIGURACIÓN ===\n";
