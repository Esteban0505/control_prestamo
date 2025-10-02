<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Format Helper - Funciones mejoradas para formateo de moneda colombiana
 * 
 * Este helper proporciona funciones para convertir entre formato colombiano
 * y formato decimal estándar para la base de datos.
 */

if (!function_exists('format_money_co')) {
    /**
     * Formatea un número a formato de moneda colombiana
     * 
     * @param float $number Número a formatear
     * @param bool $include_symbol Si incluir el símbolo $ (default: true)
     * @return string Valor formateado (ej: "$1.234.567,89")
     */
    function format_money_co($number, $include_symbol = true) {
        if (empty($number) || $number === null || $number === '') {
            return $include_symbol ? '$0' : '0';
        }
        
        $number = (float) $number;

        // Determinar si mostrar decimales
        $decimals = (floor($number) == $number) ? 0 : 2;

        // Formatear con separadores de miles y decimales
        $formatted = number_format($number, $decimals, ',', '.');
        
        return $include_symbol ? '$' . $formatted : $formatted;
    }
}

if (!function_exists('parse_money_co')) {
    /**
     * Convierte formato de moneda a decimal estándar para base de datos
     * Acepta: 1.000.000,50 o 1000000.50
     */
    function parse_money_co($str) {
        if ($str === null || $str === '') {
            return 0.00;
        }
        $str = trim($str);
        $str = str_replace('$', '', $str);
        $str = str_replace(' ', '', $str);

        // Eliminar puntos de miles
        $str = str_replace('.', '', $str);
        // Reemplazar coma por punto decimal
        $str = str_replace(',', '.', $str);

        if (is_numeric($str)) {
            return (float) $str;
        }

        return false;
    }
}

if (!function_exists('validate_money_co')) {
    /**
     * Valida si un valor está en formato de moneda colombiana válido
     * 
     * @param string $value Valor a validar
     * @return bool True si es válido, false si no
     */
    function validate_money_co($str) {
        if ($str === null || $str === '') {
            return false;
        }
        $str = trim($str);
        $str = str_replace('$', '', $str);
        $str = str_replace(' ', '', $str);
        return (bool) preg_match('/^(\d{1,3}(\.\d{3})*|\d+)(,\d{1,2})?$/', $str);
    }
}

if (!function_exists('sanitize_money_input')) {
    /**
     * Sanitiza y valida input de moneda colombiana
     * 
     * @param string $input Input del usuario
     * @return array Array con 'valid' (bool) y 'value' (float|false)
     */
    function sanitize_money_input($input) {
        $input = trim($input);
        
        if (empty($input)) {
            return ['valid' => true, 'value' => 0.00];
        }
        
        // Validar formato
        if (!validate_money_co($input)) {
            return ['valid' => false, 'value' => false];
        }
        
        // Convertir a decimal
        $decimal_value = parse_money_co($input);
        
        if ($decimal_value === false) {
            return ['valid' => false, 'value' => false];
        }
        
        return ['valid' => true, 'value' => $decimal_value];
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


