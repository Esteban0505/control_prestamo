<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment Validator Library
 *
 * Provides comprehensive validation for payment calculations and loan operations
 */
class PaymentValidator {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Validate payment calculation accuracy
     *
     * @param array $payment_breakdown Payment breakdown to validate
     * @return array Validation results with errors and warnings
     */
    public function validate_payment_calculation($payment_breakdown) {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'summary' => [
                'total_interest_due' => 0,
                'total_capital_due' => 0,
                'total_interest_paid' => 0,
                'total_capital_paid' => 0,
                'total_balance_remaining' => 0
            ]
        ];

        if (!is_array($payment_breakdown) || empty($payment_breakdown)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'El desglose de pagos está vacío o no es válido';
            return $validation;
        }

        foreach ($payment_breakdown as $index => $payment) {
            // Validate required fields
            $required_fields = ['installment_id', 'num_quota', 'total_due', 'interest_due', 'capital_due', 'interest_paid', 'capital_paid', 'remaining_balance'];
            foreach ($required_fields as $field) {
                if (!isset($payment[$field])) {
                    $validation['is_valid'] = false;
                    $validation['errors'][] = "Campo requerido faltante '{$field}' en pago índice {$index}";
                }
            }

            if (!$validation['is_valid']) continue;

            // Validate data types and ranges
            if (!is_numeric($payment['total_due']) || $payment['total_due'] < 0) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Monto total debido inválido en cuota {$payment['num_quota']}";
            }

