<?php
define('BASEPATH', 'test');

// Función get_period_rate copiada de Loans_m.php
function get_period_rate($annual_rate, $periods_per_year, $tasa_tipo = 'Nominal') {
    $rate_decimal = $annual_rate / 100.0;

    if (strtolower($tasa_tipo) === 'nominal') {
        return $rate_decimal / $periods_per_year;
    } else { // Efectiva
        return pow(1 + $rate_decimal, 1 / $periods_per_year) - 1;
    }
}

// Función pmt copiada de Amortization.php
function pmt($rate, $nper, $pv) {
    if ($rate == 0) {
        return $pv / $nper;
    }
    return $pv * $rate / (1 - pow(1 + $rate, -$nper));
}

// Función calculate_french_method simplificada
function calculate_french_method($principal, $periodic_rate, $periods) {
    $amortization_table = [];

    // Calcular cuota fija usando la función pmt
    $payment = pmt($periodic_rate, $periods, $principal);

    $balance = $principal;
    $total_principal_paid = 0;

    for ($i = 0; $i < $periods; $i++) {
        $interest_payment = $balance * $periodic_rate;
        $principal_payment = $payment - $interest_payment;

        // Ajustar la última cuota para evitar diferencias por redondeo
        if ($i == $periods - 1) {
            $principal_payment = $balance; // Pago el saldo restante
            $payment = $principal_payment + $interest_payment; // Recalcular la cuota
        }

        $balance = $balance - $principal_payment;
        $total_principal_paid += $principal_payment;

        $amortization_table[] = [
            'period' => $i + 1,
            'payment' => round($payment, 2),
            'principal' => round($principal_payment, 2),
            'interest' => round($interest_payment, 2),
            'balance' => round($balance, 2)
        ];
    }

    return $amortization_table;
}

// Función calculate_loan_summary simplificada
function calculate_loan_summary($amortization_table) {
    $total_payments = 0;
    $total_principal = 0;
    $total_interest = 0;

    foreach ($amortization_table as $payment) {
        $total_payments += $payment['payment'];
        $total_principal += $payment['principal'];
        $total_interest += $payment['interest'];
    }

    return [
        'total_payments' => round($total_payments, 2),
        'total_principal' => round($total_principal, 2),
        'total_interest' => round($total_interest, 2),
        'periods' => count($amortization_table)
    ];
}

// Parámetros del ejemplo
$principal = 1000000;
$annual_rate = 10;
$periods = 5;
$payment_frequency = 'mensual';
$method = 'francesa';
$start_date = '2025-01-01';

// Calcular tasa periódica
$periodic_rate = get_period_rate($annual_rate, 12, 'Nominal');
echo "Tasa periódica: $periodic_rate\n";

// Calcular cuota
$payment = pmt($periodic_rate, $periods, $principal);
echo "Cuota: $payment\n";

// Calcular tabla de amortización (solo método francés)
$amortization_table = calculate_french_method($principal, $periodic_rate, $periods);

// Mostrar tabla
echo "Tabla de amortización:\n";
foreach ($amortization_table as $row) {
    echo "Periodo: {$row['period']}, Cuota: {$row['payment']}, Principal: {$row['principal']}, Interés: {$row['interest']}, Saldo: {$row['balance']}\n";
}

// Calcular resumen
$summary = calculate_loan_summary($amortization_table);
echo "\nResumen:\n";
echo "Total pagos: {$summary['total_payments']}\n";
echo "Total principal: {$summary['total_principal']}\n";
echo "Total intereses: {$summary['total_interest']}\n";
echo "Periodos: {$summary['periods']}\n";

// Verificar saldo final
$last_balance = end($amortization_table)['balance'];
echo "\nSaldo final: $last_balance\n";