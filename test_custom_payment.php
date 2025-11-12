<?php
/**
 * Script para simular un pago personalizado para el préstamo #142
 * Basado en la información proporcionada por el usuario
 */

// Simular sesión de usuario
$_SESSION['loggedin'] = TRUE;
$_SESSION['user_id'] = 1;

echo "=== SIMULACIÓN DE PAGO PERSONALIZADO PARA PRÉSTAMO #142 ===\n";
echo "Préstamo: #142 - Cliente: prueba prueba\n";
echo "Monto del Crédito: $50,000.00\n";
echo "Cuota Mensual: $17,337.73\n";
echo "Próxima cuota: #3 - Vence: 01/02/2026 - $17,337.73\n\n";

// Datos del préstamo basado en la información proporcionada
$loan_data = [
    'id' => 142,
    'customer_id' => null, // Necesitamos encontrar el customer_id
    'credit_amount' => 50000.00,
    'balance' => 50000.00,
    'interest_amount' => 2.00,
    'num_fee' => 3,
    'fee_amount' => 17337.73,
    'status' => 1
];

// Cuotas basadas en la información
$quotas = [
    [
        'id' => 426, // Asumiendo IDs secuenciales
        'loan_id' => 142,
        'num_quota' => 1,
        'fee_amount' => 17337.73,
        'interest_amount' => 833.33,
        'capital_amount' => 16504.40,
        'balance' => 33504.40,
        'status' => 0, // Pagada
        'interest_paid' => 833.33,
        'capital_paid' => 16504.40,
        'date' => '2025-11-01'
    ],
    [
        'id' => 427,
        'loan_id' => 142,
        'num_quota' => 2,
        'fee_amount' => 17337.73,
        'interest_amount' => 668.09,
        'capital_amount' => 16669.64,
        'balance' => 16834.76,
        'status' => 0, // Pagada
        'interest_paid' => 668.09,
        'capital_paid' => 16669.64,
        'date' => '2025-12-01'
    ],
    [
        'id' => 428,
        'loan_id' => 142,
        'num_quota' => 3,
        'fee_amount' => 17337.73,
        'interest_amount' => 336.70,
        'capital_amount' => 17001.03,
        'balance' => 16834.76, // Balance pendiente
        'status' => 1, // Pendiente
        'interest_paid' => 0,
        'capital_paid' => 0,
        'date' => '2026-01-01'
    ]
];

echo "=== DATOS DE LAS CUOTAS ===\n";
foreach ($quotas as $quota) {
    $status_text = $quota['status'] == 0 ? 'Pagada' : 'Pendiente';
    echo "Cuota #{$quota['num_quota']} - ID: {$quota['id']} - Estado: {$status_text}\n";
    echo "  Monto: $" . number_format($quota['fee_amount'], 2) . "\n";
    echo "  Interés: $" . number_format($quota['interest_amount'], 2) . " (pagado: $" . number_format($quota['interest_paid'], 2) . ")\n";
    echo "  Capital: $" . number_format($quota['capital_amount'], 2) . " (pagado: $" . number_format($quota['capital_paid'], 2) . ")\n";
    echo "  Balance: $" . number_format($quota['balance'], 2) . "\n";
    echo "  Fecha: {$quota['date']}\n\n";
}

// Simular pago personalizado de $10,000 para la cuota #3
$custom_payment_amount = 10000.00;
$selected_quota_ids = [428]; // Solo la cuota #3

echo "=== SIMULACIÓN DE PAGO PERSONALIZADO ===\n";
echo "Monto del pago personalizado: $" . number_format($custom_payment_amount, 2) . "\n";
echo "Cuota seleccionada: #3 (ID: 428)\n";
echo "Tipo de pago: custom (prioridad interés-capital)\n\n";

// Simular la lógica del método process_custom_payment_partial
echo "=== PROCESAMIENTO DEL PAGO ===\n";

$remaining_amount = $custom_payment_amount;
$selected_quota = $quotas[2]; // Cuota #3

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