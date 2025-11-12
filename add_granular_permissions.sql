-- Script para agregar permisos granulares al sistema de préstamos
-- Ejecutar después de update_permissions_sidebar.sql

USE prestamobd;

-- Agregar permisos granulares para submenús de Clientes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'customers_list', 1),
(1, 'customers_overdue', 1),
(2, 'customers_list', 1),
(2, 'customers_overdue', 1),
(3, 'customers_list', 1),
(3, 'customers_overdue', 0),
(4, 'customers_list', 1),
(4, 'customers_overdue', 0),
(5, 'customers_list', 1),
(5, 'customers_overdue', 0);

-- Agregar permisos granulares para submenús de Reportes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'reports_collector_commissions', 1),
(1, 'reports_admin_commissions', 1),
(1, 'reports_general_customer', 1),
(2, 'reports_collector_commissions', 1),
(2, 'reports_admin_commissions', 1),
(2, 'reports_general_customer', 1),
(3, 'reports_collector_commissions', 0),
(3, 'reports_admin_commissions', 0),
(3, 'reports_general_customer', 1),
(4, 'reports_collector_commissions', 0),
(4, 'reports_admin_commissions', 0),
(4, 'reports_general_customer', 1),
(5, 'reports_collector_commissions', 0),
(5, 'reports_admin_commissions', 0),
(5, 'reports_general_customer', 1);

-- Agregar permisos granulares para submenús de Configuración
INSERT IGNORE INTO user_permissions (user_id, permission_name, value) VALUES
(1, 'config_edit_data', 1),
(1, 'config_change_password', 1),
(2, 'config_edit_data', 1),
(2, 'config_change_password', 1),
(3, 'config_edit_data', 0),
(3, 'config_change_password', 1),
(4, 'config_edit_data', 0),
(4, 'config_change_password', 1),
(5, 'config_edit_data', 0),
(5, 'config_change_password', 1);

-- Verificar que los permisos se agregaron correctamente
SELECT
    u.first_name,
    u.last_name,
    up.permission_name,
    up.value
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
WHERE up.permission_name IN (
    'customers_list', 'customers_overdue',
    'reports_collector_commissions', 'reports_admin_commissions', 'reports_general_customer',
    'config_edit_data', 'config_change_password'
)
ORDER BY u.id, up.permission_name;

COMMIT;