<?php
/**
 * Script de prueba para verificar la funcionalidad de bloqueo
 * Ejecutar desde: http://localhost/prestamo-1/test_block_functionality.php
 */

// Cargar configuración de CodeIgniter
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');
require_once APPPATH . 'config/database.php';

// Obtener configuración de base de datos
$db_config = $db['default'];
$host = $db_config['hostname'];
$dbname = $db_config['database'];
$username = $db_config['username'];
$password = $db_config['password'];

echo "<h2>Prueba de Funcionalidad de Bloqueo</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verificar columna status
    echo "1. Verificando columna 'status'...\n";
    $check_status = $pdo->query("SHOW COLUMNS FROM `customers` LIKE 'status'");
    if ($check_status->rowCount() == 0) {
        echo "   ❌ ERROR: La columna 'status' no existe.\n";
        echo "   💡 Ejecuta: add_customer_status_field.php\n";
        exit;
    }
    echo "   ✅ Columna 'status' existe.\n\n";
    
    // 2. Obtener un cliente de prueba
    echo "2. Obteniendo cliente de prueba...\n";
    $customer = $pdo->query("SELECT id, dni, first_name, last_name, status FROM customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo "   ❌ ERROR: No hay clientes en la base de datos.\n";
        exit;
    }
    
    echo "   ✅ Cliente encontrado:\n";
    echo "      ID: {$customer['id']}\n";
    echo "      DNI: {$customer['dni']}\n";
    echo "      Nombre: {$customer['first_name']} {$customer['last_name']}\n";
    echo "      Status actual: " . ($customer['status'] ?? 'NULL') . "\n\n";
    
    // 3. Probar actualización
    echo "3. Probando actualización de status...\n";
    $new_status = ($customer['status'] == 1) ? 0 : 1;
    $status_text = $new_status == 1 ? 'Activo' : 'Inactivo';
    
    $stmt = $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $result = $stmt->execute([$new_status, $customer['id']]);
    
    if ($result) {
        echo "   ✅ UPDATE ejecutado correctamente.\n";
        echo "      Nuevo status: $new_status ($status_text)\n";
        
        // Verificar que se actualizó
        $updated = $pdo->query("SELECT status FROM customers WHERE id = {$customer['id']}")->fetchColumn();
        if ($updated == $new_status) {
            echo "   ✅ Verificación: Status actualizado correctamente en BD.\n";
        } else {
            echo "   ❌ ERROR: El status no se actualizó correctamente.\n";
        }
    } else {
        echo "   ❌ ERROR: No se pudo ejecutar el UPDATE.\n";
    }
    
    // 4. Restaurar status original
    echo "\n4. Restaurando status original...\n";
    $original_status = $customer['status'] ?? 1;
    $stmt = $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $stmt->execute([$original_status, $customer['id']]);
    echo "   ✅ Status restaurado a: $original_status\n";
    
    // 5. Verificar tabla de historial
    echo "\n5. Verificando tabla de historial...\n";
    $check_history = $pdo->query("SHOW TABLES LIKE 'customer_status_history'");
    if ($check_history->rowCount() == 0) {
        echo "   ⚠️ ADVERTENCIA: La tabla 'customer_status_history' no existe.\n";
        echo "   💡 Ejecuta: create_customer_status_history.php\n";
    } else {
        $count = $pdo->query("SELECT COUNT(*) FROM customer_status_history")->fetchColumn();
        echo "   ✅ Tabla existe con $count registros.\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ PRUEBA COMPLETADA\n";
    echo "Si todos los pasos fueron exitosos, el sistema debería funcionar.\n";
    echo "Si hay errores, corrige los problemas indicados.\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "\n<p><a href='admin/customers/overdue'>Ir a la lista de clientes morosos</a></p>";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>





