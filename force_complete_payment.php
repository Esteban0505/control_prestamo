<?php
/**
 * FUERZA COMPLETACIÓN DE PAGOS PARA PRÉSTAMOS ESPECÍFICOS
 * Aplica pagos faltantes y actualiza estados para préstamos 123-128
 * Ejecutar desde línea de comandos: php force_complete_payment.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

$target_loans = [123, 124, 125, 126, 127, 128];

echo "=== FUERZA COMPLETACIÓN DE PAGOS PARA PRÉSTAMOS ESPECÍFICOS ===\n\n";

echo "PRÉSTAMOS A PROCESAR: " . implode(', ', $target_loans) . "\n\n";

$corrections_applied = [];

foreach ($target_loans as $loan_id) {
    echo "🔄 PROCESANDO PRÉSTAMO ID: {$loan_id}\n";

    // 1. Obtener datos actuales del préstamo
    $loan_query = $CI->db->query("
        SELECT l.id, l.status, l.customer_id, l.amortization_type
        FROM loans l
        WHERE l.id = ?
    ", [$loan_id]);

    $loan = $loan_query->row();

    if (!$loan) {
        echo "   ❌ PRÉSTAMO NO ENCONTRADO\n\n";
        continue;
    }

    // 2. Calcular balance y cuotas pendientes
    $balance_query = $CI->db->query("
        SELECT
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
            GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount,
            COUNT(CASE WHEN li.status IN (1, 3) THEN 1 END) AS pending_installments,
            COUNT(CASE WHEN li.extra_payment = 3 THEN 1 END) AS condoned_count
        FROM loan_items li
        WHERE li.loan_id = ?
    ", [$loan_id]);

    $balance = $balance_query->row();

    echo "   💰 Balance actual: $" . number_format($balance->balance_amount ?? 0, 2) . "\n";
    echo "   📋 Cuotas pendientes: {$balance->pending_installments}\n";
    echo "   📋 Cuotas condonadas: {$balance->condoned_count}\n";

    // 3. Aplicar pago faltante para completar el préstamo
    if (($balance->balance_amount ?? 0) > 0) {
        echo "   💳 Aplicando pago faltante de $" . number_format($balance->balance_amount, 2) . "\n";

        // Obtener la última cuota pendiente
        $pending_quota_query = $CI->db->query("
            SELECT * FROM loan_items
            WHERE loan_id = ? AND status IN (1, 3)
            ORDER BY num_quota DESC
            LIMIT 1
        ", [$loan_id]);

        $pending_quota = $pending_quota_query->row();

        if ($pending_quota) {
            // Calcular cuánto aplicar a intereses y capital
            $remaining_balance = $balance->balance_amount;
            $interest_to_pay = min($remaining_balance, ($pending_quota->interest_amount - $pending_quota->interest_paid));
            $capital_to_pay = $remaining_balance - $interest_to_pay;

            // Actualizar cuota
            $CI->db->where('id', $pending_quota->id);
            $CI->db->update('loan_items', [
                'interest_paid' => $pending_quota->interest_paid + $interest_to_pay,
                'capital_paid' => $pending_quota->capital_paid + $capital_to_pay,
                'balance' => 0,
                'status' => 0,
                'pay_date' => date('Y-m-d H:i:s')
            ]);

            // Registrar pago en tabla payments
            $CI->db->insert('payments', [
                'loan_id' => $loan_id,
                'loan_item_id' => $pending_quota->id,
                'amount' => $remaining_balance,
                'tipo_pago' => 'force_complete',
                'monto_pagado' => $remaining_balance,
                'interest_paid' => $interest_to_pay,
                'capital_paid' => $capital_to_pay,
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_user_id' => 1, // Admin
                'method' => 'sistema',
                'notes' => 'Pago forzado para completar préstamo - Corrección automática'
            ]);

            echo "      ✅ Pago aplicado: Interés $" . number_format($interest_to_pay, 2) . ", Capital $" . number_format($capital_to_pay, 2) . "\n";
        }
    }

    // 4. Actualizar estado del préstamo a pagado
    $CI->db->where('id', $loan_id);
    $CI->db->update('loans', ['status' => 0]);

    // 5. Actualizar estado del cliente
    $CI->db->where('id', $loan->customer_id);
    $CI->db->update('customers', ['loan_status' => 0]);

    // 6. Marcar cuotas condonadas como pagadas
    if (($balance->condoned_count ?? 0) > 0) {
        $CI->db->where('loan_id', $loan_id);
        $CI->db->where('extra_payment', 3);
        $CI->db->where('status !=', 0);
        $CI->db->update('loan_items', [
            'status' => 0,
            'balance' => 0,
            'pay_date' => date('Y-m-d H:i:s')
        ]);
    }

    $corrections_applied[] = [
        'loan_id' => $loan_id,
        'customer_id' => $loan->customer_id,
        'old_balance' => $balance->balance_amount ?? 0,
        'pending_installments' => $balance->pending_installments ?? 0,
        'condoned_count' => $balance->condoned_count ?? 0
    ];

    echo "   ✅ PRÉSTAMO {$loan_id} COMPLETADO Y MARCADO COMO PAGADO\n";
    echo "   👤 Cliente {$loan->customer_id} actualizado\n\n";
}

// VERIFICACIÓN FINAL
echo "=== VERIFICACIÓN FINAL ===\n\n";

$verification_query = $CI->db->query("
    SELECT
        l.id,
        l.status as loan_status,
        c.loan_status as customer_status,
        COALESCE(calc.balance_amount, 0) as balance_amount,
        COALESCE(calc.pending_installments, 0) as pending_installments
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount,
               SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_installments
        FROM loan_items li GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.id IN (" . implode(',', $target_loans) . ")
");

$verification_results = $verification_query->result();

echo "🔍 RESULTADOS FINALES:\n";
foreach ($verification_results as $result) {
    $loan_status_ok = $result->loan_status == 0 ? '✅ PAGADO' : '❌ PENDIENTE';
    $customer_status_ok = $result->customer_status == 0 ? '✅ PAGADO' : '❌ PENDIENTE';

    echo "   Préstamo {$result->id}: {$loan_status_ok} | Cliente: {$customer_status_ok} | Balance: $" . number_format($result->balance_amount, 2) . " | Cuotas pendientes: {$result->pending_installments}\n";
}

echo "\n=== RESUMEN DE CORRECCIONES ===\n\n";

echo "📊 PRÉSTAMOS PROCESADOS: " . count($corrections_applied) . "\n";
echo "✅ TODOS LOS PRÉSTAMOS FUERZADOS A PAGADO\n";
echo "✅ PAGOS APLICADOS PARA COMPLETAR BALANCES\n";
echo "✅ ESTADOS DE CLIENTES SINCRONIZADOS\n";
echo "✅ CUOTAS CONDONADAS MARCADAS COMO PAGADAS\n\n";

echo "⚠️  NOTA: Esta fue una corrección forzada. Verifique que los pagos aplicados sean correctos.\n\n";

echo "=== FIN DE FUERZA COMPLETACIÓN ===\n";
?>