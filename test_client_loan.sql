-- Script para crear cliente de prueba con DNI 1152675687 y un préstamo activo
-- Fecha: 2025-11-05

-- Verificar si el cliente ya existe
SELECT * FROM customers WHERE dni = '1152675687';

-- Si no existe, insertar el cliente de prueba
INSERT INTO customers (id, dni, first_name, last_name, gender, department_id, province_id, district_id, address, mobile, phone_fixed, phone, user_id, ruc, company, loan_status, tipo_cliente, tope_manual, secondary_phone)
VALUES (51, '1152675687', 'Juan', 'Pérez', 'masculino', 1, '0101', '010101', 'Calle Principal 123', '3001234567', NULL, 'juan.perez@email.com', 1, NULL, 'Cliente de prueba para testing de pagos', 1, 'normal', NULL, NULL)
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    loan_status = 1;

-- Crear un préstamo activo para el cliente
-- Usando amortización francesa, mensual, 12 cuotas, 10% interés
INSERT INTO loans (id, num_prestamo, customer_id, credit_amount, balance, interest_amount, num_fee, fee_amount, payment_m, amortization_type, coin_id, date, status, created_by, assigned_user_id, tipo_cliente, payment_start_day)
VALUES (100, 100, (SELECT id FROM customers WHERE dni = '1152675687'), '10000.00', '10000.00', '10.00', 12, '879.16', 'mensual', 'francesa', 1, CURDATE(), b'1', 1, 1, 'normal', 1)
ON DUPLICATE KEY UPDATE
    balance = VALUES(balance),
    status = b'1';

-- Generar las cuotas del préstamo (loan_items)
-- Para amortización francesa, necesitamos calcular cada cuota
-- Cuota mensual aproximada: 879.16 para 10000 a 10% en 12 meses

-- Cuota 1: Fecha actual + 1 mes
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1001, 100, DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 1, '879.16', '795.83', '0.00', '83.33', '0.00', '9204.17', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 2
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1002, 100, DATE_ADD(CURDATE(), INTERVAL 2 MONTH), 2, '879.16', '808.33', '0.00', '70.83', '0.00', '8395.84', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 3
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1003, 100, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 3, '879.16', '821.25', '0.00', '57.91', '0.00', '7574.59', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 4
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1004, 100, DATE_ADD(CURDATE(), INTERVAL 4 MONTH), 4, '879.16', '834.58', '0.00', '44.58', '0.00', '6740.01', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 5
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1005, 100, DATE_ADD(CURDATE(), INTERVAL 5 MONTH), 5, '879.16', '848.33', '0.00', '30.83', '0.00', '5891.68', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 6
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1006, 100, DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 6, '879.16', '862.50', '0.00', '16.66', '0.00', '5029.18', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 7
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1007, 100, DATE_ADD(CURDATE(), INTERVAL 7 MONTH), 7, '879.16', '877.08', '0.00', '2.08', '0.00', '4152.10', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 8
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1008, 100, DATE_ADD(CURDATE(), INTERVAL 8 MONTH), 8, '879.16', '879.16', '0.00', '0.00', '0.00', '3272.94', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 9
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1009, 100, DATE_ADD(CURDATE(), INTERVAL 9 MONTH), 9, '879.16', '879.16', '0.00', '0.00', '0.00', '2393.78', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 10
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1010, 100, DATE_ADD(CURDATE(), INTERVAL 10 MONTH), 10, '879.16', '879.16', '0.00', '0.00', '0.00', '1514.62', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 11
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1011, 100, DATE_ADD(CURDATE(), INTERVAL 11 MONTH), 11, '879.16', '879.16', '0.00', '0.00', '0.00', '635.46', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Cuota 12 (última cuota)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, capital_amount, capital_paid, interest_amount, interest_paid, balance, extra_payment, payment_desc, pay_date, status, paid_by)
VALUES (1012, 100, DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 12, '879.16', '635.46', '0.00', '0.00', '0.00', '0.00', '0.00', NULL, NOW(), b'1', NULL)
ON DUPLICATE KEY UPDATE status = b'1';

-- Crear algunos pagos de prueba
-- Pago de la primera cuota
INSERT INTO payments (id, loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes, created_at)
VALUES (1001, 100, 1001, '879.16', 'cuota', '879.16', '83.33', '795.83', NOW(), 1, 'efectivo', 'Pago de primera cuota - Prueba', NOW())
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Actualizar el loan_item pagado
UPDATE loan_items SET
    capital_paid = '795.83',
    interest_paid = '83.33',
    status = b'0',
    pay_date = NOW(),
    paid_by = 1
WHERE id = 1001;

-- Pago de la segunda cuota
INSERT INTO payments (id, loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes, created_at)
VALUES (1002, 100, 1002, '879.16', 'cuota', '879.16', '70.83', '808.33', NOW(), 1, 'efectivo', 'Pago de segunda cuota - Prueba', NOW())
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Actualizar el loan_item pagado
UPDATE loan_items SET
    capital_paid = '808.33',
    interest_paid = '70.83',
    status = b'0',
    pay_date = NOW(),
    paid_by = 1
WHERE id = 1002;

-- Verificar los datos creados
SELECT 'Cliente creado:' as info, c.* FROM customers c WHERE c.dni = '1152675687';
SELECT 'Préstamo creado:' as info, l.* FROM loans l WHERE l.customer_id = (SELECT id FROM customers WHERE dni = '1152675687') AND l.status = b'1';
SELECT 'Cuotas del préstamo:' as info, li.* FROM loan_items li WHERE li.loan_id = 100 ORDER BY li.num_quota;
SELECT 'Pagos realizados:' as info, p.* FROM payments p WHERE p.loan_id = 100 ORDER BY p.payment_date;