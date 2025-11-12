<?php
/**
 * Script de prueba exacto para diagnosticar pagos personalizados
 * Simula selección de cuota #3 y pago personalizado de $5,000
 * Incluye logs detallados para identificar el problema exacto
 */

// Simular entorno básico para acceder a BD
define('BASEPATH', true);
define('APPPATH', 'application/');
define('ENVIRONMENT', 'development');

// Simular sesión
$_SESSION['loggedin'] = TRUE;
$_SESSION['user_id'] = 1;

// Conectar directamente a la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'prestamobd';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conexión a BD exitosa\n\n";

echo "=== DIAGNÓSTICO DEFINITIVO DE PAGOS PERSONALIZADOS ===\n";
echo "Script de prueba: Selección cuota #3 + Pago personalizado $5,000\n\n";

// Buscar un préstamo con al menos 3 cuotas para la prueba
echo "=== BUSCANDO PRÉSTAMO PARA PRUEBA ===\n";

$query = "
    SELECT l.id as loan_id, l.customer_id, l.credit_amount, l.fee_amount,
           COUNT(li.id) as total_quotas,
           SUM(CASE WHEN li.status = 1 THEN 1 ELSE 0 END) as pending_quotas
    FROM loans l
    JOIN loan_items li ON l.id = li.loan_id
    WHERE l.status = 1
    GROUP BY l.id, l.customer_id, l.credit_amount, l.fee_amount
    HAVING COUNT(li.id) >= 3 AND SUM(CASE WHEN li.status = 1 THEN 1 ELSE 0 END) >= 1
    ORDER BY l.id ASC
    LIMIT 1
";

$result = $conn->query($query);

if ($result->num_rows == 0) {
    echo "ERROR: No se encontró ningún préstamo válido para la prueba\n";
    exit;
}

$loan = $result->fetch_assoc();
$loan_id = $loan['loan_id'];
$customer_id = $loan['customer_id'];

echo "Préstamo encontrado: ID #$loan_id\n";
echo "Cliente ID: $customer_id\n";
echo "Monto crédito: $" . number_format($loan['credit_amount'], 2) . "\n";
echo "Cuota mensual: $" . number_format($loan['fee_amount'], 2) . "\n";
echo "Total cuotas: {$loan['total_quotas']}\n";
echo "Cuotas pendientes: {$loan['pending_quotas']}\n\n";

// Obtener cuotas del préstamo
echo "=== OBTENIENDO CUOTAS DEL PRÉSTAMO ===\n";

$query_quotas = "
    SELECT id, loan_id, num_quota, fee_amount, interest_amount, capital_amount,
           balance, status, interest_paid, capital_paid, date
    FROM loan_items
    WHERE loan_id = $loan_id
    ORDER BY num_quota ASC
";

$result_quotas = $conn->query($query_quotas);
$quotas = [];

while ($row = $result_quotas->fetch_assoc()) {
    $quotas[] = $row;
}

foreach ($quotas as $quota) {
    $status_text = $quota['status'] == 0 ? 'Pagada' : ($quota['status'] == 1 ? 'Pendiente' : 'Parcial');
    echo "Cuota #{$quota['num_quota']} - ID: {$quota['id']} - Estado: {$status_text}\n";
    echo "  Monto: $" . number_format($quota['fee_amount'], 2) . "\n";
    echo "  Interés: $" . number_format($quota['interest_amount'], 2) . " (pagado: $" . number_format($quota['interest_paid'], 2) . ")\n";
    echo "  Capital: $" . number_format($quota['capital_amount'], 2) . " (pagado: $" . number_format($quota['capital_paid'], 2) . ")\n";
    echo "  Balance: $" . number_format($quota['balance'], 2) . "\n";
    echo "  Fecha: {$quota['date']}\n\n";
}

// Encontrar cuota #3
$cuota_3 = null;
foreach ($quotas as $quota) {
    if ($quota['num_quota'] == 3) {
        $cuota_3 = $quota;
        break;
    }
}

if (!$cuota_3) {
    echo "ERROR: No se encontró la cuota #3 en el préstamo #$loan_id\n";
    exit;
}

echo "=== CUOTA #3 SELECCIONADA PARA PRUEBA ===\n";
echo "ID de cuota: {$cuota_3['id']}\n";
echo "Estado actual: " . ($cuota_3['status'] == 0 ? 'Pagada' : ($cuota_3['status'] == 1 ? 'Pendiente' : 'Parcial')) . "\n";
echo "Monto total: $" . number_format($cuota_3['fee_amount'], 2) . "\n";
echo "Interés pendiente: $" . number_format($cuota_3['interest_amount'] - $cuota_3['interest_paid'], 2) . "\n";
echo "Capital pendiente: $" . number_format($cuota_3['capital_amount'] - $cuota_3['capital_paid'], 2) . "\n";
echo "Balance pendiente: $" . number_format($cuota_3['balance'], 2) . "\n\n";

// Simular pago personalizado de $5,000
$custom_amount = 5000.00;
$selected_quota_ids = [$cuota_3['id']];
$user_id = 1;

