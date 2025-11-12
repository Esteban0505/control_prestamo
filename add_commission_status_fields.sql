-- Agregar columnas faltantes a collector_commissions para el sistema de envío
-- Ejecutar este script si la tabla no tiene estos campos

ALTER TABLE `collector_commissions`
ADD COLUMN IF NOT EXISTS `status` enum('pendiente','enviado','pagado') DEFAULT 'pendiente' COMMENT 'Estado de la comisión' AFTER `commission`,
ADD COLUMN IF NOT EXISTS `sent_at` datetime DEFAULT NULL COMMENT 'Fecha de envío al administrador' AFTER `status`,
ADD COLUMN IF NOT EXISTS `period_start` date DEFAULT NULL COMMENT 'Inicio del período' AFTER `sent_at`,
ADD COLUMN IF NOT EXISTS `period_end` date DEFAULT NULL COMMENT 'Fin del período' AFTER `period_start`,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `period_end`;

-- Agregar índices para mejor rendimiento
ALTER TABLE `collector_commissions`
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_period` (`period_start`, `period_end`),
ADD INDEX IF NOT EXISTS `idx_sent_at` (`sent_at`);

-- Actualizar registros existentes a estado 'pendiente' si no tienen estado
UPDATE `collector_commissions` SET `status` = 'pendiente' WHERE `status` IS NULL;


