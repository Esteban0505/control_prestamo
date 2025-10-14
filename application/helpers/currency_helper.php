<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Currency Helper - Funciones para formateo de moneda colombiana
 * 
 * Este helper proporciona funciones para convertir entre formato colombiano
 * y formato decimal estándar para la base de datos.
 */

if (!function_exists('format_to_db')) {
    /**
     * Convierte formato colombiano a decimal estándar para base de datos
     * 
     * @param string $value Valor en formato colombiano (ej: "2.000.000,50")
     * @return float Valor decimal estándar (ej: 2000000.50)
     */
    function format_to_db($value) {
        if (empty($value) || $value === '') {
            return 0.00;
        }
        
        // Remover espacios y caracteres no numéricos excepto punto y coma
        $value = trim($value);
        $value = str_replace('$', '', $value); // Remover símbolo de peso
        $value = str_replace(' ', '', $value); // Remover espacios
        
        // Validar formato colombiano
        if (!preg_match('/^[\d]{1,3}(\.[\d]{3})*(,[\d]{1,2})?$/', $value)) {
            return false; // Formato inválido
        }
        
        // Convertir a formato decimal estándar
        $value = str_replace('.', '', $value); // Remover separadores de miles
        $value = str_replace(',', '.', $value); // Convertir coma decimal a punto
        
        return (float) $value;
    }
}

if (!function_exists('format_to_display')) {
    /**
     * Convierte decimal estándar a formato colombiano para mostrar
     * 
     * @param float $value Valor decimal estándar (ej: 2000000.50)
     * @param bool $include_symbol Si incluir el símbolo $ (default: true)
     * @return string Valor en formato colombiano (ej: "$2.000.000,50")
     */
    function format_to_display($value, $include_symbol = true) {
        log_message('debug', 'format_to_display input: ' . $value . ', type: ' . gettype($value));
        if (empty($value) || $value === null || $value === '') {
            return $include_symbol ? '$0' : '0';
        }

        $value = (float) $value;
        log_message('debug', 'format_to_display after float cast: ' . $value);

        // Usar floor para evitar redondeo, truncar hacia abajo
        $value = floor($value);

        // Formatear con separadores de miles sin decimales
        $formatted = number_format($value, 0, ',', '.');
        log_message('debug', 'format_to_display output: ' . $formatted);

        return $include_symbol ? '$' . $formatted : $formatted;
    }
}

if (!function_exists('validate_colombian_currency')) {
    /**
     * Valida si un valor está en formato de moneda colombiana válido
     * 
     * @param string $value Valor a validar
     * @return bool True si es válido, false si no
     */
    function validate_colombian_currency($value) {
        if (empty($value) || $value === '') {
            return false;
        }
        
        $value = trim($value);
        $value = str_replace('$', '', $value);
        $value = str_replace(' ', '', $value);
        
        // Patrón para formato colombiano: 1.000.000,50 o 1000000
        return preg_match('/^[\d]+(\.[\d]{3})*(,[\d]{1,2})?$/', $value);
    }
}

if (!function_exists('format_currency_input')) {
    /**
     * Formatea un valor para mostrar en input de moneda
     * 
     * @param float $value Valor decimal
     * @return string Valor formateado para input
     */
    function format_currency_input($value) {
        if (empty($value) || $value === null || $value === '') {
            return '';
        }
        
        $value = (float) $value;
        return number_format($value, 2, ',', '.');
    }
}

if (!function_exists('sanitize_currency_input')) {
    /**
     * Sanitiza y valida input de moneda colombiana
     * 
     * @param string $input Input del usuario
     * @return array Array con 'valid' (bool) y 'value' (float|false)
     */
    function sanitize_currency_input($input) {
        $input = trim($input);
        
        if (empty($input)) {
            return ['valid' => true, 'value' => 0.00];
        }
        
        // Validar formato
        if (!validate_colombian_currency($input)) {
            return ['valid' => false, 'value' => false];
        }
        
        // Convertir a decimal
        $decimal_value = format_to_db($input);
        
        if ($decimal_value === false) {
            return ['valid' => false, 'value' => false];
        }
        
        return ['valid' => true, 'value' => $decimal_value];
    }
}

