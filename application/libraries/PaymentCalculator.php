<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment Calculator Library
 *
 * Provides accurate payment calculations for loan installments with support for:
 * - Partial payments with balance redistribution
 * - Interest and capital distribution
 * - Payment validation and audit trails
 * - Automatic balance adjustments
 */
class PaymentCalculator {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('loans_m');
        $this->CI->load->model('payments_m');
    }

    /**
     * Calculate payment distribution for a single installment
     *
     * @param array $installment Installment data
     * @param float $payment_amount Amount being paid
     * @return array Payment breakdown
     */
    public function calculate_installment_payment($installment, $payment_amount) {
        $result = [
            'installment_id' => $installment['id'],
            'num_quota' => $installment['num_quota'],
            'total_due' => 0,
            'interest_due' => 0,
            'capital_due' => 0,
            'interest_paid' => 0,
            'capital_paid' => 0,
            'remaining_balance' => 0,
            'payment_status' => 'partial',
            'excess_amount' => 0,
            'is_complete' => false
        ];

        // Calculate amounts due
        $interest_due = floatval($installment['interest_amount']) - floatval($installment['interest_paid'] ?? 0);
        $capital_due = floatval($installment['capital_amount']) - floatval($installment['capital_paid'] ?? 0);
        $total_due = $interest_due + $capital_due;

        $result['total_due'] = $total_due;
        $result['interest_due'] = $interest_due;
        $result['capital_due'] = $capital_due;

        // Apply payment with priority: interest first, then capital
        if ($payment_amount >= $total_due) {
            // Full payment
            $result['interest_paid'] = $interest_due;
            $result['capital_paid'] = $capital_due;
            $result['remaining_balance'] = 0;
            $result['payment_status'] = 'complete';
            $result['is_complete'] = true;
            $result['excess_amount'] = $payment_amount - $total_due;
        } else {
            // Partial payment
            $result['interest_paid'] = min($payment_amount, $interest_due);
            $remaining_after_interest = $payment_amount - $result['interest_paid'];
            $result['capital_paid'] = min($remaining_after_interest, $capital_due);
            $result['remaining_balance'] = $total_due - $payment_amount;
            $result['payment_status'] = 'partial';
            $result['is_complete'] = false;
            $result['excess_amount'] = 0;
        }

        return $result;
    }

    /**
     * Process custom payment with balance redistribution
     *
     * @param int $loan_id Loan ID
     * @param array $selected_installments Array of selected installment IDs
     * @param float $total_payment_amount Total payment amount
     * @param string $payment_description Payment description
     * @return array Processing result
     */
    public function process_custom_payment($loan_id, $selected_installments, $total_payment_amount, $payment_description = '') {
        $result = [
            'success' => false,
            'message' => '',
            'payment_breakdown' => [],
            'redistribution_log' => [],
            'total_processed' => 0,
            'remaining_amount' => $total_payment_amount
        ];

        try {
            // Start transaction
            $this->CI->db->trans_start();

            // Validate loan
            $loan = $this->CI->loans_m->get_loan($loan_id);
            if (!$loan || $loan->status != 1) {
                throw new Exception('Préstamo no válido o cerrado');
            }

            // Get selected installments data
            $installments_data = $this->CI->loans_m->get_selected_installments($loan_id, array_column($selected_installments, 'id'));

            $remaining_amount = $total_payment_amount;
            $payment_breakdown = [];
            $redistribution_log = [];

            // Process each selected installment
            foreach ($installments_data as $installment) {
                if ($remaining_amount <= 0) break;

                $payment_result = $this->calculate_installment_payment($installment, $remaining_amount);

                // Update installment in database
                $update_data = [
                    'interest_paid' => ($installment['interest_paid'] ?? 0) + $payment_result['interest_paid'],
                    'capital_paid' => ($installment['capital_paid'] ?? 0) + $payment_result['capital_paid'],
                    'balance' => $payment_result['remaining_balance'],
                    'status' => $payment_result['is_complete'] ? 0 : 3, // 0=paid, 3=partially paid
                    'payment_desc' => $payment_description
                ];

                if ($payment_result['is_complete']) {
                    $update_data['pay_date'] = date('Y-m-d H:i:s');
                }

                $this->CI->loans_m->update_installment($installment['id'], $update_data);

                // Record payment
                $this->CI->payments_m->create_payment([
                    'loan_id' => $loan_id,
                    'installment_id' => $installment['id'],
                    'amount' => min($remaining_amount, $payment_result['total_due']),
                    'interest_paid' => $payment_result['interest_paid'],
                    'capital_paid' => $payment_result['capital_paid'],
                    'status' => $payment_result['payment_status'],
                    'saldo_trasladado' => 0,
                    'cuota_origen' => $installment['num_quota'],
                    'cuota_destino' => null
                ]);

                $payment_breakdown[] = $payment_result;
                $remaining_amount -= ($payment_result['interest_paid'] + $payment_result['capital_paid']);

                // Handle excess amount (balance redistribution)
                if ($payment_result['excess_amount'] > 0) {
                    $redistribution_result = $this->redistribute_excess_amount(
                        $loan_id,
                        $installment['id'],
                        $payment_result['excess_amount'],
                        $redistribution_log
                    );
                    $remaining_amount -= $payment_result['excess_amount'];
                }
            }

            // Update loan balance and status
            $this->CI->payments_m->update_loan_balance_and_status($loan_id);

            // Check if loan is completely paid
            $this->CI->payments_m->check_and_close_loan($loan_id);

            // Log redistribution if any
            if (!empty($redistribution_log)) {
                $this->CI->loans_m->log_redistribution($loan_id, $redistribution_log);
            }

            $this->CI->db->trans_complete();

            if ($this->CI->db->trans_status() === FALSE) {
                throw new Exception('Error en la transacción de base de datos');
            }

            $result['success'] = true;
            $result['message'] = 'Pago personalizado procesado exitosamente';
            $result['payment_breakdown'] = $payment_breakdown;
            $result['redistribution_log'] = $redistribution_log;
            $result['total_processed'] = $total_payment_amount - $remaining_amount;
            $result['remaining_amount'] = $remaining_amount;

        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            $result['message'] = $e->getMessage();
            log_message('error', 'Error en process_custom_payment: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Redistribute excess amount to next installments
     *
     * @param int $loan_id Loan ID
     * @param int $current_installment_id Current installment ID
     * @param float $excess_amount Amount to redistribute
     * @param array &$redistribution_log Log array to update
     * @return bool Success status
     */
    private function redistribute_excess_amount($loan_id, $current_installment_id, $excess_amount, &$redistribution_log) {
        // Check if current installment is the last one
        $is_last = $this->CI->loans_m->is_last_installment($loan_id, $current_installment_id);

        if ($is_last) {
            // Create new installment
            $current_installment = $this->CI->loans_m->get_loan_item($current_installment_id);
            $new_installment_data = [
                'loan_id' => $loan_id,
                'num_quota' => $current_installment->num_quota + 1,
                'fee_amount' => $excess_amount,
                'interest_amount' => $excess_amount * 0.1, // 10% interest
                'capital_amount' => $excess_amount * 0.9,
                'balance' => $excess_amount,
                'date' => date('Y-m-d', strtotime('+1 month', strtotime($current_installment->date))),
                'status' => 1, // Pending
                'payment_desc' => 'Saldo restante trasladado desde cuota anterior'
            ];

            $this->CI->loans_m->create_new_installment($new_installment_data);
            $redistribution_log[] = "Saldo restante de {$excess_amount} COP trasladado a nueva cuota adicional (última cuota del préstamo)";
        } else {
            // Redistribute to remaining installments
            $pending_installments = $this->CI->loans_m->get_pending_installments_after($loan_id, $current_installment_id);

            if (!empty($pending_installments)) {
                $per_installment_increase = $excess_amount / count($pending_installments);

                foreach ($pending_installments as $next) {
                    $new_fee = $next['fee_amount'] + $per_installment_increase;
                    $interest_increase = $per_installment_increase * 0.1;
                    $capital_increase = $per_installment_increase * 0.9;

                    $this->CI->loans_m->update_installment($next['id'], [
                        'fee_amount' => $new_fee,
                        'interest_amount' => $next['interest_amount'] + $interest_increase,
                        'capital_amount' => $next['capital_amount'] + $capital_increase,
                        'balance' => $next['balance'] + $per_installment_increase,
                        'status' => 3 // Partially paid status
                    ]);
                }

                $redistribution_log[] = "Saldo faltante de {$excess_amount} COP redistribuido proporcionalmente entre " . count($pending_installments) . " cuotas pendientes";
            } else {
                // No pending installments, add to loan balance
                $this->CI->loans_m->increase_loan_balance($loan_id, $excess_amount);
                $redistribution_log[] = "Saldo restante acumulado al balance global: {$excess_amount} COP";
            }
        }

        return true;
    }

    /**
     * Validate payment calculation accuracy
     *
     * @param array $payment_breakdown Payment breakdown to validate
     * @return array Validation results
     */
    public function validate_payment_calculation($payment_breakdown) {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        foreach ($payment_breakdown as $payment) {
            // Check that paid amounts don't exceed due amounts
            if ($payment['interest_paid'] > $payment['interest_due']) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Interés pagado excede lo debido en cuota {$payment['num_quota']}";
            }

            if ($payment['capital_paid'] > $payment['capital_due']) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Capital pagado excede lo debido en cuota {$payment['num_quota']}";
            }

            // Check balance calculation
            $expected_balance = $payment['total_due'] - ($payment['interest_paid'] + $payment['capital_paid']);
            if (abs($expected_balance - $payment['remaining_balance']) > 0.01) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Balance calculado incorrectamente en cuota {$payment['num_quota']}";
            }

            // Warnings for edge cases
            if ($payment['remaining_balance'] < 0) {
                $validation['warnings'][] = "Balance negativo en cuota {$payment['num_quota']}";
            }
        }

        return $validation;
    }

    /**
     * Generate audit trail for payment calculations
     *
     * @param int $loan_id Loan ID
     * @param array $payment_data Payment processing data
     * @return bool Success status
     */
    public function generate_audit_trail($loan_id, $payment_data) {
        $audit_data = [
            'loan_id' => $loan_id,
            'timestamp' => date('Y-m-d H:i:s'),
            'payment_amount' => $payment_data['total_payment_amount'] ?? 0,
            'breakdown' => json_encode($payment_data['payment_breakdown'] ?? []),
            'redistribution_log' => json_encode($payment_data['redistribution_log'] ?? []),
            'validation_results' => json_encode($this->validate_payment_calculation($payment_data['payment_breakdown'] ?? [])),
            'user_id' => get_user_id(),
            'ip_address' => $this->CI->input->ip_address()
        ];

        return $this->CI->db->insert('payment_audit_trail', $audit_data);
    }
}