<?php
/**
 * Script para corregir balances y status de préstamos
 * Ejecutar desde línea de comandos: php fix_loan_items_ids.php
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "prestamobd";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "=== CORRECCIÓN DE BALANCES Y STATUS DE PRÉSTAMOS ===\n\n";

// 1. Obtener todos los préstamos
$result = $conn->query("SELECT id, customer_id, credit_amount, status FROM loans ORDER BY id");

if ($result->num_rows > 0) {
    while($loan = $result->fetch_assoc()) {
        $loan_id = $loan['id'];
        $customer_id = $loan['customer_id'];
        $credit_amount = $loan['credit_amount'];

        echo "Procesando préstamo ID: $loan_id (Cliente: $customer_id, Monto: $credit_amount)\n";

        // 2. Obtener todas las cuotas del préstamo ordenadas por número de cuota
        $items_result = $conn->query("SELECT id, num_quota, capital_amount, balance, status FROM loan_items WHERE loan_id = $loan_id ORDER BY num_quota");

        if ($items_result->num_rows > 0) {
            $running_balance = $credit_amount; // Balance inicial = monto del préstamo
            $total_paid = 0;
            $pending_count = 0;

            while($item = $items_result->fetch_assoc()) {
                $item_id = $item['id'];
                $num_quota = $item['num_quota'];
                $capital_amount = $item['capital_amount'];
                $current_balance = $item['balance'];
                $status = $item['status'];

                // Calcular el balance correcto para esta cuota
                $correct_balance = $running_balance - $capital_amount;

                // Si la cuota está pagada (status=0), el balance debería ser 0 o el correcto
                if ($status == 0) {
                    $correct_balance = 0;
                    $total_paid += $capital_amount;
                } else {
                    $pending_count++;
                }

                // Actualizar el balance si es diferente
                if (abs($current_balance - $correct_balance) > 0.01) { // Tolerancia para decimales
                    $conn->query("UPDATE loan_items SET balance = $correct_balance WHERE id = $item_id");
                    echo "  ✓ Cuota $num_quota: Balance corregido de $current_balance a $correct_balance\n";
                } else {
                    echo "  - Cuota $num_quota: Balance correcto ($current_balance)\n";
                }

                // Reducir el balance acumulado solo si la cuota no está pagada
                if ($status != 0) {
                    $running_balance = $correct_balance;
                }
            }

            // 3. Calcular balance total del préstamo
            $total_balance_result = $conn->query("SELECT SUM(COALESCE(balance, 0)) as total_balance FROM loan_items WHERE loan_id = $loan_id");
            $total_balance_row = $total_balance_result->fetch_assoc();
            $total_balance = $total_balance_row['total_balance'];

            // 4. Actualizar status del préstamo basado en el balance real
            $new_status = ($total_balance <= 0) ? 0 : 1; // 0 = completado, 1 = activo
            $status_changed = ($loan['status'] != $new_status);

            if ($status_changed) {
                $conn->query("UPDATE loans SET status = $new_status, balance = $total_balance WHERE id = $loan_id");
                echo "  ✓ Status del préstamo actualizado: {$loan['status']} -> $new_status\n";
            } else {
                $conn->query("UPDATE loans SET balance = $total_balance WHERE id = $loan_id");
                echo "  - Status del préstamo correcto: {$loan['status']}\n";
            }

            // 5. Actualizar status del cliente
            $customer_status = ($new_status == 1) ? 1 : 0; // Si hay préstamo activo, cliente tiene préstamo
            $conn->query("UPDATE customers SET loan_status = $customer_status WHERE id = $customer_id");

            echo "  → Balance total: $total_balance, Cuotas pendientes: $pending_count\n\n";

        } else {
            echo "  ⚠ No se encontraron cuotas para este préstamo\n\n";
        }
    }
} else {
    echo "No se encontraron préstamos\n";
}

// 6. Verificar y corregir límites de crédito
echo "=== VERIFICACIÓN DE LÍMITES DE CRÉDITO ===\n";

$customers_result = $conn->query("SELECT c.id, c.dni, c.first_name, c.last_name, c.tipo_cliente FROM customers c ORDER BY c.id");

while($customer = $customers_result->fetch_assoc()) {
    $customer_id = $customer['id'];
    $tipo_cliente = $customer['tipo_cliente'];

    // Contar préstamos completados (balance = 0)
    $completed_result = $conn->query("
        SELECT COUNT(*) as completed_count
        FROM loans l
        LEFT JOIN loan_items li ON li.loan_id = l.id
        WHERE l.customer_id = $customer_id
        GROUP BY l.id
        HAVING SUM(COALESCE(li.balance, 0)) = 0
    ");

    $completed_count = 0;
    while ($completed_result->fetch_assoc()) {
        $completed_count++;
    }

    // Calcular límite correcto
    if ($tipo_cliente == 'especial') {
        $correct_limit = 999999999;
    } elseif ($completed_count >= 2) {
        $correct_limit = 5000000;
    } elseif ($completed_count == 1) {
        $correct_limit = 1200000;
    } else {
        $correct_limit = 500000;
    }

    echo "Cliente {$customer['dni']} ({$customer['first_name']} {$customer['last_name']}): Tipo=$tipo_cliente, Completados=$completed_count, Límite=$correct_limit\n";
}

echo "\n=== CORRECCIÓN COMPLETADA ===\n";
echo "Los balances y status de préstamos han sido corregidos.\n";
echo "Los límites de crédito ahora se calcularán correctamente.\n";

$conn->close();
?>