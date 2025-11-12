<?php
// Script para ejecutar queries de prueba en la base de datos
define('BASEPATH', dirname(__FILE__));

$config = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'prestamobd',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => TRUE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
];

try {
    $conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");

    echo "=== VALIDACIÓN DE DATOS PARA REPORTES ===\n\n";

    // 1. Contar cobradores
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'cobrador'");
    $row = $result->fetch_assoc();
    echo "1. Total cobradores registrados: " . $row['total'] . "\n";

    // 2. Cobradores con pagos realizados
    $result = $conn->query("SELECT CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(li.id) as pagos FROM users u LEFT JOIN loan_items li ON u.id = li.paid_by AND li.status = 0 WHERE u.role = 'cobrador' GROUP BY u.id ORDER BY pagos DESC");
    echo "2. Cobradores con pagos realizados:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['name']}: {$row['pagos']} pagos\n";
    }

    // 3. Datos específicos de Esteban Marin
    $result = $conn->query("SELECT COUNT(li.id) as total_pagos, SUM(li.interest_paid) as total_intereses, SUM(li.fee_amount) as total_cobrado FROM loan_items li JOIN users u ON li.paid_by = u.id WHERE CONCAT(u.first_name, ' ', u.last_name) = 'Esteban Marin' AND li.status = 0");
    $row = $result->fetch_assoc();
    echo "3. Datos de Esteban Marin:\n";
    echo "   - Total pagos: {$row['total_pagos']}\n";
    echo "   - Total intereses: $" . number_format($row['total_intereses'], 2) . "\n";
    echo "   - Total cobrado: $" . number_format($row['total_cobrado'], 2) . "\n";

    // 4. Conteo por cobrador
    $result = $conn->query("SELECT CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(li.id) as registros FROM users u LEFT JOIN loan_items li ON u.id = li.paid_by WHERE u.role = 'cobrador' GROUP BY u.id ORDER BY registros DESC");
    echo "4. Registros por cobrador:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['name']}: {$row['registros']} registros\n";
    }

    // 5. Verificar intereses pagados
    $result = $conn->query("SELECT COUNT(*) as total_con_intereses FROM loan_items li JOIN users u ON li.paid_by = u.id WHERE li.status = 0 AND li.interest_paid > 0 AND u.role = 'cobrador'");
    $row = $result->fetch_assoc();
    echo "5. Pagos con intereses registrados: {$row['total_con_intereses']}\n";

    // 6. Verificar LEFT JOIN funciona
    $result = $conn->query("SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, COALESCE(COUNT(li.id), 0) as pagos FROM users u LEFT JOIN loan_items li ON u.id = li.paid_by AND li.status = 0 WHERE u.role = 'cobrador' GROUP BY u.id ORDER BY u.first_name");
    echo "6. Verificación LEFT JOIN (todos los cobradores):\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['name']} (ID: {$row['id']}): {$row['pagos']} pagos\n";
    }

    $conn->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>