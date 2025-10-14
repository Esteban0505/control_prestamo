<?php
/**
 * SCRIPT DE DIAGNÓSTICO PASO A PASO PARA PERMISOS
 * Sistema de Préstamo y Cobranzas - CodeIgniter 3
 *
 * Este script simula el flujo completo de permisos:
 * 1. Frontend envía datos
 * 2. API guarda en BD
 * 3. get_permissions lee correctamente
 * 4. can_view usa permisos
 * 5. Frontend refleja cambios
 */

define('BASEPATH', 'diagnostic_script');

// Configurar logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/diagnostic_permissions.log');
error_reporting(E_ALL);

// Incluir configuración de BD
require_once __DIR__ . '/application/config/database.php';

class DiagnosticPermissions {

    private $db;
    private $log_file;

    public function __construct() {
        $this->log_file = __DIR__ . '/diagnostic_permissions.log';
        $this->init_database();
        $this->clear_log();
    }

    private function init_database() {
        global $db;
        $this->db = mysqli_connect($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);

        if (!$this->db) {
            $this->log("ERROR: No se pudo conectar a la BD: " . mysqli_connect_error());
            die("Error de conexión a BD\n");
        }

        $this->log("✓ Conexión a BD exitosa");
    }

    private function clear_log() {
        file_put_contents($this->log_file, "");
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message\n";
        echo $log_message;
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    // Simular funciones del sistema
    private function get_permissions_by_role($role) {
        $permissions = [
            'admin' => [
                'dashboard' => true,
                'customers' => true,
                'loans' => true,
                'payments' => true,
                'reports' => true,
                'config' => true
            ],
            'operador' => [
                'dashboard' => true,
                'customers' => true,
                'loans' => true,
                'payments' => true,
                'reports' => true,
                'config' => false
            ],
            'viewer' => [
                'dashboard' => true,
                'customers' => false,
                'loans' => false,
                'payments' => false,
                'reports' => true,
                'config' => false
            ]
        ];

        return isset($permissions[$role]) ? $permissions[$role] : $permissions['viewer'];
    }

    private function get_permissions($user_id) {
        $this->log("[SIMULATE] get_permissions called for user_id: $user_id");

        // Verificar si existe la tabla user_permissions
        $result = mysqli_query($this->db, "SHOW TABLES LIKE 'user_permissions'");
        if (mysqli_num_rows($result) == 0) {
            $this->log("[SIMULATE] get_permissions: user_permissions table doesn't exist, creating...");
            $this->create_user_permissions_table();
            // Insertar permisos por defecto solo al crear la tabla
            $user = $this->get_user($user_id);
            if ($user) {
                $user_role = isset($user->role) ? $user->role : $user->perfil;
                $permissions = $this->get_permissions_by_role($user_role);
                foreach ($permissions as $name => $value) {
                    $data = [
                        'user_id' => $user_id,
                        'permission_name' => $name,
                        'value' => $value ? 1 : 0
                    ];
                    mysqli_query($this->db, "INSERT INTO user_permissions (user_id, permission_name, value) VALUES ({$data['user_id']}, '{$data['permission_name']}', {$data['value']})");
                    $this->log("[SIMULATE] get_permissions: inserted default permission $name = $value");
                }
            }
        }

        // Obtener permisos de la tabla user_permissions
        $query = "SELECT permission_name, value FROM user_permissions WHERE user_id = $user_id ORDER BY permission_name";
        $result = mysqli_query($this->db, $query);

        $permissions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $permissions[] = ['permission_name' => $row['permission_name'], 'value' => (int) $row['value']];
        }

        $this->log("[SIMULATE] get_permissions: found " . count($permissions) . " permissions in DB");

        // Si no hay permisos, devolver permisos por defecto SIN guardarlos en BD
        if (empty($permissions)) {
            $this->log("[SIMULATE] get_permissions: no permissions in DB, using defaults");
            $user = $this->get_user($user_id);
            if ($user) {
                $user_role = isset($user->role) ? $user->role : $user->perfil;
                $default_permissions = $this->get_permissions_by_role($user_role);
                foreach ($default_permissions as $name => $value) {
                    $permissions[] = ['permission_name' => $name, 'value' => (int) $value];
                    $this->log("[SIMULATE] get_permissions: default permission $name = $value");
                }
            }
        }

        $this->log("[SIMULATE] get_permissions: returning " . count($permissions) . " permissions");
        return $permissions;
    }