echo "=== SIMULACIÓN DE PAGO PERSONALIZADO ===\n";
echo "Monto del pago: $" . number_format($custom_amount, 2) . "\n";
echo "Cuota seleccionada: #3 (ID: {$cuota_3['id']})\n";
echo "Tipo de pago: custom (prioridad interés-capital)\n";
echo "Usuario ID: $user_id\n\n";

// Simular la lógica exacta del método process_custom_payment_partial
echo "=== EJECUTANDO LÓGICA DE process_custom_payment_partial ===\n";

$remaining_amount = $custom_amount;
$payment_distribution = [];
$is_partial = false;
$remaining_balance_distributed = 0;
$additional_quota_generated = false;

// Verificar si es la última cuota
$is_last_installment = ($cuota_3['num_quota'] == count($quotas));
echo "Es la última cuota: " . ($is_last_installment ? 'SÍ' : 'NO') . "\n\n";

// Procesar la cuota seleccionada
echo "Procesando cuota #{$cuota_3['num_quota']} (ID: {$cuota_3['id']})\n";

$interest_pending = max(0, $cuota_3['interest_amount'] - $cuota_3['interest_paid']);
$capital_pending = max(0, $cuota_3['capital_amount'] - $cuota_3['capital_paid']);
$total_pending = $interest_pending + $capital_pending;

echo "Cálculos de montos pendientes:\n";
echo "  Interés pendiente: $" . number_format($interest_pending, 2) . "\n";
echo "  Capital pendiente: $" . number_format($capital_pending, 2) . "\n";
echo "  Total pendiente: $" . number_format($total_pending, 2) . "\n";
echo "  Monto del pago: $" . number_format($remaining_amount, 2) . "\n\n";

$amount_to_pay = min($remaining_amount, $total_pending);

if ($amount_to_pay < $total_pending) {
    $is_partial = true;
    echo "PAGO IDENTIFICADO COMO PARCIAL: $amount_to_pay < $total_pending\n";
} else {
    echo "PAGO IDENTIFICADO COMO COMPLETO: $amount_to_pay >= $total_pending\n";
}

$quota_payment = [
    'quota_id' => $cuota_3['id'],
    'interest_paid' => 0,
    'capital_paid' => 0,
    'total_paid' => $amount_to_pay,
    'amount' => $amount_to_pay,
    'status_changed' => ($amount_to_pay >= $total_pending)
];

// Aplicar prioridad interés-capital
$interest_to_pay = 0;
$capital_to_pay = 0;

echo "Aplicando prioridad interés-capital:\n";

// Primero intereses pendientes
if ($interest_pending > 0 && $remaining_amount > 0) {
    $interest_to_pay = min($remaining_amount, $interest_pending);
    $remaining_amount -= $interest_to_pay;
    echo "  Aplicado a intereses: $" . number_format($interest_to_pay, 2) . " (restante: $" . number_format($remaining_amount, 2) . ")\n";
}

// Luego capital pendiente
if ($capital_pending > 0 && $remaining_amount > 0) {
    $capital_to_pay = min($remaining_amount, $capital_pending);
    $remaining_amount -= $capital_to_pay;
    echo "  Aplicado a capital: $" . number_format($capital_to_pay, 2) . " (restante: $" . number_format($remaining_amount, 2) . ")\n";
}

$total_paid_on_quota = $interest_to_pay + $capital_to_pay;

// Verificar si la cuota queda completamente pagada
$new_interest_paid = $cuota_3['interest_paid'] + $interest_to_pay;
$new_capital_paid = $cuota_3['capital_paid'] + $capital_to_pay;

$will_complete_quota = ($new_interest_paid >= $cuota_3['interest_amount'] && $new_capital_paid >= $cuota_3['capital_amount']);

echo "\nVerificación de completitud:\n";
echo "  Nuevo interés pagado: $" . number_format($new_interest_paid, 2) . " >= $" . number_format($cuota_3['interest_amount'], 2) . " = " . ($new_interest_paid >= $cuota_3['interest_amount'] ? 'TRUE' : 'FALSE') . "\n";
echo "  Nuevo capital pagado: $" . number_format($new_capital_paid, 2) . " >= $" . number_format($cuota_3['capital_amount'], 2) . " = " . ($new_capital_paid >= $cuota_3['capital_amount'] ? 'TRUE' : 'FALSE') . "\n";
echo "  Cuota quedará completamente pagada: " . ($will_complete_quota ? 'SÍ' : 'NO') . "\n";

$quota_payment['interest_paid'] = $interest_to_pay;
$quota_payment['capital_paid'] = $capital_to_pay;
$quota_payment['status_changed'] = $will_complete_quota;

