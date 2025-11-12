-- Agregar nuevos permisos granulares para submenús
-- Ejecutar después de revisar la estructura actual

-- Permisos para Clientes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'customers_list', 1),
(1, 'customers_overdue', 1),
(2, 'customers_list', 1),
(2, 'customers_overdue', 1),
(3, 'customers_list', 1),
(3, 'customers_overdue', 1),
(4, 'customers_list', 1),
(4, 'customers_overdue', 1),
(5, 'customers_list', 1),
(5, 'customers_overdue', 1);

-- Permisos para Reportes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'reports_collector_commissions', 1),
(1, 'reports_admin_commissions', 1),
(1, 'reports_general_customer', 1),
(2, 'reports_collector_commissions', 1),
(2, 'reports_admin_commissions', 1),
(2, 'reports_general_customer', 1),
(3, 'reports_collector_commissions', 1),
(3, 'reports_admin_commissions', 1),
(3, 'reports_general_customer', 1),
(4, 'reports_collector_commissions', 1),
(4, 'reports_admin_commissions', 1),
(4, 'reports_general_customer', 1),
(5, 'reports_collector_commissions', 1),
(5, 'reports_admin_commissions', 1),
(5, 'reports_general_customer', 1);

-- Permisos para Configuración
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'config_edit_data', 1),
(1, 'config_change_password', 1),
(2, 'config_edit_data', 1),
(2, 'config_change_password', 1),
(3, 'config_edit_data', 0),
(3, 'config_change_password', 1),
(4, 'config_edit_data', 0),
(4, 'config_change_password', 1),
(5, 'config_edit_data', 1),
(5, 'config_change_password', 1);