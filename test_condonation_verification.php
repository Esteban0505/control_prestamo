<?php
/**
 * Script de prueba para verificar que después de condonación no se muestren cuotas en nuevos pagos
 *
 * Este script simula el proceso completo:
 * 1. Hace un pago con condonación (early_total o total_condonacion)
 * 2. Verifica que las cuotas condonadas no aparezcan en búsquedas posteriores
 */

// Configuración inicial
define('BASEPATH', 'C:/xampp/htdocs/prestamo-1/system/');
define('APPPATH', 'C:/xampp/htdocs/prestamo-1/application/');
define('ENVIRONMENT', 'testing');

// Incluir archivos necesarios de CodeIgniter
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/Controller.php';
require_once BASEPATH . 'core/Model.php';

// Incluir MY_Model primero
require_once APPPATH . 'core/MY_Model.php';

// Configurar autoload
require_once APPPATH . '/config/autoload.php';

// Cargar configuración de base de datos
require_once APPPATH . '/config/database.php';

// Incluir modelos necesarios
require_once 'application/models/Payments_m.php';
require_once 'application/models/Loans_m.php';
require_once 'application/models/Customers_m.php';

// Inicializar base de datos usando mysqli directamente
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'prestamobd';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Inicializar modelos con conexión directa
$payments_m = new Payments_m();
$loans_m = new Loans_m();
$customers_m = new Customers_m();

// Asignar conexión a los modelos
$payments_m->db = $conn;
$loans_m->db = $conn;
$customers_m->db = $conn;

echo "=== PRUEBA DE CONDONACIÓN: VERIFICACIÓN DE CUOTAS NO MOSTRADAS ===\n\n";

// Usar el préstamo ID 105 que ya tiene cuotas pendientes
$loan_id = 105;
$user_id = 1; // Usuario de prueba
$customer_id = 1; // Cliente de prueba

echo "Préstamo a usar: ID {$loan_id}\n";
echo "Usuario: ID {$user_id}\n";
echo "Cliente: ID {$customer_id}\n\n";

// PASO 1: Verificar estado inicial del préstamo
echo "PASO 1: Verificando estado inicial del préstamo\n";
$loan = $loans_m->get_loan($loan_id);
if (!$loan) {
    die("ERROR: Préstamo {$loan_id} no encontrado\n");
}

echo "Estado del préstamo: " . ($loan->status == 1 ? "ACTIVO" : "INACTIVO") . "\n";
echo "Monto del crédito: $" . number_format($loan->credit_amount, 2, ',', '.') . "\n";
echo "Número de cuotas: {$loan->num_fee}\n\n";

// Obtener cuotas iniciales
$initial_quotas = $payments_m->get_quotasCst($loan_id);
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

// Simular el pago con condonación
$payment_data = [
    'loan_id' => $loan_id,
    'loan_item_id' => $first_pending_quota['id'],
    'amount' => $first_pending_quota['balance'], // Pagar el balance completo de la cuota seleccionada
    'tipo_pago' => 'early_total',
    'monto_pagado' => $first_pending_quota['balance'],
    'interest_paid' => $first_pending_quota['interest_amount'] - ($first_pending_quota['interest_paid'] ?? 0),
    'capital_paid' => $first_pending_quota['capital_amount'] - ($first_pending_quota['capital_paid'] ?? 0),
    'payment_date' => date('Y-m-d H:i:s'),
    'payment_user_id' => $user_id,
    'method' => 'efectivo',
    'notes' => 'PAGO TOTAL ANTICIPADO CON CONDONACIÓN - Prueba automática'
];

echo "Procesando pago...\n";
$result = $payments_m->process_payment($payment_data);

if ($result['success']) {
    echo "✅ Pago procesado exitosamente\n";
    echo "ID del pago registrado: " . ($result['data']['payment_id'] ?? 'N/A') . "\n\n";
} else {
    echo "❌ Error en el pago: " . ($result['error'] ?? 'Error desconocido') . "\n";
    exit(1);
}

