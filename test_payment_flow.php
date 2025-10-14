<?php
// Script para probar el flujo completo de pago y verificación en reports

require_once 'application/models/Payments_m.php';

$payments_m = new Payments_m();

// Simular datos de pago
$payment_data = [
    'loan_id' => 39, // Usar un loan existente
    'loan_item_id' => 41, // Usar una cuota existente
    'amount' => 500000,
    'payment_type' => 'full',
    'payment_user_id' => 1, // Usuario admin
    'method' => 'efectivo',
    'notes' => 'Prueba de integración'
];

// Ejecutar pago
$result = $payments_m->process_payment($payment_data);

if ($result['success']) {
    echo "✅ Pago registrado exitosamente\n";
    echo "ID del pago: " . $result['data']['payment_id'] . "\n";

    // Verificar que se registró en collector_commissions
    $conn = new mysqli("localhost", "root", "", "prestamobd");
    $sql = "SELECT * FROM collector_commissions ORDER BY id DESC LIMIT 1";
    $commission = $conn->query($sql)->fetch_assoc();

    if ($commission) {
        echo "✅ Comisión registrada:\n";
        echo "- Cliente: " . $commission['client_name'] . "\n";
        echo "- Cédula: " . $commission['client_cedula'] . "\n";
        echo "- Monto: $" . number_format($commission['amount'], 2, ',', '.') . "\n";
        echo "- Comisión (40%): $" . number_format($commission['commission'], 2, ',', '.') . "\n";
    } else {
        echo "❌ No se encontró registro de comisión\n";
    }

    $conn->close();

} else {
    echo "❌ Error en el pago: " . $result['error'] . "\n";
}
?>