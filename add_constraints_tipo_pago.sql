-- Script para agregar constraints de base de datos para el campo tipo_pago
-- Ejecutar después de verificar que no hay datos inválidos en la tabla payments

-- 1. Verificar datos existentes antes de agregar constraints
SELECT DISTINCT tipo_pago FROM payments WHERE tipo_pago IS NOT NULL;

-- 2. Agregar constraint CHECK para tipo_pago válido
ALTER TABLE payments
ADD CONSTRAINT chk_tipo_pago_valid
CHECK (tipo_pago IN ('full', 'interest', 'capital', 'both', 'total', 'custom', 'early_total', 'total_condonacion'));

-- 3. Agregar constraint NOT NULL para tipo_pago (después de verificar que no hay NULLs)
-- Primero verificar si hay valores NULL:
SELECT COUNT(*) as null_count FROM payments WHERE tipo_pago IS NULL;

-- Si no hay NULLs, ejecutar:
-- ALTER TABLE payments MODIFY tipo_pago VARCHAR(50) NOT NULL;

-- 4. Crear índice para mejorar rendimiento de consultas por tipo_pago
CREATE INDEX idx_payments_tipo_pago ON payments(tipo_pago);

-- 5. Verificar estructura final de la tabla
DESCRIBE payments;

-- 6. Script de rollback si es necesario
-- ALTER TABLE payments DROP CONSTRAINT chk_tipo_pago_valid;
-- DROP INDEX idx_payments_tipo_pago ON payments;