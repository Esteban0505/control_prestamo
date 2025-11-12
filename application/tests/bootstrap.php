<?php
// Bootstrap para pruebas PHPUnit en CodeIgniter

// Definir constantes necesarias
define('BASEPATH', 'C:/xampp/htdocs/prestamo-1/system/');
define('APPPATH', 'C:/xampp/htdocs/prestamo-1/application/');
define('ENVIRONMENT', 'testing');

// Incluir archivos necesarios de CodeIgniter
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/Controller.php';
require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'libraries/Session/Session.php';
require_once BASEPATH . 'libraries/Form_validation.php';
require_once BASEPATH . 'core/Input.php';

// Configurar autoload
require_once APPPATH . '/config/autoload.php';

// Cargar configuración de base de datos para testing
require_once APPPATH . '/config/database.php';

// Configurar base de datos de prueba
$db_config = $db['default'];
$db_config['database'] = 'prestamo_test'; // Base de datos de prueba

// Función para inicializar CodeIgniter
function init_codeigniter()
{
    global $db_config;

    // Crear instancia de CI manualmente
    $CI = new stdClass();

    // Cargar base de datos
    require_once BASEPATH . 'database/DB.php';
    $CI->db = DB($db_config, TRUE);

    // Cargar modelos necesarios
    require_once APPPATH . 'models/Payments_m.php';
    $CI->payments_m = new Payments_m();

    require_once APPPATH . 'models/Loans_m.php';
    $CI->loans_m = new Loans_m();

    require_once APPPATH . 'models/Customers_m.php';
    $CI->customers_m = new Customers_m();

    require_once APPPATH . 'models/Coins_m.php';
    $CI->coins_m = new Coins_m();

    require_once APPPATH . 'models/User_m.php';
    $CI->user_m = new User_m();

    // Cargar librerías
    require_once BASEPATH . 'libraries/Form_validation.php';
    $CI->form_validation = new CI_Form_validation();

    require_once BASEPATH . 'libraries/Session/Session.php';
    $CI->session = new CI_Session();

    // Cargar input
    require_once BASEPATH . 'core/Input.php';
    $CI->input = new CI_Input();

    // Cargar helper de errores
    require_once APPPATH . 'helpers/error_handler_helper.php';

    return $CI;
}

// Función para crear tablas de prueba
function setup_test_database()
{
    $CI = init_codeigniter();

    // Aquí irían las sentencias SQL para crear tablas de prueba
    // Por simplicidad, asumimos que la BD de prueba ya está configurada
}

// Función para limpiar base de datos de prueba
function teardown_test_database()
{
    $CI = init_codeigniter();

    // Limpiar tablas de prueba
    $CI->db->truncate('payments');
    $CI->db->truncate('loan_items');
    $CI->db->truncate('loans');
    $CI->db->truncate('customers');
    $CI->db->truncate('users');
    $CI->db->truncate('coins');
}