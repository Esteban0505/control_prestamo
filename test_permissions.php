<?php
/**
 * Script de prueba para verificar que la función can_view() respete los valores de la base de datos
 * Ejecutar desde el navegador o línea de comandos para validar permisos
 */

define('BASEPATH', true);
require_once 'application/helpers/permission_helper.php';

// Simular datos de sesión para pruebas
class MockSession {
    private $data = [];

    public function userdata($key) {
        return $this->data[$key] ?? null;
    }

    public function set_userdata($key, $value) {
        $this->data[$key] = $value;
    }

    public function set_flashdata($key, $value) {
        $this->data[$key] = $value;
    }
}

// Simular instancia de CodeIgniter
class MockCI {
    public $session;
    public $user_m;
    public $load;

    public function __construct() {
        $this->session = new MockSession();
        $this->load = new MockLoader();
    }
}

// Simular loader
class MockLoader {
    public function model($model) {
        // No hacer nada, solo simular
    }
}

// Simular modelo de usuario
class MockUserModel {
    public function get_permissions($user_id) {
        // Simular permisos de BD para usuario de prueba
        return [
            ['permission_name' => 'dashboard', 'value' => 1],
            ['permission_name' => 'sidebar', 'value' => 1],
            ['permission_name' => 'customers', 'value' => 1],
            ['permission_name' => 'customers_list', 'value' => 1],
            ['permission_name' => 'customers_overdue', 'value' => 0], // Permiso denegado
            ['permission_name' => 'coins', 'value' => 1],
            ['permission_name' => 'loans', 'value' => 1],
            ['permission_name' => 'payments', 'value' => 1],
            ['permission_name' => 'reports', 'value' => 1],
            ['permission_name' => 'reports_collector_commissions', 'value' => 1],
            ['permission_name' => 'reports_admin_commissions', 'value' => 0], // Permiso denegado
            ['permission_name' => 'config', 'value' => 1],
            ['permission_name' => 'config_edit_data', 'value' => 1],
            ['permission_name' => 'config_change_password', 'value' => 0], // Permiso denegado
        ];
    }
}

// Función para obtener instancia de CI (simulada)
function &get_instance() {
    static $instance;
    if (!$instance) {
        $instance = new MockCI();
        $instance->user_m = new MockUserModel();
    }
    return $instance;
}

// Función de prueba
function test_permissions() {
    echo "<h1>Pruebas de Permisos - Sistema de Sidebar</h1>";
    echo "<style>body{font-family:Arial,sans-serif;} .success{color:green;} .error{color:red;} .test{margin:10px 0;padding:10px;border:1px solid #ccc;}</style>";

    // Configurar sesión de prueba
    $CI =& get_instance();
    $CI->session->set_userdata('user_id', 1);
    $CI->session->set_userdata('perfil', 'operador');

    $permissions_to_test = [
        'dashboard' => 'Dashboard',
        'sidebar' => 'Sidebar',
        'sidebar_back' => 'Sidebar Back',
        'customers' => 'Clientes',
        'customers_list' => 'Lista de Clientes',
        'customers_overdue' => 'Pagos Vencidos',
        'coins' => 'Monedas',
        'loans' => 'Préstamos',
        'payments' => 'Cobranzas',
        'reports' => 'Reportes',
        'reports_collector_commissions' => 'Comisiones por Cobrador',
        'reports_admin_commissions' => 'Comisiones por Administrador',
        'reports_general_customer' => 'Comisiones General x Cliente',
        'config' => 'Configuración',
        'config_edit_data' => 'Editar datos',
        'config_change_password' => 'Cambiar Contraseña',
    ];

    $expected_results = [
        'dashboard' => true,
        'sidebar' => true,
        'sidebar_back' => false, // No definido en BD, debería ser false
        'customers' => true,
        'customers_list' => true,
        'customers_overdue' => false, // Definido como 0 en BD
        'coins' => true,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'reports_collector_commissions' => true,
        'reports_admin_commissions' => false, // Definido como 0 en BD
        'reports_general_customer' => false, // No definido en BD, debería ser false
        'config' => true,
        'config_edit_data' => true,
        'config_change_password' => false, // Definido como 0 en BD
    ];

    $all_passed = true;

    foreach ($permissions_to_test as $permission => $description) {
        $result = can_view('operador', $permission);
        $expected = $expected_results[$permission];
        $passed = $result === $expected;

        if (!$passed) {
            $all_passed = false;
        }

        $status_class = $passed ? 'success' : 'error';
        $status_text = $passed ? 'PASÓ' : 'FALLÓ';

        echo "<div class='test $status_class'>";
        echo "<strong>$description ($permission)</strong><br>";
        echo "Resultado: " . ($result ? 'true' : 'false') . " | Esperado: " . ($expected ? 'true' : 'false') . " | <strong>$status_text</strong>";
        echo "</div>";
    }

    echo "<hr>";
    if ($all_passed) {
        echo "<div class='success'><h2>✅ TODAS LAS PRUEBAS PASARON</h2></div>";
        echo "<p>La función can_view() está funcionando correctamente y respeta los valores de la base de datos.</p>";
    } else {
        echo "<div class='error'><h2>❌ ALGUNAS PRUEBAS FALLARON</h2></div>";
        echo "<p>Revisar la implementación de can_view() en permission_helper.php</p>";
    }

    // Mostrar permisos cargados en sesión
    echo "<hr><h3>Permisos cargados en sesión:</h3>";
    $session_permissions = $CI->session->userdata('permissions');
    if ($session_permissions) {
        echo "<pre>";
        print_r($session_permissions);
        echo "</pre>";
    } else {
        echo "<p>No hay permisos en sesión</p>";
    }
}

// Ejecutar pruebas
test_permissions();
?>