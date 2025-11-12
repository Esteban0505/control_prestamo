<?php
/**
 * Script para probar el módulo ticket directamente
 * Simula el flujo completo de procesamiento de pagos
 */

// Simular sesión de usuario
$_SESSION['loggedin'] = TRUE;
$_SESSION['user_id'] = 1;

echo "=== PRUEBA DIRECTA DEL MÓDULO TICKET ===\n";

// Incluir el framework CodeIgniter
require_once 'index.php';

// Obtener instancia del controlador
$CI =& get_instance();

// Cargar modelos y librerías necesarias
$CI->load->model('payments_m');
$CI->load->model('user_m');
$CI->load->model('loans_m');
$CI->load->model('customers_m');
$CI->load->model('coins_m');
$CI->load->library('form_validation');
$CI->load->library('session');
$CI->load->driver('cache');
$CI->load->helper('error_handler');
$CI->load->library('PaymentTypeValidator');
$CI->load->library('PaymentCalculator');
$CI->load->library('PaymentValidator');

// Simular datos POST para pago personalizado
$_POST = [
    'name_cst' => 'Cliente Prueba',
    'coin' => 'COP',
    'loan_id' => 159,
    'user_id' => 1,
    'customer_id' => 50,
    'tipo_pago' => 'custom',
    'custom_amount' => 10000,
    'custom_payment_type' => 'cuota',
    'quota_id' => [428],
    'payment_description' => 'Prueba automática del módulo ticket'
];

echo "Datos de prueba preparados:\n";
echo "- Tipo de pago: custom\n";
echo "- Monto personalizado: $10,000\n";
echo "- Cuota seleccionada: 428\n";
echo "- Loan ID: 159\n\n";

try {
    // Ejecutar el método ticket
    echo "Ejecutando método ticket()...\n";
    $CI->ticket();
    echo "✓ Método ticket ejecutado exitosamente\n";

} catch (Exception $e) {
    echo "✗ Error en método ticket: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";
?>