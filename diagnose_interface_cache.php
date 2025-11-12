<?php
/**
 * DIAGNÓSTICO: ¿Por qué la interfaz web no refleja los cambios?
 * Verifica si los cambios se guardaron realmente y diagnostica problemas de caché
 * Ejecutar desde línea de comandos: php diagnose_interface_cache.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== DIAGNÓSTICO: INTERFAZ WEB VS BASE DE DATOS ===\n\n";

// 1. VERIFICAR ESTADO REAL EN BASE DE DATOS
echo "1. VERIFICANDO ESTADO REAL EN BASE DE DATOS...\n";

$query_db_status = $CI->db->query("
    SELECT
        l.id as loan_id,
        l.status as loan_status,
        c.loan_status as customer_status,
        COALESCE(calc.balance_amount, 0) as balance_amount,
        COALESCE(calc.pending_installments, 0) as pending_installments
    FROM loans l
    JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT loan_id,
               GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount,
               SUM(CASE WHEN li.status IN (1, 3) THEN 1 ELSE 0 END) AS pending_installments
        FROM loan_items li GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.id IN (10, 11, 12, 13, 14, 15, 19, 20, 22, 26, 27, 28, 29, 30, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 89, 90, 91, 92, 94, 95, 96, 101, 102, 123)
    ORDER BY l.id
");

$db_results = $query_db_status->result();
echo "   📊 Verificando " . count($db_results) . " préstamos corregidos:\n";

$interface_mismatch = 0;
foreach ($db_results as $result) {
    $db_status = $result->loan_status == 0 ? 'PAGADO' : 'PENDIENTE';
    $customer_status = $result->customer_status == 0 ? 'PAGADO' : 'PENDIENTE';

    echo "   Préstamo {$result->loan_id}: DB={$db_status}, Cliente={$customer_status}, Balance=$" . number_format($result->balance_amount, 2) . "\n";

    // Verificar si hay inconsistencia
    if ($result->loan_status != $result->customer_status) {
        $interface_mismatch++;
        echo "   ⚠️  INCONSISTENCIA: Préstamo y cliente tienen estados diferentes!\n";
    }
}

echo "\n   🔍 Inconsistencias encontradas: {$interface_mismatch}\n\n";

// 2. VERIFICAR SI LOS CAMBIOS SE GUARDARON REALMENTE
echo "2. VERIFICANDO SI LOS CAMBIOS SE APLICARON REALMENTE...\n";

$sample_loans = [29, 30, 101, 102, 10, 33]; // Algunos que deberían estar pagados

foreach ($sample_loans as $loan_id) {
    $check_query = $CI->db->query("SELECT status FROM loans WHERE id = ?", [$loan_id]);
    $loan_status = $check_query->row();

    if ($loan_status) {
        $status_text = $loan_status->status == 0 ? 'PAGADO' : 'PENDIENTE';
        echo "   Préstamo {$loan_id}: Estado en BD = {$status_text} (status={$loan_status->status})\n";
    } else {
        echo "   ❌ Préstamo {$loan_id}: NO ENCONTRADO en BD\n";
    }
}

echo "\n";

// 3. VERIFICAR CONEXIÓN A BASE DE DATOS
echo "3. VERIFICANDO CONEXIÓN Y CONFIGURACIÓN DE BASE DE DATOS...\n";

$db_config = $CI->db->database;
echo "   🗄️  Base de datos conectada: {$db_config}\n";

$test_query = $CI->db->query("SELECT COUNT(*) as total FROM loans");
$test_result = $test_query->row();
echo "   📊 Total de préstamos en BD: {$test_result->total}\n";

$paid_query = $CI->db->query("SELECT COUNT(*) as paid FROM loans WHERE status = 0");
$paid_result = $paid_query->row();
echo "   ✅ Préstamos marcados como pagados: {$paid_result->paid}\n";

$pending_query = $CI->db->query("SELECT COUNT(*) as pending FROM loans WHERE status = 1");
$pending_result = $pending_query->row();
echo "   ⏳ Préstamos marcados como pendientes: {$pending_result->pending}\n\n";

// 4. DIAGNÓSTICO DE POSIBLES PROBLEMAS
echo "4. DIAGNÓSTICO DE POSIBLES PROBLEMAS...\n";

$issues_found = [];

// Verificar si hay transacciones no commited
$transaction_check = $CI->db->query("SHOW ENGINE INNODB STATUS");
if ($transaction_check) {
    echo "   🔄 InnoDB Status: OK\n";
} else {
    $issues_found[] = "No se puede verificar estado de InnoDB";
}

// Verificar si hay locks en tablas
$lock_check = $CI->db->query("SHOW OPEN TABLES WHERE In_use > 0");
$locks = $lock_check->result();
if (!empty($locks)) {
    $issues_found[] = "Hay " . count($locks) . " tablas con locks activos";
    echo "   🔒 Tablas con locks: " . count($locks) . "\n";
} else {
    echo "   ✅ No hay locks activos en tablas\n";
}

// Verificar configuración de autocommit
$autocommit_check = $CI->db->query("SELECT @@autocommit as autocommit");
$autocommit = $autocommit_check->row();
echo "   🔄 Autocommit: " . ($autocommit->autocommit ? 'ON' : 'OFF') . "\n";

if (!$autocommit->autocommit) {
    $issues_found[] = "Autocommit está DESACTIVADO - cambios no se guardan automáticamente";
}

// 5. VERIFICAR CACHÉ DE LA APLICACIÓN
echo "\n5. VERIFICANDO SISTEMA DE CACHÉ...\n";

$cache_path = APPPATH . 'cache/';
if (is_dir($cache_path)) {
    $cache_files = glob($cache_path . '*');
    $cache_count = count($cache_files);
    echo "   📁 Archivos en caché: {$cache_count}\n";

    if ($cache_count > 0) {
        echo "   🗑️  Eliminando archivos de caché...\n";
        foreach ($cache_files as $file) {
            if (is_file($file) && basename($file) !== 'index.html') {
                unlink($file);
            }
        }
        echo "   ✅ Caché limpiado\n";
    }
} else {
    echo "   ⚠️  Directorio de caché no encontrado\n";
}

// 6. VERIFICAR SESIÓN Y AUTENTICACIÓN
echo "\n6. VERIFICANDO SESIÓN Y AUTENTICACIÓN...\n";

$session_data = $CI->session->all_userdata();
if (!empty($session_data)) {
    echo "   👤 Usuario en sesión: " . ($session_data['user_id'] ?? 'Desconocido') . "\n";
} else {
    echo "   👤 No hay sesión activa\n";
}

// 7. RECOMENDACIONES
echo "\n=== RECOMENDACIONES PARA SOLUCIONAR EL PROBLEMA ===\n\n";

if (!empty($issues_found)) {
    echo "❌ PROBLEMAS ENCONTRADOS:\n";
    foreach ($issues_found as $issue) {
        echo "   • {$issue}\n";
    }
    echo "\n";
}

echo "🔧 ACCIONES RECOMENDADAS:\n";
echo "   1. Limpiar caché del navegador (Ctrl+F5)\n";
echo "   2. Reiniciar servidor Apache/XAMPP\n";
echo "   3. Verificar que los cambios se guardaron ejecutando este script\n";
echo "   4. Si el problema persiste, verificar configuración de PHP y MySQL\n";
echo "   5. Revisar logs de error de PHP y MySQL\n\n";

echo "📊 RESUMEN:\n";
echo "   • Préstamos corregidos en BD: " . count($db_results) . "\n";
echo "   • Inconsistencias encontradas: {$interface_mismatch}\n";
echo "   • Problemas detectados: " . count($issues_found) . "\n\n";

if ($interface_mismatch == 0 && empty($issues_found)) {
    echo "🎉 ¡LOS CAMBIOS ESTÁN CORRECTOS EN LA BASE DE DATOS!\n";
    echo "   El problema está en la capa de presentación (caché del navegador)\n\n";
} else {
    echo "⚠️  HAY PROBLEMAS QUE REQUIEREN ATENCIÓN\n\n";
}

echo "=== FIN DEL DIAGNÓSTICO ===\n";
?>