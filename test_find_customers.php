<?php
/**
 * Script para encontrar clientes válidos en la base de datos
 */

// Configuración de base de datos
$db_config = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'prestamobd'
];

$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "=== BUSCANDO CLIENTES CON PRÉSTAMOS ACTIVOS ===\n\n";

// Buscar clientes con préstamos activos
$query = "
    SELECT
        c.id as customer_id,
        c.dni,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        l.id as loan_id,
        l.status as loan_status,
        l.credit_amount,
        COUNT(li.id) as total_quotas,
        COUNT(CASE WHEN li.status = 1 THEN 1 END) as pending_quotas
    FROM customers c
    INNER JOIN loans l ON l.customer_id = c.id
    LEFT JOIN loan_items li ON li.loan_id = l.id
    WHERE l.status = 1
    GROUP BY c.id, l.id
    HAVING pending_quotas > 0
    ORDER BY c.dni
    LIMIT 10
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Clientes encontrados con préstamos activos:\n\n";

    while ($row = $result->fetch_assoc()) {
        echo "DNI: {$row['dni']}\n";
        echo "Nombre: {$row['customer_name']}\n";
        echo "Préstamo ID: {$row['loan_id']}\n";
        echo "Monto: $" . number_format($row['credit_amount'], 2) . "\n";
        echo "Cuotas totales: {$row['total_quotas']}\n";
        echo "Cuotas pendientes: {$row['pending_quotas']}\n";
        echo "---\n";
    }
} else {
    echo "No se encontraron clientes con préstamos activos.\n";
}

$conn->close();

echo "\n=== FIN DE BÚSQUEDA ===\n";