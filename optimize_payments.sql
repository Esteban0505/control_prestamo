-- Optimizaciones de base de datos para el módulo de pagos
-- Ejecutar estas consultas en phpMyAdmin o mediante línea de comandos

-- Índices para mejorar rendimiento de consultas de pagos
CREATE INDEX IF NOT EXISTS idx_loan_items_pay_date ON loan_items(pay_date);
CREATE INDEX IF NOT EXISTS idx_loan_items_loan_id_status ON loan_items(loan_id, status);
CREATE INDEX IF NOT EXISTS idx_loan_items_status_date ON loan_items(status, date);
CREATE INDEX IF NOT EXISTS idx_payments_payment_date ON payments(payment_date);
CREATE INDEX IF NOT EXISTS idx_payments_loan_id ON payments(loan_id);

-- Índices adicionales para búsquedas y filtros
CREATE INDEX IF NOT EXISTS idx_customers_dni ON customers(dni);
CREATE INDEX IF NOT EXISTS idx_customers_first_name ON customers(first_name);
CREATE INDEX IF NOT EXISTS idx_customers_last_name ON customers(last_name);
CREATE INDEX IF NOT EXISTS idx_loans_customer_id_status ON loans(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_loans_status ON loans(status);

-- Índice compuesto para búsquedas de clientes
CREATE INDEX IF NOT EXISTS idx_customers_name_search ON customers(first_name, last_name, dni);
