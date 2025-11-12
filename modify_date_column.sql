ALTER TABLE loans MODIFY COLUMN date DATETIME NOT NULL;  
ALTER TABLE loans ADD COLUMN payment_start_day TINYINT(2) NOT NULL DEFAULT 1 COMMENT 'Dia del mes para inicio de cobros (1-31)'; 
