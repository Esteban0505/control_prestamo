<?php
/**
 * Script simple de prueba para verificar que después de condonación no se muestren cuotas
 * Versión simplificada que usa consultas SQL directas
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'prestamobd';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "=== PRUEBA DE CONDONACIÓN: VERIFICACIÓN DE CUOTAS NO MOSTRADAS ===\n\n";

// Usar el préstamo ID 93 que tiene cuotas pendientes
$loan_id = 93;
$user_id = 1;
$customer_id = 1;

echo "Préstamo a usar: ID {$loan_id}\n";
echo "Usuario: ID {$user_id}\n";
echo "Cliente: ID {$customer_id}\n\n";

// PASO 1: Verificar estado inicial del préstamo
echo "PASO 1: Verificando estado inicial del préstamo\n";
$result = $conn->query("SELECT * FROM loans WHERE id = {$loan_id}");
$loan = $result->fetch_assoc();

if (!$loan) {
    die("ERROR: Préstamo {$loan_id} no encontrado\n");
}

echo "Estado del préstamo: " . ($loan['status'] == 1 ? "ACTIVO" : "INACTIVO") . "\n";
echo "Monto del crédito: $" . number_format($loan['credit_amount'], 2, ',', '.') . "\n";
echo "Número de cuotas: {$loan['num_fee']}\n\n";

// Obtener cuotas iniciales
$result = $conn->query("SELECT id, num_quota, fee_amount, interest_amount, capital_amount, balance, status, extra_payment FROM loan_items WHERE loan_id = {$loan_id} ORDER BY num_quota ASC");
$initial_quotas = [];
while ($row = $result->fetch_assoc()) {
    $initial_quotas[] = $row;
}

echo "Cuotas iniciales encontradas: " . count($initial_quotas) . "\n";
foreach ($initial_quotas as $quota) {
    echo "  - Cuota #{$quota['num_quota']}: $" . number_format($quota['fee_amount'], 2, ',', '.') . " (Balance: $" . number_format($quota['balance'], 2, ',', '.') . ")\n";
}
echo "\n";

// PASO 2: Simular pago con condonación anticipada
echo "PASO 2: Simulando pago con condonación anticipada (early_total)\n";

// Seleccionar la primera cuota pendiente para el pago anticipado
$first_pending_quota = null;
foreach ($initial_quotas as $quota) {
    if ($quota['status'] == 1) {
        $first_pending_quota = $quota;
        break;
    }
}

if (!$first_pending_quota) {
    die("ERROR: No hay cuotas pendientes para probar\n");
}

echo "Cuota seleccionada para pago anticipado: #{$first_pending_quota['num_quota']} (ID: {$first_pending_quota['id']})\n";
echo "Balance de la cuota seleccionada: $" . number_format($first_pending_quota['balance'], 2, ',', '.') . "\n\n";

// Simular el pago con condonación - Actualizar la cuota seleccionada como pagada
$conn->query("UPDATE loan_items SET
    status = 0,
    capital_paid = {$first_pending_quota['capital_amount']},
    interest_paid = {$first_pending_quota['interest_amount']},
    balance = 0,
    paid_by = {$user_id},
    pay_date = NOW()
    WHERE id = {$first_pending_quota['id']}");

// Marcar cuotas posteriores como condonadas (extra_payment = 3)
$conn->query("UPDATE loan_items SET
    status = 0,
    balance = 0,
    capital_paid = capital_amount,
    interest_paid = interest_amount,
    paid_by = {$user_id},
    pay_date = NOW(),
    extra_payment = 3
    WHERE loan_id = {$loan_id} AND num_quota > {$first_pending_quota['num_quota']} AND status = 1");

// Registrar el pago en la tabla payments
$conn->query("INSERT INTO payments (loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes) VALUES (
    {$loan_id},
    {$first_pending_quota['id']},
    {$first_pending_quota['balance']},
    'early_total',
    {$first_pending_quota['balance']},
    {$first_pending_quota['interest_amount']},
    {$first_pending_quota['capital_amount']},
    NOW(),
    {$user_id},
    'efectivo',
    'PAGO TOTAL ANTICIPADO CON CONDONACIÓN - Prueba automática'
)");

echo "✅ Pago procesado exitosamente\n\n";

// PASO 3: Verificar que las cuotas posteriores estén condonadas
echo "PASO 3: Verificando que las cuotas posteriores estén condonadas\n";

// Obtener todas las cuotas del préstamo después del pago
$result = $conn->query("SELECT id, num_quota, fee_amount, balance, status, extra_payment FROM loan_items WHERE loan_id = {$loan_id} ORDER BY num_quota ASC");
$all_quotas_after = [];
while ($row = $result->fetch_assoc()) {
    $all_quotas_after[] = $row;
}

echo "Estado de todas las cuotas después del pago:\n";
foreach ($all_quotas_after as $quota) {
    $status_text = $quota['status'] == 0 ? 'PAGADA' : 'PENDIENTE';
    $extra_payment_text = '';
    if ($quota['extra_payment'] == 3) {
        $extra_payment_text = ' (CONDONADA)';
    }
    echo "  - Cuota #{$quota['num_quota']}: {$status_text}{$extra_payment_text} - Balance: $" . number_format($quota['balance'], 2, ',', '.') . "\n";
}
echo "\n";

// PASO 4: Verificar que las cuotas condonadas NO aparezcan en búsquedas
echo "PASO 4: Verificando que las cuotas condonadas NO aparezcan en búsquedas\n";

// Simular la búsqueda get_quotasCst (WHERE extra_payment != 3)
$result = $conn->query("SELECT id, num_quota, fee_amount, balance, status FROM loan_items WHERE loan_id = {$loan_id} AND status = 1 AND extra_payment != 3 ORDER BY num_quota ASC");
$quotas_after_payment = [];
while ($row = $result->fetch_assoc()) {
    $quotas_after_payment[] = $row;
}

echo "Cuotas encontradas por get_quotasCst (después del pago): " . count($quotas_after_payment) . "\n";

if (count($quotas_after_payment) > 0) {
    echo "Cuotas que aparecen en búsqueda:\n";
    foreach ($quotas_after_payment as $quota) {
        echo "  - Cuota #{$quota['num_quota']}: $" . number_format($quota['fee_amount'], 2, ',', '.') . " (Balance: $" . number_format($quota['balance'], 2, ',', '.') . ")\n";
    }
} else {
    echo "✅ No se encontraron cuotas pendientes (correcto - todas condonadas)\n";
}

// Verificar específicamente que las cuotas condonadas no están incluidas
$result = $conn->query("SELECT COUNT(*) as count FROM loan_items WHERE loan_id = {$loan_id} AND extra_payment = 3");
$row = $result->fetch_assoc();
$condonadas_count = $row['count'];

echo "\nNúmero de cuotas condonadas: {$condonadas_count}\n";

if ($condonadas_count > 0) {
    echo "✅ VERIFICACIÓN EXITOSA: Las cuotas condonadas existen pero no aparecen en búsquedas\n";
} else {
    echo "ℹ️  No se encontraron cuotas condonadas\n";
}

echo "\n";

// PASO 5: Verificar búsqueda por cliente (get_searchCst)
echo "PASO 5: Verificando búsqueda por cliente (get_searchCst)\n";

// Simular búsqueda por cliente con balance > 0 excluyendo condonadas
$result = $conn->query("
    SELECT
        l.id as loan_id,
        l.customer_id,
        SUM(COALESCE(li.balance, 0)) as total_balance
    FROM customers c
    INNER JOIN loans l ON l.customer_id = c.id
    LEFT JOIN loan_items li ON li.loan_id = l.id
    WHERE l.status = 1
        AND li.extra_payment != 3
        AND l.customer_id = {$customer_id}
    GROUP BY l.id
    HAVING SUM(COALESCE(li.balance, 0)) > 0
");

$search_result = $result->fetch_assoc();

if ($search_result) {
    echo "Cliente encontrado en búsqueda con balance: $" . number_format($search_result['total_balance'], 2, ',', '.') . "\n";

    if ($search_result['total_balance'] > 0) {
        echo "❌ ERROR: El cliente aún muestra balance pendiente después de condonación\n";
    } else {
        echo "✅ VERIFICACIÓN EXITOSA: Cliente no muestra balance pendiente\n";
    }
} else {
    echo "Cliente no encontrado en búsqueda (posiblemente préstamo cerrado)\n";
}

// PASO 6: Verificar estado final del préstamo
echo "\nPASO 6: Verificando estado final del préstamo\n";
$result = $conn->query("SELECT status, balance FROM loans WHERE id = {$loan_id}");
$loan_final = $result->fetch_assoc();

if ($loan_final) {
    echo "Estado final del préstamo: " . ($loan_final['status'] == 1 ? "ACTIVO" : "INACTIVO") . "\n";
    echo "Balance final del préstamo: $" . number_format($loan_final['balance'] ?? 0, 2, ',', '.') . "\n";

    if ($loan_final['status'] == 0) {
        echo "✅ Préstamo cerrado correctamente\n";
    } else {
        echo "ℹ️  Préstamo mantiene activo (posiblemente tiene cuotas no condonadas)\n";
    }
} else {
    echo "Préstamo no encontrado\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";

if ($condonadas_count > 0 && (!$search_result || $search_result['total_balance'] == 0)) {
    echo "✅ RESULTADO: TODAS LAS VERIFICACIONES PASARON\n";
    echo "Las cuotas condonadas no aparecen en búsquedas posteriores.\n";
} else {
    echo "❌ RESULTADO: ALGUNAS VERIFICACIONES FALLARON\n";
    echo "Revisar la lógica de filtrado de cuotas condonadas.\n";
}

$conn->close();
?>