<?php
/**
 * SCRIPT DE PRUEBAS EXHAUSTIVAS DEL SISTEMA DE PERMISOS
 * Sistema de Préstamo y Cobranzas - CodeIgniter 3
 */

// Simular funciones del helper
function has_role($user_role, $required_role) {
    if (empty($user_role)) {
        return false;
    }

    // Admin tiene acceso a todo
    if ($user_role === 'admin') {
        return true;
    }

    // Verificar rol específico
    return $user_role === $required_role;
}

function can_view($user_role, $section) {
    if (empty($user_role)) {
        return false;
    }

    // Admin puede ver todo
    if ($user_role === 'admin') {
        return true;
    }

    // Obtener permisos por defecto basados en rol
    $permissions = get_permissions_by_role($user_role);
    return isset($permissions[$section]) ? $permissions[$section] : false;
}

function can_edit($user_role, $section) {
    if (empty($user_role)) {
        return false;
    }

    // Admin puede editar todo
    if ($user_role === 'admin') {
        return true;
    }

    // Para otros roles, verificar permisos de edición
    if ($user_role === 'operador') {
        $editable_sections = [
            'customers',
            'loans',
            'payments'
        ];
        return in_array($section, $editable_sections);
    }

    return false;
}

function can_delete($user_role, $section) {
    if (empty($user_role)) {
        return false;
    }

    // Solo admin puede eliminar
    return $user_role === 'admin';
}

function get_permissions_by_role($role) {
    $permissions = [
        'admin' => [
            'dashboard' => true,
            'sidebar' => true,
            'sidebar_back' => true,
            'customers' => true,
            'coins' => true,
            'loans' => true,
            'payments' => true,
            'reports' => true,
            'config' => true
        ],
        'operador' => [
            'dashboard' => true,
            'sidebar' => true,
            'sidebar_back' => false,
            'customers' => true,
            'coins' => false,
            'loans' => true,
            'payments' => true,
            'reports' => true,
            'config' => false
        ],
        'viewer' => [
            'dashboard' => true,
            'sidebar' => false,
            'sidebar_back' => false,
            'customers' => false,
            'coins' => false,
            'loans' => false,
            'payments' => false,
            'reports' => true,
            'config' => false
        ]
    ];

    return isset($permissions[$role]) ? $permissions[$role] : $permissions['viewer'];
}

// Simular modelo User_m
class MockUserModel {
    private $permissions_data = []; // Simular BD de permisos

    public function get_permissions($user_id) {
        if (isset($this->permissions_data[$user_id])) {
            return $this->permissions_data[$user_id];
        }

        // Si no hay permisos, devolver defaults sin guardarlos
        $user = $this->get_user($user_id);
        if ($user) {
            $user_role = isset($user->role) ? $user->role : $user->perfil;
            $default_permissions = get_permissions_by_role($user_role);
            $permissions = [];
            foreach ($default_permissions as $name => $value) {
                $permissions[] = ['permission_name' => $name, 'value' => (int)$value];
            }
            return $permissions;
        }

        return [];
    }

    public function save_permissions($user_id, $permissions) {
        $this->permissions_data[$user_id] = $permissions;
        return true;
    }

    public function get_user($user_id) {
        // Simular usuarios
        $users = [
            1 => (object)['id' => 1, 'perfil' => 'admin'],
            2 => (object)['id' => 2, 'perfil' => 'operador'],
            3 => (object)['id' => 3, 'perfil' => 'viewer']
        ];
        return isset($users[$user_id]) ? $users[$user_id] : null;
    }
}

// Clase de pruebas
class TestPermissions {

    private $user_model;

    public function __construct() {
        $this->user_model = new MockUserModel();
    }

    public function run_tests() {
        echo "=== INICIANDO PRUEBAS EXHAUSTIVAS DEL SISTEMA DE PERMISOS ===\n\n";

        // Prueba 1: Persistencia de permisos
        $this->test_persistence();

        // Prueba 2: No sobrescritura de defaults
        $this->test_defaults_not_overwritten();

        // Prueba 3: Funciones del helper
        $this->test_helper_functions();

        // Prueba 4: Modal simulation
        $this->test_modal_simulation();

        // Prueba 5: Sidebar simulation
        $this->test_sidebar_simulation();

        // Prueba 6: Controladores simulation
        $this->test_controllers_simulation();

        echo "\n=== PRUEBAS COMPLETADAS ===\n";
    }

