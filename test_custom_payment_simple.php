<?php
/**
 * Simulación simplificada para diagnosticar pagos personalizados
 * Sin cargar CodeIgniter completo para evitar problemas de dependencias
 */

// Simular datos de prueba basados en el problema reportado
$test_data = [
    'loan_id' => 160,
    'custom_amount' => 5000,
    'selected_quotas' => [
        [
            'id' => 1,
            'num_quota' => 1,
            'interest_amount' => 1000,
            'capital_amount' => 4000,
            'interest_paid' => 0,
            'capital_paid' => 0,
            'balance' => 5000,
            'status' => 1
        ],
        [
            'id' => 2,
            'num_quota' => 2,
            'interest_amount' => 800,
            'capital_amount' => 4200,
            'interest_paid' => 0,
            'capital_paid' => 0,
            'balance' => 5000,
            'status' => 1
        ]
    ]
];

echo "=== SIMULACIÓN SIMPLIFICADA DE PAGO PERSONALIZADO ===\n";
echo "Loan ID: {$test_data['loan_id']}\n";
echo "Monto personalizado: \${$test_data['custom_amount']}\n";
echo "Cuotas seleccionadas: " . count($test_data['selected_quotas']) . "\n\n";

function simulate_custom_payment($custom_amount, $quotas) {
    $remaining_amount = $custom_amount;
    $payment_distribution = [];
    $is_partial = false;

    echo "Procesando pago personalizado de \${$custom_amount}\n";
    echo "Monto restante inicial: \${$remaining_amount}\n\n";

    foreach ($quotas as $quota) {
        if ($remaining_amount <= 0) {
            echo "Monto restante agotado, deteniendo procesamiento\n";
            break;
        }

        echo "Procesando cuota ID {$quota['id']} (#{$quota['num_quota']})\n";

        // Calcular montos pendientes
        $interest_pending = max(0, $quota['interest_amount'] - $quota['interest_paid']);
        $capital_pending = max(0, $quota['capital_amount'] - $quota['capital_paid']);
        $total_pending = $interest_pending + $capital_pending;

        echo "  - Interés pendiente: \${$interest_pending}\n";
        echo "  - Capital pendiente: \${$capital_pending}\n";
        echo "  - Total pendiente: \${$total_pending}\n";
        echo "  - Monto restante antes: \${$remaining_amount}\n";

        // Determinar cuánto pagar de esta cuota
        $amount_to_pay = min($remaining_amount, $total_pending);

        // Si el pago es menor al total pendiente, marcar como parcial
        if ($amount_to_pay < $total_pending) {
            $is_partial = true;
            echo "  - PAGO PARCIAL DETECTADO: \${$amount_to_pay} < \${$total_pending}\n";
        }

        // Aplicar prioridad interés-capital
        $interest_to_pay = 0;
        $capital_to_pay = 0;

        // Primero intereses pendientes
        if ($interest_pending > 0 && $amount_to_pay > 0) {
            $interest_to_pay = min($amount_to_pay, $interest_pending);
            $amount_to_pay -= $interest_to_pay;
            echo "  - Aplicado a intereses: \${$interest_to_pay}\n";
        }

        // Luego capital pendiente
        if ($capital_pending > 0 && $amount_to_pay > 0) {
            $capital_to_pay = min($amount_to_pay, $capital_pending);
            $amount_to_pay -= $capital_to_pay;
            echo "  - Aplicado a capital: \${$capital_to_pay}\n";
        }

        $total_paid_on_quota = $interest_to_pay + $capital_to_pay;
        $remaining_amount -= $total_paid_on_quota;

        echo "  - Total aplicado a esta cuota: \${$total_paid_on_quota}\n";
        echo "  - Monto restante después: \${$remaining_amount}\n";

        // Verificar si la cuota queda completamente pagada
        $new_interest_paid = $quota['interest_paid'] + $interest_to_pay;
        $new_capital_paid = $quota['capital_paid'] + $capital_to_pay;

        $is_quota_complete = ($new_interest_paid >= $quota['interest_amount'] &&
                            $new_capital_paid >= $quota['capital_amount']);

        echo "  - Cuota queda " . ($is_quota_complete ? 'COMPLETAMENTE PAGADA' : 'PARCIALMENTE PAGADA') . "\n";

        $payment_distribution[] = [
            'quota_id' => $quota['id'],
            'interest_paid' => $interest_to_pay,
            'capital_paid' => $capital_to_pay,
            'total_paid' => $total_paid_on_quota,
            'status_changed' => $is_quota_complete
        ];

        echo "\n";
    }

    return [
        'payment_breakdown' => $payment_distribution,
        'is_partial' => $is_partial,
        'remaining_amount' => $remaining_amount,
        'total_applied' => $custom_amount - $remaining_amount
    ];
}

