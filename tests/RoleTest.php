<?php
/**
 * Tests para el módulo de Roles - DT Studio
 */

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../includes/Database.php';

class RoleTest {
    private $roleModel;
    private $testRoleId;

    public function __construct() {
        $this->roleModel = new Role();
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests() {
        echo "=== INICIANDO TESTS DEL MÓDULO DE ROLES ===\n\n";
        
        $this->testCreateRole();
        $this->testGetRoleById();
        $this->testGetRoleByName();
        $this->testUpdateRole();
        $this->testValidateRole();
        $this->testGetAllRoles();
        $this->testGetAvailablePermissions();
        $this->testGetForSelect();
        $this->testGetRoleStats();
        $this->testDuplicateRole();
        $this->testDeleteRole();
        
        echo "\n=== TESTS COMPLETADOS ===\n";
    }

    /**
     * Test: Crear rol
     */
    public function testCreateRole() {
        echo "Test: Crear rol... ";
        
        try {
            $roleData = [
                'name' => 'Rol de Prueba',
                'description' => 'Rol para pruebas del sistema',
                'permissions' => [
                    'users' => ['create' => true, 'read' => true],
                    'roles' => ['read' => true]
                ],
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
     * Test: Obtener rol por ID
     */
    public function testGetRoleById() {
        echo "Test: Obtener rol por ID... ";
        
        try {
            $role = $this->roleModel->getById($this->testRoleId);
            
            if ($role && $role['id'] == $this->testRoleId) {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Rol no encontrado o ID incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener rol por nombre
     */
    public function testGetRoleByName() {
        echo "Test: Obtener rol por nombre... ";
        
        try {
            $role = $this->roleModel->getByName('Rol de Prueba');
            
            if ($role && $role['name'] == 'Rol de Prueba') {
                echo "✓ PASSED\n";
            } else {
                echo "✗ FAILED - Rol no encontrado o nombre incorrecto\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Actualizar rol
     */
    public function testUpdateRole() {
        echo "Test: Actualizar rol... ";
        
        try {
            $updateData = [
                'name' => 'Rol Actualizado',
                'description' => 'Descripción actualizada',
                'permissions' => [
                    'users' => ['create' => true, 'read' => true, 'update' => true],
                    'roles' => ['read' => true, 'update' => true]
                ]
            ];
            
            $result = $this->roleModel->update($this->testRoleId, $updateData);
            
            if ($result) {
                // Verificar que se actualizó
                $role = $this->roleModel->getById($this->testRoleId);
                if ($role['name'] == 'Rol Actualizado' && $role['description'] == 'Descripción actualizada') {
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
     * Test: Validar datos de rol
     */
    public function testValidateRole() {
        echo "Test: Validar datos de rol... ";
        
        try {
            // Test datos válidos
            $validData = [
                'name' => 'Rol Válido',
                'description' => 'Descripción válida',
                'permissions' => ['users' => true]
            ];
            
            $errors = $this->roleModel->validate($validData, false);
            
            if (empty($errors)) {
                // Test datos inválidos
                $invalidData = [
                    'name' => '', // Nombre vacío
                    'description' => str_repeat('a', 501), // Descripción muy larga
                    'permissions' => 'invalid' // Permisos inválidos
                ];
                
                $errors = $this->roleModel->validate($invalidData, false);
                
                if (!empty($errors) && isset($errors['name']) && isset($errors['description'])) {
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
     * Test: Obtener todos los roles
     */
    public function testGetAllRoles() {
        echo "Test: Obtener todos los roles... ";
        
        try {
            $result = $this->roleModel->getAll(1, 10, '');
            
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
     * Test: Obtener permisos disponibles
     */
    public function testGetAvailablePermissions() {
        echo "Test: Obtener permisos disponibles... ";
        
        try {
            $permissions = $this->roleModel->getAvailablePermissions();
            
            if (is_array($permissions) && !empty($permissions)) {
                // Verificar que tiene las secciones esperadas
                $expectedSections = ['users', 'roles', 'products', 'customers'];
                $hasExpectedSections = true;
                
                foreach ($expectedSections as $section) {
                    if (!isset($permissions[$section])) {
                        $hasExpectedSections = false;
                        break;
                    }
                }
                
                if ($hasExpectedSections) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Faltan secciones de permisos esperadas\n";
                }
            } else {
                echo "✗ FAILED - Permisos no obtenidos o estructura incorrecta\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener roles para select
     */
    public function testGetForSelect() {
        echo "Test: Obtener roles para select... ";
        
        try {
            $roles = $this->roleModel->getForSelect();
            
            if (is_array($roles) && !empty($roles)) {
                // Verificar estructura
                $firstRole = $roles[0];
                if (isset($firstRole['id']) && isset($firstRole['name'])) {
                    echo "✓ PASSED (Total: " . count($roles) . ")\n";
                } else {
                    echo "✗ FAILED - Estructura de roles incorrecta\n";
                }
            } else {
                echo "✗ FAILED - No se obtuvieron roles\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Obtener estadísticas de roles
     */
    public function testGetRoleStats() {
        echo "Test: Obtener estadísticas de roles... ";
        
        try {
            $stats = $this->roleModel->getStats();
            
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
     * Test: Duplicar rol
     */
    public function testDuplicateRole() {
        echo "Test: Duplicar rol... ";
        
        try {
            $newRoleId = $this->roleModel->duplicate($this->testRoleId, 'Rol Duplicado');
            
            if ($newRoleId) {
                // Verificar que se creó el rol duplicado
                $duplicatedRole = $this->roleModel->getById($newRoleId);
                if ($duplicatedRole && $duplicatedRole['name'] == 'Rol Duplicado') {
                    echo "✓ PASSED (ID: {$newRoleId})\n";
                    
                    // Limpiar rol duplicado
                    $this->roleModel->delete($newRoleId);
                } else {
                    echo "✗ FAILED - Rol duplicado no se creó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo duplicar el rol\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Eliminar rol
     */
    public function testDeleteRole() {
        echo "Test: Eliminar rol... ";
        
        try {
            $result = $this->roleModel->delete($this->testRoleId);
            
            if ($result) {
                // Verificar que se eliminó
                $role = $this->roleModel->getById($this->testRoleId);
                if (!$role) {
                    echo "✓ PASSED\n";
                } else {
                    echo "✗ FAILED - Rol no se eliminó correctamente\n";
                }
            } else {
                echo "✗ FAILED - No se pudo eliminar\n";
            }
        } catch (Exception $e) {
            echo "✗ FAILED - " . $e->getMessage() . "\n";
        }
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new RoleTest();
    $test->runAllTests();
}
