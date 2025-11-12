<?php
/**
 * Script para crear la tabla de historial de cambios de estado de clientes
 * Ejecutar desde: http://localhost/prestamo-1/create_customer_status_history.php
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
    
    echo "<h2>Creando tabla de historial de cambios de estado</h2>";
    echo "<pre>";
    
    // Verificar si la tabla ya existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'customer_status_history'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ La tabla 'customer_status_history' ya existe.\n";
        echo "No se requiere ninguna acción.\n";
    } else {
        // Crear la tabla
        $sql = "CREATE TABLE IF NOT EXISTS `customer_status_history` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `customer_id` INT(11) NOT NULL,
          `old_status` TINYINT(1) NOT NULL COMMENT 'Estado anterior: 1=Activo, 0=Inactivo',
          `new_status` TINYINT(1) NOT NULL COMMENT 'Estado nuevo: 1=Activo, 0=Inactivo',
          `action` ENUM('activated', 'deactivated') NOT NULL COMMENT 'Acción realizada',
          `changed_by` INT(11) NULL COMMENT 'ID del usuario que realizó el cambio',
          `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del cambio',
          `notes` TEXT NULL COMMENT 'Notas adicionales sobre el cambio',
          `ip_address` VARCHAR(45) NULL COMMENT 'Dirección IP desde donde se realizó el cambio',
          PRIMARY KEY (`id`),
          INDEX `idx_customer_id` (`customer_id`),
          INDEX `idx_changed_at` (`changed_at`),
          INDEX `idx_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Historial de cambios de estado de clientes'";
        
        $pdo->exec($sql);
        echo "✓ Tabla 'customer_status_history' creada exitosamente.\n";
        
        // Intentar agregar foreign keys (pueden fallar si las tablas no existen)
        try {
            $fk1 = "ALTER TABLE `customer_status_history` 
                    ADD CONSTRAINT `fk_customer_status_history_customer` 
                    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE";
            $pdo->exec($fk1);
            echo "✓ Foreign key para customers agregada.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Cannot add foreign key constraint') === false) {
                echo "⚠ Advertencia al agregar foreign key para customers: " . $e->getMessage() . "\n";
            }
        }
        
        try {
            $fk2 = "ALTER TABLE `customer_status_history` 
                    ADD CONSTRAINT `fk_customer_status_history_user` 
                    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL";
            $pdo->exec($fk2);
            echo "✓ Foreign key para users agregada.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Cannot add foreign key constraint') === false) {
                echo "⚠ Advertencia al agregar foreign key para users: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✅ Proceso completado exitosamente!\n";
        echo "Ahora se registrará el historial de todos los cambios de estado de clientes.\n";
    }
    
    echo "</pre>";
    echo "<p><a href='admin/customers/overdue'>Ir a la lista de clientes morosos</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<p>Por favor, verifica la configuración de la base de datos en el archivo.</p>";
}


