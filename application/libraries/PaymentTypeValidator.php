<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Librería centralizada para validación de tipos de pago
 * Unifica la lógica de validación entre cliente y servidor
 */
class PaymentTypeValidator {

    // Tipos de pago válidos
    const VALID_PAYMENT_TYPES = [
        'full',           // Cuota completa
        'interest',       // Solo interés
        'capital',        // Pago a capital
        'both',           // Interés y capital
        'total',          // Pago total (una cuota específica)
        'custom',         // Monto personalizado
        'early_total',    // Pago total anticipado
        'total_condonacion' // Pago total anticipado con condonación
    ];

    // Tipos que requieren exactamente una cuota
    const SINGLE_QUOTA_TYPES = [
        'total',
        'early_total',
        'total_condonacion'
    ];

    // Tipos que requieren campo custom_amount
    const CUSTOM_AMOUNT_TYPES = [
        'custom'
    ];

    /**
     * Valida si un tipo de pago es válido
     */
    public static function isValidType($tipo_pago) {
        return in_array($tipo_pago, self::VALID_PAYMENT_TYPES);
    }

    /**
     * Obtiene lista de tipos válidos
     */
    public static function getValidTypes() {
        return self::VALID_PAYMENT_TYPES;
    }

    /**
     * Verifica si el tipo requiere exactamente una cuota
     */
    public static function requiresSingleQuota($tipo_pago) {
        return in_array($tipo_pago, self::SINGLE_QUOTA_TYPES);
    }

    /**
     * Verifica si el tipo requiere campo custom_amount
     */
    public static function requiresCustomAmount($tipo_pago) {
        return in_array($tipo_pago, self::CUSTOM_AMOUNT_TYPES);
    }

    /**
     * Valida configuración completa de pago
     */
    public static function validatePaymentConfig($tipo_pago, $quota_count, $custom_amount = null) {
        $errors = [];

        // Validar tipo de pago
        if (!self::isValidType($tipo_pago)) {
            $errors[] = "Tipo de pago inválido: {$tipo_pago}";
        }

        // Validar cantidad de cuotas
        if (self::requiresSingleQuota($tipo_pago) && $quota_count !== 1) {
            $errors[] = "El tipo '{$tipo_pago}' requiere exactamente una cuota seleccionada";
        }

        // Validar monto personalizado
        if (self::requiresCustomAmount($tipo_pago)) {
            if (empty($custom_amount) || !is_numeric($custom_amount) || $custom_amount <= 0) {
                $errors[] = "El tipo '{$tipo_pago}' requiere un monto personalizado válido";
            }
        }

        return $errors;
    }

    /**
     * Obtiene descripción del tipo de pago
     */
    public static function getTypeDescription($tipo_pago) {
        $descriptions = [
            'full' => 'Cuota completa',
            'interest' => 'Solo interés',
            'capital' => 'Pago a capital',
            'both' => 'Interés y capital',
            'total' => 'Pago total (una cuota específica)',
            'custom' => 'Monto personalizado',
            'early_total' => 'Pago total anticipado',
            'total_condonacion' => 'Pago total anticipado con condonación'
        ];

        return $descriptions[$tipo_pago] ?? 'Tipo desconocido';
    }

    /**
     * Valida consistencia entre cliente y servidor
     */
    public static function validateConsistency($client_data, $server_data) {
        $issues = [];

        // Comparar tipos de pago
        if ($client_data['tipo_pago'] !== $server_data['tipo_pago']) {
            $issues[] = "Inconsistencia en tipo_pago: cliente={$client_data['tipo_pago']}, servidor={$server_data['tipo_pago']}";
        }

        // Comparar montos para tipos específicos
        if (in_array($client_data['tipo_pago'], ['total', 'total_condonacion'])) {
            $client_total = $client_data['total_amount'] ?? 0;
            $server_total = $server_data['total_amount'] ?? 0;

            if (abs($client_total - $server_total) > 0.01) { // Tolerancia decimal
                $issues[] = "Inconsistencia en monto total: cliente={$client_total}, servidor={$server_total}";
            }
        }

        return $issues;
    }
}