-- Tabla para tracking de envío de comisiones del 40%
-- Esta tabla registra cuando los cobradores envían su parte al administrador

CREATE TABLE IF NOT EXISTS `collector_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID del cobrador (FK to users)',
  `loan_id` int(11) DEFAULT NULL COMMENT 'ID del préstamo específico',
  `client_id` int(11) DEFAULT NULL COMMENT 'ID del cliente específico',
  `total_interest` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de intereses cobrados',
  `commission_40` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Comisión del 40% a enviar',
  `status` enum('pendiente','enviado') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado del envío',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha cuando se marcó como enviado',
  `period_start` date DEFAULT NULL COMMENT 'Inicio del período de comisión',
  `period_end` date DEFAULT NULL COMMENT 'Fin del período de comisión',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `loan_id` (`loan_id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `period_range` (`period_start`, `period_end`),
  KEY `commission_status` (`user_id`, `loan_id`, `client_id`, `status`),
  CONSTRAINT `fk_collector_commissions_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para tracking de envío de comisiones del 40% por cobradores - por préstamo/cliente';

-- Índices adicionales para optimización
ALTER TABLE `collector_commissions`
  ADD INDEX `idx_user_status_date` (`user_id`, `status`, `created_at`),
  ADD INDEX `idx_period_status` (`period_start`, `period_end`, `status`),
  ADD INDEX `idx_loan_client_status` (`loan_id`, `client_id`, `status`);

-- Comentarios explicativos
/*
Esta tabla permite:
1. Tracking de qué cobradores han enviado su 40% de comisiones
2. Historial de envíos por períodos
3. Estados: 'pendiente' (no enviado) o 'enviado' (ya enviado al admin)
4. Relación con users para identificar cobradores
5. Fechas de período para agrupar comisiones por tiempo
*/