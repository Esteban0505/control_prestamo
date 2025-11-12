<?php
// Script para validar que la corrección del pago no completo funcionó

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "prestamobd";

echo "=== VALIDACIÓN DE CORRECCIÓN: PAGO NO COMPLETO ===\n\n";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    // 1. Verificar que el campo status ahora es tinyint(1)
    echo "1. VERIFICANDO CAMBIO DE ESQUEMA:\n";
    $result = $conn->query("DESCRIBE loan_items");
    $statusFieldFound = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'status') {
            echo "   Campo status: " . $row['Type'] . " (esperado: tinyint(1))\n";
            $statusFieldFound = true;
            if (strpos($row['Type'], 'tinyint(1)') !== false) {
                echo "   ✅ CORRECTO: El campo status ahora es tinyint(1)\n";
            } else {
                echo "   ❌ ERROR: El campo status no se cambió correctamente\n";
            }
            break;
        }
    }

    if (!$statusFieldFound) {
        echo "   ❌ ERROR: Campo status no encontrado\n";
    }

    echo "\n";

    // 2. Verificar datos del préstamo #167
    echo "2. VERIFICANDO DATOS DEL PRÉSTAMO #167:\n";
    $result = $conn->query("SELECT id, customer_id, credit_amount, status FROM loans WHERE id = 167");
    if ($result && $result->num_rows > 0) {
        $loan = $result->fetch_assoc();
        echo "   Préstamo encontrado: ID {$loan['id']}, Cliente {$loan['customer_id']}, Monto \${$loan['credit_amount']}\n";
        echo "   Estado del préstamo: {$loan['status']}\n";
    } else {
        echo "   ❌ ERROR: Préstamo #167 no encontrado\n";
    }

    echo "\n";

    // 3. Verificar cuotas del préstamo
    echo "3. VERIFICANDO CUOTAS DEL PRÉSTAMO:\n";
    $result = $conn->query("SELECT id, num_quota, fee_amount, interest_amount, capital_amount, balance, status, interest_paid, capital_paid FROM loan_items WHERE loan_id = 167 ORDER BY num_quota");
    if ($result && $result->num_rows > 0) {
        echo "   Cuotas encontradas: {$result->num_rows}\n";
        while ($quota = $result->fetch_assoc()) {
            $statusText = '';
            switch ($quota['status']) {
                case 0: $statusText = 'Pagada'; break;
                case 1: $statusText = 'Pendiente'; break;
                case 3: $statusText = 'Parcial'; break;
                case 4: $statusText = 'Incompleto'; break;
                default: $statusText = 'Desconocido'; break;
            }

            echo "   Cuota #{$quota['num_quota']} (ID: {$quota['id']}): \${$quota['fee_amount']} - Estado: {$statusText} - Balance: \${$quota['balance']}\n";
            echo "     Interés: \${$quota['interest_amount']} (pagado: \${$quota['interest_paid']})\n";
            echo "     Capital: \${$quota['capital_amount']} (pagado: \${$quota['capital_paid']})\n";
        }
    } else {
        echo "   ❌ ERROR: No se encontraron cuotas para el préstamo #167\n";
    }

    echo "\n";

    // 4. Verificar si hay pagos registrados para este préstamo
    echo "4. VERIFICANDO PAGOS REGISTRADOS:\n";
    $result = $conn->query("SELECT id, payment_date, amount, payment_type, custom_payment_type FROM payments WHERE loan_id = 167 ORDER BY payment_date DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "   Últimos pagos encontrados: {$result->num_rows}\n";
        while ($payment = $result->fetch_assoc()) {
            echo "   Pago ID {$payment['id']}: \${$payment['amount']} - Tipo: {$payment['payment_type']} - Custom: {$payment['custom_payment_type']} - Fecha: {$payment['payment_date']}\n";
        }
    } else {
        echo "   No se encontraron pagos registrados para este préstamo\n";
    }

    echo "\n";

    // 5. Verificar si hay cuotas con status=4 (incompleto)
    echo "5. VERIFICANDO CUOTAS CON STATUS INCOMPLETO:\n";
    $result = $conn->query("SELECT COUNT(*) as incomplete_count FROM loan_items WHERE loan_id = 167 AND status = 4");
    if ($result) {
        $count = $result->fetch_assoc()['incomplete_count'];
        echo "   Cuotas con status incompleto (4): {$count}\n";
        if ($count > 0) {
            echo "   ✅ ENCONTRADAS: Hay {$count} cuota(s) con status incompleto\n";
        } else {
            echo "   ℹ️  No hay cuotas con status incompleto (aún no se ha probado la funcionalidad)\n";
        }
    }

    echo "\n=== VALIDACIÓN COMPLETADA ===\n";
    echo "\nINSTRUCCIONES PARA PROBAR LA FUNCIONALIDAD:\n";
    echo "1. Ve a http://localhost/prestamo-1/admin/payments\n";
    echo "2. Busca el cliente 'martin francisco' (préstamo #167)\n";
    echo "3. Selecciona la cuota #2 (ID: 3111)\n";
    echo "4. Elige 'Monto personalizado' con tipo 'Pago no completo'\n";
    echo "5. Ingresa $100,000.00\n";
    echo "6. Ejecuta el pago y verifica que:\n";
    echo "   - Se genera el ticket correctamente\n";
    echo "   - La cuota queda con status=4 (incompleto)\n";
    echo "   - El saldo no pagado se distribuye a cuotas futuras\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>