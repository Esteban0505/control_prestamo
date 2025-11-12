<?php
/**
 * Script para simular un pago personalizado para el préstamo #160
 * Basado en la información proporcionada por el usuario
 * Préstamo #160 con 5 cuotas - Pago personalizado menor al monto de cuota
 */

echo "=== SIMULACIÓN DE PAGO PERSONALIZADO PARA PRÉSTAMO #160 ===\n";
echo "Préstamo: #160 - 5 cuotas mensuales\n";
echo "Monto prestado: $50,000\n";
echo "Cuota #1: $11,231.35 (fecha: 30/11/2025)\n";
echo "Estado: Todas pendientes\n\n";

// Datos del préstamo basado en la información proporcionada
$loan_data = [
    'id' => 160,
    'credit_amount' => 50000.00,
    'interest_amount' => 2.00, // Asumiendo tasa mensual
    'num_fee' => 5,
    'fee_amount' => 11231.35,
    'status' => 1
];

// Simular cuotas basadas en amortización francesa
// Para simplificar, asumiremos cuotas similares
$quotas = [];
$total_capital = 50000.00;
$monthly_rate = 0.02; // 2%

for ($i = 1; $i <= 5; $i++) {
    $interest_amount = $total_capital * $monthly_rate;
    $capital_amount = 11231.35 - $interest_amount;
    $balance = $total_capital - $capital_amount;

    $quotas[] = [
        'id' => 1600 + $i, // IDs simulados
        'loan_id' => 160,
        'num_quota' => $i,
        'fee_amount' => 11231.35,
        'interest_amount' => round($interest_amount, 2),
        'capital_amount' => round($capital_amount, 2),
        'balance' => round($balance, 2),
        'status' => 1, // Todas pendientes
        'interest_paid' => 0,
        'capital_paid' => 0,
        'date' => date('Y-m-d', strtotime('2025-11-30 +' . ($i-1) . ' months'))
    ];

    $total_capital = $balance;
}

echo "=== DATOS DE LAS CUOTAS ===\n";
foreach ($quotas as $quota) {
    $status_text = $quota['status'] == 0 ? 'Pagada' : ($quota['status'] == 3 ? 'Pago Parcial' : 'Pendiente');
    echo "Cuota #{$quota['num_quota']} - ID: {$quota['id']} - Estado: {$status_text}\n";
    echo "  Monto: $" . number_format($quota['fee_amount'], 2) . "\n";
    echo "  Interés: $" . number_format($quota['interest_amount'], 2) . " (pagado: $" . number_format($quota['interest_paid'], 2) . ")\n";
    echo "  Capital: $" . number_format($quota['capital_amount'], 2) . " (pagado: $" . number_format($quota['capital_paid'], 2) . ")\n";
    echo "  Balance: $" . number_format($quota['balance'], 2) . "\n";
    echo "  Fecha: {$quota['date']}\n\n";
}

// Simular pago personalizado de $5,000 para la cuota #1 (menor al monto de cuota)
$custom_payment_amount = 5000.00;
$selected_quota_ids = [1601]; // Solo la cuota #1

echo "=== SIMULACIÓN DE PAGO PERSONALIZADO ===\n";
echo "Monto del pago personalizado: $" . number_format($custom_payment_amount, 2) . "\n";
echo "Cuota seleccionada: #1 (ID: 1601)\n";
echo "Tipo de pago: custom (prioridad interés-capital)\n\n";

// Simular la lógica del método process_custom_payment_partial
echo "=== PROCESAMIENTO DEL PAGO ===\n";

$remaining_amount = $custom_payment_amount;
$selected_quota = $quotas[0]; // Cuota #1

echo "Procesando cuota #{$selected_quota['num_quota']} (ID: {$selected_quota['id']})\n";
echo "Monto pendiente de interés: $" . number_format($selected_quota['interest_amount'] - $selected_quota['interest_paid'], 2) . "\n";
echo "Monto pendiente de capital: $" . number_format($selected_quota['capital_amount'] - $selected_quota['capital_paid'], 2) . "\n";
echo "Total pendiente: $" . number_format($selected_quota['fee_amount'], 2) . "\n";
echo "Monto del pago: $" . number_format($remaining_amount, 2) . "\n\n";

