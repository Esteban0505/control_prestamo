<?php
/**
 * Script para corregir estados de préstamos
 * Ejecutar desde línea de comandos: php fix_loan_statuses.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN DE ESTADOS DE PRÉSTAMOS ===\n\n";

// Obtener todos los préstamos con sus balances calculados
$query = $CI->db->query("
    SELECT
        l.id,
        l.status as current_status,
        l.customer_id,
        COALESCE(calc.total_fees, 0) as total_amount,
        COALESCE(calc.total_paid, 0) as paid_amount,
        GREATEST(0, COALESCE(calc.total_fees, 0) - COALESCE(calc.total_paid, 0)) as balance_amount
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(COALESCE(li.fee_amount, 0)) AS total_fees,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
");

$loans = $query->result();
$updated_count = 0;
$errors = 0;

echo "Procesando " . count($loans) . " préstamos...\n\n";

foreach ($loans as $loan) {
    $current_status = $loan->current_status;
    $balance_amount = $loan->balance_amount ?? 0;

    // Determinar nuevo estado basado en balance_amount
    $new_status = ($balance_amount <= 0.01) ? 0 : 1;

    // Solo actualizar si el estado cambió
    if ($current_status != $new_status) {
        try {
            // Actualizar préstamo
            $CI->db->where('id', $loan->id);
            $CI->db->update('loans', ['status' => $new_status]);

            // Actualizar estado del cliente si el préstamo se completó
            if ($new_status == 0 && isset($loan->customer_id)) {
                $CI->db->where('id', $loan->customer_id);
                $CI->db->update('customers', ['loan_status' => 0]);
            }

            $updated_count++;
            echo "✅ Préstamo {$loan->id}: {$current_status} → {$new_status} (balance: $" . number_format($balance_amount, 2) . ")\n";

        } catch (Exception $e) {
            $errors++;
            echo "❌ Error en préstamo {$loan->id}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== RESULTADO ===\n";
echo "✅ Préstamos actualizados: {$updated_count}\n";
echo "❌ Errores: {$errors}\n";
echo "📊 Total procesado: " . count($loans) . "\n";

echo "\n=== VERIFICACIÓN FINAL ===\n";

// Verificar que no queden préstamos con estados incorrectos
$query_check = $CI->db->query("
    SELECT COUNT(*) as incorrect_status
    FROM loans l
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(COALESCE(li.fee_amount, 0)) AS total_fees,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.status = 1 AND GREATEST(0, COALESCE(calc.total_fees, 0) - COALESCE(calc.total_paid, 0)) <= 0.01
");

$incorrect = $query_check->row()->incorrect_status ?? 0;

if ($incorrect == 0) {
    echo "✅ ¡Todos los estados están correctos!\n";
} else {
    echo "⚠️  Aún quedan {$incorrect} préstamos con estados incorrectos\n";
}

echo "\n=== FIN DEL PROCESO ===\n";
?>