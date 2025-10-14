<?php
// Script para probar get_quotasPaid con el nuevo fallback
define('BASEPATH', true); // Evitar "No direct script access allowed"

require_once 'application/models/Payments_m.php';

$payments_m = new Payments_m();

// Simular datos inválidos como los que envía el frontend
$test_data = ['0', '0'];
$loan_id = 65;

echo "Probando get_quotasPaid con datos inválidos...\n";
echo "Datos de entrada: " . json_encode($test_data) . "\n";
echo "Loan ID: $loan_id\n\n";

$result = $payments_m->get_quotasPaid($test_data, $loan_id);

echo "Resultado: " . count($result) . " cuotas encontradas\n";

if (!empty($result)) {
    echo "\nDetalles de las cuotas encontradas:\n";
    foreach ($result as $quota) {
        echo "- ID: {$quota->id}, Num: {$quota->num_quota}, Status: {$quota->status}, Pay_date: {$quota->pay_date}\n";
    }
} else {
    echo "No se encontraron cuotas.\n";
}