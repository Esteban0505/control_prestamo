-- Optimizaciones específicas para el módulo de clientes con pagos vencidos
-- Ejecutar estas consultas en phpMyAdmin o mediante línea de comandos

-- Crear índices adicionales para consultas de clientes vencidos
-- Nota: Ejecutar manualmente en phpMyAdmin si CREATE INDEX IF NOT EXISTS no es soportado

-- Índices principales para consultas de overdue
ALTER TABLE loan_items ADD INDEX idx_loan_items_status_date_loan_id (status, date, loan_id);
ALTER TABLE loan_items ADD INDEX idx_loan_items_loan_id_date (loan_id, date);
ALTER TABLE loans ADD INDEX idx_loans_customer_id_status_date (customer_id, status, date);
ALTER TABLE customers ADD INDEX idx_customers_name_dni_search (first_name, last_name, dni);
ALTER TABLE loan_items ADD INDEX idx_loan_items_overdue_stats (status, date, loan_id, fee_amount);

-- Índices opcionales (solo crear si las tablas existen)
-- Para loans_penalties (si existe):
-- ALTER TABLE loans_penalties ADD INDEX idx_loans_penalties_customer_loan (customer_id, loan_id);

-- Para collection_tracking (si existe):
-- ALTER TABLE collection_tracking ADD INDEX idx_collection_tracking_customer_status (customer_id, status, next_followup_date);

-- Optimización de consultas complejas para estadísticas de mora
-- Las consultas ya están optimizadas con subqueries eficientes en el modelo Payments_m.php
-- Recomendación: Monitorear el rendimiento con EXPLAIN PLAN en consultas complejas