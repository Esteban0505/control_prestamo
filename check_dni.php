<?php
$conn = new mysqli('localhost', 'root', '', 'prestamobd');
$result = $conn->query('SELECT c.id, c.dni, c.first_name, c.last_name, l.id as loan_id, l.assigned_user_id FROM customers c LEFT JOIN loans l ON c.id = l.customer_id WHERE c.dni = "111111113"');
while ($row = $result->fetch_assoc()) {
    echo 'Customer ID: ' . $row['id'] . ', DNI: ' . $row['dni'] . ', Name: ' . $row['first_name'] . ' ' . $row['last_name'] . ', Loan ID: ' . $row['loan_id'] . ', assigned_user_id: ' . $row['assigned_user_id'] . PHP_EOL;
}
$conn->close();
?>