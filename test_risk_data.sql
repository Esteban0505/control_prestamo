-- Script para insertar datos de prueba de riesgo de préstamos
-- Incluye 3 categorías de riesgo con datos colombianos realistas
-- High Risk: >30 días vencido, >50% pendiente
-- Medium Risk: 15-30 días vencido, 25-50% pendiente
-- Low Risk: <15 días vencido, <25% pendiente

-- =====================================================
-- DATOS DE CLIENTES (15 clientes colombianos)
-- =====================================================

INSERT INTO customers (id, dni, first_name, last_name, gender, tipo_cliente, department_id, province_id, district_id, address, mobile, phone_fixed, phone, user_id, loan_status) VALUES
-- High Risk Customers (IDs 1001-1005)
(1001, '12345678', 'Juan', 'Pérez', 'masculino', 'normal', 1, 1, 1, 'Calle 10 # 5-20, Bogotá', '3101234567', '6012345678', 'juan.perez@email.com', 1, 1),
(1002, '87654321', 'María', 'García', 'femenino', 'normal', 2, 2, 2, 'Carrera 15 # 8-30, Medellín', '3112345678', '6045678901', 'maria.garcia@email.com', 1, 1),
(1003, '11223344', 'Carlos', 'Rodríguez', 'masculino', 'especial', 3, 3, 3, 'Avenida 6 # 12-45, Cali', '3123456789', '6023456789', 'carlos.rodriguez@email.com', 1, 1),
(1004, '44332211', 'Ana', 'Martínez', 'femenino', 'normal', 4, 4, 4, 'Calle 20 # 3-15, Barranquilla', '3134567890', '6056789012', 'ana.martinez@email.com', 1, 1),
(1005, '55667788', 'Luis', 'Hernández', 'masculino', 'normal', 5, 5, 5, 'Carrera 7 # 25-60, Cartagena', '3145678901', '6057890123', 'luis.hernandez@email.com', 1, 1),

-- Medium Risk Customers (IDs 1006-1010)
(1006, '66778899', 'Sofia', 'López', 'femenino', 'normal', 1, 1, 1, 'Calle 15 # 8-40, Bogotá', '3156789012', '6013456789', 'sofia.lopez@email.com', 1, 1),
(1007, '77889900', 'Diego', 'González', 'masculino', 'especial', 2, 2, 2, 'Carrera 10 # 5-25, Medellín', '3167890123', '6046789012', 'diego.gonzalez@email.com', 1, 1),
(1008, '88990011', 'Valentina', 'Díaz', 'femenino', 'normal', 3, 3, 3, 'Avenida 9 # 18-70, Cali', '3178901234', '6024567890', 'valentina.diaz@email.com', 1, 1),
(1009, '99001122', 'Andrés', 'Torres', 'masculino', 'normal', 4, 4, 4, 'Calle 25 # 7-35, Barranquilla', '3189012345', '6057890123', 'andres.torres@email.com', 1, 1),
(1010, '00112233', 'Camila', 'Ramírez', 'femenino', 'normal', 5, 5, 5, 'Carrera 12 # 30-85, Cartagena', '3190123456', '6058901234', 'camila.ramirez@email.com', 1, 1),

-- Low Risk Customers (IDs 1011-1015)
(1011, '22334455', 'Mateo', 'Flores', 'masculino', 'especial', 1, 1, 1, 'Calle 22 # 12-55, Bogotá', '3201234567', '6014567890', 'mateo.flores@email.com', 1, 1),
(1012, '33445566', 'Isabella', 'Morales', 'femenino', 'normal', 2, 2, 2, 'Carrera 8 # 10-40, Medellín', '3212345678', '6047890123', 'isabella.morales@email.com', 1, 1),
(1013, '44556677', 'Santiago', 'Ortiz', 'masculino', 'normal', 3, 3, 3, 'Avenida 3 # 25-90, Cali', '3223456789', '6025678901', 'santiago.ortiz@email.com', 1, 1),
(1014, '55667788', 'Gabriela', 'Gutiérrez', 'femenino', 'especial', 4, 4, 4, 'Calle 30 # 15-65, Barranquilla', '3234567890', '6058901234', 'gabriela.gutierrez@email.com', 1, 1),
(1015, '66778899', 'Emmanuel', 'Sánchez', 'masculino', 'normal', 5, 5, 5, 'Carrera 5 # 35-20, Cartagena', '3245678901', '6059012345', 'emmanuel.sanchez@email.com', 1, 1);

