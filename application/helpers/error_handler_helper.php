<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper para manejo centralizado de errores en español
 * Proporciona mensajes específicos según el tipo de error
 */

/**
 * Tipos de errores comunes en el sistema
 */
define('ERROR_TYPES', [
    'VALIDATION' => 'validacion',
    'DATABASE' => 'base_datos',
    'PAYMENT' => 'pago',
    'CUSTOMER' => 'cliente',
    'LOAN' => 'prestamo',
    'QUOTA' => 'cuota',
    'AMORTIZATION' => 'amortizacion',
    'PERMISSION' => 'permiso',
    'SYSTEM' => 'sistema',
    'NETWORK' => 'red'
]);

/**
 * Códigos de error específicos
 */
define('ERROR_CODES', [
    // Errores de validación
    'VALIDATION_REQUIRED' => 'VALIDATION_001',
    'VALIDATION_FORMAT' => 'VALIDATION_002',
    'VALIDATION_RANGE' => 'VALIDATION_003',
    'VALIDATION_TYPE' => 'VALIDATION_004',

    // Errores de base de datos
    'DB_CONNECTION' => 'DATABASE_001',
    'DB_QUERY' => 'DATABASE_002',
    'DB_NOT_FOUND' => 'DATABASE_003',
    'DB_DUPLICATE' => 'DATABASE_004',

    // Errores de pago
    'PAYMENT_INSUFFICIENT' => 'PAYMENT_001',
    'PAYMENT_INVALID_TYPE' => 'PAYMENT_002',
    'PAYMENT_QUOTA_NOT_FOUND' => 'PAYMENT_003',
    'PAYMENT_ALREADY_PAID' => 'PAYMENT_004',
    'PAYMENT_AMOUNT_TOO_HIGH' => 'PAYMENT_005',
    'PAYMENT_AMOUNT_TOO_LOW' => 'PAYMENT_006',
    'PAYMENT_NO_QUOTAS_SELECTED' => 'PAYMENT_007',
    'PAYMENT_TOO_MANY_QUOTAS' => 'PAYMENT_008',
    'PAYMENT_TOTAL_MULTIPLE_QUOTAS' => 'PAYMENT_009',
    'PAYMENT_CUSTOM_AMOUNT_MISSING' => 'PAYMENT_010',
    'PAYMENT_CUSTOM_AMOUNT_INVALID' => 'PAYMENT_011',
    'PAYMENT_CUSTOM_AMOUNT_TOO_HIGH' => 'PAYMENT_012',
    'PAYMENT_CUSTOM_AMOUNT_TOO_LOW' => 'PAYMENT_013',
    'PAYMENT_CUSTOM_TYPE_MISSING' => 'PAYMENT_014',
    'PAYMENT_CUSTOM_TYPE_INVALID' => 'PAYMENT_015',
    'PAYMENT_LIQUIDATION_INSUFFICIENT' => 'PAYMENT_016',
    'PAYMENT_MIXED_ODD_INTEREST' => 'PAYMENT_017',
    'PAYMENT_MIXED_EVEN_CAPITAL' => 'PAYMENT_018',
    'PAYMENT_MIXED_ODD_INTEREST_PENDING' => 'PAYMENT_019',
    'PAYMENT_MIXED_EVEN_CAPITAL_PENDING' => 'PAYMENT_020',

    // Errores de cliente
    'CUSTOMER_NOT_FOUND' => 'CUSTOMER_001',
    'CUSTOMER_INACTIVE' => 'CUSTOMER_002',
    'CUSTOMER_LIMIT_EXCEEDED' => 'CUSTOMER_003',

    // Errores de préstamo
    'LOAN_NOT_FOUND' => 'LOAN_001',
    'LOAN_INACTIVE' => 'LOAN_002',
    'LOAN_INVALID_STATUS' => 'LOAN_003',

    // Errores de cuota
    'QUOTA_NOT_FOUND' => 'QUOTA_001',
    'QUOTA_ALREADY_PAID' => 'QUOTA_002',
    'QUOTA_INVALID_STATUS' => 'QUOTA_003',

    // Errores de amortización
    'AMORTIZATION_INVALID_TYPE' => 'AMORTIZATION_001',
    'AMORTIZATION_CALCULATION_ERROR' => 'AMORTIZATION_002',

    // Errores de permisos
    'PERMISSION_DENIED' => 'PERMISSION_001',
    'PERMISSION_INSUFFICIENT' => 'PERMISSION_002',

    // Errores del sistema
    'SYSTEM_ERROR' => 'SYSTEM_001',
    'SYSTEM_TIMEOUT' => 'SYSTEM_002',
    'SYSTEM_MAINTENANCE' => 'SYSTEM_003',

    // Errores de red
    'NETWORK_TIMEOUT' => 'NETWORK_001',
    'NETWORK_CONNECTION' => 'NETWORK_002'
]);

