<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Amortization {
    
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Calcula el pago mensual (PMT) para una anualidad
     *
     * @param float $principal Monto del préstamo
     * @param float $periodic_rate Tasa de interés por período
     * @param int $periods Número de períodos
     * @return float Pago mensual
     */
    public function pmt($rate, $nper, $pv) {
        if ($rate == 0) {
            return $pv / $nper;
        }
        // Usar BCMath para precisión
        $rate_bc = bcmul($rate, '1', 8);
        $nper_bc = bcmul($nper, '1', 0);
        $pv_bc = bcmul($pv, '100', 0); // Convertir a centavos

        $rate_plus_one = bcadd('1', $rate_bc, 8);
        $power = bcpow($rate_plus_one, bcmul('-1', $nper_bc, 0), 8);
        $denominator = bcsub('1', $power, 8);

        $numerator = bcmul($rate_bc, $pv_bc, 8);
        $result_cents = bcdiv($numerator, $denominator, 2);

        return bcdiv($result_cents, '100', 2); // Convertir de centavos
    }
    
    /**
     * Calcula la tabla de amortización según el método especificado
     *
     * @param float $principal Monto del préstamo
     * @param float $periodic_rate Tasa de interés periódica efectiva
     * @param int $periods Número de períodos
     * @param string $payment_frequency Frecuencia de pago (diario, semanal, quincenal, mensual)
     * @param string $start_date Fecha de inicio del préstamo
     * @param string $method Método de amortización ('francesa', 'estadounidense', 'mixta')
     * @param string $tasa_tipo Tipo de tasa ('TNA', 'periodica')
     * @param string $payment_start_date Fecha de inicio de cobros en formato dd/mm/yyyy
     * @return array Tabla de amortización
     */
    public function calculate_amortization_table($principal, $periodic_rate, $periods, $payment_frequency, $start_date, $method, $tasa_tipo = 'TNA', $payment_start_date = null) {
        error_log("Amortization type en librería: " . $method);
        log_message('debug', 'Iniciando cálculo de tabla de amortización: principal=' . $principal . ', periodic_rate=' . $periodic_rate . ', periods=' . $periods . ', method=' . $method . ', frequency=' . $payment_frequency . ', start_date=' . $start_date);

        // Validaciones de entrada
        if ($principal <= 0) {
            throw new Exception('El principal debe ser mayor que cero.');
        }
        if ($periodic_rate < 0) {
            throw new Exception('La tasa periódica debe ser mayor o igual a cero.');
        }
        if ($periods <= 0) {
            throw new Exception('El número de períodos debe ser mayor que cero.');
        }
        try {
            error_log('Fecha en Amortization: ' . $start_date);
            // Convertir formato dd/mm/yyyy a yyyy-mm-dd si es necesario
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start_date)) {
                $parts = explode('/', $start_date);
                $start_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
            $start_date = date('Y-m-d', strtotime($start_date)); new DateTime($start_date);
        } catch (Exception $e) {
            throw new Exception('La fecha de inicio no es válida.');
        }

        // Validación del método de amortización
        log_message('debug', 'Validando método de amortización: ' . $method);
        if (!in_array($method, ['francesa', 'estaunidense', 'mixta'])) {
            log_message('error', 'Método de amortización inválido recibido: ' . $method);
            throw new Exception('Método de amortización no válido. Debe ser "francesa", "estaunidense" o "mixta".');
        }

        // Forzar frecuencia quincenal para amortización mixta
        if ($method === 'mixta') {
            $payment_frequency = 'quincenal';
            log_message('debug', 'Amortización mixta forzada a frecuencia quincenal');

            // CORRECCIÓN: Manejo correcto de tasas para amortización mixta
            if (strtolower($tasa_tipo) === 'tna') {
                // TNA: convertir a tasa quincenal efectiva
                $periodic_rate = $periodic_rate / 24; // TNA / 24 períodos quincenales por año
                log_message('debug', 'Tasa TNA convertida a quincenal: ' . ($periodic_rate * 100) . '%');
            } elseif (strtolower($tasa_tipo) === 'periodica') {
                // Periódica: la tasa ya viene como quincenal, mantener como está
                log_message('debug', 'Tasa ya es periódica quincenal: ' . ($periodic_rate * 100) . '%');
            } else {
                // Default: asumir TNA si no está especificado
                $periodic_rate = $periodic_rate / 24;
                log_message('debug', 'Tasa no especificada, asumiendo TNA convertida a quincenal: ' . ($periodic_rate * 100) . '%');
            }
        }

        // Calcular intervalo de fechas
        $date_interval = $this->get_date_interval($payment_frequency);

        // Generar fechas de pago basadas en el día de inicio de cobros
        $payment_dates = $this->generate_payment_dates_based_on_day($start_date, $periods, $date_interval, $payment_start_date, $payment_frequency);

        $amortization_table = [];

        log_message('debug', 'Método de amortización recibido en calculate_amortization_table: ' . $method);
        switch ($method) {
            case 'francesa':
                log_message('debug', 'Usando método francés');
                $amortization_table = $this->calculate_french_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'estaunidense':
                log_message('debug', 'Usando método estaunidense (americano): intereses constantes + capital al final');
                $amortization_table = $this->calculate_american_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'mixta':
                log_message('debug', 'Usando método mixto');
                $amortization_table = $this->calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates, $payment_frequency);
                break;
            default:
                log_message('debug', 'Método de amortización no válido recibido: ' . $method);
                throw new Exception('Método de amortización inválido');
        }

        return $amortization_table;
    }
    
    /**
     * Método Francés (Cuota fija)
     * Cada cuota es constante, se descompone en capital + interés
     */
    private function calculate_french_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];

        // Calcular cuota fija usando la función pmt con BCMath
        $payment = $this->pmt($periodic_rate, $periods, $principal);

        $balance = $principal;
        $total_principal_paid = 0;

        for ($i = 0; $i < $periods; $i++) {
            // Usar BCMath para cálculos precisos
            $interest_payment = bcmul($balance, $periodic_rate, 2);
            $principal_payment = bcsub($payment, $interest_payment, 2);

            // Ajustar la última cuota para evitar diferencias por redondeo
            if ($i == $periods - 1) {
                $principal_payment = $balance; // Pago el saldo restante
                $payment = bcadd($principal_payment, $interest_payment, 2); // Recalcular la cuota
            }

            $balance = bcsub($balance, $principal_payment, 2);
            $total_principal_paid = bcadd($total_principal_paid, $principal_payment, 2);

            // Asegurar que el balance nunca sea negativo
            if (bccomp($balance, '0', 2) < 0) {
                $balance = '0.00';
            }

            $amortization_table[] = [
                'period' => $i + 1,
                'payment_date' => $payment_dates[$i],
                'payment' => round(floatval($payment), 2),
                'principal' => round(floatval($principal_payment), 2),
                'interest' => round(floatval($interest_payment), 2),
                'balance' => round(floatval($balance), 2)
            ];
        }

        return $amortization_table;
    }
    
    /**
     * Método Americano (Solo intereses + capital al final)
     * En cada cuota se paga solo interés, en la última se paga el capital completo
     */
    private function calculate_american_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        $interest_payment = $principal * $periodic_rate;
        
        for ($i = 0; $i < $periods; $i++) {
            if ($i == $periods - 1) {
                // Última cuota: interés + capital completo
                $payment = $interest_payment + $principal;
                $principal_payment = $principal;
                $balance = 0;
            } else {
                // Cuotas intermedias: solo interés
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
    
    /**
     * Método Mixto (Alternancia de capital e interés para quincenal)
     * Para quincenal: alterna pagos de capital e interés según el patrón especificado.
     * Para otras frecuencias: capital fijo + interés sobre saldo.
     */
    private function calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates, $payment_frequency) {
        $amortization_table = [];
        $balance = $principal;

        if ($payment_frequency === 'quincenal') {
            // CORRECCIÓN: Patrón correcto para amortización mixta quincenal
            // - Períodos impares (1,3,5,...): solo capital
            // - Períodos pares (2,4,6,...): solo interés
            // El capital se distribuye uniformemente entre los pagos de capital

            // Calcular número de pagos de capital (períodos impares)
            $capital_payments = ceil($periods / 2); // Número de períodos impares
            $capital_per_payment = bcdiv($principal, $capital_payments, 2); // Capital uniforme por pago con BCMath

            for ($i = 0; $i < $periods; $i++) {
                $period_number = $i + 1; // Número de período (1, 2, 3, ...)

                // Si el balance ya es 0, no generar más pagos
                if (bccomp($balance, '0', 2) <= 0) {
                    break;
                }

                if ($period_number % 2 == 1) {
                    // Períodos impares: solo capital
                    $interest_payment = '0.00';
                    $principal_payment = $capital_per_payment;

                    // Ajustar el último pago de capital para evitar diferencias por redondeo
                    if (bccomp($balance, $capital_per_payment, 2) <= 0) {
                        $principal_payment = $balance;
                    }

                    $payment = $principal_payment;
                    $balance = bcsub($balance, $principal_payment, 2);

                } else {
                    // Períodos pares: solo interés sobre el saldo actual
                    $interest_payment = bcmul($balance, $periodic_rate, 2);
                    $principal_payment = '0.00';
                    $payment = $interest_payment;
                    // Balance no cambia en pagos de interés
                }

                // Asegurar que el balance nunca sea negativo
                if (bccomp($balance, '0', 2) < 0) {
                    $balance = '0.00';
                }

                // Solo agregar a la tabla si hay pago (evitar cuotas en 0)
                if (bccomp($payment, '0', 2) > 0) {
                    $amortization_table[] = [
                        'period' => $period_number,
                        'payment_date' => $payment_dates[$i],
                        'payment' => round(floatval($payment), 2),
                        'principal' => round(floatval($principal_payment), 2),
                        'interest' => round(floatval($interest_payment), 2),
                        'balance' => round(floatval($balance), 2)
                    ];
                }
            }

            // Ajuste final: asegurar que el último saldo sea exactamente 0.00
            if (!empty($amortization_table)) {
                $last_index = count($amortization_table) - 1;
                $amortization_table[$last_index]['balance'] = 0.00;
            }
        } else {
            // Lógica original para otras frecuencias: capital fijo + interés sobre saldo
            $base_principal_payment = bcdiv($principal, $periods, 2);

            for ($i = 0; $i < $periods; $i++) {
                $interest_payment = bcmul($balance, $periodic_rate, 2);
                $principal_payment = $base_principal_payment;

                // Ajustar la última cuota para evitar diferencias por redondeo
                if ($i == $periods - 1) {
                    $principal_payment = $balance; // Pago el saldo restante
                }

                $payment = bcadd($principal_payment, $interest_payment, 2);
                $balance = bcsub($balance, $principal_payment, 2);

                // Asegurar que el balance nunca sea negativo
                if (bccomp($balance, '0', 2) < 0) {
                    $balance = '0.00';
                }

                $amortization_table[] = [
                    'period' => $i + 1,
                    'payment_date' => $payment_dates[$i],
                    'payment' => round(floatval($payment), 2),
                    'principal' => round(floatval($principal_payment), 2),
                    'interest' => round(floatval($interest_payment), 2),
                    'balance' => round(floatval($balance), 2)
                ];
            }
        }

        return $amortization_table;
    }
    
    
    /**
     * Obtiene el intervalo de fechas según la frecuencia de pago
     */
    private function get_date_interval($payment_frequency) {
        switch ($payment_frequency) {
            case 'diario':
                return 'P1D';
            case 'semanal':
                return 'P7D';
            case 'quincenal':
                return 'P15D';
            case 'mensual':
                return 'P1M'; // 1 mes exacto
            default:
                return 'P30D'; // Default mensual
        }
    }
    
    /**
     * Genera las fechas de pago basadas en la fecha de inicio de cobros especificada para TODAS las frecuencias
     */
    private function generate_payment_dates_based_on_day($start_date, $periods, $date_interval, $payment_start_date = null, $payment_frequency = 'mensual') {
        $dates = [];

        // Si no se especifica fecha de inicio de cobros, usar el método original
        if ($payment_start_date === null) {
            return $this->generate_payment_dates($start_date, $periods, $date_interval);
        }

        // Para TODAS las frecuencias: usar la fecha de inicio de cobros especificada como primera fecha de pago
        $first_payment_date = DateTime::createFromFormat('d/m/Y', $payment_start_date);
        if (!$first_payment_date) {
            throw new Exception('Fecha de inicio de cobros no es válida.');
        }

        // Ahora generar todas las fechas usando intervalos desde la primera fecha ajustada
        $current_date = clone $first_payment_date;

        for ($i = 0; $i < $periods; $i++) {
            $dates[] = $current_date->format('Y-m-d');
            $current_date->add(new DateInterval($date_interval));
        }

        return $dates;
    }

    /**
     * Genera las fechas de pago (método original para compatibilidad)
     */
    private function generate_payment_dates($start_date, $periods, $date_interval) {
        $dates = [];
        $start = new DateTime($start_date);
        $interval = new DateInterval($date_interval);

        for ($i = 0; $i < $periods; $i++) {
            $payment_date = clone $start;
            $payment_date->add($interval);
            $dates[] = $payment_date->format('Y-m-d');
            $start = $payment_date;
        }

        return $dates;
    }
    
    /**
     * Calcula el resumen del préstamo
     */
    public function calculate_loan_summary($amortization_table) {
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
    
    /**
     * Calcula la amortización vía AJAX para mostrar en tiempo real
     */
    public function ajax_calculate_amortization($principal, $periodic_rate, $periods, $payment_frequency, $start_date, $method, $tasa_tipo = 'TNA', $payment_start_date = null) {
        log_message('debug', 'Iniciando cálculo AJAX de amortización');
        try {
            $amortization_table = $this->calculate_amortization_table(
                $principal,
                $periodic_rate,
                $periods,
                $payment_frequency,
                $start_date,
                $method,
                $tasa_tipo,
                $payment_start_date
            );

            $summary = $this->calculate_loan_summary($amortization_table);
            log_message('debug', 'Cálculo AJAX completado exitosamente');

            // DIAGNÓSTICO: Agregar validaciones de coherencia
            $diagnostics = $this->validate_amortization_coherence($amortization_table, $principal, $method, $tasa_tipo);

            return [
                'success' => true,
                'amortization_table' => $amortization_table,
                'summary' => $summary,
                'diagnostics' => $diagnostics
            ];

        } catch (Exception $e) {
            log_message('error', 'Error en cálculo AJAX de amortización: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida la coherencia de la tabla de amortización
     */
    private function validate_amortization_coherence($table, $principal, $method, $tasa_tipo) {
        $diagnostics = [
            'is_coherent' => true,
            'issues' => [],
            'recommendations' => []
        ];

        $total_capital = 0;
        $total_interes = 0;
        $total_cuota = 0;
        $final_balance = end($table)['balance'];

        foreach ($table as $row) {
            $total_capital += $row['principal'];
            $total_interes += $row['interest'];
            $total_cuota += $row['payment'];
        }

        // Validación 1: Suma de capital debe igualar al principal
        if (abs($total_capital - $principal) > 0.01) {
            $diagnostics['is_coherent'] = false;
            $diagnostics['issues'][] = "Suma de capital ($total_capital) no coincide con principal ($principal)";
        }

        // Validación 2: Saldo final debe ser 0
        if (abs($final_balance) > 0.01) {
            $diagnostics['is_coherent'] = false;
            $diagnostics['issues'][] = "Saldo final ($final_balance) no es cero";
        }

        // Validación 3: Para amortización mixta, validar patrón alterno
        if ($method === 'mixta') {
            $pattern_valid = true;
            foreach ($table as $i => $row) {
                $period = $i + 1;
                if ($period % 2 == 1) {
                    // Períodos impares: solo capital
                    if ($row['interest'] != 0) {
                        $pattern_valid = false;
                        break;
                    }
                } else {
                    // Períodos pares: solo interés
                    if ($row['principal'] != 0) {
                        $pattern_valid = false;
                        break;
                    }
                }
            }

            if (!$pattern_valid) {
                $diagnostics['is_coherent'] = false;
                $diagnostics['issues'][] = "Patrón alterno interés-capital no se cumple correctamente";
                $diagnostics['recommendations'][] = "Revisar lógica de períodos impares (capital) y pares (interés)";
            }
        }

        // Validación 4: Verificar que no hay cuotas en 0
        $zero_payments = array_filter($table, function($row) { return $row['payment'] <= 0; });
        if (count($zero_payments) > 0) {
            $diagnostics['is_coherent'] = false;
            $diagnostics['issues'][] = "Se encontraron " . count($zero_payments) . " cuotas con valor cero";
            $diagnostics['recommendations'][] = "Eliminar filas con cuota = 0 de la tabla";
        }

        // Recomendaciones basadas en tipo de tasa
        if ($tasa_tipo === 'TNA' && $method === 'mixta') {
            $diagnostics['recommendations'][] = "Tasa TNA convertida correctamente a quincenal (/24)";
        } elseif ($tasa_tipo === 'periodica' && $method === 'mixta') {
            $diagnostics['recommendations'][] = "Tasa periódica aplicada directamente (ya es quincenal)";
        }

        return $diagnostics;
    }

    /**
     * Calcula la tabla de amortización
     *
     * @param float $monto Monto del préstamo
     * @param float $tasa_anual Tasa de interés anual en porcentaje
     * @param int $n_cuotas Número de cuotas
     * @param string $fecha_inicio Fecha de inicio en formato 'Y-m-d'
     * @param string $tipo_amortizacion Tipo de amortización ('francesa', 'americana', 'mixta')
     * @param string $frecuencia_pago Frecuencia de pago ('mensual', 'quincenal', 'semanal')
     * @return array Array con 'summary' y 'table'
     */
    public function calculate_french_amortization($monto, $tasa_anual, $n_cuotas, $fecha_inicio, $tipo_amortizacion = 'francesa', $frecuencia_pago = 'mensual') {
        // Calcular períodos por año y tasa periódica
        switch ($frecuencia_pago) {
            case 'mensual':
                $periodos_por_anio = 12;
                $modify_date = '+1 month';
                break;
            case 'quincenal':
                $periodos_por_anio = 24;
                $modify_date = '+15 days';
                break;
            case 'semanal':
                $periodos_por_anio = 52;
                $modify_date = '+7 days';
                break;
            case 'diario':
                $periodos_por_anio = 365;
                $modify_date = '+1 day';
                break;
            default:
                $periodos_por_anio = 12;
                $modify_date = '+1 month';
        }
        $tasa_periodica = $tasa_anual / 100 / $periodos_por_anio;

        // Generar fechas desde fecha_inicio sin desplazar la primera
        $fechas = [];
        $fecha_actual = new DateTime($fecha_inicio);
        $fechas[] = $fecha_actual->format('Y-m-d'); // Primera fecha es fecha_inicio
        for ($i = 1; $i < $n_cuotas; $i++) {
            $fecha_actual->modify($modify_date);
            $fechas[] = $fecha_actual->format('Y-m-d');
        }

        // Calcular tabla según tipo
        $tabla = [];
        $saldo = $monto;
        $total_pagado = 0;
        $total_interes = 0;

        switch ($tipo_amortizacion) {
            case 'francesa':
                // Cuota fija
                $cuota_fija = $this->pmt($tasa_periodica, $n_cuotas, $monto);
                for ($periodo = 1; $periodo <= $n_cuotas; $periodo++) {
                    $interes = $saldo * $tasa_periodica;
                    $capital = $cuota_fija - $interes;

                    // Ajustar la última cuota
                    if ($periodo == $n_cuotas) {
                        $capital = $saldo;
                        $cuota_fija = $capital + $interes;
                    }

                    $saldo -= $capital;

                    $cuota_redondeada = round($cuota_fija, 2);
                    $interes_redondeado = round($interes, 2);
                    $capital_redondeado = round($capital, 2);
                    $saldo_redondeado = round($saldo, 2);

                    $tabla[] = [
                        'periodo' => $periodo,
                        'fecha' => $fechas[$periodo - 1],
                        'cuota' => $cuota_redondeada,
                        'interes' => $interes_redondeado,
                        'capital' => $capital_redondeado,
                        'saldo' => $saldo_redondeado
                    ];

                    $total_pagado += $cuota_redondeada;
                    $total_interes += $interes_redondeado;
                }
                $valor_cuota = preg_replace('/,00$/', '', number_format($cuota_fija, 2, ',', '.'));
                break;

            case 'americana':
                // Intereses constantes, capital al final
                $interes_periodico = $monto * $tasa_periodica;
                for ($periodo = 1; $periodo <= $n_cuotas; $periodo++) {
                    if ($periodo == $n_cuotas) {
                        $capital = $saldo;
                        $cuota = $interes_periodico + $capital;
                        $saldo = 0;
                    } else {
                        $capital = 0;
                        $cuota = $interes_periodico;
                        $saldo = $monto;
                    }

                    $interes = $interes_periodico;

                    $cuota_redondeada = round($cuota, 2);
                    $interes_redondeado = round($interes, 2);
                    $capital_redondeado = round($capital, 2);
                    $saldo_redondeado = round($saldo, 2);

                    $tabla[] = [
                        'periodo' => $periodo,
                        'fecha' => $fechas[$periodo - 1],
                        'cuota' => $cuota_redondeada,
                        'interes' => $interes_redondeado,
                        'capital' => $capital_redondeado,
                        'saldo' => $saldo_redondeado
                    ];

                    $total_pagado += $cuota_redondeada;
                    $total_interes += $interes_redondeado;
                }
                $valor_cuota = preg_replace('/,00$/', '', number_format($interes_periodico, 2, ',', '.'));
                break;

            case 'mixta':
                // Capital fijo + intereses sobre saldo
                $capital_fijo = $monto / $n_cuotas;
                for ($periodo = 1; $periodo <= $n_cuotas; $periodo++) {
                    $interes = $saldo * $tasa_periodica;
                    $capital = $capital_fijo;

                    // Ajustar la última cuota
                    if ($periodo == $n_cuotas) {
                        $capital = $saldo;
                    }

                    $cuota = $capital + $interes;
                    $saldo -= $capital;

                    $cuota_redondeada = round($cuota, 2);
                    $interes_redondeado = round($interes, 2);
                    $capital_redondeado = round($capital, 2);
                    $saldo_redondeado = round($saldo, 2);

                    $tabla[] = [
                        'periodo' => $periodo,
                        'fecha' => $fechas[$periodo - 1],
                        'cuota' => $cuota_redondeada,
                        'interes' => $interes_redondeado,
                        'capital' => $capital_redondeado,
                        'saldo' => $saldo_redondeado
                    ];

                    $total_pagado += $cuota_redondeada;
                    $total_interes += $interes_redondeado;
                }
                $valor_cuota = preg_replace('/,00$/', '', number_format($capital_fijo + ($monto * $tasa_periodica), 2, ',', '.'));
                break;

            default:
                throw new Exception('Tipo de amortización no válido');
        }

        // Calcular resumen
        $valor_interes_total = preg_replace('/,00$/', '', number_format($total_interes, 2, ',', '.'));
        $monto_total = preg_replace('/,00$/', '', number_format($total_pagado, 2, ',', '.'));

        $summary = [
            'valor_cuota' => $valor_cuota,
            'valor_interes_total' => $valor_interes_total,
            'monto_total' => $monto_total
        ];

        return [
            'summary' => $summary,
            'table' => $tabla
        ];
    }

    /**
     * Calcula la tabla de amortización americana
     *
     * @param float $monto Monto del préstamo
     * @param float $tasa_anual Tasa de interés anual en porcentaje
     * @param int $n_cuotas Número de cuotas
     * @param string $fecha_inicio Fecha de inicio en formato 'Y-m-d'
     * @param string $frecuencia_pago Frecuencia de pago ('mensual', 'quincenal', 'semanal')
     * @return array Array con 'summary' y 'table'
     */
    public function calculate_american_amortization($monto, $tasa_anual, $n_cuotas, $fecha_inicio, $frecuencia_pago = 'mensual') {
        return $this->calculate_french_amortization($monto, $tasa_anual, $n_cuotas, $fecha_inicio, 'americana', $frecuencia_pago);
    }

    /**
     * Calcula la tabla de amortización mixta
     *
     * @param float $monto Monto del préstamo
     * @param float $tasa_anual Tasa de interés anual en porcentaje
     * @param int $n_cuotas Número de cuotas
     * @param string $fecha_inicio Fecha de inicio en formato 'Y-m-d'
     * @param string $frecuencia_pago Frecuencia de pago ('mensual', 'quincenal', 'semanal')
     * @return array Array con 'summary' y 'table'
     */
    public function calculate_mixed_amortization($monto, $tasa_anual, $n_cuotas, $fecha_inicio, $frecuencia_pago = 'mensual') {
        return $this->calculate_french_amortization($monto, $tasa_anual, $n_cuotas, $fecha_inicio, 'mixta', $frecuencia_pago);
    }
}