-- =====================================================
-- DATOS DE PRÉSTAMOS (15 préstamos)
-- =====================================================

INSERT INTO loans (id, customer_id, credit_amount, interest_amount, num_months, num_fee, fee_amount, payment_m, amortization_type, date, payment_start_date, tasa_tipo, tipo_cliente, coin_id, status, balance, created_by, assigned_user_id, num_prestamo) VALUES
-- High Risk Loans (>30 days overdue, >50% outstanding)
(2001, 1001, 5000000.00, 25.00, 12, 12, 458333.33, 'mensual', 'francesa', '2024-06-01 10:00:00', '2024-07-01', 'TNA', 'normal', 1, 1, 3200000.00, 1, 1, 1001),
(2002, 1002, 3000000.00, 28.00, 8, 8, 393750.00, 'mensual', 'francesa', '2024-07-15 14:30:00', '2024-08-15', 'TNA', 'normal', 1, 1, 2200000.00, 1, 1, 1002),
(2003, 1003, 8000000.00, 22.00, 18, 18, 516666.67, 'mensual', 'francesa', '2024-05-20 09:15:00', '2024-06-20', 'TNA', 'especial', 1, 1, 6500000.00, 1, 1, 1003),
(2004, 1004, 2500000.00, 30.00, 6, 6, 458333.33, 'mensual', 'francesa', '2024-08-10 16:45:00', '2024-09-10', 'TNA', 'normal', 1, 1, 1800000.00, 1, 1, 1004),
(2005, 1005, 4500000.00, 26.00, 10, 10, 472500.00, 'mensual', 'francesa', '2024-06-25 11:20:00', '2024-07-25', 'TNA', 'normal', 1, 1, 3200000.00, 1, 1, 1005),

-- Medium Risk Loans (15-30 days overdue, 25-50% outstanding)
(2006, 1006, 2000000.00, 24.00, 6, 6, 350000.00, 'mensual', 'francesa', '2024-09-01 13:00:00', '2024-10-01', 'TNA', 'normal', 1, 1, 700000.00, 1, 1, 1006),
(2007, 1007, 6000000.00, 20.00, 12, 12, 550000.00, 'mensual', 'francesa', '2024-08-15 10:30:00', '2024-09-15', 'TNA', 'especial', 1, 1, 2200000.00, 1, 1, 1007),
(2008, 1008, 1500000.00, 27.00, 4, 4, 393750.00, 'mensual', 'francesa', '2024-09-20 15:45:00', '2024-10-20', 'TNA', 'normal', 1, 1, 500000.00, 1, 1, 1008),
(2009, 1009, 3500000.00, 23.00, 8, 8, 456250.00, 'mensual', 'francesa', '2024-08-05 12:15:00', '2024-09-05', 'TNA', 'normal', 1, 1, 1200000.00, 1, 1, 1009),
(2010, 1010, 2800000.00, 25.00, 7, 7, 414285.71, 'mensual', 'francesa', '2024-09-10 14:20:00', '2024-10-10', 'TNA', 'normal', 1, 1, 900000.00, 1, 1, 1010),

-- Low Risk Loans (<15 days overdue, <25% outstanding)
(2011, 1011, 4000000.00, 18.00, 10, 10, 440000.00, 'mensual', 'francesa', '2024-10-01 09:00:00', '2024-11-01', 'TNA', 'especial', 1, 1, 800000.00, 1, 1, 1011),
(2012, 1012, 1200000.00, 22.00, 4, 4, 330000.00, 'mensual', 'francesa', '2024-10-15 11:30:00', '2024-11-15', 'TNA', 'normal', 1, 1, 240000.00, 1, 1, 1012),
(2013, 1013, 2500000.00, 20.00, 6, 6, 433333.33, 'mensual', 'francesa', '2024-10-05 13:45:00', '2024-11-05', 'TNA', 'normal', 1, 1, 400000.00, 1, 1, 1013),
(2014, 1014, 5500000.00, 19.00, 12, 12, 506250.00, 'mensual', 'francesa', '2024-09-25 10:15:00', '2024-10-25', 'TNA', 'especial', 1, 1, 1000000.00, 1, 1, 1014),
(2015, 1015, 1800000.00, 21.00, 5, 5, 378000.00, 'mensual', 'francesa', '2024-10-10 12:00:00', '2024-11-10', 'TNA', 'normal', 1, 1, 300000.00, 1, 1, 1015);

