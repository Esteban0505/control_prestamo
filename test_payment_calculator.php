<?php
/**
 * Script de prueba para PaymentCalculator
 * Ejecutar desde línea de comandos: php test_payment_calculator.php
 */

// Simular constantes de CodeIgniter
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// Incluir archivos necesarios
require_once 'application/libraries/PaymentCalculator.php';
require_once 'application/libraries/PaymentValidator.php';

// Simular funciones de CodeIgniter
function get_instance() {
    return (object) [
        'load' => (object) [
            'model' => function($model) {
                // Simular carga de modelos
                return true;
            }
        ],
        'loans_m' => (object) [
            'get_loan' => function($id) {
                return (object) ['id' => $id, 'status' => 1];
            },
            'get_selected_installments' => function($loan_id, $ids) {
                return []; // Retornar array vacío para pruebas
            },
            'update_installment' => function($id, $data) {
                return true;
            },
            'is_last_installment' => function($loan_id, $installment_id) {
                return false;
            },
            'get_loan_item' => function($id) {
                return (object) ['id' => $id, 'num_quota' => 1];
            },
            'create_new_installment' => function($data) {
                return true;
            },
            'log_redistribution' => function($loan_id, $log) {
                return true;
            },
            'increase_loan_balance' => function($loan_id, $amount) {
                return true;
            }
        ],
        'payments_m' => (object) [
            'create_payment' => function($data) {
                return true;
            },
            'update_loan_balance_and_status' => function($loan_id) {
                return true;
            },
            'check_and_close_loan' => function($loan_id) {
                return true;
            }
        ],
        'db' => (object) [
            'trans_start' => function() {},
            'trans_complete' => function() {},
            'trans_rollback' => function() {},
            'trans_status' => function() { return true; },
            'insert' => function($table, $data) { return true; },
            'insert_id' => function() { return 1; }
        ],
        'input' => (object) [
            'ip_address' => function() { return '127.0.0.1'; }
        ]
    ];
}

function get_user_id() {
    return 1;
}

function log_message($level, $message) {
    echo "[$level] $message\n";
}

// Crear instancias de las librerías
$paymentCalculator = new PaymentCalculator();
$paymentValidator = new PaymentValidator();

echo "🚀 INICIANDO PRUEBAS DE PaymentCalculator\n\n";

// Test 1: Cálculo de pago completo
echo "=== TEST 1: Pago Completo ===\n";
$installment = [
    'id' => 1,
    'num_quota' => 1,
    'interest_amount' => 100.00,
    'capital_amount' => 200.00,
    'interest_paid' => 0,
    'capital_paid' => 0
];

$result = $paymentCalculator->calculate_installment_payment($installment, 300.00);
echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
echo "✅ Esperado: interest_paid=100, capital_paid=200, balance=0\n\n";

// Test 2: Cálculo de pago parcial
echo "=== TEST 2: Pago Parcial ===\n";
$result = $paymentCalculator->calculate_installment_payment($installment, 150.00);
echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
echo "✅ Esperado: interest_paid=100, capital_paid=50, balance=150\n\n";

// Test 3: Validación de cálculo válido
echo "=== TEST 3: Validación de Cálculo Válido ===\n";
$payment_breakdown = [
    [
        'installment_id' => 1,
        'num_quota' => 1,
        'total_due' => 300.00,
        'interest_due' => 100.00,
        'capital_due' => 200.00,
        'interest_paid' => 100.00,
        'capital_paid' => 200.00,
        'remaining_balance' => 0
    ]
];

$validation = $paymentValidator->validate_payment_calculation($payment_breakdown);
echo "Validación: " . ($validation['is_valid'] ? '✅ VÁLIDA' : '❌ INVÁLIDA') . "\n";
echo "Errores: " . json_encode($validation['errors']) . "\n\n";

// Test 4: Validación de cálculo inválido
echo "=== TEST 4: Validación de Cálculo Inválido ===\n";
$payment_breakdown_invalid = [
    [
        'installment_id' => 1,
        'num_quota' => 1,
        'total_due' => 300.00,
        'interest_due' => 100.00,
        'capital_due' => 200.00,
        'interest_paid' => 150.00, // Excede lo debido
        'capital_paid' => 200.00,
        'remaining_balance' => 0
    ]
];

$validation = $paymentValidator->validate_payment_calculation($payment_breakdown_invalid);
echo "Validación: " . ($validation['is_valid'] ? '✅ VÁLIDA' : '❌ INVÁLIDA') . "\n";
echo "Errores: " . json_encode($validation['errors']) . "\n\n";

echo "🎉 PRUEBAS COMPLETADAS\n";