// Aplicar prioridad interés-capital
$interest_pending = $selected_quota['interest_amount'] - $selected_quota['interest_paid'];
$capital_pending = $selected_quota['capital_amount'] - $selected_quota['capital_paid'];

$interest_to_pay = 0;
$capital_to_pay = 0;

// Primero intereses
if ($interest_pending > 0 && $remaining_amount > 0) {
    $interest_to_pay = min($remaining_amount, $interest_pending);
    $remaining_amount -= $interest_to_pay;
    echo "Aplicado a intereses: $" . number_format($interest_to_pay, 2) . "\n";
}

// Luego capital
if ($capital_pending > 0 && $remaining_amount > 0) {
    $capital_to_pay = min($remaining_amount, $capital_pending);
    $remaining_amount -= $capital_to_pay;
    echo "Aplicado a capital: $" . number_format($capital_to_pay, 2) . "\n";
}

$total_paid = $interest_to_pay + $capital_to_pay;
$new_balance = $selected_quota['balance'] - $capital_to_pay;

// Verificar si la cuota queda completamente pagada
$new_interest_paid = $selected_quota['interest_paid'] + $interest_to_pay;
$new_capital_paid = $selected_quota['capital_paid'] + $capital_to_pay;

$is_complete = ($new_interest_paid >= $selected_quota['interest_amount'] &&
                $new_capital_paid >= $selected_quota['capital_amount']);

echo "\n=== RESULTADO DEL PAGO ===\n";
echo "Total aplicado: $" . number_format($total_paid, 2) . "\n";
echo "Interés pagado: $" . number_format($interest_to_pay, 2) . "\n";
echo "Capital pagado: $" . number_format($capital_to_pay, 2) . "\n";
echo "Nuevo balance de la cuota: $" . number_format($new_balance, 2) . "\n";
echo "Monto restante sin aplicar: $" . number_format($remaining_amount, 2) . "\n";
echo "Cuota completamente pagada: " . ($is_complete ? 'SÍ' : 'NO') . "\n";

if (!$is_complete) {
    echo "Estado de la cuota: Parcialmente pagada (status = 3)\n";
} else {
    echo "Estado de la cuota: Completamente pagada (status = 0)\n";
}

// Verificar si es la última cuota
$is_last_installment = ($selected_quota['num_quota'] == count($quotas));
echo "Es la última cuota: " . ($is_last_installment ? 'SÍ' : 'NO') . "\n";

if ($remaining_amount > 0 && !$is_complete) {
    echo "\n=== MONTO RESTANTE - DISTRIBUCIÓN ===\n";
    echo "Monto restante por distribuir: $" . number_format($remaining_amount, 2) . "\n";

    if ($is_last_installment) {
        // Calcular mora para última cuota
        $current_interest_rate = 2.00; // 2% mensual
        $penalty_rate = 1.5 * $current_interest_rate; // 1.5 × tasa corriente
        $penalty_amount = round($remaining_amount * ($penalty_rate / 100), 2);
        $new_quota_total = $remaining_amount + $penalty_amount;

        echo "Como es la última cuota, se genera nueva cuota con mora:\n";
        echo "Tasa de interés corriente: {$current_interest_rate}%\n";
        echo "Tasa de mora: {$penalty_rate}%\n";
        echo "Monto de mora calculado: $" . number_format($penalty_amount, 2) . "\n";
        echo "Nueva cuota total (saldo + mora): $" . number_format($new_quota_total, 2) . "\n";
    } else {
        echo "Se distribuiría en cuotas futuras (no implementado en esta simulación)\n";
    }
}

echo "\n=== RESUMEN FINAL ===\n";
echo "Pago personalizado procesado exitosamente\n";
echo "Monto total pagado: $" . number_format($custom_payment_amount, 2) . "\n";
echo "Monto aplicado a la cuota: $" . number_format($total_paid, 2) . "\n";
echo "Monto restante: $" . number_format($remaining_amount, 2) . "\n";
echo "Tipo de pago: " . ($is_complete ? 'COMPLETO' : 'PARCIAL') . "\n";

if ($is_last_installment && $remaining_amount > 0) {
    echo "Nueva cuota generada con mora: SÍ\n";
}

echo "\n=== FIN DE SIMULACIÓN ===\n";
?>