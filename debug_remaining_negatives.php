<?php
/**
 * Script para diagnosticar balances negativos restantes
 * Ejecutar desde línea de comandos: php debug_remaining_negatives.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== DIAGNÓSTICO DE BALANCES NEGATIVOS RESTANTES ===\n\n";

// Verificar qué préstamos aún tienen balances negativos
$query = $CI->db->query('
    SELECT
        l.id,
        l.status,
        COALESCE(calc.total_expected, 0) as total_expected,
        COALESCE(calc.total_paid, 0) as total_paid,
        (COALESCE(calc.total_expected, 0) - COALESCE(calc.total_paid, 0)) as balance_amount,
        COUNT(CASE WHEN li.extra_payment = 3 THEN 1 END) as condoned_count,
        COUNT(CASE WHEN li.status = 0 THEN 1 END) as paid_installments,
        COUNT(CASE WHEN li.status IN (1, 3) THEN 1 END) as pending_installments,
        COUNT(li.id) as total_installments
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    LEFT JOIN loan_items li ON li.loan_id = l.id
    WHERE (COALESCE(calc.total_expected, 0) - COALESCE(calc.total_paid, 0)) < 0
    GROUP BY l.id
    ORDER BY balance_amount ASC
');

$negative_balances = $query->result();
echo 'Préstamos con balance negativo restantes: ' . count($negative_balances) . PHP_EOL . PHP_EOL;

foreach ($negative_balances as $loan) {
    echo "PRÉSTAMO ID: {$loan->id}\n";
    echo "  Estado: " . ($loan->status == 0 ? 'Pagado' : 'Pendiente') . "\n";
    echo "  Total esperado (sin condonadas): $" . number_format($loan->total_expected, 2) . "\n";
    echo "  Total pagado: $" . number_format($loan->total_paid, 2) . "\n";
    echo "  Balance calculado: $" . number_format($loan->balance_amount, 2) . "\n";
    echo "  Cuotas condonadas: {$loan->condoned_count}\n";
    echo "  Cuotas pagadas: {$loan->paid_installments}\n";
    echo "  Cuotas pendientes: {$loan->pending_installments}\n";
    echo "  Total cuotas: {$loan->total_installments}\n";

    // Obtener detalles de cuotas para este préstamo
    $quota_query = $CI->db->query("
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
    ", [$loan->id]);

    $quotas = $quota_query->result();
    echo "  DETALLE DE CUOTAS:\n";
    foreach ($quotas as $quota) {
        $status_text = $quota->status == 0 ? 'Pagada' : ($quota->status == 1 ? 'Pendiente' : 'Otro');
        $extra_text = $quota->extra_payment == 3 ? ' (CONDONADA)' : '';
        echo "    Cuota {$quota->num_quota}: Monto $" . number_format($quota->fee_amount, 2) .
             ", Pagado $" . number_format($quota->interest_paid + $quota->capital_paid, 2) .
             ", Estado: {$status_text}{$extra_text}\n";
    }

    echo PHP_EOL;
}

echo "=== ANÁLISIS DE CAUSAS ===\n\n";

// Posibles causas de balances negativos restantes
echo "CAUSAS POSIBLES:\n";
echo "1. Pagos excedentes en cuotas no condonadas\n";
echo "2. Errores en cálculos de amortización\n";
echo "3. Pagos duplicados o incorrectos\n";
echo "4. Problemas de precisión decimal\n\n";

echo "=== RECOMENDACIONES ===\n\n";
echo "1. Revisar manualmente los préstamos con balances negativos\n";
echo "2. Verificar cálculos de amortización\n";
echo "3. Corregir pagos excedentes si es necesario\n";
echo "4. Considerar ajustes manuales para casos específicos\n\n";

echo "=== FIN DEL DIAGNÓSTICO ===\n";
?>