-- Crear tabla loans_penalties faltante
-- Esta tabla registra las penalizaciones aplicadas a préstamos por mora

CREATE TABLE IF NOT EXISTS `loans_penalties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL COMMENT 'ID del préstamo penalizado',
  `customer_id` int(11) NOT NULL COMMENT 'ID del cliente penalizado',
  `penalty_reason` text NOT NULL COMMENT 'Razón de la penalización',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la penalización',
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_loans_penalties_loans` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loans_penalties_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para registrar penalizaciones aplicadas a préstamos por mora';

-- Insertar algunos datos de ejemplo si es necesario (opcional)
-- INSERT INTO `loans_penalties` (`loan_id`, `customer_id`, `penalty_reason`) VALUES
-- (1, 1, 'Mora mayor a 60 días - Penalización automática');