            if (!is_numeric($payment['interest_paid']) || $payment['interest_paid'] < 0) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Interés pagado inválido en cuota {$payment['num_quota']}";
            }

            if (!is_numeric($payment['capital_paid']) || $payment['capital_paid'] < 0) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Capital pagado inválido en cuota {$payment['num_quota']}";
            }

            // Validate payment logic
            $total_paid = $payment['interest_paid'] + $payment['capital_paid'];
            $expected_balance = $payment['total_due'] - $total_paid;

            // Check if paid amounts don't exceed due amounts
            if ($payment['interest_paid'] > $payment['interest_due']) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Interés pagado excede lo debido en cuota {$payment['num_quota']}: {$payment['interest_paid']} > {$payment['interest_due']}";
            }

            if ($payment['capital_paid'] > $payment['capital_due']) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Capital pagado excede lo debido en cuota {$payment['num_quota']}: {$payment['capital_paid']} > {$payment['capital_due']}";
            }

            // Check balance calculation
            if (abs($expected_balance - $payment['remaining_balance']) > 0.01) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Balance calculado incorrectamente en cuota {$payment['num_quota']}: esperado {$expected_balance}, obtenido {$payment['remaining_balance']}";
            }

            // Warnings for edge cases
            if ($payment['remaining_balance'] < -0.01) {
                $validation['warnings'][] = "Balance negativo en cuota {$payment['num_quota']}: {$payment['remaining_balance']}";
            }

            if ($payment['interest_paid'] == 0 && $payment['capital_paid'] == 0 && $payment['total_due'] > 0) {
                $validation['warnings'][] = "Pago cero en cuota {$payment['num_quota']} con monto debido pendiente";
            }

            // Accumulate summary
            $validation['summary']['total_interest_due'] += $payment['interest_due'];
            $validation['summary']['total_capital_due'] += $payment['capital_due'];
            $validation['summary']['total_interest_paid'] += $payment['interest_paid'];
            $validation['summary']['total_capital_paid'] += $payment['capital_paid'];
            $validation['summary']['total_balance_remaining'] += $payment['remaining_balance'];
        }

        return $validation;
    }

    /**
     * Validate loan installment data integrity
     *
     * @param int $loan_id Loan ID to validate
     * @return array Validation results
     */
    public function validate_loan_integrity($loan_id) {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'loan_data' => null
        ];

        // Get loan data
        $loan = $this->CI->loans_m->get_loan($loan_id);
        if (!$loan) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Préstamo no encontrado';
            return $validation;
        }

        $validation['loan_data'] = $loan;

        // Get loan items
        $loan_items = $this->CI->loans_m->get_loanItems($loan_id);
        if (empty($loan_items)) {
            $validation['warnings'][] = 'El préstamo no tiene cuotas definidas';
            return $validation;
        }

        // Validate installment sequence
        $expected_quota = 1;
        $total_balance = 0;

        foreach ($loan_items as $item) {
            // Check quota numbering
            if ($item->num_quota != $expected_quota) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Número de cuota incorrecto: esperado {$expected_quota}, encontrado {$item->num_quota}";
            }
            $expected_quota++;

            // Validate amounts
            if ($item->fee_amount <= 0) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Monto de cuota inválido en cuota {$item->num_quota}: {$item->fee_amount}";
            }

            if ($item->interest_amount < 0 || $item->capital_amount < 0) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Montos negativos en cuota {$item->num_quota}";
            }

            // Check balance consistency
            $calculated_balance = $item->fee_amount - ($item->interest_paid + $item->capital_paid);
            if (abs($calculated_balance - $item->balance) > 0.01) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Balance inconsistente en cuota {$item->num_quota}: calculado {$calculated_balance}, almacenado {$item->balance}";
            }

            $total_balance += $item->balance;

            // Validate status
            $valid_statuses = [0, 1, 2, 3]; // Paid, Pending, Partial, Incomplete
            if (!in_array($item->status, $valid_statuses)) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Estado inválido en cuota {$item->num_quota}: {$item->status}";
            }
        }

        // Validate loan balance
        $loan_balance = $this->CI->loans_m->get_loan_balance($loan_id);
        if (abs($total_balance - $loan_balance) > 0.01) {
            $validation['warnings'][] = "Balance del préstamo inconsistente: cuotas suman {$total_balance}, préstamo registra {$loan_balance}";
        }

        return $validation;
    }

    /**
     * Validate payment amount against loan limits
     *
     * @param float $payment_amount Payment amount
     * @param int $loan_id Loan ID
     * @param array $selected_installments Selected installments
     * @return array Validation results
     */
    public function validate_payment_amount($payment_amount, $loan_id, $selected_installments) {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'max_allowed' => 0,
            'min_required' => 0
        ];

        if ($payment_amount <= 0) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'El monto del pago debe ser mayor a cero';
            return $validation;
        }

        // Calculate maximum allowed payment (sum of all pending balances)
        $all_pending = $this->CI->loans_m->get_loanItems($loan_id);
        $total_pending_balance = 0;

        foreach ($all_pending as $item) {
            if ($item->status != 0) { // Not fully paid
                $total_pending_balance += $item->balance;
            }
        }

        $validation['max_allowed'] = $total_pending_balance;

        // Calculate minimum required for selected installments
        $selected_pending_balance = 0;
        foreach ($selected_installments as $installment) {
            $selected_pending_balance += $installment['balance'];
        }

        $validation['min_required'] = min($selected_pending_balance, $payment_amount);

        // Validate payment amount
        if ($payment_amount > $total_pending_balance) {
            $validation['warnings'][] = "El pago excede el balance total pendiente: {$payment_amount} > {$total_pending_balance}";
        }

        // Check if payment covers at least one installment partially
        $smallest_balance = PHP_FLOAT_MAX;
        foreach ($selected_installments as $installment) {
            if ($installment['balance'] > 0) {
                $smallest_balance = min($smallest_balance, $installment['balance']);
            }
        }

        if ($payment_amount < 0.01 && $smallest_balance < PHP_FLOAT_MAX) {
            $validation['warnings'][] = 'El monto del pago es muy pequeño para afectar las cuotas seleccionadas';
        }

        return $validation;
    }

    /**
     * Validate installment selection logic
     *
     * @param array $selected_installments Selected installments
     * @param int $loan_id Loan ID
     * @return array Validation results
     */
    public function validate_installment_selection($selected_installments, $loan_id) {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        if (empty($selected_installments)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Debe seleccionar al menos una cuota';
            return $validation;
        }

        // Check for duplicates
        $ids = array_column($selected_installments, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Hay cuotas duplicadas en la selección';
        }

        // Validate each installment belongs to the loan and is payable
        foreach ($selected_installments as $installment) {
            if (!isset($installment['id']) || !isset($installment['loan_id'])) {
                $validation['is_valid'] = false;
                $validation['errors'][] = 'Datos de cuota incompletos';
                continue;
            }

            if ($installment['loan_id'] != $loan_id) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "La cuota {$installment['id']} no pertenece al préstamo {$loan_id}";
            }

            if ($installment['status'] == 0) {
                $validation['warnings'][] = "La cuota {$installment['num_quota']} ya está completamente pagada";
            }

            if ($installment['balance'] <= 0) {
                $validation['warnings'][] = "La cuota {$installment['num_quota']} no tiene balance pendiente";
            }
        }

        return $validation;
    }

    /**
     * Generate comprehensive validation report
     *
     * @param array $validation_results Array of validation results
     * @return string HTML report
     */
    public function generate_validation_report($validation_results) {
        $report = '<div class="validation-report">';
        $report .= '<h4>Reporte de Validación de Pagos</h4>';

        $has_errors = false;
        $has_warnings = false;

        foreach ($validation_results as $type => $results) {
            if (!empty($results['errors'])) {
                $has_errors = true;
                $report .= '<div class="alert alert-danger">';
                $report .= '<h5>Errores en ' . ucfirst($type) . ':</h5>';
                $report .= '<ul>';
                foreach ($results['errors'] as $error) {
                    $report .= '<li>' . htmlspecialchars($error) . '</li>';
                }
                $report .= '</ul>';
                $report .= '</div>';
            }

            if (!empty($results['warnings'])) {
                $has_warnings = true;
                $report .= '<div class="alert alert-warning">';
                $report .= '<h5>Advertencias en ' . ucfirst($type) . ':</h5>';
                $report .= '<ul>';
                foreach ($results['warnings'] as $warning) {
                    $report .= '<li>' . htmlspecialchars($warning) . '</li>';
                }
                $report .= '</ul>';
                $report .= '</div>';
            }
        }

        if (!$has_errors && !$has_warnings) {
            $report .= '<div class="alert alert-success">';
            $report .= '<h5>✅ Validación Exitosa</h5>';
            $report .= '<p>Todos los cálculos y datos son correctos.</p>';
            $report .= '</div>';
        }

        $report .= '</div>';

        return $report;
    }
}