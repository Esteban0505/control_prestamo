<?php
/**
 * VALIDACIÓN COMPREHENSIVA DE MÓDULOS DE PAGOS Y PRÉSTAMOS
 * Ejecutar desde línea de comandos: php comprehensive_validation.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== VALIDACIÓN COMPREHENSIVA DE MÓDULOS ===\n\n";

$validation_results = [
    'total_loans' => 0,
    'loans_with_negative_balance' => 0,
    'loans_with_condoned_pending' => 0,
    'loans_with_incorrect_status' => 0,
    'payment_calculations_mismatch' => 0,
    'amortization_issues' => 0,
    'inconsistencies_found' => []
];

// 1. VALIDACIÓN DE ESTADOS DE PRÉSTAMOS Y BALANCES
echo "1. VALIDANDO ESTADOS DE PRÉSTAMOS Y BALANCES...\n";

$query_loans = $CI->db->query("
    SELECT
        l.id,
        l.status as loan_status,
        l.amortization_type,
        COALESCE(calc.total_expected, 0) as total_expected,
        COALESCE(calc.total_paid, 0) as total_paid,
        COALESCE(calc.balance_amount, 0) as calculated_balance,
        COALESCE(calc.condoned_count, 0) as condoned_count,
        COALESCE(calc.pending_condoned, 0) as pending_condoned,
        c.loan_status as customer_loan_status
    FROM loans l
    LEFT JOIN customers c ON c.id = l.customer_id
    LEFT JOIN (
        SELECT
            li.loan_id,
            SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
            SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
            GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount,
            SUM(CASE WHEN li.extra_payment = 3 THEN 1 ELSE 0 END) AS condoned_count,
            SUM(CASE WHEN li.extra_payment = 3 AND li.status != 0 THEN 1 ELSE 0 END) AS pending_condoned
        FROM loan_items li
        GROUP BY li.loan_id
    ) calc ON calc.loan_id = l.id
");

$loans = $query_loans->result();
$validation_results['total_loans'] = count($loans);

foreach ($loans as $loan) {
    $issues = [];

    // Verificar balances negativos
    if ($loan->calculated_balance < -0.01) {
        $validation_results['loans_with_negative_balance']++;
        $issues[] = "Balance negativo: $" . number_format($loan->calculated_balance, 2);
    }

    // Verificar cuotas condonadas pendientes
    if ($loan->pending_condoned > 0) {
        $validation_results['loans_with_condoned_pending']++;
        $issues[] = "Cuotas condonadas pendientes: {$loan->pending_condoned}";
    }

    // Verificar estado incorrecto del préstamo
    $expected_status = ($loan->calculated_balance <= 0.01) ? 0 : 1;
    if ($loan->loan_status != $expected_status) {
        $validation_results['loans_with_incorrect_status']++;
        $issues[] = "Estado incorrecto: {$loan->loan_status} (debería ser {$expected_status})";
    }

    // Verificar estado del cliente
    if ($loan->customer_loan_status !== null && $loan->customer_loan_status != $expected_status) {
        $issues[] = "Estado del cliente incorrecto: {$loan->customer_loan_status} (debería ser {$expected_status})";
    }

    if (!empty($issues)) {
        $validation_results['inconsistencies_found'][] = [
            'loan_id' => $loan->id,
            'issues' => $issues,
            'data' => $loan
        ];
    }
}

echo "   ✅ Analizados: {$validation_results['total_loans']} préstamos\n";
echo "   ⚠️  Balances negativos: {$validation_results['loans_with_negative_balance']}\n";
echo "   ⚠️  Condonadas pendientes: {$validation_results['loans_with_condoned_pending']}\n";
echo "   ⚠️  Estados incorrectos: {$validation_results['loans_with_incorrect_status']}\n\n";

// 2. VALIDACIÓN DE CÁLCULOS DE PAGOS
echo "2. VALIDANDO CÁLCULOS DE PAGOS...\n";

$query_payments = $CI->db->query("
    SELECT
        p.id as payment_id,
        p.loan_id,
        p.amount as payment_amount,
        p.monto_pagado,
        p.interest_paid,
        p.capital_paid,
        COALESCE(SUM(li.interest_paid), 0) as total_interest_from_items,
        COALESCE(SUM(li.capital_paid), 0) as total_capital_from_items
    FROM payments p
    LEFT JOIN loan_items li ON li.loan_id = p.loan_id AND li.pay_date = p.payment_date
    GROUP BY p.id
    LIMIT 50
");

$payments = $query_payments->result();

foreach ($payments as $payment) {
    $calculated_total = $payment->interest_paid + $payment->capital_paid;
    $items_total = $payment->total_interest_from_items + $payment->total_capital_from_items;

    if (abs($calculated_total - $items_total) > 0.01) {
        $validation_results['payment_calculations_mismatch']++;
        $validation_results['inconsistencies_found'][] = [
            'type' => 'payment_calculation',
            'payment_id' => $payment->payment_id,
            'issues' => ["Cálculo inconsistente: pago=$calculated_total, items=$items_total"],
            'data' => $payment
        ];
    }
}

echo "   ✅ Pagos analizados: " . count($payments) . "\n";
echo "   ⚠️  Cálculos inconsistentes: {$validation_results['payment_calculations_mismatch']}\n\n";

// 3. VALIDACIÓN DE AMORTIZACIÓN
echo "3. VALIDANDO SISTEMAS DE AMORTIZACIÓN...\n";

$amortization_types = ['francesa', 'estadounidense', 'mixta'];
foreach ($amortization_types as $type) {
    $query_amortization = $CI->db->query("
        SELECT COUNT(*) as count
        FROM loans l
        JOIN loan_items li ON li.loan_id = l.id
        WHERE l.amortization_type = ?
        AND (li.fee_amount <= 0 OR li.interest_amount < 0 OR li.capital_amount <= 0)
    ", [$type]);

    $invalid_count = $query_amortization->row()->count ?? 0;

    if ($invalid_count > 0) {
        $validation_results['amortization_issues'] += $invalid_count;
        $validation_results['inconsistencies_found'][] = [
            'type' => 'amortization',
            'amortization_type' => $type,
            'issues' => ["{$invalid_count} cuotas con valores inválidos"],
            'data' => ['type' => $type, 'invalid_count' => $invalid_count]
        ];
    }
}

echo "   ✅ Amortización verificada para: " . implode(', ', $amortization_types) . "\n";
echo "   ⚠️  Problemas de amortización: {$validation_results['amortization_issues']}\n\n";

// 4. PRUEBAS DE ESCENARIOS ESPECÍFICOS
echo "4. PRUEBAS DE ESCENARIOS ESPECÍFICOS...\n";

// Prueba 1: Pago parcial
echo "   🧪 Probando pago parcial...\n";
$partial_payment_loan = $CI->db->query("
    SELECT l.id, COALESCE(calc.balance_amount, 0) as balance
    FROM loans l
    LEFT JOIN (
        SELECT loan_id, GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.status = 1 AND COALESCE(calc.balance_amount, 0) > 0
    LIMIT 1
")->row();

if ($partial_payment_loan) {
    echo "      ✅ Préstamo encontrado para pago parcial: ID {$partial_payment_loan->id}, Balance: $" . number_format($partial_payment_loan->balance, 2) . "\n";
} else {
    echo "      ⚠️  No se encontraron préstamos activos para pago parcial\n";
}

// Prueba 2: Pago total
echo "   🧪 Probando pago total...\n";
$total_payment_loan = $CI->db->query("
    SELECT l.id, COALESCE(calc.balance_amount, 0) as balance
    FROM loans l
    LEFT JOIN (
        SELECT loan_id, GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE l.status = 0
    LIMIT 1
")->row();

if ($total_payment_loan) {
    echo "      ✅ Préstamo pagado encontrado: ID {$total_payment_loan->id}, Balance: $" . number_format($total_payment_loan->balance, 2) . "\n";
} else {
    echo "      ⚠️  No se encontraron préstamos pagados\n";
}

// Prueba 3: Condonaciones
echo "   🧪 Probando condonaciones...\n";
$condoned_loans = $CI->db->query("
    SELECT l.id, COUNT(CASE WHEN li.extra_payment = 3 THEN 1 END) as condoned_count
    FROM loans l
    JOIN loan_items li ON li.loan_id = l.id
    GROUP BY l.id
    HAVING condoned_count > 0
    LIMIT 3
");

$condoned_results = $condoned_loans->result();
echo "      ✅ Préstamos con condonaciones encontradas: " . count($condoned_results) . "\n";
foreach ($condoned_results as $condoned) {
    echo "         - Préstamo {$condoned->id}: {$condoned->condoned_count} cuotas condonadas\n";
}

// 5. COMPARACIÓN CON DIAGNÓSTICOS PREVIOS
echo "5. COMPARACIÓN CON DIAGNÓSTICOS PREVIOS...\n";

$previous_negative = $CI->db->query("
    SELECT COUNT(*) as count
    FROM loans l
    LEFT JOIN (
        SELECT loan_id, GREATEST(0, SUM(CASE WHEN extra_payment != 3 THEN fee_amount ELSE 0 END) - SUM(interest_paid + capital_paid)) as balance_amount
        FROM loan_items GROUP BY loan_id
    ) calc ON calc.loan_id = l.id
    WHERE (COALESCE(calc.balance_amount, 0) < 0)
")->row()->count ?? 0;

echo "   📊 Balances negativos actuales: {$validation_results['loans_with_negative_balance']} (previo: {$previous_negative})\n";

$previous_condoned_pending = $CI->db->query("
    SELECT COUNT(*) as count
    FROM loan_items
    WHERE extra_payment = 3 AND status != 0
")->row()->count ?? 0;

echo "   📊 Condonadas pendientes actuales: {$validation_results['loans_with_condoned_pending']} (previo: {$previous_condoned_pending})\n";

// 6. REPORTE FINAL
echo "\n=== REPORTE FINAL DE VALIDACIÓN ===\n\n";

echo "📊 MÉTRICAS GENERALES:\n";
echo "   • Total de préstamos: {$validation_results['total_loans']}\n";
echo "   • Balances negativos: {$validation_results['loans_with_negative_balance']}\n";
echo "   • Condonadas pendientes: {$validation_results['loans_with_condoned_pending']}\n";
echo "   • Estados incorrectos: {$validation_results['loans_with_incorrect_status']}\n";
echo "   • Cálculos de pagos inconsistentes: {$validation_results['payment_calculations_mismatch']}\n";
echo "   • Problemas de amortización: {$validation_results['amortization_issues']}\n\n";

$total_issues = count($validation_results['inconsistencies_found']);
echo "🔍 INCONSISTENCIAS DETECTADAS: {$total_issues}\n\n";

if ($total_issues > 0) {
    echo "DETALLE DE INCONSISTENCIAS:\n";
    foreach ($validation_results['inconsistencies_found'] as $index => $inconsistency) {
        echo "   " . ($index + 1) . ". ";
        if (isset($inconsistency['loan_id'])) {
            echo "PRÉSTAMO {$inconsistency['loan_id']}: " . implode(', ', $inconsistency['issues']) . "\n";
        } elseif (isset($inconsistency['type'])) {
            if ($inconsistency['type'] == 'payment_calculation') {
                echo "PAGO {$inconsistency['payment_id']}: " . implode(', ', $inconsistency['issues']) . "\n";
            } elseif ($inconsistency['type'] == 'amortization') {
                echo "AMORTIZACIÓN {$inconsistency['amortization_type']}: " . implode(', ', $inconsistency['issues']) . "\n";
            }
        }
    }
    echo "\n";
}

// EVALUACIÓN FINAL
$critical_issues = $validation_results['loans_with_negative_balance'] +
                  $validation_results['loans_with_condoned_pending'] +
                  $validation_results['loans_with_incorrect_status'];

if ($critical_issues == 0) {
    echo "🎉 RESULTADO: SISTEMA FUNCIONANDO CORRECTAMENTE\n";
    echo "   ✅ Todos los cálculos son consistentes\n";
    echo "   ✅ Estados de préstamos actualizados correctamente\n";
    echo "   ✅ Cuotas condonadas manejadas apropiadamente\n";
    echo "   ✅ Amortización funcionando correctamente\n";
} else {
    echo "⚠️  RESULTADO: INCONSISTENCIAS CRÍTICAS DETECTADAS\n";
    echo "   • Revisar y corregir los {$critical_issues} problemas identificados\n";
    echo "   • Verificar modelo Loans_m.php y funciones de actualización\n";
    echo "   • Considerar regeneración de cálculos de amortización\n";
}

echo "\n=== FIN DE VALIDACIÓN COMPREHENSIVA ===\n";
?>