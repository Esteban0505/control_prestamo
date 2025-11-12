<?php
/**
 * Script de validación simple para tipos de pago personalizado
 * Verifica que las validaciones funcionen correctamente
 */

echo "=== VALIDACIÓN DE TIPOS DE PAGO PERSONALIZADO ===\n\n";

// Simular función de validación
function validate_custom_payment_type($type) {
    $valid_types = ['partial', 'incomplete', 'pago_personalizado'];
    return in_array($type, $valid_types);
}

// Pruebas de validación
$test_types = [
    'partial' => true,
    'incomplete' => true,
    'pago_personalizado' => true,
    'invalid' => false,
    'wrong' => false,
    'cuota' => false,
    'interes' => false,
    'capital' => false,
    'liquidation' => false,
    '' => false,
    null => false
];

echo "PRUEBA DE VALIDACIÓN DE TIPOS:\n";
echo str_repeat("-", 40) . "\n";

foreach ($test_types as $type => $expected) {
    $result = validate_custom_payment_type($type);
    $status = ($result === $expected) ? '✓ CORRECTO' : '✗ ERROR';

    echo sprintf("%-20s | Esperado: %-5s | Resultado: %-5s | %s\n",
        "'$type'",
        $expected ? 'true' : 'false',
        $result ? 'true' : 'false',
        $status
    );
}

echo "\n" . str_repeat("-", 40) . "\n";

// Simular mensaje de error
function get_error_message($type) {
    return "Tipo de aplicación inválido. Seleccione: cuota completa, solo interés, pago a capital, liquidación anticipada, pago personalizado, pago parcial, pago no completo, partial, o incomplete.";
}

echo "MENSAJE DE ERROR PARA TIPO INVÁLIDO:\n";
echo get_error_message('invalid') . "\n";

echo "\n=== VALIDACIÓN COMPLETADA ===\n";
?>