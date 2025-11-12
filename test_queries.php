<?php
// Test de queries para validar datos de cobradores y reportes
define('BASEPATH', true);

// Configuración de la base de datos
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'prestamobd';
$dbdriver = 'mysqli';

try {
    $conn = new mysqli($hostname, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    echo "Conexión exitosa a la base de datos '$database'.\n\n";

    // 1) Número total de cobradores en la tabla users
    echo "1) Número total de cobradores en la tabla users:\n";
    $query1 = "SELECT COUNT(*) as total_cobradores FROM users WHERE role = 'operador'";
    $result1 = $conn->query($query1);
    if ($result1) {
        $row1 = $result1->fetch_assoc();
        echo "Total cobradores: " . $row1['total_cobradores'] . "\n";
    } else {
        echo "Error en query 1: " . $conn->error . "\n";
    }
    echo "\n";

    // 2) Cobradores con pagos realizados (status=0 en loan_items)
    echo "2) Cobradores con pagos realizados (status=0 en loan_items):\n";
    $query2 = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(li.id) as pagos_realizados
               FROM users u
               LEFT JOIN loan_items li ON u.id = li.paid_by AND li.status = b'0'
               WHERE u.role = 'operador'
               GROUP BY u.id, u.first_name, u.last_name
               ORDER BY pagos_realizados DESC";
    $result2 = $conn->query($query2);
    if ($result2) {
        while ($row2 = $result2->fetch_assoc()) {
            echo "Cobrador: " . $row2['name'] . " - Pagos realizados: " . $row2['pagos_realizados'] . "\n";
        }
    } else {
        echo "Error en query 2: " . $conn->error . "\n";
    }
    echo "\n";

    // 3) Datos de pagos de Esteban Marin específicamente
    echo "3) Datos de pagos de Esteban Marin específicamente:\n";
    $query3 = "SELECT li.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, l.credit_amount as loan_amount
               FROM loan_items li
               JOIN loans l ON li.loan_id = l.id
               JOIN customers c ON l.customer_id = c.id
               JOIN users u ON li.paid_by = u.id
               WHERE CONCAT(u.first_name, ' ', u.last_name) = 'Esteban Marin' AND li.status = b'0'
               ORDER BY li.pay_date DESC";
    $result3 = $conn->query($query3);
    if ($result3) {
        while ($row3 = $result3->fetch_assoc()) {
            echo "ID: " . $row3['id'] . " - Monto: " . $row3['fee_amount'] . " - Fecha: " . $row3['pay_date'] . " - Cliente: " . $row3['customer_name'] . " - Préstamo: " . $row3['loan_amount'] . "\n";
        }
    } else {
        echo "Error en query 3: " . $conn->error . "\n";
    }
    echo "\n";

    // 4) Conteo de registros en loan_items por cobrador
    echo "4) Conteo de registros en loan_items por cobrador:\n";
    $query4 = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(li.id) as total_registros
               FROM users u
               LEFT JOIN loan_items li ON u.id = li.paid_by
               WHERE u.role = 'operador'
               GROUP BY u.id, u.first_name, u.last_name
               ORDER BY total_registros DESC";
    $result4 = $conn->query($query4);
    if ($result4) {
        while ($row4 = $result4->fetch_assoc()) {
            echo "Cobrador: " . $row4['name'] . " - Total registros: " . $row4['total_registros'] . "\n";
        }
    } else {
        echo "Error en query 4: " . $conn->error . "\n";
    }
    echo "\n";

    // 5) Verificar si hay datos de intereses pagados
    echo "5) Verificar si hay datos de intereses pagados:\n";
    $query5 = "SELECT li.id, li.fee_amount as amount, li.interest_amount, li.pay_date as payment_date, CONCAT(u.first_name, ' ', u.last_name) as cobrador, CONCAT(c.first_name, ' ', c.last_name) as customer_name
               FROM loan_items li
               JOIN users u ON li.paid_by = u.id
               JOIN loans l ON li.loan_id = l.id
               JOIN customers c ON l.customer_id = c.id
               WHERE li.interest_amount > 0 AND li.status = b'0'
               ORDER BY li.pay_date DESC
               LIMIT 10";
    $result5 = $conn->query($query5);
    if ($result5) {
        while ($row5 = $result5->fetch_assoc()) {
            echo "ID: " . $row5['id'] . " - Monto total: " . $row5['amount'] . " - Interés: " . $row5['interest_amount'] . " - Fecha: " . $row5['payment_date'] . " - Cobrador: " . $row5['cobrador'] . " - Cliente: " . $row5['customer_name'] . "\n";
        }
    } else {
        echo "Error en query 5: " . $conn->error . "\n";
    }

    $conn->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>