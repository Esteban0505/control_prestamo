-- Agregar campo status a la tabla customers para activar/desactivar clientes
ALTER TABLE `customers` 
ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado del cliente: 1=Activo, 0=Inactivo' AFTER `tope_manual`;

-- Crear índice para mejorar rendimiento en consultas por estado
CREATE INDEX `idx_customers_status` ON `customers`(`status`);

-- Actualizar todos los clientes existentes como activos por defecto
UPDATE `customers` SET `status` = 1 WHERE `status` IS NULL OR `status` = 0;








