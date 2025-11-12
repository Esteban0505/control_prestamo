<?php
/**
 * Script para sincronizar estados de clientes con estados de préstamos
 * Ejecutar desde línea de comandos: php fix_customer_status_sync.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== SINCRONIZACIÓN DE ESTADOS DE CLIENTES ===\n\n";

// Obtener todos los préstamos con sus clientes
$query = $CI->db->query("
    SELECT
        l.id as loan_id,
        l.status as loan_status,
        l.customer_id,
        c.loan_status as customer_loan_status,
        COALESCE(calc.balance_amount, 0) as current_balance
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
");

$loans = $query->result();
$updated_count = 0;

echo "Analizando " . count($loans) . " préstamos con clientes...\n\n";

foreach ($loans as $loan) {
    $expected_customer_status = ($loan->current_balance <= 0.01) ? 0 : 1;

    if ($loan->customer_loan_status != $expected_customer_status) {
        // Actualizar estado del cliente
        $CI->db->where('id', $loan->customer_id);
        $CI->db->update('customers', ['loan_status' => $expected_customer_status]);

        echo "✅ Cliente {$loan->customer_id}: {$loan->customer_loan_status} → {$expected_customer_status} (Préstamo {$loan->loan_id})\n";
        $updated_count++;
    }
}

echo "\n=== RESULTADO ===\n";
echo "✅ Estados de clientes sincronizados: {$updated_count}\n";

// Verificación final
$query_check = $CI->db->query("
    SELECT COUNT(*) as synced_count
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE c.loan_status = CASE WHEN COALESCE(calc.balance_amount, 0) <= 0.01 THEN 0 ELSE 1 END
");

$synced_count = $query_check->row()->synced_count ?? 0;
echo "✅ Estados sincronizados correctamente: {$synced_count}/" . count($loans) . "\n";

if ($synced_count == count($loans)) {
    echo "\n🎉 ¡SINCRONIZACIÓN COMPLETA! Todos los estados de clientes están sincronizados.\n";
} else {
    echo "\n⚠️  Aún quedan algunos estados sin sincronizar.\n";
}

echo "\n=== FIN DE SINCRONIZACIÓN ===\n";
?>