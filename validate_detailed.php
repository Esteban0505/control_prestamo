<?php
// Script detallado para validar pagos y límites del cliente con DNI 1152675687

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

$dni = "1152675687";

// Buscar cliente por DNI
$sql_customer = "SELECT id, first_name, last_name, tipo_cliente FROM customers WHERE dni = '$dni'";
$result_customer = $conn->query($sql_customer);

if ($result_customer->num_rows > 0) {
    $customer = $result_customer->fetch_assoc();
    $customer_id = $customer['id'];
    $tipo_cliente = $customer['tipo_cliente'];

    echo "Cliente encontrado: " . $customer['first_name'] . " " . $customer['last_name'] . " (ID: $customer_id, Tipo: $tipo_cliente)\n\n";

    // Obtener todos los préstamos del cliente
    $sql_loans = "SELECT id, credit_amount, status, date FROM loans WHERE customer_id = $customer_id ORDER BY date DESC";
    $result_loans = $conn->query($sql_loans);

    echo "Préstamos del cliente:\n";
    $completed_count = 0;
    while ($loan = $result_loans->fetch_assoc()) {
        $loan_id = $loan['id'];
        echo "- Préstamo ID: $loan_id, Monto: " . number_format($loan['credit_amount'], 0, ',', '.') . ", Fecha: " . $loan['date'] . ", Status: " . ($loan['status'] == 1 ? 'Activo' : 'Completado') . "\n";

        // Verificar si está completado
        $sql_balance = "SELECT SUM(COALESCE(balance, 0)) as total_balance FROM loan_items WHERE loan_id = $loan_id";
        $balance_result = $conn->query($sql_balance);
        $balance = $balance_result->fetch_assoc()['total_balance'];

        if ($balance == 0) {
            $completed_count++;
            echo "  -> Completado (balance: 0)\n";
        } else {
            echo "  -> Pendiente (balance: " . number_format($balance, 2, ',', '.') . ")\n";
        }

        // Cuotas pagadas
        $sql_paid_items = "SELECT COUNT(*) as paid_count FROM loan_items WHERE loan_id = $loan_id AND status = 0";
        $paid_result = $conn->query($sql_paid_items);
        $paid_count = $paid_result->fetch_assoc()['paid_count'];

        $sql_total_items = "SELECT COUNT(*) as total_count FROM loan_items WHERE loan_id = $loan_id";
        $total_result = $conn->query($sql_total_items);
        $total_count = $total_result->fetch_assoc()['total_count'];

        echo "  -> Cuotas pagadas: $paid_count / $total_count\n";
    }

    echo "\nResumen:\n";
    echo "Total préstamos completados: $completed_count\n";

    // Calcular límite
    if ($tipo_cliente == 'especial') {
        $limit = 999999999; // Sin límite
        echo "Límite (cliente especial): Sin límite\n";
    } else {
        if ($completed_count >= 2) {
            $limit = 5000000;
        } elseif ($completed_count == 1) {
            $limit = 1200000;
        } else {
            $limit = 500000;
        }
        echo "Límite calculado: " . number_format($limit, 0, ',', '.') . "\n";
    }

    // Verificar pagos realizados
    $sql_payments = "SELECT COUNT(*) as total_payments, SUM(amount) as total_amount FROM payments p JOIN loans l ON p.loan_id = l.id WHERE l.customer_id = $customer_id";
    $payments_result = $conn->query($sql_payments);
    $payments = $payments_result->fetch_assoc();

    echo "\nPagos realizados:\n";
    echo "Total pagos: " . $payments['total_payments'] . "\n";
    echo "Monto total pagado: " . number_format($payments['total_amount'], 2, ',', '.') . "\n";

} else {
    echo "Cliente con DNI $dni no encontrado en la base de datos.\n";
}

$conn->close();
?>