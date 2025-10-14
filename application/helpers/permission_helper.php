<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Helper - Funciones para control de permisos y roles
 * 
 * Este helper proporciona funciones para verificar permisos de usuario
 * basados en roles y secciones del sistema.
 */

if (!function_exists('has_role')) {
    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param string $user_role Rol del usuario actual
     * @param string $required_role Rol requerido
     * @return bool True si el usuario tiene el rol requerido
     */
    function has_role($user_role, $required_role) {
        if (empty($user_role)) {
            return false;
        }
        
        // Admin tiene acceso a todo
        if ($user_role === 'admin') {
            return true;
        }
        
        // Verificar rol específico
        return $user_role === $required_role;
    }
}

if (!function_exists('can_view')) {
      /**
       * Verifica si el usuario puede ver una sección específica
       *
       * @param string $user_role Rol del usuario actual
       * @param string $section Sección a verificar
       * @return bool True si el usuario puede ver la sección
       */
      function can_view($user_role, $section) {
          // LOG DIAGNÓSTICO
          $log_msg = "[DIAGNOSTIC] can_view called: role='$user_role', section='$section'";
          error_log($log_msg);

          if (empty($user_role)) {
              error_log("[DIAGNOSTIC] can_view: empty user_role, returning false");
              return false;
          }

          // Admin puede ver todo
          if ($user_role === 'admin') {
              error_log("[DIAGNOSTIC] can_view: admin role, returning true");
              return true;
          }

          // Priorizar permisos de sesión/BD sobre defaults por rol
          $CI =& get_instance();
          $user_id = $CI->session->userdata('user_id');
          error_log("[DIAGNOSTIC] can_view: user_id from session='$user_id'");

          if ($user_id) {
              // Primero verificar permisos en sesión
              $session_permissions = $CI->session->userdata('permissions');
              if ($session_permissions && isset($session_permissions[$section])) {
                  $result = (bool) $session_permissions[$section];
                  error_log("[DIAGNOSTIC] can_view: found permission in session for '$section', returning " . ($result ? 'true' : 'false'));
                  return $result;
              }

              // Si no está en sesión, obtener de BD y actualizar sesión
              $CI->load->model('user_m');
              $user_permissions = $CI->user_m->get_permissions($user_id);
              error_log("[DIAGNOSTIC] can_view: got " . count($user_permissions) . " permissions from get_permissions");

              // Actualizar sesión con permisos de BD
              $permissions_array = [];
              foreach ($user_permissions as $perm) {
                  $permissions_array[$perm['permission_name']] = (int) $perm['value'];
              }
              $CI->session->set_userdata('permissions', $permissions_array);

              // Buscar el permiso específico
              if (isset($permissions_array[$section])) {
                  $result = (bool) $permissions_array[$section];
                  error_log("[DIAGNOSTIC] can_view: found permission in DB for '$section', returning " . ($result ? 'true' : 'false'));
                  return $result;
              }
          }

          // Si no hay permisos granulares explícitos, usar permisos por defecto del rol
          error_log("[DIAGNOSTIC] can_view: no granular permission found for '$section', using role defaults");
          $default_permissions = [
              'admin' => [
                  'dashboard' => true, 'customers' => true, 'loans' => true, 'payments' => true, 'reports' => true, 'config' => true
              ],
              'operador' => [
                  'dashboard' => true, 'customers' => true, 'loans' => true, 'payments' => true, 'reports' => true, 'config' => false
              ],
              'viewer' => [
                  'dashboard' => true, 'customers' => false, 'loans' => false, 'payments' => false, 'reports' => true, 'config' => false
              ]
          ];

          $role_defaults = isset($default_permissions[$user_role]) ? $default_permissions[$user_role] : $default_permissions['viewer'];
          $result = isset($role_defaults[$section]) ? $role_defaults[$section] : false;
          error_log("[DIAGNOSTIC] can_view: role default for '$section' = " . ($result ? 'true' : 'false'));
          return $result;
      }
  }

if (!function_exists('can_edit')) {
    /**
     * Verifica si el usuario puede editar en una sección específica
     *
     * @param string $user_role Rol del usuario actual
     * @param string $section Sección a verificar
     * @return bool True si el usuario puede editar en la sección
     */
    function can_edit($user_role, $section) {
        if (empty($user_role)) {
            return false;
        }

        // Admin puede editar todo
        if ($user_role === 'admin') {
            return true;
        }

        // Para otros roles, verificar permisos granulares (por ahora mantenemos lógica de operador)
        // TODO: Implementar permisos de edición granulares si es necesario
        if ($user_role === 'operador') {
            $editable_sections = [
                'customers',
                'loans',
                'payments'
            ];
            return in_array($section, $editable_sections);
        }

        return false;
    }
}

if (!function_exists('can_delete')) {
    /**
     * Verifica si el usuario puede eliminar en una sección específica
     * 
     * @param string $user_role Rol del usuario actual
     * @param string $section Sección a verificar
     * @return bool True si el usuario puede eliminar en la sección
     */
    function can_delete($user_role, $section) {
        if (empty($user_role)) {
            return false;
        }
        
        // Solo admin puede eliminar
        return $user_role === 'admin';
    }
}

if (!function_exists('get_user_role')) {
    /**
     * Obtiene el rol del usuario actual desde la sesión
     * 
     * @return string|false Rol del usuario o false si no está logueado
     */
    function get_user_role() {
        $CI =& get_instance();
        
        if ($CI->session->userdata('loggedin')) {
            return $CI->session->userdata('perfil');
        }
        
        return false;
    }
}

if (!function_exists('get_user_id')) {
    /**
     * Obtiene el ID del usuario actual desde la sesión
     * 
     * @return int|false ID del usuario o false si no está logueado
     */
    function get_user_id() {
        $CI =& get_instance();
        
        if ($CI->session->userdata('loggedin')) {
            return $CI->session->userdata('user_id');
        }
        
        return false;
    }
}

if (!function_exists('require_role')) {
    /**
     * Requiere que el usuario tenga un rol específico
     * Redirige al login si no cumple el requisito
     * 
     * @param string $required_role Rol requerido
     * @return void
     */
    function require_role($required_role) {
        $user_role = get_user_role();
        
        if (!$user_role || !has_role($user_role, $required_role)) {
            $CI =& get_instance();
            $CI->session->set_flashdata('error', 'No tiene permisos para acceder a esta sección');
            redirect('user/login');
        }
    }
}

if (!function_exists('require_permission')) {
    /**
     * Requiere que el usuario tenga permiso para ver una sección
     * Redirige al dashboard si no cumple el requisito
     * 
     * @param string $section Sección requerida
     * @return void
     */
    function require_permission($section) {
        $user_role = get_user_role();
        
        if (!$user_role || !can_view($user_role, $section)) {
            $CI =& get_instance();
            $CI->session->set_flashdata('error', 'No tiene permisos para acceder a esta sección');
            redirect('admin/dashboard');
        }
    }
}
