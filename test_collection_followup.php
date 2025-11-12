<?php
/**
 * Script para probar la funcionalidad de seguimiento de cobranza
 * Genera nueva cuota al préstamo con el saldo restante
 */

echo "=== PRUEBA DE SEGUIMIENTO DE COBRANZA ===\n";
echo "Préstamo #160 - Cliente: prueba prueba\n";
echo "Saldo pendiente actual: $11,231.35\n\n";

// Simular datos del préstamo #160
$loan_data = [
    'id' => 160,
    'credit_amount' => 50000.00,
    'interest_amount' => 2.00,
    'num_fee' => 5,
    'fee_amount' => 11231.35,
    'status' => 1,
    'amortization_type' => 'francesa',
    'payment_m' => 'mensual'
];

// Simular cuotas existentes (basado en la información proporcionada)
$existing_quotas = [
    [
        'id' => 1601,
        'loan_id' => 160,
        'num_quota' => 1,
        'fee_amount' => 11231.35,
        'interest_amount' => 2000.00,
        'capital_amount' => 9231.35,
        'balance' => 40768.65,
        'status' => 0, // Pagada
        'interest_paid' => 2000.00,
        'capital_paid' => 9231.35,
        'date' => '2025-12-01'
    ],
    [
        'id' => 1602,
        'loan_id' => 160,
        'num_quota' => 2,
        'fee_amount' => 11231.35,
        'interest_amount' => 815.37,
        'capital_amount' => 10415.98,
        'balance' => 30352.67,
        'status' => 0, // Pagada
        'interest_paid' => 815.37,
        'capital_paid' => 10415.98,
        'date' => '2026-01-01'
    ],
    [
        'id' => 1603,
        'loan_id' => 160,
        'num_quota' => 3,
        'fee_amount' => 12231.35,
        'interest_amount' => 607.05,
        'capital_amount' => 11624.30,
        'balance' => 18728.37,
        'status' => 0, // Pagada
        'interest_paid' => 607.05,
        'capital_paid' => 11624.30,
        'date' => '2026-02-01'
    ],
    [
        'id' => 1604,
        'loan_id' => 160,
        'num_quota' => 4,
        'fee_amount' => 12231.35,
        'interest_amount' => 374.57,
        'capital_amount' => 11856.78,
        'balance' => 6871.59,
        'status' => 0, // Pagada
        'interest_paid' => 374.57,
        'capital_paid' => 11856.78,
        'date' => '2026-03-01'
    ],
    [
        'id' => 1605,
        'loan_id' => 160,
        'num_quota' => 5,
        'fee_amount' => 12231.35,
        'interest_amount' => 137.43,
        'capital_amount' => 12093.92,
        'balance' => -4222.33,
        'status' => 0, // Pagada
        'interest_paid' => 137.43,
        'capital_paid' => 12093.92,
        'date' => '2026-04-01'
    ],
    [
        'id' => 1606,
        'loan_id' => 160,
        'num_quota' => 6,
        'fee_amount' => 1874.76,
        'interest_amount' => 0.00,
        'capital_amount' => 1874.76,
        'balance' => -4222.33,
        'status' => 0, // Pagada
        'interest_paid' => 0.00,
        'capital_paid' => 1874.76,
        'date' => '2026-05-01'
    ],
    [
        'id' => 1607,
        'loan_id' => 160,
        'num_quota' => 7,
        'fee_amount' => 132.75,
        'interest_amount' => 0.00,
        'capital_amount' => 132.75,
        'balance' => 132.75, // Saldo pendiente
        'status' => 1, // Pendiente
        'interest_paid' => 0.00,
        'capital_paid' => 0.00,
        'date' => '2026-06-01'
    ]
];

// Calcular saldo pendiente total (solo cuotas con balance > 0)
$total_balance = 0;
foreach ($existing_quotas as $quota) {
    if ($quota['status'] == 1 && $quota['balance'] > 0) { // Solo cuotas pendientes con balance positivo
        $total_balance += $quota['balance'];
    }
}

