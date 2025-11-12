-- Script para actualizar completamente el sistema de permisos
-- Incluye todos los permisos granulares para sidebar según la estructura actual
-- Ejecutar este script para agregar permisos sin duplicados

-- Permisos principales del sidebar
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'dashboard', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'sidebar', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'sidebar_back', 1 FROM users u WHERE u.active = 1;

-- Permisos para Clientes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'customers', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'customers_list', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'customers_overdue', 1 FROM users u WHERE u.active = 1;

-- Permisos para Monedas
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'coins', 1 FROM users u WHERE u.active = 1;

-- Permisos para Préstamos
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'loans', 1 FROM users u WHERE u.active = 1;

-- Permisos para Cobranzas
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'payments', 1 FROM users u WHERE u.active = 1;

-- Permisos para Reportes
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'reports', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'reports_collector_commissions', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'reports_admin_commissions', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'reports_general_customer', 1 FROM users u WHERE u.active = 1;

-- Permisos para Configuración
INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'config', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'config_edit_data', 1 FROM users u WHERE u.active = 1;

INSERT IGNORE INTO user_permissions (user_id, permission_name, value)
SELECT u.user_id, 'config_change_password', 1 FROM users u WHERE u.active = 1;

-- Verificación: Mostrar permisos agregados
SELECT 'Permisos actualizados correctamente' as status,
       COUNT(*) as total_permissions_added
FROM user_permissions
WHERE permission_name IN (
    'dashboard', 'sidebar', 'sidebar_back', 'customers', 'customers_list', 'customers_overdue',
    'coins', 'loans', 'payments', 'reports', 'reports_collector_commissions',
    'reports_admin_commissions', 'reports_general_customer', 'config',
    'config_edit_data', 'config_change_password'
);