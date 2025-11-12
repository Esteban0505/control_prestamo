-- Crear tabla blacklist para clientes morosos
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
);

-- Crear índices para mejor rendimiento
CREATE INDEX idx_customer_blacklist_customer_id ON customer_blacklist(customer_id);
CREATE INDEX idx_customer_blacklist_is_active ON customer_blacklist(is_active);
CREATE INDEX idx_customer_blacklist_reason ON customer_blacklist(reason);

-- Insertar algunos registros de ejemplo para clientes con alta mora
INSERT IGNORE INTO customer_blacklist (customer_id, reason, notes)
SELECT DISTINCT
    li.paid_by as customer_id,
    'overdue_payments' as reason,
    CONCAT('Cliente con pagos vencidos automáticos - ', COUNT(*), ' cuotas pendientes') as notes
FROM loan_items li
WHERE li.status = 1
  AND li.pay_date < DATE_SUB(NOW(), INTERVAL 60 DAY)
GROUP BY li.paid_by
HAVING COUNT(*) >= 3;