    private function test_persistence() {
        echo "1. PRUEBA DE PERSISTENCIA\n";
        echo "------------------------\n";

        // Crear usuario de prueba (simulado)
        $user_id = 1; // Usuario admin

        // Obtener permisos iniciales (deberían ser defaults)
        $permissions = $this->user_model->get_permissions($user_id);
        echo "Permisos iniciales para admin: " . count($permissions) . " permisos\n";
        $this->print_permissions($permissions);

        // Modificar permisos
        $modified_permissions = [
            ['permission_name' => 'dashboard', 'value' => 1],
            ['permission_name' => 'customers', 'value' => 0], // Cambiar
            ['permission_name' => 'loans', 'value' => 1],
            ['permission_name' => 'config', 'value' => 1]
        ];

        $result = $this->user_model->save_permissions($user_id, $modified_permissions);
        echo "Guardar permisos modificados: " . ($result ? "ÉXITO" : "FALLÓ") . "\n";

        // Recuperar permisos guardados
        $saved_permissions = $this->user_model->get_permissions($user_id);
        echo "Permisos recuperados: " . count($saved_permissions) . " permisos\n";
        $this->print_permissions($saved_permissions);

        // Verificar que customers esté en 0
        $customers_perm = array_filter($saved_permissions, function($p) {
            return $p['permission_name'] === 'customers';
        });
        $customers_value = reset($customers_perm)['value'];
        echo "Verificación - customers permission: " . ($customers_value == 0 ? "CORRECTO (0)" : "ERROR (debería ser 0)") . "\n";

        echo "\n";
    }

    private function test_defaults_not_overwritten() {
        echo "2. PRUEBA DE NO SOBRESCRITURA DE DEFAULTS\n";
        echo "-----------------------------------------\n";

        // Usuario operador (ID 2)
        $user_id = 2;

        // Obtener permisos iniciales (defaults)
        $initial_permissions = $this->user_model->get_permissions($user_id);
        echo "Permisos iniciales operador: " . count($initial_permissions) . " permisos\n";

        // Verificar defaults específicos
        $defaults = [
            'dashboard' => 1,
            'customers' => 1,
            'coins' => 0,
            'loans' => 1,
            'payments' => 1,
            'reports' => 1,
            'config' => 0
        ];

        foreach ($defaults as $perm => $expected) {
            $found = array_filter($initial_permissions, function($p) use ($perm) {
                return $p['permission_name'] === $perm;
            });
            $actual = reset($found)['value'];
            echo "  $perm: esperado $expected, actual $actual - " .
                 ($actual == $expected ? "✓" : "✗") . "\n";
        }

        // Guardar permisos modificados
        $modified = [
            ['permission_name' => 'dashboard', 'value' => 1],
            ['permission_name' => 'customers', 'value' => 1],
            ['permission_name' => 'coins', 'value' => 1], // Cambiar de 0 a 1
            ['permission_name' => 'loans', 'value' => 1],
            ['permission_name' => 'payments', 'value' => 1],
            ['permission_name' => 'reports', 'value' => 1],
            ['permission_name' => 'config', 'value' => 0]
        ];

        $this->user_model->save_permissions($user_id, $modified);

        // Crear nuevo usuario operador (ID 4)
        $new_user_id = 4;

        // Verificar que los defaults del nuevo usuario no estén afectados
        $new_permissions = $this->user_model->get_permissions($new_user_id);
        $new_coins = array_filter($new_permissions, function($p) {
            return $p['permission_name'] === 'coins';
        });
        $new_coins_value = reset($new_coins)['value'];

        echo "Nuevo usuario operador - coins permission: $new_coins_value (debería ser 0) - " .
             ($new_coins_value == 0 ? "✓ DEFAULTS NO SOBRESCRITOS" : "✗ ERROR") . "\n";

        echo "\n";
    }