echo "=== ANÁLISIS DEL SALDO PENDIENTE ===\n";
echo "Saldo pendiente total: $" . number_format($total_balance, 2) . "\n";
echo "Cuotas pendientes: 1 (Cuota #7)\n\n";

if ($total_balance > 0) {
    echo "=== GENERANDO NUEVA CUOTA POR SEGUIMIENTO DE COBRANZA ===\n";

    // Calcular nueva cuota
    $last_quota = end($existing_quotas);
    $next_quota_num = $last_quota['num_quota'] + 1;

    // Calcular fecha de la nueva cuota (mensual)
    $last_date = new DateTime($last_quota['date']);
    $next_date = clone $last_date;
    $next_date->add(new DateInterval('P1M')); // Agregar 1 mes

    // Calcular interés sobre el saldo pendiente
    $interest_rate = 2.00; // 2% mensual
    $interest_amount = round($total_balance * ($interest_rate / 100), 2);
    $fee_amount = $total_balance + $interest_amount;

    // Nueva cuota
    $new_quota = [
        'id' => 1608, // ID simulado
        'loan_id' => 160,
        'num_quota' => $next_quota_num,
        'fee_amount' => $fee_amount,
        'interest_amount' => $interest_amount,
        'capital_amount' => $total_balance,
        'balance' => $total_balance,
        'status' => 1, // Pendiente (o 0 si se marca como pagada)
        'interest_paid' => 0,
        'capital_paid' => 0,
        'date' => $next_date->format('Y-m-d'),
        'extra_payment' => 0,
        'payment_desc' => 'Seguimiento de cobranza - Nueva cuota generada'
    ];

    echo "Nueva cuota generada:\n";
    echo "- Número de cuota: " . $new_quota['num_quota'] . "\n";
    echo "- Fecha: " . $new_quota['date'] . "\n";
    echo "- Monto total: $" . number_format($new_quota['fee_amount'], 2) . "\n";
    echo "- Interés: $" . number_format($new_quota['interest_amount'], 2) . "\n";
    echo "- Capital: $" . number_format($new_quota['capital_amount'], 2) . "\n";
    echo "- Balance inicial: $" . number_format($new_quota['balance'], 2) . "\n";
    echo "- Estado: " . ($new_quota['status'] == 1 ? 'Pendiente' : 'Pagada') . "\n";
    echo "- Descripción: " . $new_quota['payment_desc'] . "\n\n";

    // Simular opciones de estado
    echo "=== OPCIONES DE ESTADO PARA LA NUEVA CUOTA ===\n";
    echo "1. Pendiente (status=1) - El cliente debe pagar esta nueva cuota\n";
    echo "2. Pagada (status=0) - Marcar como pagada inmediatamente\n\n";

    // Simular seguimiento de cobranza
    echo "=== SEGUIMIENTO DE COBRANZA REGISTRADO ===\n";
    echo "- Cliente: prueba prueba\n";
    echo "- Estado del seguimiento: Activo\n";
    echo "- Prioridad: Alta\n";
    echo "- Próxima acción: " . date('Y-m-d', strtotime('+7 days')) . "\n";
    echo "- Notas: Nueva cuota #" . $new_quota['num_quota'] . " generada con saldo $" . number_format($total_balance, 2) . "\n\n";

    echo "=== RESULTADO FINAL ===\n";
    echo "✅ Nueva cuota generada exitosamente\n";
    echo "✅ Seguimiento de cobranza activado\n";
    echo "✅ Saldo pendiente transferido a nueva cuota\n";
    echo "Saldo total del préstamo: $" . number_format(52000.00 + $fee_amount, 2) . "\n";

} else {
    echo "No hay saldo pendiente para generar nueva cuota\n";
}

echo "\n=== FIN DE PRUEBA ===\n";
?>