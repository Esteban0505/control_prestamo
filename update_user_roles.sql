-- Script de migración para cambiar nomenclatura de roles de usuario
-- "viewer" → "Visitante" y "operador" → "Colaborador"

-- Actualizar valores en la tabla users
UPDATE users SET perfil = 'Visitante' WHERE perfil = 'viewer';
UPDATE users SET perfil = 'Colaborador' WHERE perfil = 'operador';

-- Actualizar valores en la tabla users (campo role si existe)
UPDATE users SET role = 'Visitante' WHERE role = 'viewer';
UPDATE users SET role = 'Colaborador' WHERE role = 'operador';

-- Verificar cambios
SELECT 'Usuarios actualizados:' as info, COUNT(*) as total FROM users WHERE perfil IN ('Visitante', 'Colaborador');
SELECT perfil, COUNT(*) as cantidad FROM users GROUP BY perfil ORDER BY perfil;

-- Mostrar usuarios afectados
SELECT id, first_name, last_name, email, perfil, role FROM users WHERE perfil IN ('Visitante', 'Colaborador') ORDER BY perfil, id;