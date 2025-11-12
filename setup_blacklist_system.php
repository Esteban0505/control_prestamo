<?php
/**
 * CONFIGURACIÓN DEL SISTEMA DE BLACKLIST PARA CLIENTES MOROSOS
 * Ejecutar desde línea de comandos: php setup_blacklist_system.php
 */

require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "=== CONFIGURACIÓN DEL SISTEMA DE BLACKLIST ===\n\n";

// 1. CREAR TABLA BLACKLIST
echo "1. CREANDO TABLA BLACKLIST...\n";

$sql_create_table = "
CREATE TABLE IF NOT EXISTS customer_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    reason ENUM('overdue_payments', 'fraud', 'manual_block', 'multiple_defaults') DEFAULT 'overdue_payments',
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_by INT NULL,
    unblocked_at TIMESTAMP NULL,
    unblocked_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unblocked_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_active_blacklist (customer_id, is_active)
)";

$result = $CI->db->query($sql_create_table);

if ($result) {
    echo "   ✅ Tabla customer_blacklist creada exitosamente\n";
} else {
    echo "   ❌ Error creando tabla: " . $CI->db->error()['message'] . "\n";
}

// 2. CREAR ÍNDICES
echo "\n2. CREANDO ÍNDICES PARA MEJOR RENDIMIENTO...\n";

$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_customer_blacklist_customer_id ON customer_blacklist(customer_id)",
    "CREATE INDEX IF NOT EXISTS idx_customer_blacklist_is_active ON customer_blacklist(is_active)",
    "CREATE INDEX IF NOT EXISTS idx_customer_blacklist_reason ON customer_blacklist(reason)"
];

foreach ($indexes as $index_sql) {
    $result = $CI->db->query($index_sql);
    if ($result) {
        echo "   ✅ Índice creado: " . substr($index_sql, strpos($index_sql, 'idx_')) . "\n";
    } else {
        echo "   ❌ Error creando índice: " . $CI->db->error()['message'] . "\n";
    }
}

// 3. INSERTAR CLIENTES MOROSOS EN BLACKLIST
echo "\n3. IDENTIFICANDO Y BLOQUEANDO CLIENTES MOROSOS...\n";

$sql_insert_blacklist = "
INSERT IGNORE INTO customer_blacklist (customer_id, reason, notes, blocked_by)
SELECT DISTINCT
    li.paid_by as customer_id,
    'overdue_payments' as reason,
    CONCAT('Cliente con pagos vencidos automáticos - ', COUNT(*), ' cuotas pendientes >60 días') as notes,
    1 as blocked_by
FROM loan_items li
WHERE li.status = 1
  AND li.pay_date < DATE_SUB(NOW(), INTERVAL 60 DAY)
GROUP BY li.paid_by
HAVING COUNT(*) >= 3";

$result = $CI->db->query($sql_insert_blacklist);
$blocked_count = $CI->db->affected_rows();

echo "   ✅ Clientes bloqueados automáticamente: {$blocked_count}\n";

// 4. VERIFICACIÓN FINAL
echo "\n4. VERIFICACIÓN DEL SISTEMA DE BLACKLIST...\n";

$stats_query = $CI->db->query("
    SELECT
        COUNT(*) as total_blacklisted,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_blocks,
        SUM(CASE WHEN reason = 'overdue_payments' THEN 1 ELSE 0 END) as overdue_blocks,
        SUM(CASE WHEN reason = 'fraud' THEN 1 ELSE 0 END) as fraud_blocks
    FROM customer_blacklist
");

$stats = $stats_query->row();

echo "   📊 ESTADÍSTICAS DE BLACKLIST:\n";
echo "      Total de registros: {$stats->total_blacklisted}\n";
echo "      Bloqueos activos: {$stats->active_blocks}\n";
echo "      Por pagos vencidos: {$stats->overdue_blocks}\n";
echo "      Por fraude: {$stats->fraud_blocks}\n\n";

// 5. MOSTRAR EJEMPLOS DE CLIENTES BLOQUEADOS
echo "5. EJEMPLOS DE CLIENTES BLOQUEADOS:\n";

$examples_query = $CI->db->query("
    SELECT
        cb.customer_id,
        c.first_name,
        c.last_name,
        cb.reason,
        cb.notes,
        cb.blocked_at
    FROM customer_blacklist cb
    JOIN customers c ON c.id = cb.customer_id
    WHERE cb.is_active = 1
    ORDER BY cb.blocked_at DESC
    LIMIT 5
");

$examples = $examples_query->result();

if (!empty($examples)) {
    foreach ($examples as $example) {
        echo "   🔒 Cliente: {$example->first_name} {$example->last_name} (ID: {$example->customer_id})\n";
        echo "      Razón: {$example->reason}\n";
        echo "      Notas: {$example->notes}\n";
        echo "      Bloqueado: {$example->blocked_at}\n\n";
    }
} else {
    echo "   📝 No hay clientes bloqueados actualmente\n\n";
}

echo "=== SISTEMA DE BLACKLIST CONFIGURADO EXITOSAMENTE ===\n\n";

echo "💡 FUNCIONALIDADES IMPLEMENTADAS:\n";
echo "   • Tabla customer_blacklist creada\n";
echo "   • Índices de rendimiento agregados\n";
echo "   • Clientes morosos bloqueados automáticamente\n";
echo "   • Sistema listo para integración con creación de préstamos\n\n";

echo "🔧 PRÓXIMOS PASOS:\n";
echo "   1. Implementar validación de blacklist en Loans controller\n";
echo "   2. Agregar notificaciones visuales en interfaz\n";
echo "   3. Crear sistema de desbloqueo manual\n";
echo "   4. Integrar con sistema de penalizaciones\n\n";

echo "=== FIN DE CONFIGURACIÓN ===\n";
?>