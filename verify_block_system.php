<?php
/**
 * Script de verificación del sistema de bloqueo de clientes
 * Ejecutar desde: http://localhost/prestamo-1/verify_block_system.php
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

echo "<h2>Verificación del Sistema de Bloqueo de Clientes</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $errors = [];
    $warnings = [];
    $success = [];
    
    // 1. Verificar columna status en customers
    echo "1. Verificando columna 'status' en tabla 'customers'...\n";
    $check_status = $pdo->query("SHOW COLUMNS FROM `customers` LIKE 'status'");
    if ($check_status->rowCount() == 0) {
        $errors[] = "La columna 'status' NO existe en la tabla 'customers'";
        echo "   ❌ ERROR: La columna 'status' no existe.\n";
        echo "   💡 Solución: Ejecuta add_customer_status_field.php\n";
    } else {
        $success[] = "Columna 'status' existe en 'customers'";
        echo "   ✅ La columna 'status' existe.\n";
    }
    
    // 2. Verificar tabla customer_status_history
    echo "\n2. Verificando tabla 'customer_status_history'...\n";
    $check_table = $pdo->query("SHOW TABLES LIKE 'customer_status_history'");
    if ($check_table->rowCount() == 0) {
        $errors[] = "La tabla 'customer_status_history' NO existe";
        echo "   ❌ ERROR: La tabla 'customer_status_history' no existe.\n";
        echo "   💡 Solución: Ejecuta create_customer_status_history.php\n";
    } else {
        $success[] = "Tabla 'customer_status_history' existe";
        echo "   ✅ La tabla 'customer_status_history' existe.\n";
        
        // Verificar estructura de la tabla
        $columns = $pdo->query("SHOW COLUMNS FROM customer_status_history")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'customer_id', 'old_status', 'new_status', 'action', 'changed_by', 'changed_at'];
        $missing_columns = array_diff($required_columns, $columns);
        
        if (!empty($missing_columns)) {
            $warnings[] = "Faltan columnas en customer_status_history: " . implode(', ', $missing_columns);
            echo "   ⚠️ ADVERTENCIA: Faltan columnas: " . implode(', ', $missing_columns) . "\n";
        } else {
            echo "   ✅ Todas las columnas requeridas están presentes.\n";
        }
        
        // Contar registros
        $count = $pdo->query("SELECT COUNT(*) FROM customer_status_history")->fetchColumn();
        echo "   📊 Total de registros en el historial: $count\n";
    }
    
    // 3. Verificar índices
    echo "\n3. Verificando índices...\n";
    $indexes = $pdo->query("SHOW INDEXES FROM customers WHERE Key_name = 'idx_customers_status'")->fetchAll();
    if (empty($indexes)) {
        $warnings[] = "El índice 'idx_customers_status' no existe";
        echo "   ⚠️ ADVERTENCIA: El índice 'idx_customers_status' no existe (no crítico).\n";
    } else {
        echo "   ✅ Índice 'idx_customers_status' existe.\n";
    }
    
    // 4. Verificar datos de ejemplo
    echo "\n4. Verificando datos de ejemplo...\n";
    $customers_with_status = $pdo->query("SELECT COUNT(*) FROM customers WHERE status IS NOT NULL")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    echo "   📊 Clientes con campo status: $customers_with_status / $total_customers\n";
    
    if ($customers_with_status < $total_customers) {
        $warnings[] = "Algunos clientes no tienen el campo status definido";
        echo "   ⚠️ ADVERTENCIA: Algunos clientes no tienen el campo 'status' definido.\n";
    }
    
    // Resumen
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RESUMEN DE VERIFICACIÓN\n";
    echo str_repeat("=", 60) . "\n";
    
    if (!empty($success)) {
        echo "✅ ÉXITOS (" . count($success) . "):\n";
        foreach ($success as $msg) {
            echo "   • $msg\n";
        }
    }
    
    if (!empty($warnings)) {
        echo "\n⚠️ ADVERTENCIAS (" . count($warnings) . "):\n";
        foreach ($warnings as $msg) {
            echo "   • $msg\n";
        }
    }
    
    if (!empty($errors)) {
        echo "\n❌ ERRORES (" . count($errors) . "):\n";
        foreach ($errors as $msg) {
            echo "   • $msg\n";
        }
        echo "\n⚠️ ACCIÓN REQUERIDA: Corrige los errores antes de usar el sistema de bloqueo.\n";
    } else {
        echo "\n✅ El sistema está listo para usar.\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "<p><a href='admin/customers/overdue'>Ir a la lista de clientes morosos</a></p>";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE BASE DE DATOS: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}

echo "</pre>";
?>





