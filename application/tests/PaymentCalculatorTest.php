<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unit Tests for PaymentCalculator Library
 *
 * Tests payment calculation logic for different payment types:
 * - full, interest, capital, both, total, total_condonacion, custom
 */
class PaymentCalculatorTest extends CI_Controller {

    private $paymentCalculator;
    private $CI;

    public function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->CI->load->library('PaymentCalculator');
        $this->paymentCalculator = new PaymentCalculator();
    }

    /**
     * Test calculate_installment_payment method
     */
    public function test_calculate_installment_payment_full() {
        echo "=== TEST: calculate_installment_payment - Pago Completo ===\n";

        // Mock installment data
        $installment = [
            'id' => 1,
            'num_quota' => 1,
            'interest_amount' => 100.00,
            'capital_amount' => 200.00,
            'interest_paid' => 0,
            'capital_paid' => 0
        ];

        $payment_amount = 300.00; // Full payment

        $result = $this->paymentCalculator->calculate_installment_payment($installment, $payment_amount);

        // Assertions
        assert($result['interest_paid'] == 100.00, "Interest paid should be 100");
        assert($result['capital_paid'] == 200.00, "Capital paid should be 200");
        assert($result['remaining_balance'] == 0, "Remaining balance should be 0");
        assert($result['payment_status'] == 'complete', "Payment status should be complete");
        assert($result['is_complete'] == true, "Should be marked as complete");

        echo "✅ Pago completo: PASSED\n";
    }

    public function test_calculate_installment_payment_partial() {
        echo "=== TEST: calculate_installment_payment - Pago Parcial ===\n";

        $installment = [
            'id' => 1,
            'num_quota' => 1,
            'interest_amount' => 100.00,
            'capital_amount' => 200.00,
            'interest_paid' => 0,
            'capital_paid' => 0
        ];

        $payment_amount = 150.00; // Partial payment

        $result = $this->paymentCalculator->calculate_installment_payment($installment, $payment_amount);

        // Assertions
        assert($result['interest_paid'] == 100.00, "Interest paid should be 100 (full interest first)");
        assert($result['capital_paid'] == 50.00, "Capital paid should be 50");
        assert($result['remaining_balance'] == 150.00, "Remaining balance should be 150");
        assert($result['payment_status'] == 'partial', "Payment status should be partial");
        assert($result['is_complete'] == false, "Should not be marked as complete");

        echo "✅ Pago parcial: PASSED\n";
    }

    /**
     * Test process_custom_payment method
     */
    public function test_process_custom_payment_full() {
        echo "=== TEST: process_custom_payment - Pago Personalizado Completo ===\n";

        // This would require database setup, so we'll mock the key parts
        // In a real test environment, we'd set up test data

        echo "✅ Pago personalizado completo: SKIPPED (requiere BD)\n";
    }

    /**
     * Test validate_payment_calculation method
     */
    public function test_validate_payment_calculation_valid() {
        echo "=== TEST: validate_payment_calculation - Cálculo Válido ===\n";

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

        $validation = $this->paymentCalculator->validate_payment_calculation($payment_breakdown);

        assert($validation['is_valid'] == true, "Validation should pass for correct calculation");

        echo "✅ Validación cálculo válido: PASSED\n";
    }

    public function test_validate_payment_calculation_invalid() {
        echo "=== TEST: validate_payment_calculation - Cálculo Inválido ===\n";

        $payment_breakdown = [
            [
                'installment_id' => 1,
                'num_quota' => 1,
                'total_due' => 300.00,
                'interest_due' => 100.00,
                'capital_due' => 200.00,
                'interest_paid' => 150.00, // Exceeds due amount
                'capital_paid' => 200.00,
                'remaining_balance' => 0
            ]
        ];

        $validation = $this->paymentCalculator->validate_payment_calculation($payment_breakdown);

        assert($validation['is_valid'] == false, "Validation should fail for incorrect calculation");
        assert(count($validation['errors']) > 0, "Should have error messages");

        echo "✅ Validación cálculo inválido: PASSED\n";
    }

    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "🚀 INICIANDO PRUEBAS UNITARIAS - PaymentCalculator\n\n";

        try {
            $this->test_calculate_installment_payment_full();
            $this->test_calculate_installment_payment_partial();
            $this->test_process_custom_payment_full();
            $this->test_validate_payment_calculation_valid();
            $this->test_validate_payment_calculation_invalid();

            echo "\n✅ TODAS LAS PRUEBAS UNITARIAS COMPLETADAS\n";
        } catch (Exception $e) {
            echo "\n❌ ERROR EN PRUEBAS: " . $e->getMessage() . "\n";
        }
    }
}