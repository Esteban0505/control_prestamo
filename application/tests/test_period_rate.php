<?php
// Script de prueba para get_period_rate

function get_period_rate_old($rate, $days_per_period, $tasa_tipo = 'TNA') {
    $rate_decimal = $rate / 100.0;

    if (strtolower($tasa_tipo) === 'tna') {
        return ($rate_decimal / 365) * $days_per_period;
    } elseif (strtolower($tasa_tipo) === 'periodica') {
        return $rate_decimal;
    } else {
        return ($rate_decimal / 365) * $days_per_period;
    }
}

function get_period_rate_new($rate, $periods_per_year, $tasa_tipo = 'TNA') {
    $rate_decimal = $rate / 100.0;

    if (strtolower($tasa_tipo) === 'tna') {
        return $rate_decimal / $periods_per_year;
    } elseif (strtolower($tasa_tipo) === 'periodica') {
        return $rate_decimal;
    } else {
        return $rate_decimal / $periods_per_year;
    }
}

// Pruebas
$rate = 24; // 24% TNA
$test_cases = [
    ['mensual', 30, 12],
    ['semanal', 7, 52],
    ['quincenal', 15, 24],
    ['diario', 1, 365]
];

echo "Comparación de tasas periódicas:\n";
echo "Tasa TNA: $rate%\n\n";

foreach ($test_cases as $case) {
    $freq = $case[0];
    $days = $case[1];
    $periods = $case[2];

    $old_rate = get_period_rate_old($rate, $days, 'TNA');
    $new_rate = get_period_rate_new($rate, $periods, 'TNA');

    echo "$freq:\n";
    echo "  Viejo (días): " . number_format($old_rate * 100, 6) . "%\n";
    echo "  Nuevo (períodos): " . number_format($new_rate * 100, 6) . "%\n";
    echo "  Diferencia: " . number_format(($new_rate - $old_rate) * 100, 6) . "%\n\n";
}