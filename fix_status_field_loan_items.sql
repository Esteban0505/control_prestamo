-- Script para corregir el campo status en la tabla loan_items
-- El campo status actualmente es bit(1) que solo permite 0 o 1
-- Necesitamos cambiarlo a tinyint(1) para permitir valores como 0, 1, 3, 4

ALTER TABLE loan_items MODIFY status TINYINT(1) NOT NULL DEFAULT 1;

-- Verificar el cambio
DESCRIBE loan_items;