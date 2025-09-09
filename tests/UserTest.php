<?php
/**
 * Tests para el módulo de Usuarios - DT Studio
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../includes/Database.php';

class UserTest {
    private $userModel;
    private $roleModel;
    private $testUserId;
    private $testRoleId;

    public function __construct() {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE USUARIOS ===\n\n";
        
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
        $this->testCleanup();
        
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
                'permissions' => ['users' => true, 'roles' => true],
                'is_active' => 1
            ];
            
            $this->testRoleId = $this->roleModel->create($roleData);
            
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
                'password' => 'password123',
                'role_id' => $this->testRoleId,
                'phone' => '5551234567',
                'is_active' => 1
            ];
            
            $this->testUserId = $this->userModel->create($userData);
            
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
            $user = $this->userModel->getById($this->testUserId);
            
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
            $user = $this->userModel->getByEmail('test@example.com');
            
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
            $updateData = [
                'name' => 'Usuario Actualizado',
                'phone' => '5559876543'
            ];
            
            $result = $this->userModel->update($this->testUserId, $updateData);
            
            if ($result) {
                // Verificar que se actualizó
                $user = $this->userModel->getById($this->testUserId);
                if ($user['name'] == 'Usuario Actualizado' && $user['phone'] == '5559876543') {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Datos no se actualizaron correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo actualizar\n";
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
            
            $errors = $this->userModel->validate($validData, false);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'email' => 'invalid-email', // Email inválido
                    'password' => '123', // Contraseña muy corta
                    'role_id' => 999 // Rol inexistente
                ];
                
                $errors = $this->userModel->validate($invalidData, false);
                
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
            $result = $this->userModel->getAll(1, 10, '');
            
            if (isset($result['data']) && isset($result['total']) && is_array($result['data'])) {
                echo "✓ PASSED (Total: {$result['total']})\n";
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
            $user = $this->userModel->getById($this->testUserId);
            $originalStatus = $user['is_active'];
            
            // Cambiar estado
            $result = $this->userModel->toggleStatus($this->testUserId);
            
            if ($result) {
                // Verificar que cambió
                $user = $this->userModel->getById($this->testUserId);
                $newStatus = $user['is_active'];
                
                if ($newStatus != $originalStatus) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Estado no cambió\n";
                }
            } else {
                echo "✗ FAILED - No se pudo cambiar el estado\n";
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
            $stats = $this->userModel->getStats();
            
            if (isset($stats['total']) && isset($stats['active']) && isset($stats['inactive']) && isset($stats['by_role'])) {
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
            $result = $this->userModel->delete($this->testUserId);
            
            if ($result) {
                // Verificar que se eliminó (soft delete)
                $user = $this->userModel->getById($this->testUserId);
                if ($user && $user['is_active'] == 0) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Usuario no se eliminó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo eliminar\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Limpiar datos de prueba
     */
    public function testCleanup() {
        echo "Test: Limpiar datos de prueba... ";
        
        try {
            // Eliminar rol de prueba
            if ($this->testRoleId) {
                $this->roleModel->delete($this->testRoleId);
            }
            
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new UserTest();
    $test->runAllTests();
}