// PASO 3: Verificar que las cuotas posteriores estén condonadas
echo "PASO 3: Verificando que las cuotas posteriores estén condonadas\n";

// Obtener todas las cuotas del préstamo después del pago
$query = $CI->db->select('*')
    ->from('loan_items')
    ->where('loan_id', $loan_id)
    ->order_by('num_quota', 'ASC')
    ->get();

$all_quotas_after = $query->result_array();

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

// Usar get_quotasCst (método usado en la interfaz de pagos)
$quotas_after_payment = $payments_m->get_quotasCst($loan_id);
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
$condonadas_en_busqueda = array_filter($quotas_after_payment, function($quota) {
    return isset($quota['extra_payment']) && $quota['extra_payment'] == 3;
});

if (count($condonadas_en_busqueda) > 0) {
    echo "❌ ERROR: Se encontraron " . count($condonadas_en_busqueda) . " cuotas condonadas en la búsqueda!\n";
    foreach ($condonadas_en_busqueda as $quota) {
        echo "  - Cuota condonada #{$quota['num_quota']} aparece en búsqueda\n";
    }
} else {
    echo "✅ VERIFICACIÓN EXITOSA: Ninguna cuota condonada aparece en búsquedas\n";
}

echo "\n";

// PASO 5: Verificar búsqueda por cliente (get_searchCst)
echo "PASO 5: Verificando búsqueda por cliente (get_searchCst)\n";

// Obtener datos del cliente
$customer = $customers_m->get($customer_id);
if (!$customer) {
    echo "Cliente no encontrado, usando DNI de prueba\n";
    $dni_search = '12345678'; // DNI de prueba
} else {
    $dni_search = $customer->dni;
    echo "Buscando cliente con DNI: {$dni_search}\n";
}

// Realizar búsqueda por cliente
$search_result = $payments_m->get_searchCst($dni_search, false);

if ($search_result) {
    echo "Cliente encontrado en búsqueda: {$search_result->cst_name} (DNI: {$search_result->client_cedula})\n";
    echo "Balance total del cliente: $" . number_format($search_result->total_balance, 2, ',', '.') . "\n";

    if ($search_result->total_balance > 0) {
        echo "❌ ERROR: El cliente aún muestra balance pendiente después de condonación\n";
    } else {
        echo "✅ VERIFICACIÓN EXITOSA: Cliente no muestra balance pendiente\n";
    }
} else {
    echo "Cliente no encontrado en búsqueda (posiblemente préstamo cerrado)\n";
}

// PASO 6: Verificar estado final del préstamo
echo "\nPASO 6: Verificando estado final del préstamo\n";
$loan_final = $loans_m->get_loan($loan_id);
if ($loan_final) {
    echo "Estado final del préstamo: " . ($loan_final->status == 1 ? "ACTIVO" : "INACTIVO") . "\n";
    echo "Balance final del préstamo: $" . number_format($loan_final->balance ?? 0, 2, ',', '.') . "\n";

    if ($loan_final->status == 0) {
        echo "✅ Préstamo cerrado correctamente\n";
    } else {
        echo "ℹ️  Préstamo mantiene activo (posiblemente tiene cuotas no condonadas)\n";
    }
} else {
    echo "Préstamo no encontrado\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";

if (count($condonadas_en_busqueda) == 0 && (!isset($search_result->total_balance) || $search_result->total_balance == 0)) {
    echo "✅ RESULTADO: TODAS LAS VERIFICACIONES PASARON\n";
    echo "Las cuotas condonadas no aparecen en búsquedas posteriores.\n";
} else {
    echo "❌ RESULTADO: ALGUNAS VERIFICACIONES FALLARON\n";
    echo "Revisar la lógica de filtrado de cuotas condonadas.\n";
}
?>