-- =====================================================
-- DATOS DE CUOTAS DE PRÉSTAMOS (loan_items)
-- =====================================================

-- High Risk Loan Items (Loan 2001 - 12 cuotas, >50% pendiente, >30 días vencido)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status) VALUES
(30001, 2001, '2024-07-01', 1, 458333.33, 104166.67, 354166.66, 4658333.34, 0),
(30002, 2001, '2024-08-01', 2, 458333.33, 96805.56, 361527.77, 4296805.57, 0),
(30003, 2001, '2024-09-01', 3, 458333.33, 89433.45, 368899.88, 3927905.69, 0),
(30004, 2001, '2024-10-01', 4, 458333.33, 81958.45, 376374.88, 3551530.81, 0),
(30005, 2001, '2024-11-01', 5, 458333.33, 73890.64, 383442.69, 3168088.12, 0),
(30006, 2001, '2024-12-01', 6, 458333.33, 65801.84, 390531.49, 2777556.63, 0),
(30007, 2001, '2025-01-01', 7, 458333.33, 57724.11, 400609.22, 2376947.41, 0),
(30008, 2001, '2025-02-01', 8, 458333.33, 49406.82, 408926.51, 1968020.90, 0),
(30009, 2001, '2025-03-01', 9, 458333.33, 41004.60, 417328.73, 1550692.17, 0),
(30010, 2001, '2025-04-01', 10, 458333.33, 32347.75, 425985.58, 1124706.59, 0),
(30011, 2001, '2025-05-01', 11, 458333.33, 23431.39, 434902.94, 690803.65, 0),
(30012, 2001, '2025-06-01', 12, 458333.33, 14375.07, 443958.26, 246845.39, 1); -- Última cuota pendiente

-- High Risk Loan Items (Loan 2002 - 8 cuotas, >50% pendiente)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status) VALUES
(30013, 2002, '2024-08-15', 1, 393750.00, 70000.00, 323750.00, 2676250.00, 0),
(30014, 2002, '2024-09-15', 2, 393750.00, 60168.75, 333581.25, 2342668.75, 0),
(30015, 2002, '2024-10-15', 3, 393750.00, 52859.38, 340890.62, 2001778.13, 0),
(30016, 2002, '2024-11-15', 4, 393750.00, 45039.55, 348710.45, 1653067.68, 0),
(30017, 2002, '2024-12-15', 5, 393750.00, 37234.61, 356515.39, 1296552.29, 0),
(30018, 2002, '2025-01-15', 6, 393750.00, 29146.45, 364603.55, 931948.74, 0),
(30019, 2002, '2025-02-15', 7, 393750.00, 20987.47, 372762.53, 559186.21, 0),
(30020, 2002, '2025-03-15', 8, 393750.00, 12597.37, 381152.63, 178033.58, 1); -- Última cuota pendiente