if ($total_paid_on_quota > 0) {
    $payment_distribution[] = $quota_payment;

    // Preparar actualización de BD
    $update_data = [
        'paid_by' => $user_id,
        'pay_date' => date('Y-m-d H:i:s'),
        'interest_paid' => $new_interest_paid,
        'capital_paid' => $new_capital_paid,
        'balance' => max(0, $cuota_3['capital_amount'] - $new_capital_paid)
    ];

    // Determinar status correcto
    if ($will_complete_quota) {
        $update_data['status'] = 0;
        $update_data['balance'] = 0;
        echo "  Status final: 0 (Completamente pagada)\n";
    } else {
        $update_data['status'] = 3; // Forzar status parcial
        echo "  Status final: 3 (Pago parcial)\n";
    }

    echo "\nDatos de actualización para BD:\n";
    foreach ($update_data as $key => $value) {
        echo "  $key: " . (is_numeric($value) ? number_format($value, 2) : $value) . "\n";
    }

    // Aplicar actualización (SIMULACIÓN - no ejecutar realmente)
    echo "\n*** SIMULACIÓN: Actualización NO aplicada a BD ***\n";
    echo "UPDATE loan_items SET " . http_build_query($update_data, '', ', ') . " WHERE id = {$cuota_3['id']}\n";

    $payment_distribution[0]['update_data'] = $update_data;
}

// Manejar monto restante
if ($remaining_amount > 0 && $is_partial) {
    echo "\n=== MANEJO DE MONTO RESTANTE ===\n";
    echo "Monto restante sin aplicar: $" . number_format($remaining_amount, 2) . "\n";

    if ($is_last_installment) {
        echo "Como es la última cuota, se generaría nueva cuota con mora\n";

        // Calcular mora
        $current_interest_rate = 2.00; // Asumir 2%
        $penalty_rate = 1.5 * $current_interest_rate;
        $penalty_amount = round($remaining_amount * ($penalty_rate / 100), 2);
        $new_quota_total = $remaining_amount + $penalty_amount;

        echo "Cálculo de mora:\n";
        echo "  Tasa corriente: {$current_interest_rate}%\n";
        echo "  Tasa mora: {$penalty_rate}%\n";
        echo "  Mora calculada: $" . number_format($penalty_amount, 2) . "\n";
        echo "  Nueva cuota total: $" . number_format($new_quota_total, 2) . "\n";

        $additional_quota_generated = true;
        $remaining_balance_distributed = $new_quota_total;
    } else {
        echo "Se distribuiría en cuotas futuras (no implementado en simulación)\n";
        $remaining_balance_distributed = $remaining_amount;
    }
}

// Resultado final
echo "\n=== RESULTADO FINAL DE LA SIMULACIÓN ===\n";
echo "Pago personalizado: $" . number_format($custom_amount, 2) . "\n";
echo "Monto aplicado a cuota #3: $" . number_format($total_paid_on_quota, 2) . "\n";
echo "  - Interés: $" . number_format($interest_to_pay, 2) . "\n";
echo "  - Capital: $" . number_format($capital_to_pay, 2) . "\n";
echo "Monto restante: $" . number_format($remaining_amount, 2) . "\n";
echo "Cuota completamente pagada: " . ($will_complete_quota ? 'SÍ' : 'NO') . "\n";
echo "Pago identificado como: " . ($is_partial ? 'PARCIAL' : 'COMPLETO') . "\n";
echo "Nueva cuota generada: " . ($additional_quota_generated ? 'SÍ' : 'NO') . "\n";
echo "Saldo distribuido: $" . number_format($remaining_balance_distributed, 2) . "\n";

echo "\n=== ANÁLISIS DEL PROBLEMA ===\n";

// Identificar posibles problemas
$problems = [];

if ($total_paid_on_quota == 0) {
    $problems[] = "ERROR CRÍTICO: No se aplicó ningún monto a la cuota";
}

if ($interest_to_pay == 0 && $capital_to_pay == 0) {
    $problems[] = "ERROR: No se aplicó ni interés ni capital";
}

if ($will_complete_quota && $remaining_amount > 0) {
    $problems[] = "INCONSISTENCIA: Cuota marcada como completa pero queda monto sin aplicar";
}

if (!$will_complete_quota && $remaining_amount == 0) {
    $problems[] = "INCONSISTENCIA: Cuota no completa pero todo el monto fue aplicado";
}

if ($is_partial && !$additional_quota_generated && !$is_last_installment) {
    $problems[] = "POSIBLE ERROR: Pago parcial pero no se distribuyó saldo en cuotas futuras";
}

if (empty($problems)) {
    echo "✓ No se detectaron problemas en la lógica de simulación\n";
} else {
    echo "⚠️ PROBLEMAS DETECTADOS:\n";
    foreach ($problems as $problem) {
        echo "  - $problem\n";
    }
}

echo "\n=== RECOMENDACIONES PARA DEBUGGING ===\n";
echo "1. Verificar que los cálculos de montos pendientes sean correctos\n";
echo "2. Confirmar que la prioridad interés-capital se aplique correctamente\n";
echo "3. Validar que el status de la cuota se actualice apropiadamente\n";
echo "4. Revisar el manejo de montos restantes en pagos parciales\n";
echo "5. Verificar la lógica de generación de nuevas cuotas con mora\n";

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";

?>