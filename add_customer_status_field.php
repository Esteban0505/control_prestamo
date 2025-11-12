<?php
/**
 * Script para agregar el campo status a la tabla customers
 * Ejecutar desde: http://localhost/prestamo-1/add_customer_status_field.php
 */

// Cargar configuración de CodeIgniter
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
    
    echo "<h2>Agregando campo status a la tabla customers</h2>";
    echo "<pre>";
    
    // Verificar si la columna ya existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM customers LIKE 'status'");
    if ($checkColumn->rowCount() > 0) {
        echo "✓ La columna 'status' ya existe en la tabla customers.\n";
        echo "No se requiere ninguna acción.\n";
    } else {
        // Verificar si la columna tope_manual existe para usar AFTER, si no, agregar al final
        $checkTopeManual = $pdo->query("SHOW COLUMNS FROM customers LIKE 'tope_manual'");
        $afterClause = "";
        if ($checkTopeManual->rowCount() > 0) {
            $afterClause = " AFTER `tope_manual`";
        }
        
        // Agregar la columna status
        $sql = "ALTER TABLE `customers` 
                ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 
                COMMENT 'Estado del cliente: 1=Activo, 0=Inactivo'" . $afterClause;
        
        $pdo->exec($sql);
        echo "✓ Columna 'status' agregada exitosamente.\n";
        
        // Crear índice para mejorar rendimiento
        try {
            $indexSql = "CREATE INDEX `idx_customers_status` ON `customers`(`status`)";
            $pdo->exec($indexSql);
            echo "✓ Índice 'idx_customers_status' creado exitosamente.\n";
        } catch (PDOException $e) {
            // El índice puede ya existir, no es crítico
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "⚠ Advertencia al crear índice: " . $e->getMessage() . "\n";
            } else {
                echo "✓ El índice ya existe.\n";
            }
        }
        
        // Actualizar todos los clientes existentes como activos por defecto
        $updateSql = "UPDATE `customers` SET `status` = 1 WHERE `status` IS NULL OR `status` = 0";
        $affected = $pdo->exec($updateSql);
        echo "✓ Actualizados $affected clientes existentes como activos.\n";
        
        echo "\n✅ Proceso completado exitosamente!\n";
        echo "Ahora puedes usar la funcionalidad de activar/desactivar clientes.\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ PROCESO COMPLETADO\n";
    echo str_repeat("=", 60) . "\n";
    echo "</pre>";
    
    // Verificación final
    $finalCheck = $pdo->query("SHOW COLUMNS FROM customers LIKE 'status'");
    if ($finalCheck->rowCount() > 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ ¡Éxito!</h3>";
        echo "<p style='color: #155724;'>La columna 'status' ha sido creada correctamente.</p>";
        echo "<p><strong>Próximos pasos:</strong></p>";
        echo "<ol>";
        echo "<li>Recarga la página de <a href='admin/customers/overdue' style='color: #155724; font-weight: bold;'>clientes morosos</a></li>";
        echo "<li>Prueba el botón de bloqueo/desbloqueo</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error</h3>";
        echo "<p style='color: #721c24;'>No se pudo crear la columna. Por favor, revisa los errores arriba.</p>";
        echo "</div>";
    }
    
    echo "<p><a href='admin/customers/overdue' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir a la lista de clientes morosos</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: #721c24;'>❌ Error de Base de Datos</h2>";
    echo "<pre style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>Posibles soluciones:</h3>";
    echo "<ol>";
    echo "<li>Verifica que la base de datos esté corriendo</li>";
    echo "<li>Verifica los permisos del usuario de la base de datos</li>";
    echo "<li>Verifica la configuración en <code>application/config/database.php</code></li>";
    echo "<li>Intenta ejecutar el SQL manualmente desde phpMyAdmin</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>SQL para ejecutar manualmente:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;'>";
    echo "-- Verificar si tope_manual existe antes de ejecutar\n";
    echo "-- Si existe, usar: AFTER `tope_manual`\n";
    echo "-- Si no existe, omitir la cláusula AFTER\n\n";
    echo "ALTER TABLE `customers` \n";
    echo "ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 \n";
    echo "COMMENT 'Estado del cliente: 1=Activo, 0=Inactivo';\n\n";
    echo "CREATE INDEX `idx_customers_status` ON `customers`(`status`);\n\n";
    echo "UPDATE `customers` SET `status` = 1 WHERE `status` IS NULL OR `status` = 0;";
    echo "</pre>";
}

