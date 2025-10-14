<?php
// Script de prueba para amortización
// Ejecuta casos A (Francesa), B (Mixta), C (Estadounidense)
// Verifica que el método estadounidense produzca cuotas intermedias de solo interés y última de capital + interés

// Clase simplificada de Amortization para test standalone
class AmortizationTest {
    public function pmt($rate, $nper, $pv) {
        if ($rate == 0) {
            return $pv / $nper;
        }
        return $pv * $rate / (1 - pow(1 + $rate, -$nper));
    }

    public function calculate_amortization_table($principal, $periodic_rate, $periods, $payment_frequency, $start_date, $method) {
        error_log("Amortization type en test: " . $method);

        // Validaciones básicas
        if ($principal <= 0 || $periodic_rate < 0 || $periods <= 0) {
            throw new Exception('Parámetros inválidos');
        }
        if (!in_array($method, ['francesa', 'estadounidense', 'mixta'])) {
            throw new Exception('Método inválido');
        }

        // Generar fechas simples
        $payment_dates = [];
        $date = new DateTime($start_date);
        for ($i = 0; $i < $periods; $i++) {
            $payment_dates[] = $date->format('Y-m-d');
            $date->modify('+1 month');
        }

        $amortization_table = [];

        switch ($method) {
            case 'francesa':
                $amortization_table = $this->calculate_french_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'estadounidense':
                $amortization_table = $this->calculate_american_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'mixta':
                $amortization_table = $this->calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
        }

        return $amortization_table;
    }

    private function calculate_french_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        $payment = $this->pmt($periodic_rate, $periods, $principal);
        $balance = $principal;

        for ($i = 0; $i < $periods; $i++) {
            $interest_payment = $balance * $periodic_rate;
            $principal_payment = $payment - $interest_payment;
            if ($i == $periods - 1) {
                $principal_payment = $balance;
                $payment = $principal_payment + $interest_payment;
            }
            $balance -= $principal_payment;

            $amortization_table[] = [
                'period' => $i + 1,
                'payment_date' => $payment_dates[$i],
                'payment' => round($payment, 2),
                'principal' => round($principal_payment, 2),
                'interest' => round($interest_payment, 2),
                'balance' => round($balance, 2)
            ];
        }
        return $amortization_table;
    }

    private function calculate_american_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        $interest_payment = $principal * $periodic_rate;

        for ($i = 0; $i < $periods; $i++) {
            if ($i == $periods - 1) {
                $payment = $interest_payment + $principal;
                $principal_payment = $principal;
                $balance = 0;
            } else {
                $payment = $interest_payment;
                $principal_payment = 0;
                $balance = $principal;
            }

            $amortization_table[] = [
                'period' => $i + 1,
                'payment_date' => $payment_dates[$i],
                'payment' => round($payment, 2),
                'principal' => round($principal_payment, 2),
                'interest' => round($interest_payment, 2),
                'balance' => round($balance, 2)
            ];
        }
        return $amortization_table;
    }

    private function calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        $base_principal_payment = $principal / $periods;
        $balance = $principal;

        for ($i = 0; $i < $periods; $i++) {
            $interest_payment = $balance * $periodic_rate;
            $principal_payment = $base_principal_payment;
            if ($i == $periods - 1) {
                $principal_payment = $balance;
            }
            $payment = $principal_payment + $interest_payment;
            $balance -= $principal_payment;

            $amortization_table[] = [
                'period' => $i + 1,
                'payment_date' => $payment_dates[$i],
                'payment' => round($payment, 2),
                'principal' => round($principal_payment, 2),
                'interest' => round($interest_payment, 2),
                'balance' => round($balance, 2)
            ];
        }
        return $amortization_table;
    }
}

// Parámetros fijos para la prueba
$principal = 10000;
$annual_rate = 5; // 5%
$periodic_rate = $annual_rate / 100 / 12; // Mensual
$periods = 12;
$payment_frequency = 'mensual';
$start_date = '2023-01-01';

// Casos a probar
$cases = [
    'A' => 'francesa',
    'B' => 'mixta',
    'C' => 'estadounidense'
];

// Instanciar la clase
$amortization = new AmortizationTest();

echo "=== PRUEBA DE AMORTIZACIÓN ===\n\n";

foreach ($cases as $case => $method) {
    echo "Caso $case: Método $method\n";
    error_log("Amortization type recibido en test: $method");

    try {
        $table = $amortization->calculate_amortization_table($principal, $periodic_rate, $periods, $payment_frequency, $start_date, $method);

        echo "Tabla de amortización:\n";
        foreach ($table as $row) {
            echo "Período {$row['period']}: Pago={$row['payment']}, Principal={$row['principal']}, Interés={$row['interest']}, Saldo={$row['balance']}\n";
        }

        // Verificación específica para método estadounidense
        if ($method === 'estadounidense') {
            echo "\nVerificación para método estadounidense:\n";
            $intermedias_solo_interes = true;
            $ultima_capital_interes = false;

            foreach ($table as $index => $row) {
                if ($index < $periods - 1) {
                    if ($row['principal'] != 0) {
                        $intermedias_solo_interes = false;
                    }
                } else {
                    if ($row['principal'] == $principal && $row['interest'] > 0) {
                        $ultima_capital_interes = true;
                    }
                }
            }

            if ($intermedias_solo_interes && $ultima_capital_interes) {
                echo "✓ Correcto: Cuotas intermedias solo interés, última capital + interés\n";
            } else {
                echo "✗ Error: No cumple con el comportamiento esperado\n";
            }
        }

        echo "\n";

    } catch (Exception $e) {
        echo "Error en caso $case: " . $e->getMessage() . "\n\n";
    }
}

echo "Prueba completada.\n";
?>