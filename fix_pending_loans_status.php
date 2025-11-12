<?php
/**
 * CORRECCIÓN AUTOMÁTICA DE PRÉSTAMOS PENDIENTES
 * Identifica y corrige préstamos en estado "pending" que deberían estar "paid"
 * Ejecutar desde línea de comandos: php fix_pending_loans_status.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN AUTOMÁTICA DE PRÉSTAMOS PENDIENTES ===\n\n";

$corrections_made = [
    'loans_to_paid' => 0,
    'customers_updated' => 0,
    'total_processed' => 0,
    'logs' => []
];

// 1. IDENTIFICAR PRÉSTAMOS PENDIENTES QUE DEBERÍAN ESTAR PAGADOS
echo "1. IDENTIFICANDO PRÉSTAMOS PENDIENTES PARA CORRECCIÓN...\n";

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
    // 2. 0 cuotas pendientes Y balance mínimo
    // 3. Solo cuotas condonadas restantes

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
        echo "      Balance: $" . number_format($loan->balance_amount, 2) . ", Cuotas pendientes: {$loan->pending_installments}, Condonadas: {$loan->condoned_count}\n";
    } else {
        echo "   ⏳ PRÉSTAMO {$loan->loan_id}: Mantiene pendiente (Balance: $" . number_format($loan->balance_amount, 2) . ", Cuotas: {$loan->pending_installments})\n";
    }
}

echo "\n2. APLICANDO CORRECCIONES...\n";
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

    $corrections_made['loans_to_paid']++;
    $corrections_made['customers_updated']++;

    // Marcar cuotas condonadas restantes como pagadas
    if ($loan->condoned_count > 0) {
        $CI->db->where('loan_id', $loan->loan_id);
        $CI->db->where('extra_payment', 3);
        $CI->db->where('status !=', 0);
        $CI->db->update('loan_items', [
            'status' => 0,
            'balance' => 0,
            'interest_paid' => 'interest_amount',
            'capital_paid' => 'capital_amount',
            'pay_date' => date('Y-m-d H:i:s')
        ]);
    }

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
    echo "      Razón: {$reason}\n";
    echo "      Cliente {$loan->customer_id} actualizado\n\n";
}

// 3. VERIFICACIÓN DE CORRECCIONES
echo "3. VERIFICACIÓN DE CORRECCIONES APLICADAS...\n";

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
    ");

    $verification_results = $verification_query->result();

    echo "   🔍 Verificando " . count($verification_results) . " préstamos corregidos:\n";

    foreach ($verification_results as $result) {
        $status_ok = $result->loan_status == 0 ? '✅' : '❌';
        $customer_ok = $result->customer_status == 0 ? '✅' : '❌';

        echo "      Préstamo {$result->id}: Estado {$status_ok}, Cliente {$customer_ok}, Balance $" . number_format($result->balance_amount, 2) . "\n";
    }
} else {
    echo "   ℹ️  No se aplicaron correcciones\n";
}

// 4. REPORTE FINAL
echo "\n=== REPORTE FINAL DE CORRECCIONES ===\n\n";

echo "📊 ESTADÍSTICAS:\n";
echo "   • Préstamos procesados: {$corrections_made['total_processed']}\n";
echo "   • Préstamos cambiados a pagado: {$corrections_made['loans_to_paid']}\n";
echo "   • Clientes actualizados: {$corrections_made['customers_updated']}\n";
echo "   • Tasa de corrección: " . (count($pending_loans) > 0 ? round(($corrections_made['loans_to_paid'] / count($pending_loans)) * 100, 1) : 0) . "%\n\n";

if (!empty($corrections_made['logs'])) {
    echo "📝 DETALLE DE CAMBIOS REALIZADOS:\n";
    foreach ($corrections_made['logs'] as $log) {
        echo "   • Préstamo {$log['loan_id']} (Cliente {$log['customer_id']}): {$log['reason']}\n";
        echo "     Balance anterior: $" . number_format($log['old_balance'], 2) . ", Cuotas pendientes: {$log['pending_installments']}\n";
    }
    echo "\n";
}

// 5. EJECUTAR VALIDACIÓN FINAL
echo "5. EJECUTANDO VALIDACIÓN FINAL...\n";

$final_validation_query = $CI->db->query("
    SELECT COUNT(*) as still_pending_wrongly
    FROM loans l
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount,
               SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_installments,
               SUM(CASE WHEN li.extra_payment = 3 THEN 1 ELSE 0 END) AS condoned_count
        FROM loan_items li GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.status = 1
    AND (
        COALESCE(calc.balance_amount, 0) <= 0.01
        OR (COALESCE(calc.pending_installments, 0) = 0 AND COALESCE(calc.balance_amount, 0) <= 1.00)
        OR (COALESCE(calc.pending_installments, 0) = COALESCE(calc.condoned_count, 0) AND COALESCE(calc.condoned_count, 0) > 0)
    )
");

$still_wrong = $final_validation_query->row()->still_pending_wrongly ?? 0;

if ($still_wrong == 0) {
    echo "   🎉 ¡VALIDACIÓN EXITOSA! No quedan préstamos pendientes incorrectos.\n\n";
} else {
    echo "   ⚠️  Aún quedan {$still_wrong} préstamos que podrían necesitar corrección manual.\n\n";
}

echo "=== FIN DEL PROCESO DE CORRECCIÓN ===\n";
?>