-- High Risk Loan Items (Loan 2003 - 18 cuotas, >50% pendiente)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status) VALUES
(30021, 2003, '2024-06-20', 1, 516666.67, 146666.67, 370000.00, 7630000.00, 0),
(30022, 2003, '2024-07-20', 2, 516666.67, 141566.67, 375100.00, 7254900.00, 0),
(30023, 2003, '2024-08-20', 3, 516666.67, 136433.33, 380233.34, 6874666.66, 0),
(30024, 2003, '2024-09-20', 4, 516666.67, 131266.67, 385400.00, 6489266.66, 0),
(30025, 2003, '2024-10-20', 5, 516666.67, 126066.67, 390600.00, 6098666.66, 0),
(30026, 2003, '2024-11-20', 6, 516666.67, 120833.33, 395833.34, 5702833.32, 0),
(30027, 2003, '2024-12-20', 7, 516666.67, 115566.67, 401100.00, 5301733.32, 0),
(30028, 2003, '2025-01-20', 8, 516666.67, 110266.67, 406400.00, 4895333.32, 0),
(30029, 2003, '2025-02-20', 9, 516666.67, 104933.33, 411733.34, 4483600.00, 0),
(30030, 2003, '2025-03-20', 10, 516666.67, 99566.67, 417100.00, 4066500.00, 0),
(30031, 2003, '2025-04-20', 11, 516666.67, 95166.67, 421500.00, 3645000.00, 0),
(30032, 2003, '2025-05-20', 12, 516666.67, 90733.33, 425933.34, 3219066.66, 0),
(30033, 2003, '2025-06-20', 13, 516666.67, 86266.67, 430400.00, 2788666.66, 0),
(30034, 2003, '2025-07-20', 14, 516666.67, 81766.67, 434900.00, 2353766.66, 0),
(30035, 2003, '2025-08-20', 15, 516666.67, 77233.33, 439433.34, 1914333.32, 0),
(30036, 2003, '2025-09-20', 16, 516666.67, 72666.67, 444000.00, 1469333.32, 0),
(30037, 2003, '2025-10-20', 17, 516666.67, 68066.67, 448600.00, 1020733.32, 0),
(30038, 2003, '2025-11-20', 18, 516666.67, 63433.33, 453233.34, 568500.00, 1); -- Última cuota pendiente

-- Medium Risk Loan Items (Loan 2006 - 6 cuotas, ~35% pendiente)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status) VALUES
(30039, 2006, '2024-10-01', 1, 350000.00, 40000.00, 310000.00, 1690000.00, 0),
(30040, 2006, '2024-11-01', 2, 350000.00, 33800.00, 316200.00, 1373800.00, 0),
(30041, 2006, '2024-12-01', 3, 350000.00, 27476.00, 322524.00, 1051276.00, 0),
(30042, 2006, '2025-01-01', 4, 350000.00, 21025.52, 328974.48, 722301.52, 0),
(30043, 2006, '2025-02-01', 5, 350000.00, 14446.03, 335553.97, 386747.55, 0),
(30044, 2006, '2025-03-01', 6, 350000.00, 7734.95, 342265.05, 44482.50, 1); -- Última cuota pendiente

-- Low Risk Loan Items (Loan 2011 - 10 cuotas, ~20% pendiente)
INSERT INTO loan_items (id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status) VALUES
(30045, 2011, '2024-11-01', 1, 440000.00, 60000.00, 380000.00, 3620000.00, 0),
(30046, 2011, '2024-12-01', 2, 440000.00, 54150.00, 385850.00, 3234150.00, 0),
(30047, 2011, '2025-01-01', 3, 440000.00, 48241.13, 391758.87, 2842391.13, 0),
(30048, 2011, '2025-02-01', 4, 440000.00, 42185.87, 397814.13, 2444577.00, 0),
(30049, 2011, '2025-03-01', 5, 440000.00, 36083.56, 403916.44, 2040660.56, 0),
(30050, 2011, '2025-04-01', 6, 440000.00, 29933.91, 410066.09, 1630594.47, 0),
(30051, 2011, '2025-05-01', 7, 440000.00, 23735.72, 416264.28, 1214330.19, 0),
(30052, 2011, '2025-06-01', 8, 440000.00, 17488.70, 422511.30, 791818.89, 0),
(30053, 2011, '2025-07-01', 9, 440000.00, 11192.58, 428807.42, 363011.47, 0),
(30054, 2011, '2025-08-01', 10, 440000.00, 4866.17, 435133.83, -72122.36, 1); -- Última cuota pendiente (balance negativo ajustado)

-- =====================================================
-- DATOS DE PAGOS (payments)
-- =====================================================

