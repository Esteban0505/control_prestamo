<?php
/**
 * Script para probar la interfaz del sidebar con permisos
 * Simula la carga del sidebar y verifica que los elementos se muestran/ocultan correctamente
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
    public $customers_m;
    public $payments_m;

    public function __construct() {
        $this->session = new MockSession();
        $this->load = new MockLoader();
        $this->customers_m = new MockCustomersModel();
        $this->payments_m = new MockPaymentsModel();
    }
}

// Simular loader
class MockLoader {
    public function model($model) {
        // No hacer nada, solo simular
    }
}

// Simular modelo de clientes
class MockCustomersModel {
    public function get_blacklist_stats() {
        return (object)['active_blocks' => 5];
    }
}

// Simular modelo de pagos
class MockPaymentsModel {
    public function count_clients_by_risk($risk) {
        $counts = [
            'high' => 3,
            'medium' => 7,
            'low' => 12
        ];
        return $counts[$risk] ?? 0;
    }
}

// Simular modelo de usuario
class MockUserModel {
    public function get_permissions($user_id) {
        // Simular permisos con algunos denegados para probar
        return [
            ['permission_name' => 'sidebar', 'value' => 1],
            ['permission_name' => 'dashboard', 'value' => 1],
            ['permission_name' => 'customers', 'value' => 1],
            ['permission_name' => 'customers_list', 'value' => 1],
            ['permission_name' => 'customers_overdue', 'value' => 0], // Denegado
            ['permission_name' => 'coins', 'value' => 1],
            ['permission_name' => 'loans', 'value' => 1],
            ['permission_name' => 'payments', 'value' => 1],
            ['permission_name' => 'reports', 'value' => 1],
            ['permission_name' => 'reports_collector_commissions', 'value' => 1],
            ['permission_name' => 'reports_admin_commissions', 'value' => 0], // Denegado
            ['permission_name' => 'reports_general_customer', 'value' => 1],
            ['permission_name' => 'config', 'value' => 1],
            ['permission_name' => 'config_edit_data', 'value' => 0], // Denegado
            ['permission_name' => 'config_change_password', 'value' => 1],
            ['permission_name' => 'sidebar_back', 'value' => 1],
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

// Función para simular site_url
function site_url($url) {
    return "http://localhost/prestamo-1/index.php/" . $url;
}

// Función para probar la lógica del sidebar
function test_sidebar_logic() {
    echo "<h1>Pruebas de Interfaz - Sidebar con Permisos</h1>";
    echo "<style>body{font-family:Arial,sans-serif;} .visible{color:green;} .hidden{color:red;} .section{margin:10px 0;padding:10px;border:1px solid #ccc;}</style>";

    // Configurar sesión de prueba
    $CI =& get_instance();
    $CI->session->set_userdata('user_id', 1);
    $CI->session->set_userdata('perfil', 'operador');
    $CI->session->set_userdata('loggedin', true);

    $sections = [
        'sidebar' => 'Sidebar Principal',
        'dashboard' => 'Dashboard/Inicio',
        'customers' => 'Sección Clientes',
        'customers_list' => 'Submenú: Lista de Clientes',
        'customers_overdue' => 'Submenú: Pagos Vencidos',
        'coins' => 'Sección Monedas',
        'loans' => 'Sección Préstamos',
        'payments' => 'Sección Cobranzas',
        'reports' => 'Sección Reportes',
        'reports_collector_commissions' => 'Submenú: Comisiones por Cobrador',
        'reports_admin_commissions' => 'Submenú: Comisiones por Administrador',
        'reports_general_customer' => 'Submenú: Comisiones General x Cliente',
        'config' => 'Sección Configuración',
        'config_edit_data' => 'Submenú: Editar datos',
        'config_change_password' => 'Submenú: Cambiar Contraseña',
        'sidebar_back' => 'Sidebar Toggler',
    ];

    $expected_visibility = [
        'sidebar' => true,
        'dashboard' => true,
        'customers' => true,
        'customers_list' => true,
        'customers_overdue' => false, // Denegado
        'coins' => true,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'reports_collector_commissions' => true,
        'reports_admin_commissions' => false, // Denegado
        'reports_general_customer' => true,
        'config' => true,
        'config_edit_data' => false, // Denegado
        'config_change_password' => true,
        'sidebar_back' => true,
    ];

    $all_passed = true;

    echo "<h2>Resultados de Visibilidad de Secciones:</h2>";

    foreach ($sections as $permission => $description) {
        $is_visible = function_exists('can_view') && can_view('operador', $permission);
        $expected = $expected_visibility[$permission];
        $passed = $is_visible === $expected;

        if (!$passed) {
            $all_passed = false;
        }

        $visibility_class = $is_visible ? 'visible' : 'hidden';
        $visibility_text = $is_visible ? 'VISIBLE' : 'OCULTO';
        $status_class = $passed ? 'visible' : 'hidden';
        $status_text = $passed ? 'CORRECTO' : 'INCORRECTO';

        echo "<div class='section $status_class'>";
        echo "<strong>$description ($permission)</strong><br>";
        echo "Estado: <strong>$visibility_text</strong> | Esperado: " . ($expected ? 'VISIBLE' : 'OCULTO') . " | <strong>$status_text</strong>";
        echo "</div>";
    }

    echo "<hr>";
    if ($all_passed) {
        echo "<div class='visible'><h2>✅ TODAS LAS PRUEBAS DE INTERFAZ PASARON</h2></div>";
        echo "<p>El sidebar se mostrará/ocultará correctamente según los permisos configurados.</p>";
    } else {
        echo "<div class='hidden'><h2>❌ ALGUNAS PRUEBAS DE INTERFAZ FALLARON</h2></div>";
        echo "<p>Revisar la implementación del sidebar en application/views/admin/components/sidebar.php</p>";
    }

    // Simular renderizado del sidebar para verificar elementos ocultos
    echo "<hr><h2>Simulación de Renderizado del Sidebar:</h2>";
    echo "<div style='background:#071e3d; color:#fff; padding:20px; border-radius:5px; font-family:monospace; white-space:pre-line;'>";

    // Simular las secciones principales
    if (can_view('operador', 'sidebar')) {
        echo "SIDEBAR PRINCIPAL - VISIBLE\n";
        echo "=======================\n\n";

        if (can_view('operador', 'dashboard')) {
            echo "✓ Dashboard/Inicio - VISIBLE\n";
        } else {
            echo "✗ Dashboard/Inicio - OCULTO\n";
        }

        if (can_view('operador', 'customers')) {
            echo "✓ Clientes - VISIBLE\n";
            echo "  ├── Lista de Clientes - " . (can_view('operador', 'customers_list') ? 'VISIBLE' : 'OCULTO') . "\n";
            echo "  └── Pagos Vencidos - " . (can_view('operador', 'customers_overdue') ? 'VISIBLE' : 'OCULTO') . "\n";
        } else {
            echo "✗ Clientes - OCULTO\n";
        }

        echo "✓ Monedas - " . (can_view('operador', 'coins') ? 'VISIBLE' : 'OCULTO') . "\n";
        echo "✓ Préstamos - " . (can_view('operador', 'loans') ? 'VISIBLE' : 'OCULTO') . "\n";
        echo "✓ Cobranzas - " . (can_view('operador', 'payments') ? 'VISIBLE' : 'OCULTO') . "\n";

        if (can_view('operador', 'reports')) {
            echo "✓ Reportes - VISIBLE\n";
            echo "  ├── Comisiones por Cobrador - " . (can_view('operador', 'reports_collector_commissions') ? 'VISIBLE' : 'OCULTO') . "\n";
            echo "  ├── Comisiones por Administrador - " . (can_view('operador', 'reports_admin_commissions') ? 'VISIBLE' : 'OCULTO') . "\n";
            echo "  └── Comisiones General x Cliente - " . (can_view('operador', 'reports_general_customer') ? 'VISIBLE' : 'OCULTO') . "\n";
        } else {
            echo "✗ Reportes - OCULTO\n";
        }

        if (can_view('operador', 'config')) {
            echo "✓ Configuración - VISIBLE\n";
            echo "  ├── Editar datos - " . (can_view('operador', 'config_edit_data') ? 'VISIBLE' : 'OCULTO') . "\n";
            echo "  └── Cambiar Contraseña - " . (can_view('operador', 'config_change_password') ? 'VISIBLE' : 'OCULTO') . "\n";
        } else {
            echo "✗ Configuración - OCULTO\n";
        }

        echo "\nSidebar Toggler - " . (can_view('operador', 'sidebar_back') ? 'VISIBLE' : 'OCULTO') . "\n";

    } else {
        echo "SIDEBAR COMPLETO - OCULTO\n";
    }

    echo "</div>";
}

// Ejecutar pruebas
test_sidebar_logic();
?>