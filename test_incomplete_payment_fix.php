<?php
/**
 * Script de prueba para validar la corrección del pago no completo (incomplete)
 * Prueba el escenario reportado por el usuario donde el pago no completo debe:
 * 1. Aplicar el pago a la cuota actual
 * 2. Distribuir el valor no pagado a la siguiente cuota futura
 * 3. Si es la última cuota, generar una nueva cuota con el valor restante
 */

echo "=== PRUEBA DE CORRECCIÓN: PAGO NO COMPLETO ===\n\n";

// Simular datos de prueba basados en el caso reportado
$test_data = [
    'loan_id' => 167,
    'quota_id' => 3111, // Cuota #2 del préstamo 167
    'custom_amount' => 100000, // Monto pagado: $100,000
    'user_id' => 1,
    'payment_description' => 'Prueba de pago no completo'
];

echo "DATOS DE PRUEBA:\n";
echo "- Préstamo ID: {$test_data['loan_id']}\n";
echo "- Cuota ID: {$test_data['quota_id']}\n";
echo "- Monto pagado: $" . number_format($test_data['custom_amount'], 2, '.', ',') . "\n";
echo "- Usuario ID: {$test_data['user_id']}\n\n";

// Simular el comportamiento esperado
echo "COMPORTAMIENTO ESPERADO:\n";
echo "1. Aplicar $100,000 a la cuota actual (ID: 3111)\n";
echo "2. Calcular intereses y capital aplicados\n";
echo "3. Marcar cuota como status=4 (incompleto) con balance=0\n";
echo "4. Distribuir el saldo restante a cuotas futuras\n";
echo "5. Si es la última cuota, generar nueva cuota con mora\n\n";

echo "VALIDACIÓN MANUAL:\n";
echo "Después de ejecutar el pago, verificar en BD:\n";
echo "- Tabla loan_items: cuota 3111 debe tener status=4, balance=0\n";
echo "- Tabla payments: debe existir registro del pago\n";
echo "- Cuotas futuras: deben tener montos aumentados proporcionalmente\n";
echo "- Si es última cuota: debe existir nueva cuota generada\n\n";

echo "SCRIPT DE VALIDACIÓN SQL:\n";
echo "-- Verificar cuota procesada\n";
echo "SELECT id, num_quota, fee_amount, interest_amount, capital_amount, balance, status, interest_paid, capital_paid FROM loan_items WHERE id = {$test_data['quota_id']};\n\n";

echo "-- Verificar pago registrado\n";
echo "SELECT * FROM payments WHERE loan_id = {$test_data['loan_id']} AND custom_payment_type = 'incomplete' ORDER BY payment_date DESC LIMIT 1;\n\n";

echo "-- Verificar cuotas futuras afectadas\n";
echo "SELECT id, num_quota, fee_amount, interest_amount, capital_amount, balance, status FROM loan_items WHERE loan_id = {$test_data['loan_id']} AND num_quota > 2 ORDER BY num_quota;\n\n";

echo "=== FIN DE PRUEBA ===\n";
?>