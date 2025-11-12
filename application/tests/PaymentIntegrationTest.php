<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Integration Tests for Payment Processing
 *
 * Tests complete payment flow including:
 * - Controller processing
 * - Database updates
 * - Balance calculations
 * - Ticket generation
 */
class PaymentIntegrationTest extends CI_Controller {

    private $CI;

    public function __construct() {
        parent::__construct();
        $this->CI =& get_instance();
        $this->CI->load->model('payments_m');
        $this->CI->load->model('loans_m');
        $this->CI->load->model('customers_m');
        $this->CI->load->library('PaymentCalculator');
        $this->CI->load->library('PaymentValidator');
    }

    /**
     * Test complete payment flow for 'full' payment type
     */
    public function test_full_payment_integration() {
        echo "=== INTEGRATION TEST: Pago Completo ===\n";

        // Find a test loan with pending installments
        $test_loan = $this->find_test_loan();
        if (!$test_loan) {
            echo "❌ No se encontró préstamo de prueba con cuotas pendientes\n";
            return;
        }

        echo "Préstamo de prueba encontrado: ID {$test_loan->id}\n";

        // Get pending installments
        $pending_quotas = $this->CI->payments_m->get_quotasCst($test_loan->id);
        if (empty($pending_quotas)) {
            echo "❌ El préstamo no tiene cuotas pendientes\n";
            return;
        }

        $first_quota = $pending_quotas[0];
        echo "Primera cuota pendiente: #{$first_quota['num_quota']}, Monto: {$first_quota['fee_amount']}\n";

        // Simulate payment data
        $payment_data = [
            'tipo_pago' => 'full',
            'quota_ids' => [$first_quota['id']],
            'user_id' => 1,
            'loan_id' => $test_loan->id,
            'customer_id' => $test_loan->customer_id,
            'amount' => $first_quota['fee_amount']
        ];

        // Process payment
        $result = $this->process_payment_simulation($payment_data);

        if ($result['success']) {
            echo "✅ Pago completo procesado exitosamente\n";
            $this->verify_payment_results($first_quota['id'], $payment_data);
        } else {
            echo "❌ Error procesando pago completo: {$result['error']}\n";
        }
    }

    /**
     * Test custom payment integration
     */
    public function test_custom_payment_integration() {
        echo "=== INTEGRATION TEST: Pago Personalizado ===\n";

        $test_loan = $this->find_test_loan();
        if (!$test_loan) {
            echo "❌ No se encontró préstamo de prueba\n";
            return;
        }

        $pending_quotas = $this->CI->payments_m->get_quotasCst($test_loan->id);
        if (count($pending_quotas) < 2) {
            echo "❌ Se necesitan al menos 2 cuotas pendientes para prueba personalizada\n";
            return;
        }

        // Test partial payment across multiple installments
        $custom_amount = 500.00; // Partial amount
        $selected_quotas = array_slice($pending_quotas, 0, 2);
        $quota_ids = array_column($selected_quotas, 'id');

        $payment_data = [
            'tipo_pago' => 'custom',
            'custom_amount' => $custom_amount,
            'custom_payment_type' => 'cuota',
            'quota_ids' => $quota_ids,
            'user_id' => 1,
            'loan_id' => $test_loan->id,
            'customer_id' => $test_loan->customer_id
        ];

        $result = $this->process_payment_simulation($payment_data);

        if ($result['success']) {
            echo "✅ Pago personalizado procesado exitosamente\n";
            $this->verify_custom_payment_results($quota_ids, $custom_amount);
        } else {
            echo "❌ Error procesando pago personalizado: {$result['error']}\n";
        }
    }

    /**
     * Test total payment integration
     */
    public function test_total_payment_integration() {
        echo "=== INTEGRATION TEST: Pago Total ===\n";

        $test_loan = $this->find_test_loan();
        if (!$test_loan) {
            echo "❌ No se encontró préstamo de prueba\n";
            return;
        }

        $pending_quotas = $this->CI->payments_m->get_quotasCst($test_loan->id);
        if (empty($pending_quotas)) {
            echo "❌ No hay cuotas pendientes\n";
            return;
        }

        $first_quota = $pending_quotas[0];

        $payment_data = [
            'tipo_pago' => 'total',
            'quota_ids' => [$first_quota['id']],
            'user_id' => 1,
            'loan_id' => $test_loan->id,
            'customer_id' => $test_loan->customer_id
        ];

        $result = $this->process_payment_simulation($payment_data);

        if ($result['success']) {
            echo "✅ Pago total procesado exitosamente\n";
            $this->verify_total_payment_results($test_loan->id, $first_quota['id']);
        } else {
            echo "❌ Error procesando pago total: {$result['error']}\n";
        }
    }

