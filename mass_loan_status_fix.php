<?php
/**
 * CORRECCIÓN MASIVA DE ESTADOS DE PRÉSTAMOS PENDIENTES
 * Identifica y corrige todos los préstamos que deberían estar pagados
 * Ejecutar desde línea de comandos: php mass_loan_status_fix.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN MASIVA DE ESTADOS DE PRÉSTAMOS PENDIENTES ===\n\n";

$corrections_made = [
    'loans_to_paid' => 0,
    'customers_updated' => 0,
    'total_processed' => 0,
    'logs' => []
];

// 1. IDENTIFICAR TODOS LOS PRÉSTAMOS PENDIENTES QUE DEBERÍAN ESTAR PAGADOS
echo "1. IDENTIFICANDO TODOS LOS PRÉSTAMOS PENDIENTES PARA CORRECCIÓN...\n";

$query_pending_loans = $CI->db->query("
    SELECT
        l.id as loan_id,
        l.customer_id,
        l.status as loan_status,
        l.amortization_type,
        COALESCE(calc.total_expected, 0) as total_expected,
        COALESCE(calc.total_paid, 0) as total_paid,
        COALESCE(calc.balance_amount, 0) as balance_amount,
        COALESCE(calc.pending_installments, 0) as pending_installments,
        COALESCE(calc.condoned_count, 0) as condoned_count
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

$pending_loans = $query_pending_loans->result();
echo "   📊 Encontrados: " . count($pending_loans) . " préstamos marcados como pendientes\n\n";

$loans_to_fix = [];

foreach ($pending_loans as $loan) {
    $corrections_made['total_processed']++;

    // CRITERIOS PARA CORRECCIÓN:
    // 1. Balance <= $0.01 (completamente pagado)
    // 2. 0 cuotas pendientes Y balance mínimo (errores de precisión)
    // 3. Solo cuotas condonadas restantes
    // 4. Balance = 0 en la vista (pagado completamente)

    $should_be_paid = false;
    $reason = '';

    if ($loan->balance_amount <= 0.01) {
        $should_be_paid = true;
        $reason = "Balance cero o negativo: $" . number_format($loan->balance_amount, 2);
    } elseif ($loan->pending_installments == 0 && $loan->balance_amount <= 1.00) {
        // Umbral pequeño para errores de precisión
        $should_be_paid = true;
        $reason = "Sin cuotas pendientes y balance mínimo: $" . number_format($loan->balance_amount, 2);
    } elseif ($loan->pending_installments == $loan->condoned_count && $loan->condoned_count > 0) {
        // Solo quedan cuotas condonadas
        $should_be_paid = true;
        $reason = "Solo cuotas condonadas pendientes: {$loan->condoned_count}";
    }

    if ($should_be_paid) {
        $loans_to_fix[] = [
            'loan' => $loan,
            'reason' => $reason
        ];

        echo "   ✅ PRÉSTAMO {$loan->loan_id}: {$reason}\n";
    } else {
        echo "   ⏳ PRÉSTAMO {$loan->loan_id}: Mantiene pendiente (Balance: $" . number_format($loan->balance_amount, 2) . ", Cuotas: {$loan->pending_installments})\n";
    }
}

echo "\n2. APLICANDO CORRECCIONES MASIVAS...\n";
echo "   🔧 Préstamos a corregir: " . count($loans_to_fix) . "\n\n";

foreach ($loans_to_fix as $fix_item) {
    $loan = $fix_item['loan'];
    $reason = $fix_item['reason'];

    // Cambiar estado del préstamo a pagado
    $CI->db->where('id', $loan->loan_id);
    $CI->db->update('loans', ['status' => 0]);

    // Cambiar estado del cliente a pagado
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

    // Log detallado
    $log_entry = [
        'loan_id' => $loan->loan_id,
        'customer_id' => $loan->customer_id,
        'reason' => $reason,
        'old_balance' => $loan->balance_amount,
        'pending_installments' => $loan->pending_installments,
        'condoned_count' => $loan->condoned_count,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $corrections_made['logs'][] = $log_entry;

    echo "   ✅ CORREGIDO: Préstamo {$loan->loan_id} → PAGADO\n";
}

// 3. VERIFICACIÓN FINAL
echo "\n3. VERIFICACIÓN FINAL DE CORRECCIONES...\n";

if (!empty($loans_to_fix)) {
    $loan_ids_fixed = array_column(array_column($loans_to_fix, 'loan'), 'loan_id');
    $loan_ids_str = implode(',', $loan_ids_fixed);

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

        echo "      Préstamo {$result->id}: {$loan_status_ok} | Cliente: {$customer_status_ok}\n";
    }

    echo "\n   ✅ CORRECCIONES EXITOSAS: {$success_count}/" . count($verification_results) . "\n";
}

// 4. ESTADÍSTICAS GENERALES
echo "\n=== ESTADÍSTICAS GENERALES ===\n\n";

echo "📊 RESUMEN DE CORRECCIONES:\n";
echo "   • Préstamos procesados: {$corrections_made['total_processed']}\n";
echo "   • Préstamos cambiados a pagado: {$corrections_made['loans_to_paid']}\n";
echo "   • Clientes actualizados: {$corrections_made['customers_updated']}\n";
echo "   • Tasa de corrección: " . (count($pending_loans) > 0 ? round(($corrections_made['loans_to_paid'] / count($pending_loans)) * 100, 1) : 0) . "%\n\n";

// 5. VERIFICACIÓN GLOBAL
echo "5. VERIFICACIÓN GLOBAL DEL SISTEMA...\n";

$global_check_query = $CI->db->query("
    SELECT
        COUNT(*) as total_loans,
        SUM(CASE WHEN l.status = 0 THEN 1 ELSE 0 END) as paid_loans,
        SUM(CASE WHEN l.status = 1 THEN 1 ELSE 0 END) as pending_loans,
        SUM(CASE WHEN l.status = 1 AND COALESCE(calc.balance_amount, 0) <= 0.01 THEN 1 ELSE 0 END) as wrongly_pending
    FROM loans l
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
");

$global_stats = $global_check_query->row();

echo "   📊 ESTADO GLOBAL:\n";
echo "      Total de préstamos: {$global_stats->total_loans}\n";
echo "      Préstamos pagados: {$global_stats->paid_loans}\n";
echo "      Préstamos pendientes: {$global_stats->pending_loans}\n";
echo "      Préstamos pendientes incorrectamente: {$global_stats->wrongly_pending}\n\n";

if (($global_stats->wrongly_pending ?? 0) == 0) {
    echo "   🎉 ¡SISTEMA COMPLETAMENTE CORREGIDO! No quedan préstamos pendientes incorrectos.\n\n";
} else {
    echo "   ⚠️  Aún quedan {$global_stats->wrongly_pending} préstamos que requieren atención adicional.\n\n";
}

echo "=== FIN DE CORRECCIÓN MASIVA ===\n";
?>