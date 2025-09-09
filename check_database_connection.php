<?php
/**
 * Script para verificar conexión a base de datos - DT Studio
 */

echo "=== VERIFICACIÓN DE CONEXIÓN A BASE DE DATOS ===\n\n";

// Credenciales actuales
$host = '216.18.195.84';
$db_name = 'dtstudio_main';
$username = 'dtstudio_main';
$password = 'TkC6E7#o#Ds#m??5';

echo "Probando credenciales actuales:\n";
echo "Host: {$host}\n";
echo "Base de datos: {$db_name}\n";
echo "Usuario: {$username}\n";
echo "Contraseña: " . str_repeat('*', strlen($password)) . "\n\n";

// Función para probar conexión
function testConnection($host, $db_name, $username, $password, $test_db = false) {
    try {
        if ($test_db) {
            $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$host};charset=utf8mb4";
        }
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()];
    }
}

// Probar conexión sin base de datos específica
echo "1. Probando conexión al servidor MySQL (sin base de datos específica)...\n";
$result1 = testConnection($host, '', $username, $password, false);

if ($result1['success']) {
    echo "   ✅ Conexión exitosa al servidor MySQL\n";
    
    // Listar bases de datos disponibles
    echo "\n2. Bases de datos disponibles en el servidor:\n";
    try {
        $databases = $result1['pdo']->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($databases as $db) {
            echo "   - {$db}\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️  No se pudieron listar las bases de datos: " . $e->getMessage() . "\n";
    }
    
    // Probar conexión a la base de datos específica
    echo "\n3. Probando conexión a la base de datos '{$db_name}'...\n";
    $result2 = testConnection($host, $db_name, $username, $password, true);
    
    if ($result2['success']) {
        echo "   ✅ Conexión exitosa a la base de datos '{$db_name}'\n";
        
        // Listar tablas existentes
        echo "\n4. Tablas existentes en '{$db_name}':\n";
        try {
            $tables = $result2['pdo']->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables) > 0) {
                foreach ($tables as $table) {
                    echo "   - {$table}\n";
                }
            } else {
                echo "   - No hay tablas en la base de datos\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️  No se pudieron listar las tablas: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ❌ Error al conectar a la base de datos '{$db_name}':\n";
        echo "      Código: {$result2['code']}\n";
        echo "      Mensaje: {$result2['error']}\n";
        
        // Intentar crear la base de datos
        echo "\n5. Intentando crear la base de datos '{$db_name}'...\n";
        try {
            $result1['pdo']->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "   ✅ Base de datos '{$db_name}' creada exitosamente\n";
            
            // Probar conexión nuevamente
            $result2 = testConnection($host, $db_name, $username, $password, true);
            if ($result2['success']) {
                echo "   ✅ Conexión exitosa a la base de datos recién creada\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error al crear la base de datos: " . $e->getMessage() . "\n";
        }
    }
    
} else {
    echo "   ❌ Error de conexión al servidor MySQL:\n";
    echo "      Código: {$result1['code']}\n";
    echo "      Mensaje: {$result1['error']}\n";
    
    echo "\n🔍 DIAGNÓSTICO:\n";
    echo "El error 'Access denied' puede deberse a:\n";
    echo "1. Credenciales incorrectas (usuario/contraseña)\n";
    echo "2. El usuario no tiene permisos desde tu IP actual\n";
    echo "3. El usuario no existe en el servidor\n";
    echo "4. Restricciones de firewall del servidor\n\n";
    
    echo "💡 SOLUCIONES SUGERIDAS:\n";
    echo "1. Verificar las credenciales con el administrador del servidor\n";
    echo "2. Solicitar que se agregue tu IP a la lista de IPs permitidas\n";
    echo "3. Verificar que el usuario tenga permisos para crear bases de datos\n";
    echo "4. Probar desde otra ubicación de red\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";