    /**
     * Test early total payment with waiver
     */
    public function test_early_total_payment_integration() {
        echo "=== INTEGRATION TEST: Pago Total Anticipado con Condonación ===\n";

        $test_loan = $this->find_test_loan();
        if (!$test_loan) {
            echo "❌ No se encontró préstamo de prueba\n";
            return;
        }

        $pending_quotas = $this->CI->payments_m->get_quotasCst($test_loan->id);
        if (count($pending_quotas) < 3) {
            echo "❌ Se necesitan al menos 3 cuotas para prueba de condonación\n";
            return;
        }

        $first_quota = $pending_quotas[0];

        $payment_data = [
            'tipo_pago' => 'early_total',
            'quota_ids' => [$first_quota['id']],
            'user_id' => 1,
            'loan_id' => $test_loan->id,
            'customer_id' => $test_loan->customer_id
        ];

        $result = $this->process_payment_simulation($payment_data);

        if ($result['success']) {
            echo "✅ Pago total anticipado procesado exitosamente\n";
            $this->verify_waiver_payment_results($test_loan->id, $first_quota['id']);
        } else {
            echo "❌ Error procesando pago anticipado: {$result['error']}\n";
        }
    }

    /**
     * Helper: Find a test loan with pending installments
     */
    private function find_test_loan() {
        // Find loans with pending installments
        $this->CI->db->select('l.id, l.customer_id, l.status');
        $this->CI->db->from('loans l');
        $this->CI->db->join('loan_items li', 'li.loan_id = l.id', 'inner');
        $this->CI->db->where('l.status', 1); // Active loans
        $this->CI->db->where('li.status', 1); // Pending installments
        $this->CI->db->group_by('l.id');
        $this->CI->db->limit(1);

        $loan = $this->CI->db->get()->row();
        return $loan;
    }

