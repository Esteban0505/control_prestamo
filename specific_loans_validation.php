<?php
/**
 * VALIDACIÓN ESPECÍFICA DE PRÉSTAMOS MENCIONADOS
 * Préstamos IDs: 123, 124, 125, 126, 127, 128
 * Ejecutar desde línea de comandos: php specific_loans_validation.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

$target_loans = [123, 124, 125, 126, 127, 128];

echo "=== VALIDACIÓN ESPECÍFICA DE PRÉSTAMOS ===\n\n";

echo "PRÉSTAMOS A REVISAR: " . implode(', ', $target_loans) . "\n\n";

foreach ($target_loans as $loan_id) {
    echo "🔍 PRÉSTAMO ID: {$loan_id}\n";

    // Obtener datos del préstamo
    $loan_query = $CI->db->query("
        SELECT
            l.id,
            l.status as loan_status,
            l.amortization_type,
            l.customer_id,
            c.loan_status as customer_loan_status,
            COALESCE(calc.total_expected, 0) as total_expected,
            COALESCE(calc.total_paid, 0) as total_paid,
            COALESCE(calc.balance_amount, 0) as calculated_balance,
            COALESCE(calc.condoned_count, 0) as condoned_count,
            COALESCE(calc.pending_count, 0) as pending_installments
        FROM loans l
        JOIN customers c ON c.id = l.customer_id
        LEFT JOIN (
            SELECT
                li.loan_id,
                SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
                SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
                GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount,
                SUM(CASE WHEN li.extra_payment = 3 THEN 1 ELSE 0 END) AS condoned_count,
                SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_count
            FROM loan_items li
            WHERE li.loan_id = ?
            GROUP BY li.loan_id
        ) calc ON calc.loan_id = l.id
        WHERE l.id = ?
    ", [$loan_id, $loan_id]);

    $loan = $loan_query->row();

    if (!$loan) {
        echo "   ❌ PRÉSTAMO NO ENCONTRADO\n\n";
        continue;
    }

    // Estado del préstamo
    $loan_status_text = $loan->loan_status == 0 ? 'Pagado' : 'Pendiente';
    echo "   📊 Estado del Préstamo: {$loan_status_text} (status: {$loan->loan_status})\n";

    // Estado del cliente
    $customer_status_text = $loan->customer_loan_status == 0 ? 'Pagado' : 'Pendiente';
    echo "   👤 Estado del Cliente: {$customer_status_text} (status: {$loan->customer_loan_status})\n";

    // Balances
    echo "   💰 Total Esperado: $" . number_format($loan->total_expected, 2) . "\n";
    echo "   💰 Total Pagado: $" . number_format($loan->total_paid, 2) . "\n";
    echo "   💰 Balance Calculado: $" . number_format($loan->calculated_balance, 2) . "\n";

    // Cuotas
    echo "   📋 Cuotas Condonadas: {$loan->condoned_count}\n";
    echo "   📋 Cuotas Pendientes: {$loan->pending_installments}\n";

    // Amortización
    echo "   🔢 Tipo de Amortización: {$loan->amortization_type}\n";

    // Verificar inconsistencias
    $issues = [];

    // Balance negativo
    if ($loan->calculated_balance < -0.01) {
        $issues[] = "BALANCE NEGATIVO: $" . number_format($loan->calculated_balance, 2);
    }

    // Estado incorrecto del préstamo
    $expected_loan_status = ($loan->calculated_balance <= 0.01) ? 0 : 1;
    if ($loan->loan_status != $expected_loan_status) {
        $issues[] = "ESTADO DE PRÉSTAMO INCORRECTO: {$loan->loan_status} (debería ser {$expected_loan_status})";
    }

    // Estado incorrecto del cliente
    if ($loan->customer_loan_status != $expected_loan_status) {
        $issues[] = "ESTADO DE CLIENTE INCORRECTO: {$loan->customer_loan_status} (debería ser {$expected_loan_status})";
    }

    // Cuotas condonadas pendientes
    if ($loan->condoned_count > 0) {
        $condoned_pending_query = $CI->db->query("
            SELECT COUNT(*) as pending_condoned
            FROM loan_items
            WHERE loan_id = ? AND extra_payment = 3 AND status != 0
        ", [$loan_id]);
        $pending_condoned = $condoned_pending_query->row()->pending_condoned ?? 0;

        if ($pending_condoned > 0) {
            $issues[] = "CUOTAS CONDONADAS PENDIENTES: {$pending_condoned}";
        }
    }

    // Reportar issues
    if (!empty($issues)) {
        echo "   ⚠️  INCONSISTENCIAS DETECTADAS:\n";
        foreach ($issues as $issue) {
            echo "      - {$issue}\n";
        }
    } else {
        echo "   ✅ SIN INCONSISTENCIAS\n";
    }

    // Detalle de cuotas
    echo "   📝 DETALLE DE CUOTAS:\n";
    $quotas_query = $CI->db->query("
        SELECT
            num_quota,
            fee_amount,
            interest_amount,
            capital_amount,
            interest_paid,
            capital_paid,
            balance,
            status,
            extra_payment
        FROM loan_items
        WHERE loan_id = ?
        ORDER BY num_quota
    ", [$loan_id]);

    $quotas = $quotas_query->result();
    foreach ($quotas as $quota) {
        $status_text = $quota->status == 0 ? 'Pagada' : ($quota->status == 1 ? 'Pendiente' : 'Otro');
        $extra_text = $quota->extra_payment == 3 ? ' (CONDONADA)' : '';
        $paid_total = $quota->interest_paid + $quota->capital_paid;

        echo "      Cuota {$quota->num_quota}: Monto $" . number_format($quota->fee_amount, 2) .
             ", Pagado $" . number_format($paid_total, 2) .
             ", Estado: {$status_text}{$extra_text}\n";
    }

    echo "\n";
}

// Resumen final
echo "=== RESUMEN DE VALIDACIÓN ===\n\n";

$summary_query = $CI->db->query("
    SELECT
        COUNT(*) as total_checked,
        SUM(CASE WHEN l.status != CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END THEN 1 ELSE 0 END) as incorrect_loan_status,
        SUM(CASE WHEN c.loan_status != CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END THEN 1 ELSE 0 END) as incorrect_customer_status,
        SUM(CASE WHEN COALESCE(calc.balance_amount, 0) < -0.01 THEN 1 ELSE 0 END) as negative_balances
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.id IN (" . implode(',', $target_loans) . ")
");

$summary = $summary_query->row();

echo "📊 PRÉSTAMOS REVISADOS: {$summary->total_checked}\n";
echo "⚠️  Estados de préstamo incorrectos: {$summary->incorrect_loan_status}\n";
echo "⚠️  Estados de cliente incorrectos: {$summary->incorrect_customer_status}\n";
echo "⚠️  Balances negativos: {$summary->negative_balances}\n\n";

if ($summary->incorrect_loan_status > 0 || $summary->incorrect_customer_status > 0 || $summary->negative_balances > 0) {
    echo "🔧 APLICANDO CORRECCIONES AUTOMÁTICAS...\n\n";

    // Ejecutar force_status_update para estos préstamos específicos
    $loans_model = $CI->load->model('loans_m');
    $updated = $CI->loans_m->force_status_update();

    echo "✅ Correcciones aplicadas a {$updated} préstamos\n\n";

    // Ejecutar sincronización de clientes
    $customer_sync = $CI->db->query("
        UPDATE customers c
        JOIN loans l ON l.customer_id = c.id
        LEFT JOIN (
            SELECT loan_id,
                   GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
            FROM loan_items GROUP BY loan_id
        ) calc ON calc.loan_id = l.id
        SET c.loan_status = CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END
        WHERE l.id IN (" . implode(',', $target_loans) . ")
    ");

    echo "✅ Estados de clientes sincronizados\n\n";
} else {
    echo "🎉 TODOS LOS PRÉSTAMOS ESTÁN CORRECTOS\n\n";
}

echo "=== FIN DE VALIDACIÓN ESPECÍFICA ===\n";
?>