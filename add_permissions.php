<?php
/**
 * Script para agregar permisos granulares a la base de datos
 * Ejecutar desde el navegador o línea de comandos
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'prestamobd';

try {
    $db = new mysqli($host, $user, $password, $database);

    if ($db->connect_error) {
        throw new Exception('Error de conexión: ' . $db->connect_error);
    }

    echo "<h2>Agregando Permisos Granulares al Sistema</h2>";
    echo "<pre>";

    // Verificar tabla user_permissions
    $result = $db->query('SHOW TABLES LIKE "user_permissions"');
    if ($result->num_rows == 0) {
        throw new Exception('La tabla user_permissions no existe.');
    }
    echo "✓ Tabla user_permissions existe\n";

    // Verificar estructura de tabla
    $result = $db->query('DESCRIBE user_permissions');
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    if (!in_array('user_id', $columns) || !in_array('permission_name', $columns) || !in_array('value', $columns)) {
        throw new Exception('La estructura de la tabla user_permissions no es correcta.');
    }
    echo "✓ Estructura de tabla correcta\n";

    // Verificar usuarios existentes
    $result = $db->query('SELECT id, first_name, last_name FROM users ORDER BY id');
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo "✓ Usuarios encontrados: " . count($users) . "\n";

    // Permisos para Clientes
    $client_permissions = [
        ['customers_list', 1], ['customers_overdue', 1], // Admin
        ['customers_list', 1], ['customers_overdue', 1], // Operador
        ['customers_list', 1], ['customers_overdue', 0], // Viewer
        ['customers_list', 1], ['customers_overdue', 0], // Usuario 4
        ['customers_list', 1], ['customers_overdue', 0]  // Usuario 5
    ];

    // Permisos para Reportes
    $report_permissions = [
        ['reports_collector_commissions', 1], ['reports_admin_commissions', 1], ['reports_general_customer', 1], // Admin
        ['reports_collector_commissions', 1], ['reports_admin_commissions', 1], ['reports_general_customer', 1], // Operador
        ['reports_collector_commissions', 0], ['reports_admin_commissions', 0], ['reports_general_customer', 1], // Viewer
        ['reports_collector_commissions', 0], ['reports_admin_commissions', 0], ['reports_general_customer', 1], // Usuario 4
        ['reports_collector_commissions', 0], ['reports_admin_commissions', 0], ['reports_general_customer', 1]  // Usuario 5
    ];

    // Permisos para Configuración
    $config_permissions = [
        ['config_edit_data', 1], ['config_change_password', 1], // Admin
        ['config_edit_data', 1], ['config_change_password', 1], // Operador
        ['config_edit_data', 0], ['config_change_password', 1], // Viewer
        ['config_edit_data', 0], ['config_change_password', 1], // Usuario 4
        ['config_edit_data', 0], ['config_change_password', 1]  // Usuario 5
    ];

    $total_inserted = 0;

    // Insertar permisos para cada usuario
    foreach ($users as $index => $user) {
        $user_id = $user['id'];

        // Permisos de Clientes
        if (isset($client_permissions[$index * 2])) {
            $stmt = $db->prepare('INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
            $stmt->bind_param('isi', $user_id, $client_permissions[$index * 2][0], $client_permissions[$index * 2][1]);
            $stmt->execute();
            $total_inserted += $stmt->affected_rows;
            $stmt->close();

            $stmt = $db->prepare('INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
            $stmt->bind_param('isi', $user_id, $client_permissions[$index * 2 + 1][0], $client_permissions[$index * 2 + 1][1]);
            $stmt->execute();
            $total_inserted += $stmt->affected_rows;
            $stmt->close();
        }

        // Permisos de Reportes
        if (isset($report_permissions[$index * 3])) {
            for ($i = 0; $i < 3; $i++) {
                $stmt = $db->prepare('INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
                $stmt->bind_param('isi', $user_id, $report_permissions[$index * 3 + $i][0], $report_permissions[$index * 3 + $i][1]);
                $stmt->execute();
                $total_inserted += $stmt->affected_rows;
                $stmt->close();
            }
        }

        // Permisos de Configuración
        if (isset($config_permissions[$index * 2])) {
            $stmt = $db->prepare('INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
            $stmt->bind_param('isi', $user_id, $config_permissions[$index * 2][0], $config_permissions[$index * 2][1]);
            $stmt->execute();
            $total_inserted += $stmt->affected_rows;
            $stmt->close();

            $stmt = $db->prepare('INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
            $stmt->bind_param('isi', $user_id, $config_permissions[$index * 2 + 1][0], $config_permissions[$index * 2 + 1][1]);
            $stmt->execute();
            $total_inserted += $stmt->affected_rows;
            $stmt->close();
        }
    }

    echo "✓ Permisos insertados: $total_inserted\n";

    // Verificar permisos agregados
    echo "\n--- VERIFICACIÓN DE PERMISOS AGREGADOS ---\n";
    $result = $db->query("
        SELECT
            u.first_name,
            u.last_name,
            up.permission_name,
            up.value
        FROM users u
        LEFT JOIN user_permissions up ON u.id = up.user_id
        WHERE up.permission_name IN (
            'customers_list', 'customers_overdue',
            'reports_collector_commissions', 'reports_admin_commissions', 'reports_general_customer',
            'config_edit_data', 'config_change_password'
        )
        ORDER BY u.id, up.permission_name
    ");

    $current_user = '';
    while ($row = $result->fetch_assoc()) {
        if ($current_user != $row['first_name'] . ' ' . $row['last_name']) {
            $current_user = $row['first_name'] . ' ' . $row['last_name'];
            echo "\n👤 {$current_user}:\n";
        }
        echo "  - {$row['permission_name']}: {$row['value']}\n";
    }

    $db->close();

    echo "\n✅ PROCESO COMPLETADO EXITOSAMENTE\n";
    echo "Los permisos granulares han sido agregados a la base de datos.\n";
    echo "Ahora puedes usar el módulo de gestión de permisos para controlar el acceso a cada submenú.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>