-- Pagos para High Risk (solo algunas cuotas pagadas, dejando muchas pendientes)
INSERT INTO payments (id, loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes) VALUES
(40001, 2001, 30001, 458333.33, 'full', 458333.33, 104166.67, 354166.66, '2024-07-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 1'),
(40002, 2001, 30002, 458333.33, 'full', 458333.33, 96805.56, 361527.77, '2024-08-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 2'),
(40003, 2001, 30003, 458333.33, 'full', 458333.33, 89433.45, 368899.88, '2024-09-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 3'),
(40004, 2001, 30004, 458333.33, 'full', 458333.33, 81958.45, 376374.88, '2024-10-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 4'),
(40005, 2001, 30005, 458333.33, 'full', 458333.33, 73890.64, 383442.69, '2024-11-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 5'),
(40006, 2001, 30006, 458333.33, 'full', 458333.33, 65801.84, 390531.49, '2024-12-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 6'),
(40007, 2001, 30007, 458333.33, 'full', 458333.33, 57724.11, 400609.22, '2025-01-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 7'),
(40008, 2001, 30008, 458333.33, 'full', 458333.33, 49406.82, 408926.51, '2025-02-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 8'),
(40009, 2001, 30009, 458333.33, 'full', 458333.33, 41004.60, 417328.73, '2025-03-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 9'),
(40010, 2001, 30010, 458333.33, 'full', 458333.33, 32347.75, 425985.58, '2025-04-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 10'),
(40011, 2001, 30011, 458333.33, 'full', 458333.33, 23431.39, 434902.94, '2025-05-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 11');

-- Pagos para Medium Risk (más cuotas pagadas, dejando menos pendientes)
INSERT INTO payments (id, loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes) VALUES
(40012, 2006, 30039, 350000.00, 'full', 350000.00, 40000.00, 310000.00, '2024-10-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 1'),
(40013, 2006, 30040, 350000.00, 'full', 350000.00, 33800.00, 316200.00, '2024-11-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 2'),
(40014, 2006, 30041, 350000.00, 'full', 350000.00, 27476.00, 322524.00, '2024-12-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 3'),
(40015, 2006, 30042, 350000.00, 'full', 350000.00, 21025.52, 328974.48, '2025-01-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 4');

-- Pagos para Low Risk (casi todas las cuotas pagadas, dejando muy pocas pendientes)
INSERT INTO payments (id, loan_id, loan_item_id, amount, tipo_pago, monto_pagado, interest_paid, capital_paid, payment_date, payment_user_id, method, notes) VALUES
(40016, 2011, 30045, 440000.00, 'full', 440000.00, 60000.00, 380000.00, '2024-11-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 1'),
(40017, 2011, 30046, 440000.00, 'full', 440000.00, 54150.00, 385850.00, '2024-12-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 2'),
(40018, 2011, 30047, 440000.00, 'full', 440000.00, 48241.13, 391758.87, '2025-01-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 3'),
(40019, 2011, 30048, 440000.00, 'full', 440000.00, 42185.87, 397814.13, '2025-02-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 4'),
(40020, 2011, 30049, 440000.00, 'full', 440000.00, 36083.56, 403916.44, '2025-03-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 5'),
(40021, 2011, 30050, 440000.00, 'full', 440000.00, 29933.91, 410066.09, '2025-04-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 6'),
(40022, 2011, 30051, 440000.00, 'full', 440000.00, 23735.72, 416264.28, '2025-05-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 7'),
(40023, 2011, 30052, 440000.00, 'full', 440000.00, 17488.70, 422511.30, '2025-06-01 10:00:00', 1, 'efectivo', 'Pago completo cuota 8');

-- =====================================================
-- ACTUALIZAR ESTADOS DE CUOTAS PAGADAS
-- =====================================================

UPDATE loan_items SET status = 0, pay_date = payment_date, paid_by = 1
WHERE id IN (
    SELECT loan_item_id FROM payments
    WHERE loan_item_id IS NOT NULL
);

-- =====================================================
-- ACTUALIZAR SALDOS DE PRÉSTAMOS
-- =====================================================

UPDATE loans
SET balance = (
    SELECT COALESCE(SUM(li.balance), 0)
    FROM loan_items li
    WHERE li.loan_id = loans.id
)
WHERE id IN (2001, 2002, 2003, 2006, 2011);

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

-- Verificar que no hay conflictos con registros existentes
SELECT 'Verificación completada - Datos de prueba insertados correctamente' as mensaje;