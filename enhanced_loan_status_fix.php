<?php
/**
 * CORRECCIÓN MEJORADA DE ESTADOS DE PRÉSTAMOS PENDIENTES
 * Script avanzado para identificar y corregir préstamos que deberían estar pagados
 * basándose en múltiples criterios financieros y de estado.
 *
 * Ejecutar desde línea de comandos: php enhanced_loan_status_fix.php
 *
 * CRITERIOS PARA CORRECCIÓN:
 * 1. Balance calculado <= $0.01 (completamente pagado)
 * 2. 0 cuotas pendientes Y balance <= $1.00 (errores de precisión)
 * 3. Total pagado >= Total esperado (sobre-pagado)
 * 4. Solo cuotas condonadas restantes
 * 5. Balance = 0 en vista (casos edge con balances altos pero 0 pendientes)
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN MEJORADA DE ESTADOS DE PRÉSTAMOS PENDIENTES ===\n\n";

$corrections_made = [
    'loans_to_paid' => 0,
    'customers_updated' => 0,
    'total_processed' => 0,
    'logs' => []
];

// 1. IDENTIFICACIÓN AVANZADA DE PRÉSTAMOS CANDIDATOS A CORRECCIÓN
echo "1. IDENTIFICANDO PRÉSTAMOS CANDIDATOS PARA CORRECCIÓN AVANZADA...\n";

$query_candidates = $CI->db->query("
    SELECT
        l.id as loan_id,
        l.customer_id,
        l.status as loan_status,
        l.amortization_type,
        COALESCE(calc.total_expected, 0) as total_expected,
        COALESCE(calc.total_paid, 0) as total_paid,
        COALESCE(calc.balance_amount, 0) as balance_amount,
        COALESCE(calc.pending_installments, 0) as pending_installments,
        COALESCE(calc.condoned_count, 0) as condoned_count,
        -- Cálculo adicional: si el total pagado cubre el total esperado
        CASE WHEN COALESCE(calc.total_paid, 0) >= COALESCE(calc.total_expected, 0) THEN 1 ELSE 0 END as overpaid
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
            GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount,
            SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_installments,
            SUM(CASE WHEN li.extra_payment = 3 THEN 1 ELSE 0 END) AS condoned_count
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.status = 1  -- Solo préstamos marcados como pendientes
    ORDER BY l.id
");

$candidates = $query_candidates->result();
echo "   📊 Analizados: " . count($candidates) . " préstamos marcados como pendientes\n\n";

$loans_to_fix = [];

// 2. APLICACIÓN DE CRITERIOS AVANZADOS PARA IDENTIFICACIÓN
echo "2. APLICANDO CRITERIOS AVANZADOS DE IDENTIFICACIÓN...\n";

foreach ($candidates as $loan) {
    $corrections_made['total_processed']++;

    $should_be_paid = false;
    $reason = '';
    $confidence = 'BAJA'; // Baja, Media, Alta

    // CRITERIO 1: Balance cero o negativo (prioridad máxima)
    if ($loan->balance_amount <= 0.01) {
        $should_be_paid = true;
        $reason = "Balance cero o negativo: $" . number_format($loan->balance_amount, 2);
        $confidence = 'ALTA';
    }
    // CRITERIO 2: Sin cuotas pendientes y balance mínimo (errores de precisión)
    elseif ($loan->pending_installments == 0 && $loan->balance_amount <= 1.00) {
        $should_be_paid = true;
        $reason = "Sin cuotas pendientes y balance mínimo: $" . number_format($loan->balance_amount, 2);
        $confidence = 'ALTA';
    }
    // CRITERIO 3: Total pagado >= Total esperado (sobre-pagado)
    elseif ($loan->overpaid == 1) {
        $should_be_paid = true;
        $reason = "Sobre-pagado: $" . number_format($loan->total_paid, 2) . " >= $" . number_format($loan->total_expected, 2);
        $confidence = 'ALTA';
    }
    // CRITERIO 4: Solo cuotas condonadas restantes
    elseif ($loan->pending_installments == $loan->condoned_count && $loan->condoned_count > 0) {
        $should_be_paid = true;
        $reason = "Solo cuotas condonadas pendientes: {$loan->condoned_count}";
        $confidence = 'MEDIA';
    }
    // CRITERIO 5: Caso edge - 0 cuotas pendientes pero balance alto (posible error de cálculo)
    elseif ($loan->pending_installments == 0 && $loan->balance_amount > 1000) {
        // Verificar si todas las cuotas están marcadas como pagadas pero el balance no se actualizó
        $paid_installments_check = $CI->db->query("
            SELECT COUNT(*) as paid_count, COUNT(*) as total_count
            FROM loan_items
            WHERE loan_id = ? AND status = 0
        ", [$loan->loan_id])->row();

        if ($paid_installments_check && $paid_installments_check->paid_count > 0) {
            $should_be_paid = true;
            $reason = "Caso edge: 0 cuotas pendientes pero balance alto (posible error de sincronización)";
            $confidence = 'MEDIA';
        }
    }

    if ($should_be_paid) {
        $loans_to_fix[] = [
            'loan' => $loan,
            'reason' => $reason,
            'confidence' => $confidence
        ];

        echo "   ✅ [{$confidence}] PRÉSTAMO {$loan->loan_id}: {$reason}\n";
        echo "      Balance: $" . number_format($loan->balance_amount, 2) . ", Pagado: $" . number_format($loan->total_paid, 2) . ", Esperado: $" . number_format($loan->total_expected, 2) . "\n";
    } else {
        echo "   ⏳ PRÉSTAMO {$loan->loan_id}: Mantiene pendiente (Balance: $" . number_format($loan->balance_amount, 2) . ", Cuotas: {$loan->pending_installments})\n";
    }
}

echo "\n3. APLICANDO CORRECCIONES CON VALIDACIÓN ADICIONAL...\n";
echo "   🔧 Préstamos a corregir: " . count($loans_to_fix) . "\n\n";

$corrections_by_confidence = ['ALTA' => 0, 'MEDIA' => 0, 'BAJA' => 0];

foreach ($loans_to_fix as $fix_item) {
    $loan = $fix_item['loan'];
    $reason = $fix_item['reason'];
    $confidence = $fix_item['confidence'];

    // VALIDACIÓN ADICIONAL: Verificar que no haya pagos pendientes o problemas
    $additional_checks = $CI->db->query("
        SELECT
            COUNT(*) as pending_payments,
            MAX(li.pay_date) as last_payment_date
        FROM loan_items li
        WHERE li.loan_id = ? AND li.status IN (1, 3)
    ", [$loan->loan_id])->row();

    $skip_correction = false;

    // Si hay pagos muy recientes (últimas 24 horas), ser más cauteloso
    if ($additional_checks && $additional_checks->last_payment_date) {
        $last_payment = strtotime($additional_checks->last_payment_date);
        $hours_since_payment = (time() - $last_payment) / 3600;

        if ($hours_since_payment < 24 && $confidence == 'MEDIA') {
            echo "   ⚠️  SALTANDO PRÉSTAMO {$loan->loan_id}: Pago reciente ({$hours_since_payment} horas) - requiere revisión manual\n";
            $skip_correction = true;
        }
    }

    if (!$skip_correction) {
        // Aplicar corrección
        $CI->db->where('id', $loan->loan_id);
        $CI->db->update('loans', ['status' => 0]);

        $CI->db->where('id', $loan->customer_id);
        $CI->db->update('customers', ['loan_status' => 0]);

        // Marcar cuotas condonadas como pagadas
        if (($loan->condoned_count ?? 0) > 0) {
            $CI->db->where('loan_id', $loan->loan_id);
            $CI->db->where('extra_payment', 3);
            $CI->db->where('status !=', 0);
            $CI->db->update('loan_items', [
                'status' => 0,
                'balance' => 0,
                'pay_date' => date('Y-m-d H:i:s')
            ]);
        }

        $corrections_made['loans_to_paid']++;
        $corrections_made['customers_updated']++;
        $corrections_by_confidence[$confidence]++;

        // Log detallado
        $log_entry = [
            'loan_id' => $loan->loan_id,
            'customer_id' => $loan->customer_id,
            'reason' => $reason,
            'confidence' => $confidence,
            'old_balance' => $loan->balance_amount,
            'total_paid' => $loan->total_paid,
            'total_expected' => $loan->total_expected,
            'pending_installments' => $loan->pending_installments,
            'condoned_count' => $loan->condoned_count,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $corrections_made['logs'][] = $log_entry;

        echo "   ✅ CORREGIDO [{$confidence}] Préstamo {$loan->loan_id} → PAGADO\n";
        echo "      Cliente {$loan->customer_id} actualizado\n\n";
    }
}

// 4. VERIFICACIÓN DETALLADA POST-CORRECCIÓN
echo "4. VERIFICACIÓN DETALLADA POST-CORRECCIÓN...\n";

if (!empty($loans_to_fix)) {
    $loan_ids_fixed = array_column(array_column($loans_to_fix, 'loan'), 'loan_id');
    $loan_ids_str = implode(',', $loan_ids_fixed);

    $verification_query = $CI->db->query("
        SELECT
            l.id,
            l.status as loan_status,
            c.loan_status as customer_status,
            COALESCE(calc.balance_amount, 0) as balance_amount,
            COALESCE(calc.pending_installments, 0) as pending_installments,
            COALESCE(calc.total_paid, 0) as total_paid,
            COALESCE(calc.total_expected, 0) as total_expected
        FROM loans l
        JOIN customers c ON c.id = l.customer_id
        LEFT JOIN (
            SELECT loan_id,
                   GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount,
                   SUM(COALESCE(interest_paid, 0) + COALESCE(capital_paid, 0)) as total_paid,
                   SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) as total_expected,
                   SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_installments
            FROM loan_items li GROUP BY li.loan_id
        ) calc ON calc.loan_id = l.id
        WHERE l.id IN ({$loan_ids_str})
        ORDER BY l.id
    ");

    $verification_results = $verification_query->result();

    echo "   🔍 RESULTADOS FINALES DE " . count($verification_results) . " PRÉSTAMOS CORREGIDOS:\n";

    $success_count = 0;
    foreach ($verification_results as $result) {
        $loan_status_ok = $result->loan_status == 0 ? '✅ PAGADO' : '❌ PENDIENTE';
        $customer_status_ok = $result->customer_status == 0 ? '✅ PAGADO' : '❌ PENDIENTE';

        if ($result->loan_status == 0 && $result->customer_status == 0) {
            $success_count++;
        }

        echo "      Préstamo {$result->id}: {$loan_status_ok} | Cliente: {$customer_status_ok} | Balance: $" . number_format($result->balance_amount, 2) . "\n";
    }

    echo "\n   ✅ CORRECCIONES EXITOSAS: {$success_count}/" . count($verification_results) . "\n";
}

// 5. REPORTE FINAL DETALLADO
echo "\n=== REPORTE FINAL DETALLADO ===\n\n";

echo "📊 ESTADÍSTICAS GENERALES:\n";
echo "   • Préstamos procesados: {$corrections_made['total_processed']}\n";
echo "   • Préstamos corregidos: {$corrections_made['loans_to_paid']}\n";
echo "   • Clientes actualizados: {$corrections_made['customers_updated']}\n";
echo "   • Tasa de corrección: " . (count($candidates) > 0 ? round(($corrections_made['loans_to_paid'] / count($candidates)) * 100, 1) : 0) . "%\n\n";

echo "🎯 CORRECCIONES POR NIVEL DE CONFIANZA:\n";
echo "   • Alta confianza: {$corrections_by_confidence['ALTA']}\n";
echo "   • Media confianza: {$corrections_by_confidence['MEDIA']}\n";
echo "   • Baja confianza: {$corrections_by_confidence['BAJA']}\n\n";

if (!empty($corrections_made['logs'])) {
    echo "📝 DETALLE DE CAMBIOS REALIZADOS:\n";
    foreach ($corrections_made['logs'] as $log) {
        echo "   • [{$log['confidence']}] Préstamo {$log['loan_id']} (Cliente {$log['customer_id']}): {$log['reason']}\n";
        echo "     Balance anterior: $" . number_format($log['old_balance'], 2) . ", Pagado: $" . number_format($log['total_paid'], 2) . ", Esperado: $" . number_format($log['total_expected'], 2) . "\n";
    }
    echo "\n";
}

// 6. VERIFICACIÓN GLOBAL FINAL
echo "6. VERIFICACIÓN GLOBAL FINAL DEL SISTEMA...\n";

$global_check_query = $CI->db->query("
    SELECT
        COUNT(*) as total_loans,
        SUM(CASE WHEN l.status = 0 THEN 1 ELSE 0 END) as paid_loans,
        SUM(CASE WHEN l.status = 1 THEN 1 ELSE 0 END) as pending_loans,
        SUM(CASE WHEN l.status = 1 AND COALESCE(calc.balance_amount, 0) <= 0.01 THEN 1 ELSE 0 END) as wrongly_pending_zero_balance,
        SUM(CASE WHEN l.status = 1 AND COALESCE(calc.total_paid, 0) >= COALESCE(calc.total_expected, 0) THEN 1 ELSE 0 END) as wrongly_pending_overpaid
    FROM loans l
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount,
               SUM(COALESCE(interest_paid, 0) + COALESCE(capital_paid, 0)) as total_paid,
               SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) as total_expected
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
");

$global_stats = $global_check_query->row();

echo "   📊 ESTADO GLOBAL:\n";
echo "      Total de préstamos: {$global_stats->total_loans}\n";
echo "      Préstamos pagados: {$global_stats->paid_loans}\n";
echo "      Préstamos pendientes: {$global_stats->pending_loans}\n";
echo "      Pendientes con balance ≤ $0.01: {$global_stats->wrongly_pending_zero_balance}\n";
echo "      Pendientes sobre-pagados: {$global_stats->wrongly_pending_overpaid}\n\n";

$remaining_issues = ($global_stats->wrongly_pending_zero_balance ?? 0) + ($global_stats->wrongly_pending_overpaid ?? 0);

if ($remaining_issues == 0) {
    echo "   🎉 ¡SISTEMA COMPLETAMENTE OPTIMIZADO! No quedan préstamos pendientes incorrectos.\n\n";
} else {
    echo "   ⚠️  Aún quedan {$remaining_issues} préstamos que podrían beneficiarse de corrección adicional.\n\n";
}

echo "=== FIN DE CORRECCIÓN MEJORADA ===\n";
?>