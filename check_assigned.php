<?php
$conn = new mysqli('localhost', 'root', '', 'prestamobd');
$result = $conn->query('SELECT l.id, l.assigned_user_id FROM loans l JOIN customers c ON c.id = l.customer_id WHERE c.dni = "1152675687"');
while ($row = $result->fetch_assoc()) {
    echo 'Loan ID: ' . $row['id'] . ', assigned_user_id: ' . $row['assigned_user_id'] . PHP_EOL;
}
$conn->close();
?>