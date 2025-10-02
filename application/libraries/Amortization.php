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
        return $pv * $rate / (1 - pow(1 + $rate, -$nper));
    }
    
    /**
     * Calcula la tabla de amortización según el método especificado
     *
     * @param float $principal Monto del préstamo
     * @param float $periodic_rate Tasa de interés periódica efectiva
     * @param int $periods Número de períodos
     * @param string $payment_frequency Frecuencia de pago (diario, semanal, quincenal, mensual)
     * @param string $method Método de amortización (francesa, americana, mixta)
     * @param string $start_date Fecha de inicio del préstamo
     * @return array Tabla de amortización
     */
    public function calculate_amortization_table($principal, $periodic_rate, $periods, $payment_frequency, $method, $start_date) {
        log_message('debug', 'Iniciando cálculo de tabla de amortización: principal=' . $principal . ', periodic_rate=' . $periodic_rate . ', periods=' . $periods . ', method=' . $method . ', frequency=' . $payment_frequency);
        
        // Calcular intervalo de fechas
        $date_interval = $this->get_date_interval($payment_frequency);
        
        // Generar fechas de pago
        $payment_dates = $this->generate_payment_dates($start_date, $periods, $date_interval);
        
        $amortization_table = [];

        log_message('debug', 'Método recibido en calculate_amortization_table: ' . $method);
        $method = strtolower($method);
        switch ($method) {
            case 'francesa':
                log_message('debug', 'Usando método de amortización francés');
                $amortization_table = $this->calculate_french_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'estadounidense':
                log_message('debug', 'Usando método de amortización estadounidense');
                $amortization_table = $this->calculate_american_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            case 'mixta':
                log_message('debug', 'Usando método de amortización mixta');
                $amortization_table = $this->calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates);
                break;
            default:
                log_message('debug', 'Método no válido recibido: ' . $method);
                throw new Exception('Método de amortización no válido');
        }
        
        return $amortization_table;
    }
    
    /**
     * Método Francés (Cuota fija)
     * Cada cuota es constante, se descompone en capital + interés
     */
    private function calculate_french_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        
        // Calcular cuota fija usando la función pmt
        $payment = $this->pmt($periodic_rate, $periods, $principal);
        
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
     * Método Mixto (Capital fijo + interés sobre saldo)
     * Se divide el capital en partes iguales y se suma el interés sobre el saldo
     */
    private function calculate_mixed_method($principal, $periodic_rate, $periods, $payment_dates) {
        $amortization_table = [];
        $base_principal_payment = $principal / $periods;
        $balance = $principal;
        
        for ($i = 0; $i < $periods; $i++) {
            $interest_payment = $balance * $periodic_rate;
            $principal_payment = $base_principal_payment;
            
            // Ajustar la última cuota para evitar diferencias por redondeo
            if ($i == $periods - 1) {
                $principal_payment = $balance; // Pago el saldo restante
            }
            
            $payment = $principal_payment + $interest_payment;
            $balance = $balance - $principal_payment;
            
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
                return 'P1M';
            default:
                return 'P1M'; // Default mensual
        }
    }
    
    /**
     * Genera las fechas de pago
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
    public function ajax_calculate_amortization($principal, $periodic_rate, $periods, $payment_frequency, $method, $start_date) {
        log_message('debug', 'Iniciando cálculo AJAX de amortización');
        try {
            $amortization_table = $this->calculate_amortization_table(
                $principal,
                $periodic_rate,
                $periods,
                $payment_frequency,
                $method,
                $start_date
            );

            $summary = $this->calculate_loan_summary($amortization_table);
            log_message('debug', 'Cálculo AJAX completado exitosamente');

            return [
                'success' => true,
                'amortization_table' => $amortization_table,
                'summary' => $summary
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