    private function save_permissions($user_id, $permissions) {
        $this->log("[SIMULATE] save_permissions called for user_id: $user_id with " . count($permissions) . " permissions");

        // Verificar si existe la tabla user_permissions
        $result = mysqli_query($this->db, "SHOW TABLES LIKE 'user_permissions'");
        if (mysqli_num_rows($result) == 0) {
            $this->log("[SIMULATE] save_permissions: user_permissions table doesn't exist, creating...");
            $this->create_user_permissions_table();
        }

        // Eliminar permisos existentes
        mysqli_query($this->db, "DELETE FROM user_permissions WHERE user_id = $user_id");
        $this->log("[SIMULATE] save_permissions: deleted existing permissions");

        // Insertar nuevos permisos
        foreach ($permissions as $perm) {
            $data = [
                'user_id' => $user_id,
                'permission_name' => $perm['permission_name'],
                'value' => isset($perm['value']) ? (int) $perm['value'] : 0
            ];
            mysqli_query($this->db, "INSERT INTO user_permissions (user_id, permission_name, value) VALUES ({$data['user_id']}, '{$data['permission_name']}', {$data['value']})");
            $this->log("[SIMULATE] save_permissions: inserted permission {$perm['permission_name']} = {$data['value']}");
        }

        $this->log("[SIMULATE] save_permissions: completed successfully");
        return true;
    }

    private function can_view($user_role, $section) {
        $this->log("[SIMULATE] can_view called: role='$user_role', section='$section'");

        if (empty($user_role)) {
            $this->log("[SIMULATE] can_view: empty user_role, returning false");
            return false;
        }

        // Admin puede ver todo
        if ($user_role === 'admin') {
            $this->log("[SIMULATE] can_view: admin role, returning true");
            return true;
        }

        // Obtener permisos granulares del usuario desde la BD (simulando la lógica real)
        // En la función real, esto se hace con $CI->user_m->get_permissions($user_id)
        // Para simular, necesitamos el user_id, pero no lo tenemos aquí
        // Usaremos la lógica de defaults por ahora, pero el problema real está en la implementación
        $permissions = $this->get_permissions_by_role($user_role);
        $result = isset($permissions[$section]) ? $permissions[$section] : false;
        $this->log("[SIMULATE] can_view: using role defaults, returning " . ($result ? 'true' : 'false'));
        return $result;
    }

    private function get_user($user_id) {
        $result = mysqli_query($this->db, "SELECT * FROM users WHERE id = $user_id");
        return mysqli_fetch_object($result);
    }

    private function create_user_permissions_table() {
        $sql = "CREATE TABLE `user_permissions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `permission_name` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
          `value` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";
        mysqli_query($this->db, $sql);
    }

    public function run_diagnostic() {
        $this->log("=== INICIANDO DIAGNÓSTICO DE PERMISOS ===\n");

        try {
            // PASO 1: Verificar estructura de BD
            $this->step_check_database_structure();

            // PASO 2: Crear usuario de prueba
            $user_id = $this->step_create_test_user();

            // PASO 3: Simular envío de datos desde frontend
            $this->step_simulate_frontend_send($user_id);

            // PASO 4: Verificar guardado en BD
            $this->step_verify_save_to_db($user_id);

            // PASO 5: Verificar lectura de permisos
            $this->step_verify_get_permissions($user_id);

            // PASO 6: Verificar función can_view
            $this->step_verify_can_view($user_id);

            // PASO 7: Simular reflexión en frontend
            $this->step_simulate_frontend_reflection($user_id);

            // PASO 8: Limpiar datos de prueba
            $this->step_cleanup_test_data($user_id);

            $this->log("\n=== DIAGNÓSTICO COMPLETADO EXITOSAMENTE ===");

        } catch (Exception $e) {
            $this->log("ERROR FATAL: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
        }
    }

    private function step_check_database_structure() {
        $this->log("\n--- PASO 1: Verificar estructura de BD ---");

        // Verificar tabla users
        $result = mysqli_query($this->db, "SHOW TABLES LIKE 'users'");
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Tabla 'users' no existe");
        }
        $this->log("✓ Tabla 'users' existe");

        // Verificar tabla user_permissions
        $result = mysqli_query($this->db, "SHOW TABLES LIKE 'user_permissions'");
        if (!$result || mysqli_num_rows($result) == 0) {
            $this->log("⚠ Tabla 'user_permissions' no existe - se creará automáticamente");
        } else {
            $this->log("✓ Tabla 'user_permissions' existe");
        }

        // Verificar estructura de user_permissions si existe
        if ($result && mysqli_num_rows($result) > 0) {
            $columns = mysqli_query($this->db, "DESCRIBE user_permissions");
            if (!$columns) {
                throw new Exception("Error al obtener estructura de user_permissions");
            }
            $required_columns = ['id', 'user_id', 'permission_name', 'value'];
            $existing_columns = [];
            while ($col = mysqli_fetch_assoc($columns)) {
                $existing_columns[] = $col['Field'];
            }

            foreach ($required_columns as $col) {
                if (!in_array($col, $existing_columns)) {
                    throw new Exception("Columna '$col' faltante en user_permissions");
                }
            }
            $this->log("✓ Estructura de user_permissions correcta");
        }
    }