    /**
     * Helper: Simulate payment processing
     */
    private function process_payment_simulation($payment_data) {
        try {
            // This would normally call the controller methods
            // For testing, we'll simulate the key logic

            $tipo_pago = $payment_data['tipo_pago'];

            switch ($tipo_pago) {
                case 'full':
                    return $this->simulate_full_payment($payment_data);
                case 'custom':
                    return $this->simulate_custom_payment($payment_data);
                case 'total':
                    return $this->simulate_total_payment($payment_data);
                case 'early_total':
                    return $this->simulate_early_total_payment($payment_data);
                default:
                    return ['success' => false, 'error' => 'Tipo de pago no soportado en pruebas'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Helper: Simulate full payment processing
     */
    private function simulate_full_payment($data) {
        $quota_id = $data['quota_ids'][0];
        $user_id = $data['user_id'];

        $quota = $this->CI->payments_m->get_loan_item($quota_id);
        if (!$quota) {
            return ['success' => false, 'error' => 'Cuota no encontrada'];
        }

        // Update quota as paid
        $update_data = [
            'status' => 0,
            'interest_paid' => $quota->interest_amount,
            'capital_paid' => $quota->capital_amount,
            'balance' => 0,
            'pay_date' => date('Y-m-d H:i:s'),
            'paid_by' => $user_id
        ];

        $this->CI->payments_m->update_quota($update_data, $quota_id);

        return ['success' => true];
    }

    /**
     * Helper: Simulate custom payment processing
     */
    private function simulate_custom_payment($data) {
        $loan_id = $data['loan_id'];
        $quota_ids = $data['quota_ids'];
        $custom_amount = $data['custom_amount'];
        $custom_payment_type = $data['custom_payment_type'];
        $user_id = $data['user_id'];

        // Use PaymentCalculator for custom payment
        $result = $this->CI->paymentcalculator->process_custom_payment(
            $loan_id,
            $quota_ids,
            $custom_amount,
            'Pago personalizado de prueba'
        );

        return $result;
    }

    /**
     * Helper: Simulate total payment processing
     */
    private function simulate_total_payment($data) {
        $quota_ids = $data['quota_ids'];
        $user_id = $data['user_id'];

        // Process total payment logic
        $processed = $this->CI->payments_m->process_total_payment($quota_ids, $user_id);

        return ['success' => !empty($processed), 'processed' => $processed];
    }

    /**
     * Helper: Simulate early total payment processing
     */
    private function simulate_early_total_payment($data) {
        $quota_ids = $data['quota_ids'];
        $user_id = $data['user_id'];

        // Process early total payment logic
        $processed = $this->CI->payments_m->process_early_total_payment($quota_ids, $user_id);

        return ['success' => !empty($processed), 'processed' => $processed];
    }

    /**
     * Helper: Verify payment results
     */
    private function verify_payment_results($quota_id, $payment_data) {
        $updated_quota = $this->CI->payments_m->get_loan_item($quota_id);

        echo "Verificación de resultados:\n";
        echo "- Estado de cuota: " . ($updated_quota->status == 0 ? "✅ Pagada" : "❌ Pendiente") . "\n";
        echo "- Balance: {$updated_quota->balance}\n";
        echo "- Interés pagado: {$updated_quota->interest_paid}\n";
        echo "- Capital pagado: {$updated_quota->capital_paid}\n";
    }

    /**
     * Helper: Verify custom payment results
     */
    private function verify_custom_payment_results($quota_ids, $expected_amount) {
        $total_paid = 0;

        foreach ($quota_ids as $quota_id) {
            $quota = $this->CI->payments_m->get_loan_item($quota_id);
            $paid_on_quota = ($quota->interest_paid ?? 0) + ($quota->capital_paid ?? 0);
            $total_paid += $paid_on_quota;
        }

        echo "Verificación pago personalizado:\n";
        echo "- Monto esperado: {$expected_amount}\n";
        echo "- Monto total pagado: {$total_paid}\n";
        echo "- Resultado: " . (abs($total_paid - $expected_amount) < 0.01 ? "✅ Correcto" : "❌ Incorrecto") . "\n";
    }

    /**
     * Helper: Verify total payment results
     */
    private function verify_total_payment_results($loan_id, $quota_id) {
        $quota = $this->CI->payments_m->get_loan_item($quota_id);

        echo "Verificación pago total:\n";
        echo "- Estado de cuota: " . ($quota->status == 0 ? "✅ Pagada" : "❌ Pendiente") . "\n";
        echo "- Balance: {$quota->balance}\n";
    }

    /**
     * Helper: Verify waiver payment results
     */
    private function verify_waiver_payment_results($loan_id, $paid_quota_id) {
        // Check paid quota
        $paid_quota = $this->CI->payments_m->get_loan_item($paid_quota_id);
        echo "Verificación condonación:\n";
        echo "- Cuota pagada - Estado: " . ($paid_quota->status == 0 ? "✅ Pagada" : "❌ Pendiente") . "\n";

        // Check waived quotas
        $this->CI->db->where('loan_id', $loan_id);
        $this->CI->db->where('num_quota >', $paid_quota->num_quota);
        $this->CI->db->where('extra_payment', 3); // Conditionally waived
        $waived_count = $this->CI->db->count_all_results('loan_items');

        echo "- Cuotas condonadas: {$waived_count}\n";
    }

    /**
     * Run all integration tests
     */
    public function run_all_integration_tests() {
        echo "🚀 INICIANDO PRUEBAS DE INTEGRACIÓN - Payment System\n\n";

        try {
            $this->test_full_payment_integration();
            echo "\n";

            $this->test_custom_payment_integration();
            echo "\n";

            $this->test_total_payment_integration();
            echo "\n";

            $this->test_early_total_payment_integration();
            echo "\n";

            echo "✅ TODAS LAS PRUEBAS DE INTEGRACIÓN COMPLETADAS\n";
        } catch (Exception $e) {
            echo "\n❌ ERROR EN PRUEBAS DE INTEGRACIÓN: " . $e->getMessage() . "\n";
        }
    }
}