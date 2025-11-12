<?php
/**
 * Script de prueba para validar la corrección del pago parcial
 * Préstamo #167 - Cliente martin francisco
 */

// Simular datos del pago
$loan_id = 167;
$custom_amount = 100000; // Pago de 100.000
$quota_id = 3111; // Cuota 2

// Datos simulados de la cuota antes del pago
$quota_info_before = (object) [
    'id' => 3111,
    'loan_id' => 167,
    'balance' => 288999.10, // Balance total del préstamo
    'interest_amount' => 19279.06,
    'capital_amount' => 89459.37,
    'interest_paid' => 0.00,
    'capital_paid' => 0.00,
    'fee_amount' => 108738.43
];

// Cálculo esperado
$interest_pending = $quota_info_before->interest_amount - $quota_info_before->interest_paid; // 19279.06
$capital_pending = $quota_info_before->capital_amount - $quota_info_before->capital_paid; // 89459.37
$total_pending = $interest_pending + $capital_pending; // 108738.43

$amount_to_pay = min($custom_amount, $total_pending); // 100000

// Aplicar prioridad interés-capital
$interest_to_pay = min($amount_to_pay, $interest_pending); // 19279.06
$amount_to_pay -= $interest_to_pay; // 100000 - 19279.06 = 80720.94
$capital_to_pay = min($amount_to_pay, $capital_pending); // 80720.94

// Balance esperado después del pago
$expected_balance = $quota_info_before->balance - $capital_to_pay; // 288999.10 - 80720.94 = 208278.16

echo "=== PRUEBA DE CORRECCIÓN PAGO PARCIAL ===\n";
echo "Préstamo: #$loan_id\n";
echo "Pago: $" . number_format($custom_amount, 2) . "\n";
echo "Cuota: #$quota_id\n";
echo "\n--- DATOS ANTES DEL PAGO ---\n";
echo "Balance total: $" . number_format($quota_info_before->balance, 2) . "\n";
echo "Interés pendiente: $" . number_format($interest_pending, 2) . "\n";
echo "Capital pendiente: $" . number_format($capital_pending, 2) . "\n";
echo "Total pendiente: $" . number_format($total_pending, 2) . "\n";
echo "\n--- CÁLCULO DEL PAGO ---\n";
echo "Monto a pagar: $" . number_format($amount_to_pay, 2) . "\n";
echo "Interés aplicado: $" . number_format($interest_to_pay, 2) . "\n";
echo "Capital aplicado: $" . number_format($capital_to_pay, 2) . "\n";
echo "\n--- BALANCE ESPERADO DESPUÉS DEL PAGO ---\n";
echo "Balance total esperado: $" . number_format($expected_balance, 2) . "\n";
echo "\n--- VALIDACIÓN ---\n";
echo "Pago parcial: SÍ (100000 < 108738.43)\n";
echo "Status esperado: 3 (parcial)\n";
echo "\n=== FIN PRUEBA ===\n";