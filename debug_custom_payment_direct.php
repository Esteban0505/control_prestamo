<?php
/**
 * Script de diagnóstico directo para pagos personalizados
 * Sin cargar CodeIgniter completo, usando consultas SQL directas
 */

// Evitar restricciones de CodeIgniter
if (!defined('BASEPATH')) define('BASEPATH', true);

echo "========== DIAGNÓSTICO DIRECTO DE PAGOS PERSONALIZADOS ==========\n\n";

// Conectar a la base de datos
require_once 'application/config/database.php';

try {
    $db_config = $db['default'];
    $pdo = new PDO(
        "mysql:host={$db_config['hostname']};dbname={$db_config['database']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Conexión a BD exitosa\n\n";

    // ========== ANÁLISIS DEL PROBLEMA ==========

    echo "1. VERIFICANDO ESTRUCTURA DE LA BASE DE DATOS\n";
    echo "==========================================\n";

    // Verificar tablas relacionadas con pagos
    $tables = ['loans', 'loan_items', 'payments', 'customers'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabla '$table' existe\n";

            // Verificar estructura básica
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "  - Columnas: " . count($columns) . "\n";
        } else {
            echo "✗ Tabla '$table' NO existe\n";
        }
    }

    echo "\n2. VERIFICANDO DATOS DE PRUEBA\n";
    echo "==============================\n";

    // Verificar préstamo de prueba (loan_id = 141)
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([141]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        echo "✓ Préstamo ID 141 encontrado:\n";
        echo "  - Status: {$loan['status']}\n";
        echo "  - Amortization type: {$loan['amortization_type']}\n";
        echo "  - Credit amount: {$loan['credit_amount']}\n";
        echo "  - Fee amount: {$loan['fee_amount']}\n";
        echo "  - Num fee: {$loan['num_fee']}\n";
    } else {
        echo "✗ Préstamo ID 141 NO encontrado\n";
    }

    // Verificar cuotas del préstamo
    $stmt = $pdo->prepare("SELECT * FROM loan_items WHERE loan_id = ? ORDER BY num_quota");
    $stmt->execute([141]);
    $quotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n✓ Cuotas del préstamo 141: " . count($quotas) . "\n";
    foreach ($quotas as $quota) {
        echo "  Cuota #{$quota['num_quota']}: status={$quota['status']}, balance={$quota['balance']}, fee_amount={$quota['fee_amount']}\n";
    }

    echo "\n3. SIMULANDO PAGO PERSONALIZADO\n";
    echo "================================\n";

    // Simular pago personalizado de $50,000 en cuota ID 1
    $custom_amount = 50000;
    $quota_id = 1; // Cambiar a una cuota que exista
    $user_id = 1;

    // Buscar una cuota pendiente para el préstamo 141
    $stmt = $pdo->prepare("SELECT id FROM loan_items WHERE loan_id = 141 AND status = 1 LIMIT 1");
    $stmt->execute();
    $pending_quota = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pending_quota) {
        $quota_id = $pending_quota['id'];
        echo "Usando cuota pendiente ID: $quota_id\n\n";
    } else {
        echo "No hay cuotas pendientes para el préstamo 141. Creando datos de prueba...\n";

        // Crear una cuota pendiente para pruebas
        $stmt = $pdo->prepare("INSERT INTO loan_items (loan_id, num_quota, date, fee_amount, interest_amount, capital_amount, balance, status, interest_paid, capital_paid) VALUES (?, 5, CURDATE(), 14181.68, 3000.00, 11181.68, 11181.68, 1, 0, 0)");
        $stmt->execute([141]);
        $quota_id = $pdo->lastInsertId();
        echo "Cuota de prueba creada con ID: $quota_id\n\n";
    }

    echo "Parámetros de simulación:\n";
    echo "- Monto personalizado: $custom_amount\n";
    echo "- Cuota ID: $quota_id\n";
    echo "- User ID: $user_id\n\n";

    // Obtener información de la cuota
    $stmt = $pdo->prepare("SELECT * FROM loan_items WHERE id = ?");
    $stmt->execute([$quota_id]);
    $quota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quota) {
        echo "✗ Cuota ID $quota_id NO encontrada\n";
        exit;
    }

    echo "Información de la cuota ANTES del pago:\n";
    echo "- Status: {$quota['status']}\n";
    echo "- Balance: {$quota['balance']}\n";
    echo "- Interest amount: {$quota['interest_amount']}\n";
    echo "- Capital amount: {$quota['capital_amount']}\n";
    echo "- Interest paid: " . ($quota['interest_paid'] ?? 0) . "\n";
    echo "- Capital paid: " . ($quota['capital_paid'] ?? 0) . "\n";
    echo "- Fee amount: {$quota['fee_amount']}\n\n";

    // Calcular montos pendientes
    $interest_pending = $quota['interest_amount'] - ($quota['interest_paid'] ?? 0);
    $capital_pending = $quota['capital_amount'] - ($quota['capital_paid'] ?? 0);
    $total_pending = $interest_pending + $capital_pending;

    echo "Cálculos de montos pendientes:\n";
    echo "- Interés pendiente: $interest_pending\n";
    echo "- Capital pendiente: $capital_pending\n";
    echo "- Total pendiente: $total_pending\n\n";

    // Aplicar lógica de pago personalizado (prioridad interés-capital)
    $remaining_amount = $custom_amount;
    $interest_to_pay = 0;
    $capital_to_pay = 0;

    // Primero intereses
    if ($interest_pending > 0 && $remaining_amount > 0) {
        $interest_to_pay = min($remaining_amount, $interest_pending);
        $remaining_amount -= $interest_to_pay;
        echo "Aplicado a intereses: $interest_to_pay\n";
    }

    // Luego capital
    if ($capital_pending > 0 && $remaining_amount > 0) {
        $capital_to_pay = min($remaining_amount, $capital_pending);
        $remaining_amount -= $capital_to_pay;
        echo "Aplicado a capital: $capital_to_pay\n";
    }

    $total_applied = $interest_to_pay + $capital_to_pay;
    echo "Total aplicado: $total_applied\n";
    echo "Saldo restante sin aplicar: $remaining_amount\n\n";

    // Determinar si es pago parcial o completo
    $is_partial = ($total_applied < $total_pending);
    echo "¿Es pago parcial? " . ($is_partial ? 'SÍ' : 'NO') . "\n\n";

    // Simular actualización de la cuota
    echo "SIMULANDO ACTUALIZACIÓN DE CUOTA:\n";
    $new_interest_paid = ($quota['interest_paid'] ?? 0) + $interest_to_pay;
    $new_capital_paid = ($quota['capital_paid'] ?? 0) + $capital_to_pay;
    $new_balance = $quota['balance'] - $capital_to_pay;

    // Determinar nuevo status
    $will_complete = ($new_interest_paid >= $quota['interest_amount'] && $new_capital_paid >= $quota['capital_amount']);
    $new_status = $will_complete ? 0 : ($is_partial ? 3 : $quota['status']);

    echo "Nuevos valores calculados:\n";
    echo "- Interest paid: $new_interest_paid\n";
    echo "- Capital paid: $new_capital_paid\n";
    echo "- Balance: $new_balance\n";
    echo "- Status: $new_status (" . ($new_status == 0 ? 'Pagada' : ($new_status == 3 ? 'Parcial' : 'Pendiente')) . ")\n";
    echo "- ¿Completará la cuota? " . ($will_complete ? 'SÍ' : 'NO') . "\n\n";

    // ========== VERIFICACIÓN DE PROBLEMAS POTENCIALES ==========

    echo "4. ANÁLISIS DE PROBLEMAS POTENCIALES\n";
    echo "=====================================\n";

    $issues = [];

    // Verificar consistencia de balance
    $expected_balance = $capital_pending - $capital_to_pay;
    if (abs($new_balance - $expected_balance) > 0.01) {
        $issues[] = "Balance inconsistente: esperado $expected_balance, calculado $new_balance";
    }

    // Verificar que no se pague más de lo debido
    if ($interest_to_pay > $interest_pending) {
        $issues[] = "Interés pagado excede lo debido: $interest_to_pay > $interest_pending";
    }

    if ($capital_to_pay > $capital_pending) {
        $issues[] = "Capital pagado excede lo debido: $capital_to_pay > $capital_pending";
    }

    // Verificar montos negativos
    if ($new_balance < 0) {
        $issues[] = "Balance negativo generado: $new_balance";
    }

    if ($remaining_amount < 0) {
        $issues[] = "Saldo restante negativo: $remaining_amount";
    }

    if (empty($issues)) {
        echo "✓ No se detectaron problemas en los cálculos\n";
    } else {
        echo "✗ PROBLEMAS DETECTADOS:\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
    }

    echo "\n5. VERIFICACIÓN DE INTEGRIDAD DE DATOS\n";
    echo "=======================================\n";

    // Verificar integridad de loan_items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM loan_items WHERE loan_id = 141");
    $total_quotas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de cuotas para préstamo 141: $total_quotas\n";

    // Verificar cuotas con balances negativos
    $stmt = $pdo->query("SELECT COUNT(*) as negative FROM loan_items WHERE balance < 0");
    $negative_balances = $stmt->fetch(PDO::FETCH_ASSOC)['negative'];
    echo "Cuotas con balance negativo: $negative_balances\n";

    // Verificar cuotas con status inválido
    $stmt = $pdo->query("SELECT COUNT(*) as invalid FROM loan_items WHERE status NOT IN (0,1,2,3)");
    $invalid_status = $stmt->fetch(PDO::FETCH_ASSOC)['invalid'];
    echo "Cuotas con status inválido: $invalid_status\n";

    echo "\n========== DIAGNÓSTICO COMPLETADO ==========\n";

    if (empty($issues)) {
        echo "RESULTADO: Los cálculos parecen correctos. El problema podría estar en:\n";
        echo "1. La ejecución del código PHP (errores de sintaxis)\n";
        echo "2. Transacciones de base de datos no completadas\n";
        echo "3. Problemas de concurrencia\n";
        echo "4. Validaciones adicionales que bloquean el proceso\n";
    } else {
        echo "RESULTADO: Se encontraron problemas en los cálculos que deben corregirse\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>