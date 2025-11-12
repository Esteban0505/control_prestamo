<?php
// Script de validación para debuggear la función is_last_installment
define('BASEPATH', true);

// Simular configuración de base de datos
$db_config = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'prestamobd',
    'dbdriver' => 'mysqli'
];

// Conectar a la base de datos
$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== DEBUG: Función is_last_installment ===\n\n";

// Obtener un préstamo de ejemplo
$result = $conn->query("SELECT id FROM loans WHERE status = 1 LIMIT 1");
if ($result->num_rows == 0) {
    die("No hay préstamos activos para probar\n");
}

$loan = $result->fetch_assoc();
$loan_id = $loan['id'];

echo "Préstamo ID: $loan_id\n\n";

// Obtener todas las cuotas del préstamo
$quotas_result = $conn->query("SELECT id, num_quota, balance, status FROM loan_items WHERE loan_id = $loan_id ORDER BY num_quota ASC");

echo "Cuotas del préstamo:\n";
echo "ID\tNum_Quota\tBalance\tStatus\n";
echo "---\t---------\t-------\t------\n";

$quotas = [];
while ($quota = $quotas_result->fetch_assoc()) {
    echo "{$quota['id']}\t{$quota['num_quota']}\t\t{$quota['balance']}\t{$quota['status']}\n";
    $quotas[] = $quota;
}

echo "\n=== PRUEBA DE FUNCIÓN is_last_installment ===\n\n";

// Simular la lógica de la función
foreach ($quotas as $quota) {
    $installment_id = $quota['id'];
    $current_num_quota = $quota['num_quota'];

    // Obtener el num_quota de la cuota actual
    $current_result = $conn->query("SELECT num_quota FROM loan_items WHERE id = $installment_id");
    $current_quota = $current_result->fetch_assoc();

    if (!$current_quota) {
        echo "❌ Cuota ID $installment_id no encontrada\n";
        continue;
    }

    // Obtener la cuota con el num_quota más alto
    $last_result = $conn->query("SELECT num_quota FROM loan_items WHERE loan_id = $loan_id ORDER BY num_quota DESC LIMIT 1");
    $last_quota = $last_result->fetch_assoc();

    $is_last = ($last_quota && $last_quota['num_quota'] == $current_quota['num_quota']);

    echo "Cuota ID {$quota['id']} (num_quota: {$quota['num_quota']}): ";
    echo $is_last ? "✅ ES LA ÚLTIMA" : "❌ NO es la última";
    echo " (última num_quota: {$last_quota['num_quota']})\n";
}

echo "\n=== VERIFICACIÓN MANUAL ===\n";
echo "Última cuota por ID: ";
$last_by_id = $conn->query("SELECT id, num_quota FROM loan_items WHERE loan_id = $loan_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
echo "ID {$last_by_id['id']} (num_quota: {$last_by_id['num_quota']})\n";

echo "Última cuota por num_quota: ";
$last_by_num = $conn->query("SELECT id, num_quota FROM loan_items WHERE loan_id = $loan_id ORDER BY num_quota DESC LIMIT 1")->fetch_assoc();
echo "ID {$last_by_num['id']} (num_quota: {$last_by_num['num_quota']})\n";

if ($last_by_id['id'] != $last_by_num['id']) {
    echo "\n⚠️  ¡ALERTA! La última cuota por ID es diferente a la última por num_quota\n";
    echo "Esto explica por qué la función fallaba anteriormente\n";
}

$conn->close();
?>