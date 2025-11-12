<?php
/**
 * Script de verificación para la migración de roles
 * Verifica que los cambios de "viewer" → "Visitante" y "operador" → "Colaborador" funcionen correctamente
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

    echo "<h2>🧪 VERIFICACIÓN DE MIGRACIÓN DE ROLES</h2>";
    echo "<pre>";

    // Verificar distribución de roles después de la migración
    echo "\n--- DISTRIBUCIÓN DE ROLES EN USUARIOS ---\n";
    $result = $db->query("SELECT perfil, COUNT(*) as count FROM users GROUP BY perfil ORDER BY perfil");

    $total_users = 0;
    while ($row = $result->fetch_assoc()) {
        echo "Rol '{$row['perfil']}': {$row['count']} usuarios\n";
        $total_users += $row['count'];
    }
    echo "Total de usuarios: $total_users\n";

    // Verificar que no queden roles antiguos
    $old_roles = ['viewer', 'operador'];
    $old_role_found = false;
    foreach ($old_roles as $old_role) {
        $result = $db->query("SELECT COUNT(*) as count FROM users WHERE perfil = '$old_role'");
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            echo "❌ ERROR: Aún existen {$row['count']} usuarios con el rol antiguo '$old_role'\n";
            $old_role_found = true;
        }
    }

    if (!$old_role_found) {
        echo "✅ Éxito: No se encontraron roles antiguos en la base de datos\n";
    }

    // Verificar permisos por rol
    echo "\n--- VERIFICACIÓN DE PERMISOS POR ROL ---\n";

    $roles_to_check = ['admin', 'Colaborador', 'Visitante'];
    foreach ($roles_to_check as $role) {
        echo "\nRol: $role\n";

        // Obtener un usuario con este rol
        $result = $db->query("SELECT id FROM users WHERE perfil = '$role' LIMIT 1");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Contar permisos para este usuario
            $result = $db->query("SELECT COUNT(*) as count FROM user_permissions WHERE user_id = $user_id");
            $row = $result->fetch_assoc();
            echo "  - Usuario ID $user_id tiene {$row['count']} permisos\n";

            // Mostrar algunos permisos clave
            $result = $db->query("SELECT permission_name, value FROM user_permissions WHERE user_id = $user_id AND permission_name IN ('dashboard', 'customers', 'reports', 'config') ORDER BY permission_name");
            while ($row = $result->fetch_assoc()) {
                echo "    * {$row['permission_name']}: {$row['value']}\n";
            }
        } else {
            echo "  - No hay usuarios con este rol\n";
        }
    }

    // Verificar funcionamiento del sistema de permisos
    echo "\n--- PRUEBA DE SISTEMA DE PERMISOS ---\n";

    // Obtener un usuario Colaborador para probar
    $result = $db->query("SELECT id, first_name, last_name FROM users WHERE perfil = 'Colaborador' LIMIT 1");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_name = $user['first_name'] . ' ' . $user['last_name'];

        echo "Probando con usuario Colaborador: $user_name (ID: $user_id)\n";

        // Verificar permisos específicos que debería tener un Colaborador
        $expected_permissions = [
            'dashboard' => 1,
            'customers' => 1,
            'customers_list' => 1,
            'customers_overdue' => 0, // Colaborador no debería tener acceso a pagos vencidos
            'loans' => 1,
            'payments' => 1,
            'reports' => 1,
            'config' => 0
        ];

        $all_correct = true;
        foreach ($expected_permissions as $perm => $expected_value) {
            $result = $db->query("SELECT value FROM user_permissions WHERE user_id = $user_id AND permission_name = '$perm'");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $actual_value = (int)$row['value'];
                if ($actual_value === $expected_value) {
                    echo "  ✅ $perm: $actual_value (correcto)\n";
                } else {
                    echo "  ❌ $perm: $actual_value (esperado: $expected_value)\n";
                    $all_correct = false;
                }
            } else {
                echo "  ❌ $perm: no encontrado en BD\n";
                $all_correct = false;
            }
        }

        if ($all_correct) {
            echo "✅ Todos los permisos del Colaborador son correctos\n";
        } else {
            echo "❌ Algunos permisos del Colaborador no son correctos\n";
        }
    } else {
        echo "No hay usuarios Colaborador para probar\n";
    }

    // Verificar funcionamiento del frontend (simular carga de modal)
    echo "\n--- SIMULACIÓN DE CARGA DE MODAL ---\n";

    // Obtener permisos completos de un usuario (simulando get_permissions)
    $result = $db->query("SELECT id, first_name, last_name, perfil FROM users WHERE perfil IN ('admin', 'Colaborador', 'Visitante') LIMIT 1");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_name = $user['first_name'] . ' ' . $user['last_name'];
        $user_role = $user['perfil'];

        echo "Simulando carga de modal para: $user_name (Rol: $user_role)\n";

        // Obtener todos los permisos del usuario
        $result = $db->query("SELECT permission_name, value FROM user_permissions WHERE user_id = $user_id ORDER BY permission_name");
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[$row['permission_name']] = (int)$row['value'];
        }

        // Lista completa de permisos que debería devolver get_permissions
        $all_possible_permissions = [
            'dashboard', 'sidebar', 'sidebar_back',
            'customers', 'customers_list', 'customers_overdue',
            'coins', 'loans', 'payments',
            'reports', 'reports_collector_commissions', 'reports_admin_commissions', 'reports_general_customer',
            'config', 'config_edit_data', 'config_change_password'
        ];

        $complete_permissions = [];
        foreach ($all_possible_permissions as $perm_name) {
            $complete_permissions[] = [
                'permission_name' => $perm_name,
                'value' => isset($permissions[$perm_name]) ? $permissions[$perm_name] : 0
            ];
        }

        echo "Permisos completos que se enviarían al frontend (" . count($complete_permissions) . "):\n";
        foreach ($complete_permissions as $perm) {
            echo "  - {$perm['permission_name']}: {$perm['value']}\n";
        }

        echo "✅ Simulación de carga de modal completada\n";
    }

    $db->close();

    echo "\n🎉 VERIFICACIÓN DE MIGRACIÓN COMPLETADA\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>