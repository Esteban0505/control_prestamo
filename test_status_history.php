<?php
/**
 * Script de prueba para verificar el funcionamiento del historial de bloqueos
 */

define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');
require_once APPPATH . 'config/database.php';

// Obtener configuración de base de datos
$db_config = $db['default'];
$host = $db_config['hostname'];
$dbname = $db_config['database'];
$username = $db_config['username'];
$password = $db_config['password'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Prueba de Historial de Bloqueos</h2>";
    echo "<pre>";
    
    // 1. Verificar que la tabla existe
    echo "1. Verificando tabla customer_status_history...\n";
    $checkTable = $pdo->query("SHOW TABLES LIKE 'customer_status_history'");
    if ($checkTable->rowCount() > 0) {
        echo "   ✓ La tabla existe\n";
    } else {
        echo "   ✗ La tabla NO existe. Ejecuta: create_customer_status_history.php\n";
        exit;
    }
    
    // 2. Verificar estructura de la tabla
    echo "\n2. Verificando estructura de la tabla...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM customer_status_history")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "   - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // 3. Verificar que hay clientes en la tabla customers
    echo "\n3. Verificando clientes...\n";
    $customers = $pdo->query("SELECT id, dni, first_name, last_name, status FROM customers LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "   Encontrados " . count($customers) . " clientes (mostrando primeros 5):\n";
    foreach ($customers as $customer) {
        echo "   - ID: " . $customer['id'] . ", DNI: " . $customer['dni'] . ", Nombre: " . $customer['first_name'] . " " . $customer['last_name'] . ", Status: " . ($customer['status'] ?? 'NULL') . "\n";
    }
    
    // 4. Verificar registros en el historial
    echo "\n4. Verificando registros en el historial...\n";
    $history = $pdo->query("SELECT COUNT(*) as total FROM customer_status_history")->fetch(PDO::FETCH_ASSOC);
    echo "   Total de registros en el historial: " . $history['total'] . "\n";
    
    if ($history['total'] > 0) {
        echo "\n   Últimos 5 registros:\n";
        $recent = $pdo->query("SELECT h.*, c.dni, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
                                FROM customer_status_history h 
                                LEFT JOIN customers c ON c.id = h.customer_id 
                                ORDER BY h.changed_at DESC 
                                LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recent as $record) {
            echo "   - ID: " . $record['id'] . ", Cliente: " . $record['customer_name'] . " (ID: " . $record['customer_id'] . "), ";
            echo "Acción: " . $record['action'] . ", ";
            echo "Estado: " . $record['old_status'] . " -> " . $record['new_status'] . ", ";
            echo "Fecha: " . $record['changed_at'] . "\n";
        }
    } else {
        echo "   ⚠ No hay registros en el historial todavía.\n";
    }
    
    // 5. Verificar usuarios
    echo "\n5. Verificando usuarios...\n";
    $users = $pdo->query("SELECT id, email, first_name, last_name FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "   Encontrados " . count($users) . " usuarios (mostrando primeros 5):\n";
    foreach ($users as $user) {
        echo "   - ID: " . $user['id'] . ", Email: " . $user['email'] . ", Nombre: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    }
    
    // 6. Probar inserción de un registro de prueba (si hay clientes)
    if (count($customers) > 0 && $history['total'] == 0) {
        echo "\n6. Creando registro de prueba...\n";
        $test_customer = $customers[0];
        $test_user = count($users) > 0 ? $users[0]['id'] : null;
        
        try {
            $insert = $pdo->prepare("INSERT INTO customer_status_history 
                                    (customer_id, old_status, new_status, action, changed_by, ip_address) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $test_customer['id'],
                1,
                0,
                'deactivated',
                $test_user,
                '127.0.0.1'
            ]);
            echo "   ✓ Registro de prueba creado exitosamente (ID: " . $pdo->lastInsertId() . ")\n";
        } catch (PDOException $e) {
            echo "   ✗ Error al crear registro de prueba: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ PRUEBA COMPLETADA\n";
    echo str_repeat("=", 60) . "\n";
    echo "</pre>";
    
    echo "<p><a href='admin/customers/overdue'>Ir a la lista de clientes morosos</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
}

