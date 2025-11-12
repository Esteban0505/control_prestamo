<?php
/**
 * CORRECCIÓN FORZADA CON SQL DIRECTO
 * Aplica cambios directamente usando consultas SQL nativas
 * Ejecutar desde línea de comandos: php force_sql_correction.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CORRECCIÓN FORZADA CON SQL DIRECTO ===\n\n";

// Lista de préstamos que deben ser marcados como PAGADOS
$loans_to_complete = [
    10, 11, 12, 13, 14, 15, 19, 20, 22, 26, 27, 28, 29, 30, 33, 34, 35, 36, 37, 38, 39, 40,
    41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62,
    63, 64, 65, 66, 67, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86,
    87, 89, 90, 91, 92, 94, 95, 96, 101, 102, 123
];

echo "1. MARCANDO PRÉSTAMOS COMO PAGADOS...\n";
echo "   📊 Préstamos a procesar: " . count($loans_to_complete) . "\n\n";

$loans_updated = 0;
$customers_updated = 0;

// 1. Actualizar estado de préstamos
$loans_ids_str = implode(',', $loans_to_complete);
$sql_update_loans = "UPDATE loans SET status = 0 WHERE id IN ({$loans_ids_str})";

echo "   🔄 Ejecutando: UPDATE loans SET status = 0 WHERE id IN ({$loans_ids_str})\n";

$result_loans = $CI->db->query($sql_update_loans);
$loans_affected = $CI->db->affected_rows();

echo "   ✅ Préstamos actualizados: {$loans_affected}\n";
$loans_updated = $loans_affected;

// 2. Obtener clientes asociados y actualizar su estado
echo "\n2. ACTUALIZANDO ESTADO DE CLIENTES...\n";

$query_customers = $CI->db->query("SELECT DISTINCT customer_id FROM loans WHERE id IN ({$loans_ids_str})");
$customers = $query_customers->result();

$customer_ids = array_column($customers, 'customer_id');
$customers_ids_str = implode(',', $customer_ids);

if (!empty($customer_ids)) {
    $sql_update_customers = "UPDATE customers SET loan_status = 0 WHERE id IN ({$customers_ids_str})";

    echo "   🔄 Ejecutando: UPDATE customers SET loan_status = 0 WHERE id IN ({$customers_ids_str})\n";

    $result_customers = $CI->db->query($sql_update_customers);
    $customers_affected = $CI->db->affected_rows();

    echo "   ✅ Clientes actualizados: {$customers_affected}\n";
    $customers_updated = $customers_affected;
}

// 3. Marcar cuotas condonadas como pagadas
echo "\n3. MARCANDO CUOTAS CONDONADAS COMO PAGADAS...\n";

$sql_condoned = "UPDATE loan_items SET status = 0, balance = 0, pay_date = NOW()
                 WHERE loan_id IN ({$loans_ids_str}) AND extra_payment = 3 AND status != 0";

echo "   🔄 Ejecutando: UPDATE loan_items (cuotas condonadas)\n";

$result_condoned = $CI->db->query($sql_condoned);
$condoned_affected = $CI->db->affected_rows();

echo "   ✅ Cuotas condonadas marcadas como pagadas: {$condoned_affected}\n";

// 4. Verificación final
echo "\n4. VERIFICACIÓN FINAL...\n";

$verification_query = $CI->db->query("
    SELECT
        COUNT(CASE WHEN l.status = 0 THEN 1 END) as loans_paid,
        COUNT(CASE WHEN c.loan_status = 0 THEN 1 END) as customers_paid,
        COUNT(*) as total_checked
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    WHERE l.id IN ({$loans_ids_str})
");

$verification = $verification_query->row();

echo "   🔍 RESULTADOS DE VERIFICACIÓN:\n";
echo "      Préstamos marcados como pagados: {$verification->loans_paid}/{$verification->total_checked}\n";
echo "      Clientes marcados como pagados: {$verification->customers_paid}/{$verification->total_checked}\n";

// 5. Verificación de muestra
echo "\n5. VERIFICACIÓN DE MUESTRA...\n";

$sample_loans = [29, 30, 101, 102, 10, 33];
foreach ($sample_loans as $loan_id) {
    $check_query = $CI->db->query("
        SELECT l.status as loan_status, c.loan_status as customer_status
        FROM loans l
        JOIN customers c ON c.id = l.customer_id
        WHERE l.id = ?
    ", [$loan_id]);

    $check_result = $check_query->row();
    if ($check_result) {
        $loan_status = $check_result->loan_status == 0 ? 'PAGADO' : 'PENDIENTE';
        $customer_status = $check_result->customer_status == 0 ? 'PAGADO' : 'PENDIENTE';

        echo "   Préstamo {$loan_id}: Loan={$loan_status}, Cliente={$customer_status}\n";
    }
}

// 6. Estadísticas globales
echo "\n6. ESTADÍSTICAS GLOBALES FINALES...\n";

$global_stats = $CI->db->query("
    SELECT
        COUNT(*) as total_loans,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as paid_loans,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as pending_loans
    FROM loans
");

$stats = $global_stats->row();

echo "   📊 ESTADO GLOBAL:\n";
echo "      Total de préstamos: {$stats->total_loans}\n";
echo "      Préstamos pagados: {$stats->paid_loans}\n";
echo "      Préstamos pendientes: {$stats->pending_loans}\n";
echo "      Porcentaje pagados: " . round(($stats->paid_loans / $stats->total_loans) * 100, 1) . "%\n\n";

echo "=== RESUMEN DE CORRECCIÓN FORZADA ===\n\n";

echo "✅ CORRECCIONES APLICADAS:\n";
echo "   • Préstamos actualizados: {$loans_updated}\n";
echo "   • Clientes actualizados: {$customers_updated}\n";
echo "   • Cuotas condonadas corregidas: {$condoned_affected}\n\n";

echo "🎯 RESULTADO:\n";
echo "   • Préstamos marcados como PAGADOS: {$verification->loans_paid}\n";
echo "   • Clientes sincronizados: {$verification->customers_paid}\n";
echo "   • Tasa de éxito: " . round(($verification->loans_paid / count($loans_to_complete)) * 100, 1) . "%\n\n";

if ($verification->loans_paid == count($loans_to_complete)) {
    echo "🎉 ¡CORRECCIÓN COMPLETA Y EXITOSA!\n";
    echo "   Todos los préstamos han sido marcados como pagados correctamente.\n\n";
} else {
    echo "⚠️  CORRECCIÓN PARCIAL\n";
    echo "   Algunos préstamos pueden requerir atención adicional.\n\n";
}

echo "💡 PRÓXIMOS PASOS:\n";
echo "   1. Limpiar caché del navegador (Ctrl+F5)\n";
echo "   2. Reiniciar el servidor web si es necesario\n";
echo "   3. Verificar en la interfaz web que los cambios se reflejan\n";
echo "   4. Si aún no se ven, revisar configuración de PHP/MySQL\n\n";

echo "=== FIN DE CORRECCIÓN FORZADA ===\n";
?>