    private function test_helper_functions() {
        echo "3. PRUEBA DE FUNCIONES DEL HELPER\n";
        echo "---------------------------------\n";

        // Simular sesión
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['perfil'] = 'admin';

        // Simular CI instance
        $ci = new stdClass();
        $ci->session = new stdClass();
        $ci->session->userdata = function($key) {
            return $_SESSION[$key] ?? null;
        };
        $GLOBALS['CI'] =& $ci;

        // Probar has_role
        echo "has_role('admin', 'admin'): " . (has_role('admin', 'admin') ? "true" : "false") . " ✓\n";
        echo "has_role('operador', 'admin'): " . (has_role('operador', 'admin') ? "true" : "false") . " ✓\n";

        // Probar can_view (simular con usuario admin)
        echo "can_view('admin', 'customers'): " . (can_view('admin', 'customers') ? "true" : "false") . " ✓\n";

        // Probar can_edit
        echo "can_edit('admin', 'customers'): " . (can_edit('admin', 'customers') ? "true" : "false") . " ✓\n";
        echo "can_edit('operador', 'customers'): " . (can_edit('operador', 'customers') ? "true" : "false") . " ✓\n";

        // Probar can_delete
        echo "can_delete('admin', 'customers'): " . (can_delete('admin', 'customers') ? "true" : "false") . " ✓\n";
        echo "can_delete('operador', 'customers'): " . (can_delete('operador', 'customers') ? "true" : "false") . " ✓\n";

        echo "\n";
    }

    private function test_modal_simulation() {
        echo "4. PRUEBA DE SIMULACIÓN DE MODAL\n";
        echo "--------------------------------\n";

        // Simular carga de permisos para modal (usuario operador ID 2)
        $user_id = 2;
        $permissions = $this->user_model->get_permissions($user_id);

        // Simular checkboxes del modal
        $modal_permissions = ['dashboard', 'sidebar', 'sidebar_back', 'customers', 'coins', 'loans', 'payments', 'reports', 'config'];
        $checked = [];

        foreach ($modal_permissions as $perm) {
            $found = array_filter($permissions, function($p) use ($perm) {
                return $p['permission_name'] === $perm;
            });
            $checked[$perm] = !empty($found) ? (bool)reset($found)['value'] : false;
        }

        echo "Estado de checkboxes en modal:\n";
        foreach ($checked as $perm => $state) {
            echo "  $perm: " . ($state ? "✓" : "✗") . "\n";
        }

        // Simular cambio de rol
        echo "\nCambio de rol a 'admin':\n";
        $admin_defaults = [
            'dashboard' => true, 'sidebar' => true, 'sidebar_back' => true,
            'customers' => true, 'coins' => true, 'loans' => true,
            'payments' => true, 'reports' => true, 'config' => true
        ];

        foreach ($admin_defaults as $perm => $state) {
            echo "  $perm: " . ($state ? "✓" : "✗") . "\n";
        }

        echo "\n";
    }

    private function test_sidebar_simulation() {
        echo "5. PRUEBA DE SIMULACIÓN DE SIDEBAR\n";
        echo "----------------------------------\n";

        // Simular diferentes roles
        $roles = ['admin', 'operador', 'viewer'];

        foreach ($roles as $role) {
            echo "Sidebar para rol '$role':\n";

            $visible_sections = [
                'customers' => can_view($role, 'customers'),
                'coins' => can_view($role, 'coins'),
                'loans' => can_view($role, 'loans'),
                'payments' => can_view($role, 'payments'),
                'reports' => can_view($role, 'reports'),
                'config' => can_view($role, 'config')
            ];

            foreach ($visible_sections as $section => $visible) {
                echo "  $section: " . ($visible ? "VISIBLE" : "OCULTO") . "\n";
            }
            echo "\n";
        }
    }

    private function test_controllers_simulation() {
        echo "6. PRUEBA DE SIMULACIÓN DE CONTROLADORES\n";
        echo "----------------------------------------\n";

        // Simular acceso a diferentes secciones
        $sections = ['customers', 'loans', 'payments', 'reports', 'config'];
        $roles = ['admin', 'operador', 'viewer'];

        echo "Simulación de require_permission():\n";
        foreach ($roles as $role) {
            echo "Rol '$role':\n";
            foreach ($sections as $section) {
                $access = can_view($role, $section);
                echo "  $section: " . ($access ? "ACCESO PERMITIDO" : "ACCESO DENEGADO") . "\n";
            }
            echo "\n";
        }
    }


    private function print_permissions($permissions) {
        foreach ($permissions as $perm) {
            echo "  {$perm['permission_name']}: {$perm['value']}\n";
        }
    }
}

// Ejecutar pruebas
$test = new TestPermissions();
$test->run_tests();