<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_m extends MY_Model {

  protected $_table_name = 'users';

  public $user_rules = array(
    array(
      'field' => 'first_name',
      'rules' => 'trim|required|min_length[2]|max_length[150]',
      'errors' => array(
        'required' => 'El nombre es requerido',
        'min_length' => 'El nombre debe tener al menos 2 caracteres',
        'max_length' => 'El nombre no puede exceder 150 caracteres',
      ),
    ),
    array(
      'field' => 'last_name',
      'rules' => 'trim|required|min_length[2]|max_length[200]',
      'errors' => array(
        'required' => 'El apellido es requerido',
        'min_length' => 'El apellido debe tener al menos 2 caracteres',
        'max_length' => 'El apellido no puede exceder 200 caracteres',
      ),
    ),
    array(
      'field' => 'email',
      'rules' => 'trim|required|valid_email|max_length[150]',
      'errors' => array(
        'required' => 'El correo electrónico es requerido',
        'valid_email' => 'El formato del correo electrónico no es válido',
        'max_length' => 'El correo electrónico no puede exceder 150 caracteres',
      ),
    ),
    array(
      'field' => 'perfil',
      'rules' => 'trim|required|in_list[admin,operador,viewer]',
      'errors' => array(
        'required' => 'El perfil es requerido',
        'in_list' => 'El perfil debe ser admin, operador o viewer',
      ),
    ),
    array(
      'field' => 'password',
      'rules' => 'trim|min_length[6]',
      'errors' => array(
        'min_length' => 'La contraseña debe tener al menos 6 caracteres',
      ),
    )
  );

  public function get_users($limit = null, $offset = null, $search = null)
  {
    $this->db->select('id, first_name, last_name, email, perfil, estado, ultimo_login, fecha');
    $this->db->from('users');
    
    // Aplicar búsqueda si se proporciona
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like('first_name', $search);
      $this->db->or_like('last_name', $search);
      $this->db->or_like('email', $search);
      $this->db->or_like('perfil', $search);
      $this->db->group_end();
    }
    
    $this->db->order_by('fecha', 'DESC');
    
    // Aplicar paginación si se proporciona
    if ($limit !== null) {
      $this->db->limit($limit, $offset);
    }
    
    return $this->db->get()->result();
  }

  public function get_users_count($search = null)
  {
    $this->db->from('users');
    
    // Aplicar búsqueda si se proporciona
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like('first_name', $search);
      $this->db->or_like('last_name', $search);
      $this->db->or_like('email', $search);
      $this->db->or_like('perfil', $search);
      $this->db->group_end();
    }
    
    return $this->db->count_all_results();
  }

  public function get_user($id)
  {
    $this->db->where('id', $id);
    return $this->db->get('users')->row();
  }

  public function get_user_by_email($email, $exclude_id = null)
  {
    $this->db->where('email', $email);
    if ($exclude_id) {
      $this->db->where('id !=', $exclude_id);
    }
    return $this->db->get('users')->row();
  }

  public function create_user($data)
  {
    // Cifrar contraseña si se proporciona
    if (!empty($data['password'])) {
      $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    // Establecer valores por defecto
    $data['estado'] = isset($data['estado']) ? $data['estado'] : 1;
    $data['fecha'] = date('Y-m-d H:i:s');

    log_message('debug', 'Datos para crear usuario: ' . json_encode($data));

    $result = $this->db->insert('users', $data);
    $id = $this->db->insert_id();

    log_message('debug', 'Resultado insert: ' . ($result ? 'true' : 'false') . ', ID: ' . $id);

    if ($result && $id > 0) {
      return true;
    } else {
      // Si se insertó con ID 0, eliminar el registro
      if ($id == 0) {
        $this->db->delete('users', ['email' => $data['email']]);
      }
      return false;
    }
  }

  public function update_user($id, $data)
  {
    // Si no se proporciona contraseña, no actualizarla
    if (empty($data['password'])) {
      unset($data['password']);
    } else {
      // Cifrar la nueva contraseña
      $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    $this->db->where('id', $id);
    return $this->db->update('users', $data);
  }


  public function toggle_user_status($id)
  {
    // Obtener el estado actual
    $user = $this->get_user($id);
    if (!$user) {
      return false;
    }

    // Cambiar el estado (1 = Activo, 0 = Inactivo)
    $new_status = $user->estado == 1 ? 0 : 1;

    $this->db->where('id', $id);
    $result = $this->db->update('users', ['estado' => $new_status]);

    return $result;
  }

  public function update_last_login($id)
  {
    $this->db->where('id', $id);
    return $this->db->update('users', ['ultimo_login' => date('Y-m-d H:i:s')]);
  }

  public function get_user_stats()
  {
    $this->db->select('
      COUNT(*) as total_users,
      SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as active_users,
      SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as inactive_users
    ');
    $this->db->from('users');
    
    return $this->db->get()->row();
  }

  public function validate_email_unique($email, $exclude_id = null)
  {
    $user = $this->get_user_by_email($email, $exclude_id);
    return $user === null;
  }

  public function get_user_full_name($id)
  {
    $user = $this->get_user($id);
    if ($user) {
      return $user->first_name . ' ' . $user->last_name;
    }
    return '';
  }

  // ========================================
  // MÉTODOS DE AUTENTICACIÓN
  // ========================================

  public $rules = array(
    array(
      'field' => 'email',
      'rules' => 'trim|required|valid_email',
      'errors' => array(
        'required' => 'El correo electrónico es requerido',
        'valid_email' => 'El formato del correo electrónico no es válido',
      ),
    ),
    array(
      'field' => 'password',
      'rules' => 'trim|required',
      'errors' => array(
        'required' => 'La contraseña es requerida',
      ),
    )
  );

  public function login()
  {
    $user = $this->get_user_by_email($this->input->post('email'));

    if ($user) {
      // Verificar si el usuario está activo
      if ($user->estado != 1) {
        return FALSE;
      }

      // Verificar contraseña
      if (password_verify($this->input->post('password'), $user->password)) {
        // Crear sesión
        $data = array(
          'loggedin' => TRUE,
          'user_id' => $user->id,
          'email' => $user->email,
          'first_name' => $user->first_name,
          'last_name' => $user->last_name,
          'perfil' => $user->perfil
        );

        $this->session->set_userdata($data);

        // Inicializar permisos en sesión leyendo primero de user_permissions
        $permissions = $this->get_permissions($user->id);
        $permissions_array = [];
        foreach ($permissions as $perm) {
          $permissions_array[$perm['permission_name']] = (int) $perm['value'];
        }
        $this->session->set_userdata('permissions', $permissions_array);

        // Actualizar último login
        $this->update_last_login($user->id);

        return TRUE;
      }
    }

    return FALSE;
  }

  public function logout()
  {
    // Destruir sesión
    $this->session->sess_destroy();
  }

  public function loggedin()
  {
    $session_loggedin = $this->session->userdata('loggedin');
    error_log("[DIAGNOSTIC] loggedin() called, session 'loggedin': " . ($session_loggedin ? 'true' : 'false'));

    if (!$session_loggedin) {
      error_log("[DIAGNOSTIC] loggedin() returning false - no session");
      return false;
    }

    // Verificar estado activo del usuario
    $user_id = $this->session->userdata('user_id');
    if ($user_id) {
      $user = $this->get_user($user_id);
      if ($user && $user->estado == 1) {
        error_log("[DIAGNOSTIC] loggedin() returning true - user active");
        return true;
      } else {
        error_log("[DIAGNOSTIC] loggedin() returning false - user inactive or not found, estado: " . ($user ? $user->estado : 'null'));
        // Destruir sesión si usuario inactivo
        $this->session->sess_destroy();
        return false;
      }
    }

    error_log("[DIAGNOSTIC] loggedin() returning false - no user_id in session");
    return false;
  }

  public function get_current_user()
  {
    if ($this->loggedin()) {
      $user_id = $this->session->userdata('user_id');
      return $this->get_user($user_id);
    }
    return FALSE;
  }

  public function is_admin()
  {
    if ($this->loggedin()) {
      return $this->session->userdata('perfil') === 'admin';
    }
    return FALSE;
  }

  /**
   * Activa un usuario
   */
  public function activate($id)
  {
    $this->db->where('id', $id);
    return $this->db->update('users', ['estado' => 1]);
  }

  /**
   * Desactiva un usuario
   */
  public function deactivate($id)
  {
    $this->db->where('id', $id);
    return $this->db->update('users', ['estado' => 0]);
  }

  /**
   * Obtiene los permisos de un usuario
   */
  public function get_permissions($user_id)
  {
    // LOG DIAGNÓSTICO
    error_log("[DIAGNOSTIC] get_permissions called for user_id: $user_id");

    // Verificar si existe la tabla user_permissions
    if (!$this->db->table_exists('user_permissions')) {
      error_log("[DIAGNOSTIC] get_permissions: user_permissions table doesn't exist, creating...");
      $this->create_user_permissions_table();
      // Insertar permisos por defecto solo al crear la tabla
      $user = $this->get_user($user_id);
      if ($user) {
        $user_role = isset($user->role) ? $user->role : $user->perfil;
        error_log("[DIAGNOSTIC] get_permissions: creating default permissions for role: $user_role");
        $permissions = $this->get_permissions_by_role($user_role);
        foreach ($permissions as $name => $value) {
          $data = [
            'user_id' => $user_id,
            'permission_name' => $name,
            'value' => $value ? 1 : 0
          ];
          $this->db->insert('user_permissions', $data);
          error_log("[DIAGNOSTIC] get_permissions: inserted default permission $name = $value");
        }
      }
    }

    // Obtener permisos de la tabla user_permissions
    $this->db->select('permission_name, value');
    $this->db->from('user_permissions');
    $this->db->where('user_id', $user_id);
    $query = $this->db->get();

    $permissions = [];
    foreach ($query->result() as $row) {
      $permissions[] = ['permission_name' => $row->permission_name, 'value' => (int) $row->value];
    }

    error_log("[DIAGNOSTIC] get_permissions: found " . count($permissions) . " permissions in DB");

    // Si no hay permisos, devolver permisos por defecto SIN guardarlos en BD
    // Solo guardar permisos cuando el usuario los configure explícitamente
    if (empty($permissions)) {
      error_log("[DIAGNOSTIC] get_permissions: no permissions in DB, using defaults");
      $user = $this->get_user($user_id);
      if ($user) {
        $user_role = isset($user->role) ? $user->role : $user->perfil;
        $default_permissions = $this->get_permissions_by_role($user_role);
        foreach ($default_permissions as $name => $value) {
          $permissions[] = ['permission_name' => $name, 'value' => (int) $value];
          error_log("[DIAGNOSTIC] get_permissions: default permission $name = $value");
        }
      }
    }

    error_log("[DIAGNOSTIC] get_permissions: returning " . count($permissions) . " permissions");
    return $permissions;
  }

  /**
   * Guarda los permisos de un usuario
   */
  public function save_permissions($user_id, $permissions)
  {
    // LOG DIAGNÓSTICO
    error_log("[DIAGNOSTIC] save_permissions called for user_id: $user_id with " . count($permissions) . " permissions");

    // VALIDACIÓN: Verificar user_id válido
    if ($user_id <= 0) {
      error_log("[DIAGNOSTIC] save_permissions: ERROR - invalid user_id: $user_id");
      return false;
    }

    // VALIDACIÓN: Verificar que el usuario existe
    $user = $this->get_user($user_id);
    if (!$user) {
      error_log("[DIAGNOSTIC] save_permissions: ERROR - user not found for user_id: $user_id");
      return false;
    }

    // Verificar si existe la tabla user_permissions
    if (!$this->db->table_exists('user_permissions')) {
      error_log("[DIAGNOSTIC] save_permissions: user_permissions table doesn't exist, creating...");
      $this->create_user_permissions_table();
    }

    $this->db->trans_start();

    // Eliminar permisos existentes
    $this->db->where('user_id', $user_id);
    $deleted = $this->db->delete('user_permissions');
    error_log("[DIAGNOSTIC] save_permissions: deleted existing permissions, affected rows: " . $this->db->affected_rows());

    // Insertar nuevos permisos
    foreach ($permissions as $perm) {
      $data = [
        'user_id' => $user_id,
        'permission_name' => $perm['permission_name'],
        'value' => isset($perm['value']) ? (int) $perm['value'] : 0
      ];
      $inserted = $this->db->insert('user_permissions', $data);
      error_log("[DIAGNOSTIC] save_permissions: inserted permission {$perm['permission_name']} = {$data['value']}, success: " . ($inserted ? 'yes' : 'no'));
    }

    $this->db->trans_complete();
    $status = $this->db->trans_status();
    error_log("[DIAGNOSTIC] save_permissions: transaction completed, status: " . ($status ? 'success' : 'failed'));

    // Refrescar permisos en sesión si la transacción fue exitosa
    if ($status) {
      $permissions = $this->get_permissions($user_id);
      $permissions_array = [];
      foreach ($permissions as $perm) {
        $permissions_array[$perm['permission_name']] = (int) $perm['value'];
      }
      $this->session->set_userdata('permissions', $permissions_array);
      error_log("[DIAGNOSTIC] save_permissions: session permissions refreshed");
    }

    return $status;
  }

  /**
   * ✅ MÉTODO CORREGIDO: Cambia el estado de un usuario (toggle)
   */
  public function toggle_state($user_id)
  {
    $user = $this->get_user($user_id);
    if (!$user) {
      return false;
    }
    
    $new_status = $user->estado == 1 ? 0 : 1;
    
    $this->db->where('id', $user_id);
    if ($this->db->update('users', ['estado' => $new_status])) {
      return $new_status;
    }
    
    return false;
  }

  /**
   * Obtiene permisos por defecto basados en el rol
   */
  private function get_permissions_by_role($role)
  {
    $permissions = [
      'admin' => [
        'dashboard' => true,
        'sidebar' => true,
        'sidebar_back' => true,
        'customers' => true,
        'coins' => true,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'config' => true
      ],
      'operador' => [
        'dashboard' => true,
        'sidebar' => true,
        'sidebar_back' => false,
        'customers' => true,
        'coins' => false,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'config' => false
      ],
      'viewer' => [
        'dashboard' => true,
        'sidebar' => false,
        'sidebar_back' => false,
        'customers' => false,
        'coins' => false,
        'loans' => false,
        'payments' => false,
        'reports' => true,
        'config' => false
      ]
    ];
    
    return isset($permissions[$role]) ? $permissions[$role] : $permissions['viewer'];
  }
  /**
   * Crea la tabla user_permissions si no existe
   */
  private function create_user_permissions_table()
  {
    $sql = "CREATE TABLE `user_permissions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `permission_name` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
      `value` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";
    $this->db->query($sql);
  }

  /**
   * Elimina un usuario (soft delete o hard delete)
   */
  public function delete_user($user_id)
  {
    // Verificar que el usuario existe
    $user = $this->get_user($user_id);
    if (!$user) {
      return false;
    }

    // Opción 1: Soft delete (cambiar estado a -1 o similar)
    // $this->db->where('id', $user_id);
    // return $this->db->update('users', ['estado' => -1]);

    // Opción 2: Hard delete (eliminar completamente)
    $this->db->where('id', $user_id);
    return $this->db->delete('users');
  }

  /**
   * Obtiene la lista de usuarios activos con id, first_name y last_name
   */
  public function get_active_users()
  {
    $this->db->select('id, first_name, last_name');
    $this->db->from('users');
    $this->db->where('estado', 1);
    return $this->db->get()->result();
  }
}