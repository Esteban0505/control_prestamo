<?php
// Script para corregir balances de préstamos donde todas las cuotas están pagadas

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

// Buscar cliente
$sql_customer = "SELECT id FROM customers WHERE dni = '$dni'";
$result_customer = $conn->query($sql_customer);
$customer = $result_customer->fetch_assoc();
$customer_id = $customer['id'];

echo "Corrigiendo balances para cliente ID: $customer_id\n\n";

// Obtener préstamos del cliente
$sql_loans = "SELECT id FROM loans WHERE customer_id = $customer_id";
$result_loans = $conn->query($sql_loans);

while ($loan = $result_loans->fetch_assoc()) {
    $loan_id = $loan['id'];

    // Verificar si todas las cuotas están pagadas (status=0)
    $sql_check = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as paid FROM loan_items WHERE loan_id = $loan_id";
    $check_result = $conn->query($sql_check);
    $check = $check_result->fetch_assoc();

    if ($check['total'] == $check['paid'] && $check['total'] > 0) {
        echo "Préstamo $loan_id: Todas las cuotas pagadas. Corrigiendo balances...\n";

        // Poner balance=0 para todas las cuotas
        $conn->query("UPDATE loan_items SET balance = 0 WHERE loan_id = $loan_id");

        // Marcar préstamo como completado
        $conn->query("UPDATE loans SET status = 0 WHERE id = $loan_id");

        echo "Balances corregidos para préstamo $loan_id\n";
    } else {
        echo "Préstamo $loan_id: No todas las cuotas pagadas (pagadas: {$check['paid']}/{$check['total']})\n";
    }
}

echo "\nCorrección completada.\n";

$conn->close();
?>