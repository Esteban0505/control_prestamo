<?php
/**
 * VALIDACIÓN Y CORRECCIÓN DE ESTADO DE CLIENTES
 * Verifica que el estado de los clientes coincida con el estado de sus préstamos
 * Ejecutar desde línea de comandos: php validate_customer_loan_status.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== VALIDACIÓN Y CORRECCIÓN DE ESTADO DE CLIENTES ===\n\n";

echo "1. ANALIZANDO ESTADO ACTUAL DE CLIENTES VS PRÉSTAMOS...\n";

// Obtener todos los clientes con sus préstamos
$query_analysis = $CI->db->query("
    SELECT
        c.id as customer_id,
        c.first_name,
        c.last_name,
        c.loan_status as customer_loan_status,
        l.id as loan_id,
        l.status as loan_status,
        l.customer_id as loan_customer_id
    FROM customers c
    LEFT JOIN loans l ON l.customer_id = c.id
    ORDER BY c.id, l.id
");

$results = $query_analysis->result();

echo "   📊 Clientes analizados: " . count($results) . "\n\n";

$customers_to_update = [];
$status_summary = [
    'correct' => 0,
    'incorrect' => 0,
    'no_loan' => 0
];

$current_customer = null;
$customer_loans = [];

foreach ($results as $result) {
    if ($current_customer != $result->customer_id) {
        // Procesar cliente anterior si existe
        if ($current_customer !== null) {
            $customer_status = process_customer_status($current_customer, $customer_loans, $customers_to_update);
            if ($customer_status == 'correct') $status_summary['correct']++;
            elseif ($customer_status == 'incorrect') $status_summary['incorrect']++;
            elseif ($customer_status == 'no_loan') $status_summary['no_loan']++;
        }

        // Iniciar nuevo cliente
        $current_customer = $result->customer_id;
        $customer_loans = [];
    }

    // Agregar préstamo al cliente actual
    if ($result->loan_id) {
        $customer_loans[] = [
            'loan_id' => $result->loan_id,
            'loan_status' => $result->loan_status,
            'customer_loan_status' => $result->customer_loan_status
        ];
    } else {
        // Cliente sin préstamos
        $customer_loans[] = [
            'loan_id' => null,
            'loan_status' => null,
            'customer_loan_status' => $result->customer_loan_status
        ];
    }
}

// Procesar último cliente
if ($current_customer !== null) {
    $customer_status = process_customer_status($current_customer, $customer_loans, $customers_to_update);
    if ($customer_status == 'correct') $status_summary['correct']++;
    elseif ($customer_status == 'incorrect') $status_summary['incorrect']++;
    elseif ($customer_status == 'no_loan') $status_summary['no_loan']++;
}

function process_customer_status($customer_id, $loans, &$customers_to_update) {
    global $CI;

    // Obtener nombre del cliente
    $customer_query = $CI->db->query("SELECT first_name, last_name FROM customers WHERE id = ?", [$customer_id]);
    $customer = $customer_query->row();

    $customer_name = $customer ? $customer->first_name . ' ' . $customer->last_name : 'Cliente ' . $customer_id;

    // Si no tiene préstamos
    if (empty($loans) || (count($loans) == 1 && $loans[0]['loan_id'] === null)) {
        $current_status = $loans[0]['customer_loan_status'] ?? null;

        if ($current_status != 0) { // Debería ser 0 (sin préstamo)
            echo "   ⚠️  Cliente {$customer_id} ({$customer_name}): Sin préstamos pero status={$current_status} - Debería ser 0\n";
            $customers_to_update[] = [
                'customer_id' => $customer_id,
                'name' => $customer_name,
                'current_status' => $current_status,
                'correct_status' => 0,
                'reason' => 'Cliente sin préstamos'
            ];
            return 'incorrect';
        } else {
            echo "   ✅ Cliente {$customer_id} ({$customer_name}): Sin préstamos, status correcto (0)\n";
            return 'no_loan';
        }
    }

    // Analizar préstamos del cliente
    $has_pending_loans = false;
    $has_paid_loans = false;

    foreach ($loans as $loan) {
        if ($loan['loan_status'] == 1) { // Pendiente
            $has_pending_loans = true;
        } elseif ($loan['loan_status'] == 0) { // Pagado
            $has_paid_loans = true;
        }
    }

    // Determinar estado correcto del cliente
    $correct_status = null;
    $reason = '';

    if ($has_pending_loans && !$has_paid_loans) {
        // Solo préstamos pendientes
        $correct_status = 1;
        $reason = 'Tiene préstamos pendientes';
    } elseif (!$has_pending_loans && $has_paid_loans) {
        // Solo préstamos pagados
        $correct_status = 0;
        $reason = 'Todos los préstamos pagados';
    } elseif ($has_pending_loans && $has_paid_loans) {
        // Mezcla: si tiene al menos un préstamo pendiente, status = 1
        $correct_status = 1;
        $reason = 'Tiene préstamos pendientes y pagados';
    }

    $current_status = $loans[0]['customer_loan_status'];

    if ($current_status != $correct_status) {
        echo "   ❌ Cliente {$customer_id} ({$customer_name}): Status actual={$current_status}, Correcto={$correct_status} - {$reason}\n";
        $customers_to_update[] = [
            'customer_id' => $customer_id,
            'name' => $customer_name,
            'current_status' => $current_status,
            'correct_status' => $correct_status,
            'reason' => $reason
        ];
        return 'incorrect';
    } else {
        echo "   ✅ Cliente {$customer_id} ({$customer_name}): Status correcto ({$correct_status}) - {$reason}\n";
        return 'correct';
    }
}

echo "\n2. RESUMEN DEL ANÁLISIS:\n";
echo "   ✅ Clientes con status correcto: {$status_summary['correct']}\n";
echo "   ❌ Clientes con status incorrecto: {$status_summary['incorrect']}\n";
echo "   📝 Clientes sin préstamos: {$status_summary['no_loan']}\n\n";

echo "3. APLICANDO CORRECCIONES...\n";
echo "   🔧 Clientes a corregir: " . count($customers_to_update) . "\n\n";

$corrections_applied = 0;

foreach ($customers_to_update as $customer) {
    $update_query = $CI->db->query("UPDATE customers SET loan_status = ? WHERE id = ?", [
        $customer['correct_status'],
        $customer['customer_id']
    ]);

    if ($CI->db->affected_rows() > 0) {
        echo "   ✅ Corregido: {$customer['name']} (ID: {$customer['customer_id']}) - {$customer['current_status']} → {$customer['correct_status']} ({$customer['reason']})\n";
        $corrections_applied++;
    } else {
        echo "   ⚠️  No se pudo corregir: {$customer['name']} (ID: {$customer['customer_id']})\n";
    }
}

echo "\n4. VERIFICACIÓN FINAL...\n";

if (!empty($customers_to_update)) {
    $customer_ids = array_column($customers_to_update, 'customer_id');
    $ids_str = implode(',', $customer_ids);

    $verification_query = $CI->db->query("
        SELECT
            c.id,
            c.first_name,
            c.last_name,
            c.loan_status as customer_status,
            COUNT(l.id) as loan_count,
            SUM(CASE WHEN l.status = 1 THEN 1 ELSE 0 END) as pending_loans,
            SUM(CASE WHEN l.status = 0 THEN 1 ELSE 0 END) as paid_loans
        FROM customers c
        LEFT JOIN loans l ON l.customer_id = c.id
        WHERE c.id IN ({$ids_str})
        GROUP BY c.id, c.first_name, c.last_name, c.loan_status
        ORDER BY c.id
    ");

    $verification_results = $verification_query->result();

    echo "   🔍 RESULTADOS DE VERIFICACIÓN:\n";

    $success_count = 0;
    foreach ($verification_results as $result) {
        $expected_status = ($result->pending_loans > 0) ? 1 : 0;
        $status_ok = $result->customer_status == $expected_status ? '✅' : '❌';

        echo "      {$status_ok} Cliente {$result->id} ({$result->first_name} {$result->last_name}): Status={$result->customer_status}, Préstamos={$result->loan_count}, Pendientes={$result->pending_loans}, Pagados={$result->paid_loans}\n";

        if ($result->customer_status == $expected_status) {
            $success_count++;
        }
    }

    echo "\n   ✅ Correcciones exitosas: {$success_count}/" . count($verification_results) . "\n";
}

echo "\n=== ESTADÍSTICAS GLOBALES ===\n";

$global_stats = $CI->db->query("
    SELECT
        COUNT(DISTINCT c.id) as total_customers,
        SUM(CASE WHEN c.loan_status = 0 THEN 1 ELSE 0 END) as customers_no_loans,
        SUM(CASE WHEN c.loan_status = 1 THEN 1 ELSE 0 END) as customers_with_loans,
        COUNT(DISTINCT l.customer_id) as customers_with_actual_loans
    FROM customers c
    LEFT JOIN loans l ON l.customer_id = c.id
");

$global = $global_stats->row();

echo "📊 ESTADO GLOBAL DE CLIENTES:\n";
echo "   • Total de clientes: {$global->total_customers}\n";
echo "   • Clientes sin préstamos: {$global->customers_no_loans}\n";
echo "   • Clientes con préstamos: {$global->customers_with_loans}\n";
echo "   • Clientes con préstamos reales: {$global->customers_with_actual_loans}\n\n";

echo "=== RESUMEN FINAL ===\n\n";

echo "✅ CORRECCIONES APLICADAS:\n";
echo "   • Clientes corregidos: {$corrections_applied}\n";
echo "   • Clientes analizados: " . ($status_summary['correct'] + $status_summary['incorrect'] + $status_summary['no_loan']) . "\n\n";

echo "🎯 RESULTADO:\n";
echo "   • Tasa de corrección: " . round(($corrections_applied / max(1, $status_summary['incorrect'])) * 100, 1) . "%\n";
echo "   • Sistema sincronizado: " . ($corrections_applied == count($customers_to_update) ? '✅ SÍ' : '⚠️ PARCIALMENTE') . "\n\n";

if ($corrections_applied > 0) {
    echo "💡 PRÓXIMOS PASOS:\n";
    echo "   1. Limpiar caché del navegador (Ctrl+F5)\n";
    echo "   2. Verificar en http://localhost/prestamo-1/admin/customers\n";
    echo "   3. Los estados de clientes deberían estar sincronizados\n\n";
}

echo "🎉 ¡VALIDACIÓN Y CORRECCIÓN DE CLIENTES COMPLETADA!\n\n";

echo "=== FIN DE VALIDACIÓN ===\n";
?>