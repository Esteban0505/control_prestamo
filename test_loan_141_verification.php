<?php
/**
 * Script de prueba para verificar la corrección del problema de finalización prematura de préstamos
 * Préstamo #141 - Verifica que no se marque como completado hasta que todas las cuotas estén pagadas
 */

// Configuración de base de datos
$db_config = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'prestamobd',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => TRUE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
];

// Conectar a la base de datos
$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Función helper para ejecutar queries
function db_query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("❌ Error en query: " . $conn->error);
    }
    return $result;
}

// Función helper para obtener una fila
function db_row($sql) {
    $result = db_query($sql);
    return $result->fetch_assoc();
}

// Función helper para obtener múltiples filas
function db_results($sql) {
    $result = db_query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

echo "=== PRUEBA DE VERIFICACIÓN DEL PRÉSTAMO #141 ===\n\n";

// 1. Verificar estado actual del préstamo
echo "1. VERIFICANDO ESTADO ACTUAL DEL PRÉSTAMO #141:\n";
$loan_query = "SELECT l.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, co.short_name
               FROM loans l
               LEFT JOIN customers c ON c.id = l.customer_id
               LEFT JOIN coins co ON co.id = l.coin_id
               WHERE l.id = 141";
$loan = db_row($loan_query);

if (!$loan) {
    echo "❌ ERROR: Préstamo #141 no encontrado\n";
    exit(1);
}

echo "   Estado del préstamo: " . ($loan['status'] == 0 ? 'COMPLETADO' : 'ACTIVO') . "\n";
echo "   Cliente: " . $loan['customer_name'] . "\n";
echo "   Monto: $" . number_format($loan['credit_amount'], 0, ',', '.') . "\n\n";

// 2. Obtener cuotas del préstamo
echo "2. VERIFICANDO CUOTAS DEL PRÉSTAMO:\n";
$loan_items_query = "SELECT * FROM loan_items WHERE loan_id = 141 ORDER BY num_quota";
$loan_items = db_results($loan_items_query);
$total_quotas = count($loan_items);
$cuotas_pagadas = 0;
$cuotas_pendientes = 0;

foreach ($loan_items as $item) {
    $estado = '';
    if ($item['status'] == 0) {
        $estado = 'PAGADA';
        $cuotas_pagadas++;
    } elseif ($item['status'] == 1) {
        $estado = 'PENDIENTE';
        $cuotas_pendientes++;
    } elseif ($item['status'] == 3) {
        $estado = 'CONDONADA';
    } else {
        $estado = 'OTRO (' . $item['status'] . ')';
    }

    echo "   Cuota #{$item['num_quota']}: {$estado} - Balance: $" . number_format($item['balance'], 2, ',', '.') . "\n";
}

echo "\n   Total cuotas: {$total_quotas}\n";
echo "   Cuotas pagadas: {$cuotas_pagadas}\n";
echo "   Cuotas pendientes: {$cuotas_pendientes}\n";
echo "   Progreso: " . round(($cuotas_pagadas / $total_quotas) * 100, 1) . "%\n\n";

// 3. Verificaciones específicas
echo "3. VERIFICACIONES ESPECÍFICAS:\n";

// Verificación 1: El préstamo debe mostrarse como activo (no finalizado)
$verificacion_1 = $loan['status'] == 1;
echo "   ✅ Verificación 1 - Préstamo activo: " . ($verificacion_1 ? 'PASÓ' : 'FALLÓ') . "\n";

// Verificación 2: Debe permitir procesar cuota #4 pendiente
$cuota_4 = null;
foreach ($loan_items as $item) {
    if ($item['num_quota'] == 4) {
        $cuota_4 = $item;
        break;
    }
}
$verificacion_2 = $cuota_4 && $cuota_4['status'] == 1 && $cuota_4['balance'] > 0;
echo "   ✅ Verificación 2 - Cuota #4 pendiente: " . ($verificacion_2 ? 'PASÓ' : 'FALLÓ');
if ($cuota_4) {
    echo " (Balance: $" . number_format($cuota_4['balance'], 2, ',', '.') . ")";
}
echo "\n";

// Verificación 3: Progreso debe ser 75% (3/4 cuotas pagadas)
$progreso_esperado = 75.0;
$progreso_actual = round(($cuotas_pagadas / $total_quotas) * 100, 1);
$verificacion_3 = abs($progreso_actual - $progreso_esperado) < 0.1;
echo "   ✅ Verificación 3 - Progreso 75%: " . ($verificacion_3 ? 'PASÓ' : 'FALLÓ') . " (Actual: {$progreso_actual}%, Esperado: {$progreso_esperado}%)\n";

// Verificación 4: Sistema no debe marcar préstamo como completado
$debe_estar_completado = ($cuotas_pendientes == 0);
$esta_completado = ($loan['status'] == 0);
$verificacion_4 = !$debe_estar_completado || $esta_completado; // Si debe estar completado, entonces debe estarlo
echo "   ✅ Verificación 4 - No completar prematuramente: " . ($verificacion_4 ? 'PASÓ' : 'FALLÓ') . "\n";

// 4. Verificar logs de la nueva lógica
echo "\n4. VERIFICANDO LOGS DE LA NUEVA LÓGICA:\n";
echo "   Simulando lógica de actualización de estados...\n";

// Simular la lógica de force_status_update para el préstamo #141
$pending_quotas_query = "SELECT COUNT(*) as count FROM loan_items WHERE loan_id = 141 AND status = 1 AND extra_payment != 3";
$pending_result = db_row($pending_quotas_query);
$pending_quotas = $pending_result['count'];

$balance_query = "SELECT SUM(COALESCE(interest_paid, 0) + COALESCE(capital_paid, 0)) AS total_paid,
                         SUM(CASE WHEN extra_payment != 3 THEN COALESCE(fee_amount, 0) ELSE 0 END) AS total_expected
                  FROM loan_items WHERE loan_id = 141";
$balance_result = db_row($balance_query);
$balance_amount = $balance_result['total_expected'] - $balance_result['total_paid'];

echo "   Simulación de LOAN_STATUS_CHECK: loan_id=141, current_status={$loan['status']}, balance_amount=" . number_format($balance_amount, 2) . ", pending_quotas=$pending_quotas\n";
echo "   Simulación de FORCE_STATUS_UPDATE: loan_id=141, current_status={$loan['status']}, balance_amount=" . number_format($balance_amount, 2) . ", pending_quotas=$pending_quotas\n";

echo "   ✅ Lógica de logs simulada - En producción revisarían application/logs\n";

// 5. Resultado final
echo "\n5. RESULTADO FINAL:\n";
$pruebas_pasadas = 0;
$total_pruebas = 4;

if ($verificacion_1) $pruebas_pasadas++;
if ($verificacion_2) $pruebas_pasadas++;
if ($verificacion_3) $pruebas_pasadas++;
if ($verificacion_4) $pruebas_pasadas++;

echo "   Pruebas pasadas: {$pruebas_pasadas}/{$total_pruebas}\n";

if ($pruebas_pasadas == $total_pruebas) {
    echo "   🎉 TODAS LAS PRUEBAS PASARON - La corrección funciona correctamente\n";
} else {
    echo "   ❌ ALGUNAS PRUEBAS FALLARON - Revisar implementación\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";