/**
 * Mensajes de error en español
 */
define('ERROR_MESSAGES', [
    // Errores de validación
    'VALIDATION_001' => 'El campo %s es obligatorio.',
    'VALIDATION_002' => 'El formato del campo %s no es válido.',
    'VALIDATION_003' => 'El valor del campo %s debe estar entre %s y %s.',
    'VALIDATION_004' => 'El tipo de dato del campo %s no es válido.',

    // Errores de base de datos
    'DATABASE_001' => 'Error de conexión con la base de datos.',
    'DATABASE_002' => 'Error al ejecutar la consulta en la base de datos.',
    'DATABASE_003' => 'El registro solicitado no fue encontrado.',
    'DATABASE_004' => 'Ya existe un registro con estos datos.',

    // Errores de pago
    'PAYMENT_001' => 'El monto del pago es insuficiente. Verifique que el monto cubra al menos los intereses pendientes.',
    'PAYMENT_002' => 'El tipo de pago seleccionado no es válido. Los tipos válidos son: completo, solo interés, solo capital, ambos, total o personalizado.',
    'PAYMENT_003' => 'La cuota especificada no existe. Verifique el ID de la cuota e intente nuevamente.',
    'PAYMENT_004' => 'Esta cuota ya ha sido pagada completamente. Seleccione una cuota pendiente.',
    'PAYMENT_005' => 'El monto del pago excede el límite permitido de $10.000.000. Reduzca el monto.',
    'PAYMENT_006' => 'El monto del pago es menor al mínimo requerido de $0,01. Ingrese un monto válido.',
    'PAYMENT_007' => 'Debe seleccionar al menos una cuota para procesar el pago.',
    'PAYMENT_008' => 'No se pueden procesar más de 50 cuotas en un solo pago. Reduzca la selección.',
    'PAYMENT_009' => 'Para pago total, debe seleccionar exactamente una cuota.',
    'PAYMENT_010' => 'Para pago personalizado, debe especificar un monto. Ingrese el monto deseado.',
    'PAYMENT_011' => 'El monto personalizado debe ser un número válido mayor a 0. Ejemplo: 1500.50',
    'PAYMENT_012' => 'El monto personalizado no puede exceder $10.000.000. Reduzca el monto.',
    'PAYMENT_013' => 'El monto personalizado debe ser al menos $0,01. Ingrese un monto mayor.',
    'PAYMENT_014' => 'Para pago personalizado, debe seleccionar dónde aplicar el pago.',
    'PAYMENT_015' => 'Tipo de aplicación inválido. Seleccione: cuota completa, solo interés, pago a capital, liquidación anticipada, pago personalizado, pago parcial, pago no completo, partial, o incomplete.',
    'PAYMENT_016' => 'Para liquidación anticipada, el monto debe ser al menos %s (saldo pendiente). Aumente el monto o seleccione otro tipo de pago.',
    'PAYMENT_017' => 'En amortización mixta, las cuotas impares deben pagar solo capital, no interés. Cambie el tipo de aplicación.',
    'PAYMENT_018' => 'En amortización mixta, las cuotas pares deben pagar solo interés, no capital. Cambie el tipo de aplicación.',
    'PAYMENT_019' => 'En amortización mixta, las cuotas impares deben tener el interés pagado antes de aplicar capital. Pague primero los intereses.',
    'PAYMENT_020' => 'En amortización mixta, las cuotas pares deben tener el capital pagado antes de aplicar interés. Pague primero el capital.',

    // Errores de cliente
    'CUSTOMER_001' => 'El cliente especificado no fue encontrado.',
    'CUSTOMER_002' => 'El cliente está inactivo o no puede realizar operaciones.',
    'CUSTOMER_003' => 'El monto solicitado excede el límite de crédito del cliente.',

    // Errores de préstamo
    'LOAN_001' => 'El préstamo especificado no fue encontrado.',
    'LOAN_002' => 'El préstamo está inactivo.',
    'LOAN_003' => 'El estado del préstamo no permite esta operación.',

    // Errores de cuota
    'QUOTA_001' => 'La cuota especificada no fue encontrada.',
    'QUOTA_002' => 'Esta cuota ya ha sido pagada completamente.',
    'QUOTA_003' => 'El estado de la cuota no permite esta operación.',

    // Errores de amortización
    'AMORTIZATION_001' => 'El tipo de amortización seleccionado no es válido.',
    'AMORTIZATION_002' => 'Error en el cálculo de la tabla de amortización.',

    // Errores de permisos
    'PERMISSION_001' => 'No tiene permisos para realizar esta operación.',
    'PERMISSION_002' => 'Sus permisos son insuficientes para esta acción.',

    // Errores del sistema
    'SYSTEM_001' => 'Error interno del sistema. Por favor, contacte al administrador.',
    'SYSTEM_002' => 'La operación tardó demasiado tiempo. Intente nuevamente.',
    'SYSTEM_003' => 'El sistema está en mantenimiento. Intente más tarde.',

    // Errores de red
    'NETWORK_001' => 'La conexión tardó demasiado tiempo. Verifique su conexión a internet.',
    'NETWORK_002' => 'Error de conexión. Verifique su conexión a internet.'
]);

