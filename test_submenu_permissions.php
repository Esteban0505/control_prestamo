<?php
/**
 * Script de prueba para verificar que los submenús se guardan correctamente
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

    echo "<h2>🧪 PRUEBA DE SUBMENÚS DE PERMISOS</h2>";
    echo "<pre>";

    // Verificar tabla user_permissions
    $result = $db->query('SHOW TABLES LIKE "user_permissions"');
    if ($result->num_rows == 0) {
        throw new Exception('La tabla user_permissions no existe.');
    }
    echo "✓ Tabla user_permissions existe\n";

    // Obtener un usuario de prueba
    $result = $db->query('SELECT id, first_name, last_name FROM users WHERE estado = 1 LIMIT 1');
    if ($result->num_rows == 0) {
        throw new Exception('No hay usuarios activos para probar.');
    }
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    echo "✓ Usuario de prueba: {$user['first_name']} {$user['last_name']} (ID: $user_id)\n";

    // Simular envío de permisos con submenús desde el frontend
    $permissions_to_save = [
        ['permission_name' => 'dashboard', 'value' => 1],
        ['permission_name' => 'sidebar', 'value' => 1],
        ['permission_name' => 'sidebar_back', 'value' => 1],
        ['permission_name' => 'customers', 'value' => 1],
        ['permission_name' => 'customers_list', 'value' => 1],
        ['permission_name' => 'customers_overdue', 'value' => 0],
        ['permission_name' => 'coins', 'value' => 1],
        ['permission_name' => 'loans', 'value' => 1],
        ['permission_name' => 'payments', 'value' => 1],
        ['permission_name' => 'reports', 'value' => 1],
        ['permission_name' => 'reports_collector_commissions', 'value' => 1],
        ['permission_name' => 'reports_admin_commissions', 'value' => 0],
        ['permission_name' => 'reports_general_customer', 'value' => 1],
        ['permission_name' => 'config', 'value' => 1],
        ['permission_name' => 'config_edit_data', 'value' => 0],
        ['permission_name' => 'config_change_password', 'value' => 1]
    ];

    echo "\n--- SIMULANDO GUARDADO DE PERMISOS ---\n";
    echo "Permisos a guardar:\n";
    foreach ($permissions_to_save as $perm) {
        echo "  - {$perm['permission_name']}: {$perm['value']}\n";
    }

    // Simular el método save_permissions del modelo
    $db->autocommit(false);

    // Eliminar permisos existentes
    $stmt = $db->prepare('DELETE FROM user_permissions WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $deleted_count = $stmt->affected_rows;
    $stmt->close();
    echo "\n✓ Eliminados $deleted_count permisos existentes\n";

    // Insertar nuevos permisos
    $inserted_count = 0;
    $stmt = $db->prepare('INSERT INTO user_permissions (user_id, permission_name, value) VALUES (?, ?, ?)');
    foreach ($permissions_to_save as $perm) {
        $stmt->bind_param('isi', $user_id, $perm['permission_name'], $perm['value']);
        $stmt->execute();
        $inserted_count++;
    }
    $stmt->close();

    $db->commit();
    echo "✓ Insertados $inserted_count nuevos permisos\n";

    // Verificar que se guardaron correctamente
    echo "\n--- VERIFICANDO PERMISOS GUARDADOS ---\n";
    $stmt = $db->prepare('SELECT permission_name, value FROM user_permissions WHERE user_id = ? ORDER BY permission_name');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $saved_permissions = [];
    while ($row = $result->fetch_assoc()) {
        $saved_permissions[$row['permission_name']] = $row['value'];
        echo "  - {$row['permission_name']}: {$row['value']}\n";
    }
    $stmt->close();

    // Verificar que todos los permisos se guardaron
    $all_saved = true;
    foreach ($permissions_to_save as $expected) {
        $name = $expected['permission_name'];
        $expected_value = $expected['value'];

        if (!isset($saved_permissions[$name]) || $saved_permissions[$name] != $expected_value) {
            echo "❌ ERROR: Permiso '$name' no se guardó correctamente. Esperado: $expected_value, Guardado: " . ($saved_permissions[$name] ?? 'no existe') . "\n";
            $all_saved = false;
        }
    }

    if ($all_saved) {
        echo "\n✅ TODOS LOS PERMISOS SE GUARDARON CORRECTAMENTE\n";

        // Verificar específicamente los submenús
        echo "\n--- VERIFICACIÓN DE SUBMENÚS ---\n";
        $submenu_checks = [
            'customers_list' => 1,
            'customers_overdue' => 0,
            'reports_collector_commissions' => 1,
            'reports_admin_commissions' => 0,
            'reports_general_customer' => 1,
            'config_edit_data' => 0,
            'config_change_password' => 1
        ];

        $submenus_ok = true;
        foreach ($submenu_checks as $submenu => $expected_value) {
            $actual_value = $saved_permissions[$submenu] ?? null;
            if ($actual_value !== $expected_value) {
                echo "❌ ERROR en submenú '$submenu': Esperado $expected_value, Actual " . ($actual_value ?? 'null') . "\n";
                $submenus_ok = false;
            } else {
                echo "✓ Submenú '$submenu': $actual_value\n";
            }
        }

        if ($submenus_ok) {
            echo "\n🎉 TODOS LOS SUBMENÚS FUNCIONAN CORRECTAMENTE\n";
        } else {
            echo "\n❌ HAY PROBLEMAS CON LOS SUBMENÚS\n";
        }

    } else {
        echo "\n❌ ERROR: No todos los permisos se guardaron correctamente\n";
    }

    $db->close();

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>