    private function step_create_test_user() {
        $this->log("\n--- PASO 2: Crear usuario de prueba ---");

        // Crear usuario de prueba
        $test_user = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_' . time() . '@example.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'perfil' => 'operador',
            'estado' => 1,
            'fecha' => date('Y-m-d H:i:s')
        ];

        $query = "INSERT INTO users (first_name, last_name, email, password, perfil, estado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->db, $query);
        mysqli_stmt_bind_param($stmt, 'sssssis', $test_user['first_name'], $test_user['last_name'], $test_user['email'], $test_user['password'], $test_user['perfil'], $test_user['estado'], $test_user['fecha']);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creando usuario de prueba: " . mysqli_error($this->db));
        }

        $user_id = mysqli_insert_id($this->db);
        $this->log("✓ Usuario de prueba creado con ID: $user_id");

        return $user_id;
    }

    private function step_simulate_frontend_send($user_id) {
        $this->log("\n--- PASO 3: Simular envío desde frontend ---");

        // Simular datos que enviaría el frontend
        $permissions_data = [
            ['permission_name' => 'dashboard', 'value' => 1],
            ['permission_name' => 'customers', 'value' => 0], // Cambiar de default
            ['permission_name' => 'loans', 'value' => 1],
            ['permission_name' => 'payments', 'value' => 1],
            ['permission_name' => 'reports', 'value' => 1],
            ['permission_name' => 'config', 'value' => 0]
        ];

        $this->log("Datos simulados del frontend:");
        foreach ($permissions_data as $perm) {
            $this->log("  {$perm['permission_name']}: {$perm['value']}");
        }

        // Simular llamada al método save_permissions
        $this->log("Llamando a save_permissions...");
        $result = $this->save_permissions($user_id, $permissions_data);

        if (!$result) {
            throw new Exception("Error en save_permissions");
        }

        $this->log("✓ save_permissions ejecutado exitosamente");
    }

    private function step_verify_save_to_db($user_id) {
        $this->log("\n--- PASO 4: Verificar guardado en BD ---");

        // Verificar que los permisos se guardaron
        $query = "SELECT permission_name, value FROM user_permissions WHERE user_id = $user_id ORDER BY permission_name";
        $result = mysqli_query($this->db, $query);

        $saved_permissions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $saved_permissions[$row['permission_name']] = (int)$row['value'];
        }

        $this->log("Permisos guardados en BD:");
        foreach ($saved_permissions as $name => $value) {
            $this->log("  $name: $value");
        }

        // Verificar valores específicos
        $expected = [
            'dashboard' => 1,
            'customers' => 0, // Este debe ser 0
            'loans' => 1,
            'payments' => 1,
            'reports' => 1,
            'config' => 0
        ];

        $errors = [];
        foreach ($expected as $perm => $expected_value) {
            if (!isset($saved_permissions[$perm])) {
                $errors[] = "Permiso '$perm' no encontrado en BD";
            } elseif ($saved_permissions[$perm] !== $expected_value) {
                $errors[] = "Permiso '$perm': esperado $expected_value, guardado {$saved_permissions[$perm]}";
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->log("✗ $error");
            }
            throw new Exception("Errores en guardado de permisos");
        }

        $this->log("✓ Todos los permisos guardados correctamente");
    }

    private function step_verify_get_permissions($user_id) {
        $this->log("\n--- PASO 5: Verificar get_permissions ---");

        // Llamar a get_permissions
        $permissions = $this->get_permissions($user_id);

        $this->log("Permisos obtenidos por get_permissions:");
        foreach ($permissions as $perm) {
            $this->log("  {$perm['permission_name']}: {$perm['value']}");
        }

        // Verificar que coincida con lo guardado
        $expected_permissions = [
            'dashboard' => 1,
            'customers' => 0,
            'loans' => 1,
            'payments' => 1,
            'reports' => 1,
            'config' => 0
        ];

        $errors = [];
        foreach ($permissions as $perm) {
            $name = $perm['permission_name'];
            $value = (int)$perm['value'];

            if (isset($expected_permissions[$name])) {
                if ($value !== $expected_permissions[$name]) {
                    $errors[] = "$name: esperado {$expected_permissions[$name]}, obtenido $value";
                }
                unset($expected_permissions[$name]);
            }
        }

        if (!empty($expected_permissions)) {
            $errors[] = "Permisos faltantes: " . implode(', ', array_keys($expected_permissions));
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->log("✗ $error");
            }
            throw new Exception("Errores en get_permissions");
        }

        $this->log("✓ get_permissions funciona correctamente");
    }

    private function step_verify_can_view($user_id) {
        $this->log("\n--- PASO 6: Verificar can_view ---");

        // Simular sesión del usuario
        $user = $this->get_user($user_id);
        $user_role = $user->perfil;

        $this->log("Usuario: ID $user_id, Rol: $user_role");

        // Probar can_view para diferentes secciones
        $sections_to_test = ['dashboard', 'customers', 'loans', 'payments', 'reports', 'config'];

        $this->log("Resultados de can_view:");
        foreach ($sections_to_test as $section) {
            $can_view = $this->can_view($user_role, $section);
            $this->log("  can_view('$user_role', '$section'): " . ($can_view ? 'true' : 'false'));
        }

        // Verificar que customers sea false (ya que lo cambiamos a 0)
        $can_view_customers = $this->can_view($user_role, 'customers');
        if ($can_view_customers) {
            $this->log("✗ ERROR: can_view debería retornar false para 'customers' (permiso=0)");
            throw new Exception("can_view no respeta permisos granulares");
        }

        // Verificar que dashboard sea true
        $can_view_dashboard = $this->can_view($user_role, 'dashboard');
        if (!$can_view_dashboard) {
            $this->log("✗ ERROR: can_view debería retornar true para 'dashboard' (permiso=1)");
            throw new Exception("can_view no funciona para permisos positivos");
        }

        $this->log("✓ can_view funciona correctamente con permisos granulares");
    }

    private function step_simulate_frontend_reflection($user_id) {
        $this->log("\n--- PASO 7: Simular reflexión en frontend ---");

        // Simular la respuesta que enviaría el backend al frontend
        $permissions = $this->get_permissions($user_id);
        $user = $this->get_user($user_id);

        $response = [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'role' => $user->perfil
            ],
            'permissions' => $permissions
        ];

        $this->log("Respuesta JSON simulada al frontend:");
        $this->log(json_encode($response, JSON_PRETTY_PRINT));

        // Simular cómo el frontend procesaría esta respuesta
        $this->log("Procesamiento simulado en frontend:");
        foreach ($response['permissions'] as $perm) {
            $checked = $perm['value'] ? 'checked' : 'unchecked';
            $this->log("  Checkbox '{$perm['permission_name']}': $checked");
        }

        $this->log("✓ Reflexión en frontend simulada correctamente");
    }

    private function step_cleanup_test_data($user_id) {
        $this->log("\n--- PASO 8: Limpiar datos de prueba ---");

        // Eliminar permisos del usuario de prueba
        mysqli_query($this->db, "DELETE FROM user_permissions WHERE user_id = $user_id");
        $this->log("✓ Permisos de usuario de prueba eliminados");

        // Eliminar usuario de prueba
        mysqli_query($this->db, "DELETE FROM users WHERE id = $user_id");
        $this->log("✓ Usuario de prueba eliminado");

        $this->log("✓ Limpieza completada");
    }
}

// Ejecutar diagnóstico
$diagnostic = new DiagnosticPermissions();
$diagnostic->run_diagnostic();

echo "\nDiagnóstico completado. Revisa el archivo diagnostic_permissions.log para detalles.\n";
