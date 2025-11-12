-- Agregar columna total_interest a la tabla collector_commissions
-- Esta columna almacena el monto total de intereses pagados por el cobrador en el período

ALTER TABLE `collector_commissions`
ADD COLUMN `total_interest` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto total de intereses pagados en el período' AFTER `commission`;