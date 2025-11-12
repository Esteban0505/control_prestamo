<?php
/**
 * Script de prueba exhaustiva para pagos personalizados
 * Simula flujo real del usuario para verificar correcciones
 */

define('BASEPATH', true);
require_once 'index.php';

// Simular datos de entrada para pago personalizado parcial
$test_data_partial = [
    'loan_item_ids' => [1, 2, 3], // IDs de cuotas seleccionadas
    'custom_amount' => 50000, // Monto a pagar
    'custom_payment_type' => 'partial', // Tipo: parcial
    'user_id' => 1,
    'customer_id' => 1,
    'payment_description' => 'Prueba de pago personalizado parcial'
];

// Simular datos de entrada para pago personalizado incompleto
$test_data_incomplete = [
    'loan_item_ids' => [4], // Solo una cuota
    'custom_amount' => 25000, // Monto menor al total de la cuota
    'custom_payment_type' => 'incomplete', // Tipo: incompleto
    'user_id' => 1,
    'customer_id' => 1,
    'payment_description' => 'Prueba de pago personalizado incompleto'
];

echo "=== SIMULACIÓN DE PAGOS PERSONALIZADOS ===\n\n";

// Prueba 1: Pago personalizado parcial
echo "PRUEBA 1: Pago personalizado PARCIAL\n";
echo "Datos: " . json_encode($test_data_partial, JSON_PRETTY_PRINT) . "\n";

try {
    // Simular POST data
    $_POST = $test_data_partial;

    // Cargar controlador
    $CI =& get_instance();
    $CI->load->library('form_validation');
    $CI->load->model('payments_m');
    $CI->load->model('loans_m');
    $CI->load->library('PaymentTypeValidator');
    $CI->load->library('PaymentCalculator');
    $CI->load->library('PaymentValidator');

    // Ejecutar método custom_payment
    $result = $CI->custom_payment();

    echo "Resultado: ÉXITO\n";
    echo "Respuesta: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Resultado: ERROR - " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Prueba 2: Pago personalizado incompleto
echo "PRUEBA 2: Pago personalizado INCOMPLETO\n";
echo "Datos: " . json_encode($test_data_incomplete, JSON_PRETTY_PRINT) . "\n";

try {
    // Simular POST data
    $_POST = $test_data_incomplete;

    // Ejecutar método custom_payment
    $result = $CI->custom_payment();

    echo "Resultado: ÉXITO\n";
    echo "Respuesta: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Resultado: ERROR - " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Prueba 3: Validación de tipos inválidos
echo "PRUEBA 3: Validación de tipos inválidos\n";

$invalid_types = ['invalid', 'wrong', 'bad_type'];

foreach ($invalid_types as $invalid_type) {
    echo "Probando tipo inválido: '$invalid_type'\n";

    try {
        $test_data_invalid = $test_data_partial;
        $test_data_invalid['custom_payment_type'] = $invalid_type;
        $_POST = $test_data_invalid;

        $result = $CI->custom_payment();
        echo "ERROR: Debería haber fallado la validación\n";

    } catch (Exception $e) {
        echo "VALIDACIÓN CORRECTA: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIN DE SIMULACIÓN ===\n";
?>