/**
 * Clase principal para manejo de errores
 */
class ErrorHandler {

    /**
     * Genera un mensaje de error basado en el código
     *
     * @param string $error_code Código del error
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de error formateado
     */
    public static function get_error_message($error_code, $params = []) {
        if (!isset(ERROR_MESSAGES[$error_code])) {
            return 'Error desconocido: ' . $error_code;
        }

        $message = ERROR_MESSAGES[$error_code];

        // Reemplazar parámetros en el mensaje
        if (!empty($params)) {
            $message = vsprintf($message, $params);
        }

        return $message;
    }

    /**
     * Genera una respuesta de error para AJAX
     *
     * @param string $error_code Código del error
     * @param array $params Parámetros para el mensaje
     * @param array $extra_data Datos adicionales
     * @return array Respuesta de error
     */
    public static function ajax_error_response($error_code, $params = [], $extra_data = []) {
        return array_merge([
            'success' => false,
            'error_code' => $error_code,
            'error_message' => self::get_error_message($error_code, $params)
        ], $extra_data);
    }

    /**
     * Genera una respuesta de éxito para AJAX
     *
     * @param array $data Datos de respuesta
     * @param string $message Mensaje opcional
     * @return array Respuesta de éxito
     */
    public static function ajax_success_response($data = [], $message = '') {
        $response = ['success' => true];
        if (!empty($message)) {
            $response['message'] = $message;
        }
        return array_merge($response, $data);
    }

    /**
     * Registra un error en el log y lanza una excepción
     *
     * @param string $error_code Código del error
     * @param array $params Parámetros para el mensaje
     * @param string $log_level Nivel del log (error, debug, info)
     * @throws Exception
     */
    public static function throw_error($error_code, $params = [], $log_level = 'error') {
        $message = self::get_error_message($error_code, $params);

        // Registrar en el log
        log_message($log_level, 'Error [' . $error_code . ']: ' . $message);

        // Lanzar excepción
        throw new Exception($message);
    }

    /**
     * Valida un pago y lanza errores específicos
     *
     * @param array $payment_data Datos del pago
     * @throws Exception
     */
    public static function validate_payment($payment_data) {
        // Validar tipo de pago - Lista centralizada de tipos válidos
        $valid_types = ['full', 'interest', 'capital', 'both', 'total', 'total_condonacion', 'early_total', 'custom', 'force_complete', 'custom_sequential', 'pago_personalizado'];
        if (empty($payment_data['tipo_pago']) || !in_array($payment_data['tipo_pago'], $valid_types)) {
            log_message('error', 'TIPO_PAGO_INVALIDO: Recibido "' . ($payment_data['tipo_pago'] ?? 'VACIO') . '", tipos válidos: ' . json_encode($valid_types));
            self::throw_error('PAYMENT_002');
        }

        // Validar cuotas seleccionadas
        if (empty($payment_data['quota_ids']) || !is_array($payment_data['quota_ids'])) {
            self::throw_error('PAYMENT_007');
        }

        if (count($payment_data['quota_ids']) > 50) {
            self::throw_error('PAYMENT_008');
        }

        // Validaciones específicas por tipo de pago
        $tipo_pago = $payment_data['tipo_pago'];

        if ($tipo_pago === 'total' && count($payment_data['quota_ids']) !== 1) {
            self::throw_error('PAYMENT_009');
        }

        if ($tipo_pago === 'custom') {
            if (empty($payment_data['custom_amount'])) {
                self::throw_error('PAYMENT_010');
            }

            if (!is_numeric($payment_data['custom_amount']) || $payment_data['custom_amount'] <= 0) {
                self::throw_error('PAYMENT_011');
            }

            if ($payment_data['custom_amount'] > 10000000) {
                self::throw_error('PAYMENT_012');
            }

            if ($payment_data['custom_amount'] < 0.01) {
                self::throw_error('PAYMENT_013');
            }

            if (empty($payment_data['custom_payment_type'])) {
                self::throw_error('PAYMENT_014');
            }

            $valid_custom_types = ['cuota', 'interes', 'capital', 'liquidation', 'pago_personalizado', 'partial', 'incomplete'];
            log_message('debug', 'PAYMENT_VALIDATION: Validando custom_payment_type - recibido: "' . $payment_data['custom_payment_type'] . '", válidos: ' . json_encode($valid_custom_types));
            if (!in_array($payment_data['custom_payment_type'], $valid_custom_types)) {
                log_message('error', 'PAYMENT_VALIDATION: custom_payment_type inválido - recibido: "' . $payment_data['custom_payment_type'] . '", válidos: ' . json_encode($valid_custom_types));
                self::throw_error('PAYMENT_015');
            }

            // Validación adicional para liquidación anticipada
            if ($payment_data['custom_payment_type'] === 'liquidation' && isset($payment_data['selected_quota_balance'])) {
                if ($payment_data['custom_amount'] < $payment_data['selected_quota_balance']) {
                    self::throw_error('PAYMENT_016', [$payment_data['selected_quota_balance']]);
                }
            }

            // Validaciones para amortización mixta
            if (isset($payment_data['amortization_type']) && $payment_data['amortization_type'] === 'mixta') {
                self::validate_mixed_amortization_payment($payment_data);
            }
        }
    }

