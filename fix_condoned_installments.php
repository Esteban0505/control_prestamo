<?php
/**
 * Script para corregir cuotas condonadas
 * Ejecutar desde línea de comandos: php fix_condoned_installments.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN DE CUOTAS CONDONADAS ===\n\n";

// Buscar cuotas condonadas (extra_payment = 3) que aún estén pendientes
$query = $CI->db->query("
    SELECT id, loan_id, num_quota, extra_payment, status, interest_amount, capital_amount,
           interest_paid, capital_paid, balance
    FROM loan_items
    WHERE extra_payment = 3 AND status = 1
");

$condoned_installments = $query->result();
$updated_count = 0;

echo "Encontradas " . count($condoned_installments) . " cuotas condonadas pendientes\n\n";

foreach ($condoned_installments as $installment) {
    try {
        // Actualizar cuota condonada a estado pagado
        $CI->db->where('id', $installment->id);
        $CI->db->update('loan_items', [
            'status' => 0, // Pagado
            'balance' => 0,
            'interest_paid' => $installment->interest_amount ?? 0,
            'capital_paid' => $installment->capital_amount ?? 0,
            'pay_date' => date('Y-m-d H:i:s')
        ]);

        $updated_count++;
        echo "✅ Cuota {$installment->id} (Préstamo: {$installment->loan_id}, Cuota: {$installment->num_quota}) actualizada a PAGADO\n";

    } catch (Exception $e) {
        echo "❌ Error en cuota {$installment->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "✅ Cuotas condonadas actualizadas: {$updated_count}\n";

// Verificar que no queden cuotas condonadas pendientes
$query_check = $CI->db->query("
    SELECT COUNT(*) as remaining_condoned
    FROM loan_items
    WHERE extra_payment = 3 AND status = 1
");

$remaining = $query_check->row()->remaining_condoned ?? 0;

if ($remaining == 0) {
    echo "✅ ¡Todas las cuotas condonadas están en estado PAGADO!\n";
} else {
    echo "⚠️  Aún quedan {$remaining} cuotas condonadas en estado pendiente\n";
}

echo "\n=== FIN DEL PROCESO ===\n";
?>