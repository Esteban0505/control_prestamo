<?php
/**
 * Script para corregir balances negativos y estados de cuotas condonadas
 * Ejecutar desde línea de comandos: php fix_negative_balances.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN COMPLETA DE BALANCES NEGATIVOS ===\n\n";

// 1. Marcar TODAS las cuotas condonadas como pagadas
echo "1. Actualizando cuotas condonadas...\n";
$query_condoned = $CI->db->query("
    UPDATE loan_items
    SET status = 0,
        balance = 0,
        interest_paid = COALESCE(interest_amount, 0),
        capital_paid = COALESCE(capital_amount, 0),
        pay_date = NOW()
    WHERE extra_payment = 3 AND status != 0
");
$condoned_updated = $CI->db->affected_rows();
echo "   ✅ Cuotas condonadas actualizadas: {$condoned_updated}\n\n";

// 2. Recalcular estados de préstamos con lógica correcta (excluyendo condonadas del total esperado)
echo "2. Recalculando estados de préstamos...\n";
$query_loans = $CI->db->query("
    SELECT
        l.id,
        l.status as current_status,
        COALESCE(calc.total_expected, 0) as total_expected,
        COALESCE(calc.total_paid, 0) as total_paid,
        GREATEST(0, COALESCE(calc.total_expected, 0) - COALESCE(calc.total_paid, 0)) as correct_balance
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
");

$loans = $query_loans->result();
$status_updated = 0;

foreach ($loans as $loan) {
    $new_status = ($loan->correct_balance <= 0.01) ? 0 : 1;

    if ($loan->current_status != $new_status) {
        $CI->db->where('id', $loan->id);
        $CI->db->update('loans', ['status' => $new_status]);

        // Actualizar estado del cliente si es necesario
        if ($new_status == 0 && isset($loan->customer_id)) {
            $CI->db->where('id', $loan->customer_id);
            $CI->db->update('customers', ['loan_status' => 0]);
        }

        $status_updated++;
        echo "   ✅ Préstamo {$loan->id}: {$loan->current_status} → {$new_status} (balance: $" . number_format($loan->correct_balance, 2) . ")\n";
    }
}

echo "\n3. Estados de préstamos actualizados: {$status_updated}\n\n";

// 4. Verificación final
echo "4. Verificación final...\n";
$query_check = $CI->db->query("
    SELECT COUNT(*) as negative_balances
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE (COALESCE(calc.total_expected, 0) - COALESCE(calc.total_paid, 0)) < 0
");

$negative_count = $query_check->row()->negative_balances ?? 0;

// Verificar cuotas condonadas pendientes
$query_condoned_check = $CI->db->query("
    SELECT COUNT(*) as condoned_pending
    FROM loan_items
    WHERE extra_payment = 3 AND status != 0
");

$condoned_pending = $query_condoned_check->row()->condoned_pending ?? 0;

echo "\n=== RESULTADO FINAL ===\n";
echo "✅ Cuotas condonadas corregidas: {$condoned_updated}\n";
echo "✅ Estados de préstamos actualizados: {$status_updated}\n";
echo "📊 Balances negativos restantes: {$negative_count}\n";
echo "📊 Cuotas condonadas pendientes restantes: {$condoned_pending}\n";

if ($negative_count == 0 && $condoned_pending == 0) {
    echo "\n🎉 ¡CORRECCIÓN COMPLETA! Todos los problemas han sido resueltos.\n";
} else {
    echo "\n⚠️  Aún quedan algunos problemas por resolver.\n";
}

echo "\n=== FIN DEL PROCESO ===\n";
?>