<?php
// Script para simular el flujo de pago de una sola cuota como en el controlador Payments::ticket

require_once 'application/config/database.php';

$CI =& get_instance();
$CI->load->database();
$CI->load->model('payments_m');

// Datos de simulación
$loan_id = 10; // Loan con cuotas pendientes
$quota_id = 43; // Una cuota específica
$user_id = 1; // Usuario admin
$name_cst = 'cesar ramos';
$coin = 'colombiano';
$customer_id = 11;

// Verificar estado antes del pago
echo "Estado ANTES del pago:\n";
$query = $CI->db->where('id', $quota_id)->get('loan_items');
$quota_before = $query->row();
echo "Cuota ID: {$quota_before->id}, Status: {$quota_before->status}, Paid_by: " . ($quota_before->paid_by ?? 'NULL') . "\n";

// Simular el pago (como en Payments::ticket)
echo "\nProcesando pago...\n";

$quota_ids = [$quota_id]; // Solo una cuota

foreach ($quota_ids as $q) {
    $CI->payments_m->update_quota(['status' => 0, 'paid_by' => $user_id], $q);
    echo "Actualizada cuota $q: status=0, paid_by=$user_id\n";
}

if (!$CI->payments_m->check_cstLoan($loan_id)) {
    $CI->payments_m->update_cstLoan($loan_id, $customer_id);
    echo "Loan $loan_id marcado como completado, customer $customer_id actualizado.\n";
} else {
    echo "Loan $loan_id aún tiene cuotas pendientes.\n";
}

$quotasPaid = $CI->payments_m->get_quotasPaid($quota_ids);
echo "Cuotas pagadas: " . count($quotasPaid) . "\n";

// Verificar estado después del pago
echo "\nEstado DESPUÉS del pago:\n";
$query = $CI->db->where('id', $quota_id)->get('loan_items');
$quota_after = $query->row();
echo "Cuota ID: {$quota_after->id}, Status: {$quota_after->status}, Paid_by: {$quota_after->paid_by}\n";

// Verificar total_amount: debería ser el fee_amount de la cuota
$total_amount = $quota_before->fee_amount;
echo "\nTotal amount calculado: $total_amount (fee_amount de la cuota)\n";

// Verificar si solo esta cuota cambió
echo "\nVerificando otras cuotas del loan $loan_id:\n";
$query = $CI->db->where('loan_id', $loan_id)->where('id !=', $quota_id)->get('loan_items');
foreach ($query->result() as $other_quota) {
    echo "Cuota ID: {$other_quota->id}, Status: {$other_quota->status}, Paid_by: " . ($other_quota->paid_by ?? 'NULL') . "\n";
}

echo "\nSimulación completada.\n";
?>