// Ejecutar simulación
$result = simulate_custom_payment($test_data['custom_amount'], $test_data['selected_quotas']);

// Mostrar resultados
echo "=== RESULTADOS DE LA SIMULACIÓN ===\n";
echo "Pago parcial: " . ($result['is_partial'] ? 'SÍ' : 'NO') . "\n";
echo "Monto total aplicado: \${$result['total_applied']}\n";
echo "Monto restante sin aplicar: \${$result['remaining_amount']}\n\n";

echo "Desglose por cuota:\n";
foreach ($result['payment_breakdown'] as $payment) {
    echo "Cuota ID {$payment['quota_id']}:\n";
    echo "  - Interés aplicado: \${$payment['interest_paid']}\n";
    echo "  - Capital aplicado: \${$payment['capital_paid']}\n";
    echo "  - Total aplicado: \${$payment['total_paid']}\n";
    echo "  - Estado esperado: " . ($payment['status_changed'] ? 'Pagada' : 'Pago Parcial') . "\n\n";
}

// Diagnóstico del problema reportado
echo "=== DIAGNÓSTICO DEL PROBLEMA REPORTADO ===\n";
echo "Problemas esperados:\n";
echo "1. Monto aplicado incorrecto (\$1,231.35 en lugar de \$5,000)\n";
echo "2. Prioridad interés-capital no funciona (0 a intereses, todo a capital)\n";
echo "3. Estado muestra 'Pagado' en lugar de 'Pago no completo'\n";
echo "4. Saldo restante negativo\n";
echo "5. No distribución a cuotas futuras\n\n";

$total_applied = $result['total_applied'];
$custom_amount = $test_data['custom_amount'];

if ($total_applied != $custom_amount) {
    echo "❌ CONFIRMADO: Monto aplicado incorrecto (\${$total_applied} != \${$custom_amount})\n";
} else {
    echo "✅ Monto aplicado correcto\n";
}

$has_wrong_priority = false;
foreach ($result['payment_breakdown'] as $payment) {
    if ($payment['interest_paid'] == 0 && $payment['capital_paid'] > 0) {
        $has_wrong_priority = true;
        break;
    }
}

if ($has_wrong_priority) {
    echo "❌ CONFIRMADO: Prioridad interés-capital invertida (todo a capital, 0 a intereses)\n";
} else {
    echo "✅ Prioridad interés-capital correcta\n";
}

$has_wrong_status = false;
if ($result['is_partial']) {
    foreach ($result['payment_breakdown'] as $payment) {
        if ($payment['status_changed']) {
            $has_wrong_status = true;
            break;
        }
    }
}

if ($has_wrong_status) {
    echo "❌ CONFIRMADO: Estado incorrecto (muestra 'Pagado' en lugar de 'Pago no completo')\n";
} else {
    echo "✅ Estado correcto en ticket\n";
}

if ($result['remaining_amount'] < 0) {
    echo "❌ CONFIRMADO: Saldo restante negativo\n";
} else {
    echo "✅ Saldo restante no negativo\n";
}

echo "\n=== CONCLUSIONES ===\n";
echo "Esta simulación muestra cómo debería funcionar la lógica correcta.\n";
echo "Si el código real produce resultados diferentes, indica problemas en:\n";
echo "1. La aplicación de prioridad interés-capital\n";
echo "2. El cálculo de montos pendientes\n";
echo "3. La determinación de estados de pago\n";
echo "4. La distribución de saldos restantes\n";
?>