    /**
     * Valida pagos específicos para amortización mixta
     *
     * @param array $payment_data Datos del pago
     * @throws Exception
     */
    public static function validate_mixed_amortization_payment($payment_data) {
        if (!isset($payment_data['quota_numbers']) || !is_array($payment_data['quota_numbers'])) {
            return; // No hay números de cuota para validar
        }

        $custom_payment_type = $payment_data['custom_payment_type'];

        foreach ($payment_data['quota_numbers'] as $quota_num) {
            $is_odd = $quota_num % 2 === 1; // true = impar (solo capital), false = par (solo interés)

            if ($custom_payment_type === 'interes' && $is_odd) {
                self::throw_error('PAYMENT_017');
            }

            if ($custom_payment_type === 'capital' && !$is_odd) {
                self::throw_error('PAYMENT_018');
            }

            if ($custom_payment_type === 'cuota') {
                // Para pago completo, validar que se respete el patrón
                if (isset($payment_data['interest_pending_' . $quota_num]) &&
                    $payment_data['interest_pending_' . $quota_num] > 0 && $is_odd) {
                    self::throw_error('PAYMENT_019');
                }

                if (isset($payment_data['capital_pending_' . $quota_num]) &&
                    $payment_data['capital_pending_' . $quota_num] > 0 && !$is_odd) {
                    self::throw_error('PAYMENT_020');
                }
            }
        }
    }

    /**
     * Valida datos de amortización
     *
     * @param array $amortization_data Datos de amortización
     * @throws Exception
     */
    public static function validate_amortization($amortization_data) {
        $valid_types = ['francesa', 'mixta', 'aleman'];
        if (empty($amortization_data['amortization_type']) || !in_array($amortization_data['amortization_type'], $valid_types)) {
            self::throw_error('AMORTIZATION_001');
        }

        if (!isset($amortization_data['credit_amount']) || !is_numeric($amortization_data['credit_amount']) || $amortization_data['credit_amount'] <= 0) {
            self::throw_error('VALIDATION_001', ['monto del préstamo']);
        }

        if (!isset($amortization_data['interest_amount']) || !is_numeric($amortization_data['interest_amount']) || $amortization_data['interest_amount'] < 0) {
            self::throw_error('VALIDATION_001', ['tasa de interés']);
        }

        if (!isset($amortization_data['num_months']) || !is_numeric($amortization_data['num_months']) || $amortization_data['num_months'] <= 0 || $amortization_data['num_months'] > 120) {
            self::throw_error('VALIDATION_003', ['plazo en meses', '1', '120']);
        }
    }
}

// Funciones helper para uso directo
if (!function_exists('get_error_message')) {
    /**
     * Función helper para obtener mensaje de error
     */
    function get_error_message($error_code, $params = []) {
        return ErrorHandler::get_error_message($error_code, $params);
    }
}

if (!function_exists('ajax_error')) {
    /**
     * Función helper para respuesta AJAX de error
     */
    function ajax_error($error_code, $params = [], $extra_data = []) {
        return ErrorHandler::ajax_error_response($error_code, $params, $extra_data);
    }
}

if (!function_exists('ajax_success')) {
    /**
     * Función helper para respuesta AJAX de éxito
     */
    function ajax_success($data = [], $message = '') {
        return ErrorHandler::ajax_success_response($data, $message);
    }
}

if (!function_exists('throw_error')) {
    /**
     * Función helper para lanzar error
     */
    function throw_error($error_code, $params = [], $log_level = 'error') {
        ErrorHandler::throw_error($error_code, $params, $log_level);
    }
}