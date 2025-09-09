<?php
/**
 * Tests para el módulo de Usuarios - DT Studio (Local)
 * Usa SQLite para pruebas locales
 */

require_once __DIR__ . '/../includes/DatabaseTest.php';

class UserTestLocal {
    private $db;
    private $testUserId;
    private $testRoleId;

    public function __construct() {
        $this->db = DatabaseTest::getInstance();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE USUARIOS (LOCAL) ===\n\n";
        
        $this->testCreateRole();
        $this->testCreateUser();
        $this->testGetUserById();
        $this->testGetUserByEmail();
        $this->testUpdateUser();
        $this->testValidateUser();
        $this->testGetAllUsers();
        $this->testToggleUserStatus();
        $this->testGetUserStats();
        $this->testDeleteUser();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear rol de prueba
     */
    public function testCreateRole() {
        echo "Test: Crear rol de prueba... ";
        
        try {
            $roleData = [
                'name' => 'Test Role',
                'description' => 'Rol para pruebas',
                'permissions' => json_encode(['users' => true, 'roles' => true]),
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO roles (name, description, permissions, is_active) VALUES (?, ?, ?, ?)";
            $this->db->query($sql, [
                $roleData['name'],
                $roleData['description'],
                $roleData['permissions'],
                $roleData['is_active']
            ]);
            
            $this->testRoleId = $this->db->lastInsertId();
            
            if ($this->testRoleId) {
                echo "✓ PASSED (ID: {$this->testRoleId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del rol\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Crear usuario
     */
    public function testCreateUser() {
        echo "Test: Crear usuario... ";
        
        try {
            $userData = [
                'name' => 'Usuario de Prueba',
                'email' => 'test@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role_id' => $this->testRoleId,
                'phone' => '5551234567',
                'is_active' => 1
            ];
            
            $sql = "INSERT INTO users (name, email, password, role_id, phone, is_active) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $userData['name'],
                $userData['email'],
                $userData['password'],
                $userData['role_id'],
                $userData['phone'],
                $userData['is_active']
            ]);
            
            $this->testUserId = $this->db->lastInsertId();
            
            if ($this->testUserId) {
                echo "✓ PASSED (ID: {$this->testUserId})\n";
            } else {
                echo "✗ FAILED - No se obtuvo ID del usuario\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener usuario por ID
     */
    public function testGetUserById() {
        echo "Test: Obtener usuario por ID... ";
        
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.id = ?";
            $user = $this->db->fetch($sql, [$this->testUserId]);
            
            if ($user && $user['id'] == $this->testUserId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Usuario no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener usuario por email
     */
    public function testGetUserByEmail() {
        echo "Test: Obtener usuario por email... ";
        
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.email = ?";
            $user = $this->db->fetch($sql, ['test@example.com']);
            
            if ($user && $user['email'] == 'test@example.com') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Usuario no encontrado o email incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar usuario
     */
    public function testUpdateUser() {
        echo "Test: Actualizar usuario... ";
        
        try {
            $sql = "UPDATE users SET name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $result = $this->db->query($sql, ['Usuario Actualizado', '5559876543', $this->testUserId]);
            
            // Verificar que se actualizó
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$this->testUserId]);
            if ($user['name'] == 'Usuario Actualizado' && $user['phone'] == '5559876543') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Datos no se actualizaron correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validar datos de usuario
     */
    public function testValidateUser() {
        echo "Test: Validar datos de usuario... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Usuario Válido',
                'email' => 'valid@example.com',
                'password' => 'password123',
                'role_id' => $this->testRoleId
            ];
            
            $errors = $this->validateUserData($validData);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'email' => 'invalid-email', // Email inválido
                    'password' => '123', // Contraseña muy corta
                    'role_id' => 999 // Rol inexistente
                ];
                
                $errors = $this->validateUserData($invalidData);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['email']) && isset($errors['password'])) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Validación de datos inválidos no funcionó\n";
                }
            } else {
                echo "✗ FAILED - Validación de datos válidos falló\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener todos los usuarios
     */
    public function testGetAllUsers() {
        echo "Test: Obtener todos los usuarios... ";
        
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    ORDER BY u.created_at DESC";
            $users = $this->db->fetchAll($sql);
            
            if (is_array($users)) {
                echo "✓ PASSED (Total: " . count($users) . ")\n";
            } else {
                echo "✗ FAILED - Estructura de respuesta incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Cambiar estado del usuario
     */
    public function testToggleUserStatus() {
        echo "Test: Cambiar estado del usuario... ";
        
        try {
            // Obtener estado actual
            $user = $this->db->fetch("SELECT is_active FROM users WHERE id = ?", [$this->testUserId]);
            $originalStatus = $user['is_active'];
            
            // Cambiar estado
            $newStatus = $originalStatus ? 0 : 1;
            $sql = "UPDATE users SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [$newStatus, $this->testUserId]);
            
            // Verificar que cambió
            $user = $this->db->fetch("SELECT is_active FROM users WHERE id = ?", [$this->testUserId]);
            
            if ($user['is_active'] != $originalStatus) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Estado no cambió\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de usuarios
     */
    public function testGetUserStats() {
        echo "Test: Obtener estadísticas de usuarios... ";
        
        try {
            $stats = [];
            
            // Total de usuarios
            $stats['total'] = $this->db->fetch("SELECT COUNT(*) as count FROM users")['count'];
            
            // Usuarios activos
            $stats['active'] = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
            
            // Usuarios inactivos
            $stats['inactive'] = $stats['total'] - $stats['active'];
            
            if (isset($stats['total']) && isset($stats['active']) && isset($stats['inactive'])) {
                echo "✓ PASSED (Total: {$stats['total']}, Activos: {$stats['active']})\n";
            } else {
                echo "✗ FAILED - Estructura de estadísticas incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar usuario
     */
    public function testDeleteUser() {
        echo "Test: Eliminar usuario... ";
        
        try {
            // Soft delete
            $sql = "UPDATE users SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->query($sql, [$this->testUserId]);
            
            // Verificar que se eliminó (soft delete)
            $user = $this->db->fetch("SELECT is_active FROM users WHERE id = ?", [$this->testUserId]);
            if ($user && $user['is_active'] == 0) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Usuario no se eliminó correctamente\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Validar datos de usuario (función auxiliar)
     */
    private function validateUserData($data) {
        $errors = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Validar contraseña
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Validar rol
        if (empty($data['role_id'])) {
            $errors['role_id'] = 'El rol es requerido';
        } else {
            $role = $this->db->fetch("SELECT id FROM roles WHERE id = ?", [$data['role_id']]);
            if (!$role) {
                $errors['role_id'] = 'El rol seleccionado no existe';
            }
        }

        return $errors;
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new UserTestLocal();
    $test->runAllTests();
}
