<?php
// Script para validar el límite del cliente con DNI 1152675687

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

    echo "Cliente encontrado: " . $customer['first_name'] . " " . $customer['last_name'] . " (ID: $customer_id)\n";

    // Contar préstamos completados (balance = 0)
    $sql_completed = "
        SELECT COUNT(*) as completed_count
        FROM loans l
        JOIN loan_items li ON li.loan_id = l.id
        WHERE l.customer_id = $customer_id
        GROUP BY l.id
        HAVING SUM(COALESCE(li.balance, 0)) = 0
    ";
    $result_completed = $conn->query($sql_completed);
    $completed_count = $result_completed->num_rows;

    echo "Préstamos completados: $completed_count\n";

    // Calcular límite
    if ($tipo_cliente == 'especial') {
        $limit = 999999999; // Sin límite
    } else {
        if ($completed_count >= 2) {
            $limit = 5000000;
        } elseif ($completed_count == 1) {
            $limit = 1200000;
        } else {
            $limit = 500000;
        }
    }

    echo "Límite actual: " . number_format($limit, 0, ',', '.') . "\n";

    if ($limit == 500000) {
        echo "VALIDACIÓN: El límite está en 500.000 como indicado.\n";
    } else {
        echo "VALIDACIÓN: El límite NO está en 500.000. Está en " . number_format($limit, 0, ',', '.') . ".\n";
    }

} else {
    echo "Cliente con DNI $dni no encontrado en la base de datos.\n";
}

$conn->close();
?>