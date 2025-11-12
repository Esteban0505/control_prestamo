<?php
/**
 * DEBUG: Diagnóstico de por qué los préstamos no cambian a "Pagado" después de pagos
 * Ejecutar desde línea de comandos: php debug_payment_status_update.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

$target_loans = [123, 124, 125, 126, 127, 128];

echo "=== DIAGNÓSTICO: POR QUÉ LOS PRÉSTAMOS NO CAMBIAN A PAGADO ===\n\n";

echo "PRÉSTAMOS A DIAGNOSTICAR: " . implode(', ', $target_loans) . "\n\n";

foreach ($target_loans as $loan_id) {
    echo "🔍 PRÉSTAMO ID: {$loan_id}\n";

    // 1. Obtener estado actual del préstamo
    $loan_query = $CI->db->query("
        SELECT l.id, l.status as loan_status, l.amortization_type, l.customer_id
        FROM loans l
        WHERE l.id = ?
    ", [$loan_id]);

    $loan = $loan_query->row();

    if (!$loan) {
        echo "   ❌ PRÉSTAMO NO ENCONTRADO\n\n";
        continue;
    }

    echo "   📊 Estado actual del préstamo: " . ($loan->loan_status == 0 ? 'Pagado' : 'Pendiente') . " (status: {$loan->loan_status})\n";

    // 2. Calcular balance actual
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

    echo "   💰 Total esperado (sin condonadas): $" . number_format($balance->total_expected ?? 0, 2) . "\n";
    echo "   💰 Total pagado: $" . number_format($balance->total_paid ?? 0, 2) . "\n";
    echo "   💰 Balance calculado: $" . number_format($balance->balance_amount ?? 0, 2) . "\n";
    echo "   📋 Cuotas pendientes: {$balance->pending_installments}\n";
    echo "   📋 Cuotas condonadas: {$balance->condoned_count}\n";

    // 3. Verificar condición para cambio a pagado
    $should_be_paid = ($balance->balance_amount <= 0.01) && ($balance->pending_installments == 0);
    $expected_status = $should_be_paid ? 0 : 1;

    echo "   🎯 ¿Debería estar pagado? " . ($should_be_paid ? 'SÍ' : 'NO') . "\n";
    echo "   🎯 Estado esperado: " . ($expected_status == 0 ? 'Pagado' : 'Pendiente') . "\n";

    if ($loan->loan_status != $expected_status) {
        echo "   ⚠️  INCONSISTENCIA: Estado actual ({$loan->loan_status}) != Estado esperado ({$expected_status})\n";

        // 4. Verificar qué impide el cambio
        if ($balance->balance_amount > 0.01) {
            echo "      ❌ RAZÓN: Balance pendiente > $0.01\n";
        }
        if ($balance->pending_installments > 0) {
            echo "      ❌ RAZÓN: {$balance->pending_installments} cuotas pendientes\n";
        }

        // 5. Mostrar cuotas pendientes
        if ($balance->pending_installments > 0) {
            $pending_quotas_query = $CI->db->query("
                SELECT num_quota, fee_amount, interest_amount, capital_amount,
                       interest_paid, capital_paid, balance, status, extra_payment
                FROM loan_items
                WHERE loan_id = ? AND status IN (1, 3)
                ORDER BY num_quota
            ", [$loan_id]);

            $pending_quotas = $pending_quotas_query->result();
            echo "      📝 CUOTAS PENDIENTES:\n";
            foreach ($pending_quotas as $quota) {
                $paid = $quota->interest_paid + $quota->capital_paid;
                $remaining = $quota->fee_amount - $paid;
                echo "         Cuota {$quota->num_quota}: Monto $" . number_format($quota->fee_amount, 2) .
                     ", Pagado $" . number_format($paid, 2) .
                     ", Restante $" . number_format($remaining, 2) . "\n";
            }
        }

        // 6. Aplicar corrección automática
        echo "   🔧 APLICANDO CORRECCIÓN...\n";

        if ($should_be_paid && $loan->loan_status != 0) {
            // Cambiar estado del préstamo
            $CI->db->where('id', $loan_id);
            $CI->db->update('loans', ['status' => 0]);
            echo "      ✅ Estado del préstamo actualizado a Pagado\n";

            // Cambiar estado del cliente
            $CI->db->where('id', $loan->customer_id);
            $CI->db->update('customers', ['loan_status' => 0]);
            echo "      ✅ Estado del cliente actualizado a Pagado\n";
        }

    } else {
        echo "   ✅ ESTADO CORRECTO\n";
    }

    // 7. Verificar pagos recientes
    $recent_payments_query = $CI->db->query("
        SELECT p.id, p.amount, p.payment_date, p.monto_pagado, p.interest_paid, p.capital_paid
        FROM payments p
        WHERE p.loan_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 3
    ", [$loan_id]);

    $recent_payments = $recent_payments_query->result();

    if (!empty($recent_payments)) {
        echo "   💳 PAGOS RECIENTES:\n";
        foreach ($recent_payments as $payment) {
            echo "      Pago {$payment->id}: $" . number_format($payment->amount, 2) .
                 " (" . date('d/m/Y H:i', strtotime($payment->payment_date)) . ")\n";
        }
    } else {
        echo "   💳 Sin pagos registrados\n";
    }

    echo "\n";
}

// 8. Verificar función force_status_update
echo "=== VERIFICANDO FUNCIÓN FORCE_STATUS_UPDATE ===\n\n";

$CI->load->model('loans_m');
$updated_count = $CI->loans_m->force_status_update();

echo "✅ Función force_status_update ejecutada: {$updated_count} préstamos actualizados\n\n";

// 9. Verificación final
echo "=== VERIFICACIÓN FINAL ===\n\n";

$final_check_query = $CI->db->query("
    SELECT
        COUNT(*) as total_checked,
        SUM(CASE WHEN l.status != CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END THEN 1 ELSE 0 END) as incorrect_status
    FROM loans l
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.id IN (" . implode(',', $target_loans) . ")
");

$final_check = $final_check_query->row();

echo "📊 Préstamos verificados: {$final_check->total_checked}\n";
echo "⚠️  Estados incorrectos restantes: {$final_check->incorrect_status}\n\n";

if ($final_check->incorrect_status == 0) {
    echo "🎉 ¡PROBLEMA RESUELTO! Todos los préstamos tienen estados correctos.\n\n";
} else {
    echo "⚠️  Aún quedan {$final_check->incorrect_status} préstamos con estados incorrectos.\n\n";

    // Mostrar cuáles
    $incorrect_query = $CI->db->query("
        SELECT l.id, l.status as current_status,
               CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END as expected_status
        FROM loans l
        LEFT JOIN (
            SELECT loan_id,
                   GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
            FROM loan_items GROUP BY loan_id
        ) calc ON calc.loan_id = l.id
        WHERE l.id IN (" . implode(',', $target_loans) . ")
        AND l.status != CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END
    ");

    $incorrect_loans = $incorrect_query->result();
    echo "PRÉSTAMOS CON ESTADOS INCORRECTOS:\n";
    foreach ($incorrect_loans as $incorrect) {
        echo "   ID {$incorrect->current_status}: Estado actual {$incorrect->current_status}, Esperado {$incorrect->expected_status}\n";
    }
    echo "\n";
}

echo "=== FIN DEL DIAGNÓSTICO ===\n";
?>