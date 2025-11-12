<?php
// Script PHP para consultar loan_items del loan_id 141 sin prompt de contraseña
// Usando credenciales de CodeIgniter

$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'prestamobd';

// Crear conexión
$conn = new mysqli($hostname, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Consulta SQL
$sql = "SELECT id, loan_id, num_quota, date, fee_amount, interest_amount, capital_amount, balance, status, interest_paid, capital_paid, extra_payment FROM loan_items WHERE loan_id = 141 ORDER BY num_quota";

// Ejecutar consulta
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Mostrar encabezados
    echo "id\tloan_id\tnum_quota\tdate\t\tfee_amount\tinterest_amount\tcapital_amount\tbalance\tstatus\tinterest_paid\tcapital_paid\textra_payment\n";
    echo str_repeat("-", 150) . "\n";

    // Mostrar datos
    while($row = $result->fetch_assoc()) {
        echo $row["id"] . "\t" .
             $row["loan_id"] . "\t" .
             $row["num_quota"] . "\t\t" .
             $row["date"] . "\t" .
             $row["fee_amount"] . "\t" .
             $row["interest_amount"] . "\t\t" .
             $row["capital_amount"] . "\t\t" .
             $row["balance"] . "\t" .
             $row["status"] . "\t" .
             $row["interest_paid"] . "\t" .
             $row["capital_paid"] . "\t" .
             $row["extra_payment"] . "\n";
    }
} else {
    echo "No se encontraron resultados para loan_id = 141\n";
}

// Cerrar conexión
$conn->close();
?>