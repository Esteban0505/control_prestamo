-- Crear tabla collector_commissions si no existe
-- Esta tabla almacena las comisiones del 40% calculadas automáticamente después de cada pago

CREATE TABLE IF NOT EXISTS `collector_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID del cobrador',
  `loan_id` int(11) DEFAULT NULL COMMENT 'ID del préstamo',
  `loan_item_id` int(11) DEFAULT NULL COMMENT 'ID del ítem de pago específico',
  `client_name` varchar(255) DEFAULT NULL COMMENT 'Nombre del cliente',
  `client_cedula` varchar(50) DEFAULT NULL COMMENT 'Cédula del cliente',
  `amount` decimal(15,2) NOT NULL COMMENT 'Monto total del pago',
  `commission` decimal(15,2) NOT NULL COMMENT 'Comisión del 40%',
  `status` enum('pendiente','enviado','pagado') DEFAULT 'pendiente' COMMENT 'Estado de la comisión',
  `sent_at` datetime DEFAULT NULL COMMENT 'Fecha de envío al administrador',
  `period_start` date DEFAULT NULL COMMENT 'Inicio del período',
  `period_end` date DEFAULT NULL COMMENT 'Fin del período',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_loan_item_id` (`loan_item_id`),
  KEY `idx_status` (`status`),
  KEY `idx_period` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Comisiones del 40% para cobradores';

-- Agregar índices adicionales para mejor rendimiento
ALTER TABLE `collector_commissions`
  ADD CONSTRAINT `fk_collector_commissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collector_commissions_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_collector_commissions_loan_item` FOREIGN KEY (`loan_item_id`) REFERENCES `loan_items` (`id`) ON DELETE SET NULL;