-- Crear tabla para historial de cambios de estado de clientes
CREATE TABLE IF NOT EXISTS `customer_status_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `old_status` TINYINT(1) NOT NULL COMMENT 'Estado anterior: 1=Activo, 0=Inactivo',
  `new_status` TINYINT(1) NOT NULL COMMENT 'Estado nuevo: 1=Activo, 0=Inactivo',
  `action` ENUM('activated', 'deactivated') NOT NULL COMMENT 'Acción realizada',
  `changed_by` INT(11) NULL COMMENT 'ID del usuario que realizó el cambio',
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del cambio',
  `notes` TEXT NULL COMMENT 'Notas adicionales sobre el cambio',
  `ip_address` VARCHAR(45) NULL COMMENT 'Dirección IP desde donde se realizó el cambio',
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_changed_at` (`changed_at`),
  INDEX `idx_action` (`action`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci COMMENT='Historial de cambios de estado de clientes';








