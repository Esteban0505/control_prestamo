<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Config extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('user_m');
    $this->load->library('session');
    $this->load->library('form_validation');
    $this->load->library('pagination');
    $this->session->userdata('loggedin') == TRUE || redirect('user/login');
  }

  public function index()
  {
    // Configuración de paginación
    $config['base_url'] = site_url('admin/config');
    $config['total_rows'] = $this->user_m->get_users_count();
    $config['per_page'] = 10;
    $config['uri_segment'] = 3;
    $config['num_links'] = 2;
    
    // Configuración de estilos de paginación
    $config['full_tag_open'] = '<nav><ul class="pagination justify-content-center">';
    $config['full_tag_close'] = '</ul></nav>';
    $config['num_tag_open'] = '<li class="page-item">';
    $config['num_tag_close'] = '</li>';
    $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
    $config['cur_tag_close'] = '</span></li>';
    $config['next_tag_open'] = '<li class="page-item">';
    $config['next_tagl_close'] = '</li>';
    $config['prev_tag_open'] = '<li class="page-item">';
    $config['prev_tagl_close'] = '</li>';
    $config['first_tag_open'] = '<li class="page-item">';
    $config['first_tagl_close'] = '</li>';
    $config['last_tag_open'] = '<li class="page-item">';
    $config['last_tagl_close'] = '</li>';
    $config['attributes'] = array('class' => 'page-link');
    
    $this->pagination->initialize($config);
    
    // Obtener parámetros de búsqueda y paginación
    $search = $this->input->get('search');
    $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
    
    // Obtener usuarios
    $data['users'] = $this->user_m->get_users($config['per_page'], $page, $search);
    $data['pagination'] = $this->pagination->create_links();
    $data['search'] = $search;
    $data['stats'] = $this->user_m->get_user_stats();
    
    $data['subview'] = 'admin/config/index';
    $this->load->view('admin/_main_layout', $data);
  }

  public function create()
  {
    $rules = $this->user_m->user_rules;
    $this->form_validation->set_rules($rules);
    
    // Agregar regla de contraseña requerida para crear
    $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]', 
      array(
        'required' => 'La contraseña es requerida',
        'min_length' => 'La contraseña debe tener al menos 6 caracteres'
      )
    );
    
    // Validar email único
    $this->form_validation->set_rules('email', 'Correo electrónico', 'callback_validate_email_unique');

    if ($this->form_validation->run() == TRUE) {
      $data = $this->user_m->array_from_post(['first_name', 'last_name', 'email', 'perfil', 'password']);
      
      if ($this->user_m->create_user($data)) {
        $this->session->set_flashdata('success', 'Usuario creado exitosamente');
        redirect('admin/config');
      } else {
        $this->session->set_flashdata('error', 'Error al crear el usuario');
      }
    }

    $data['subview'] = 'admin/config/form';
    $data['form_title'] = 'Crear Usuario';
    $data['form_action'] = 'admin/config/create';
    $this->load->view('admin/_main_layout', $data);
  }

  public function edit($id)
  {
    $user = $this->user_m->get_user($id);
    if (!$user) {
      $this->session->set_flashdata('error', 'Usuario no encontrado');
      redirect('admin/config');
    }

    $rules = $this->user_m->user_rules;
    $this->form_validation->set_rules($rules);
    
    // Validar email único excluyendo el usuario actual
    $this->form_validation->set_rules('email', 'Correo electrónico', 'callback_validate_email_unique[' . $id . ']');

    if ($this->form_validation->run() == TRUE) {
      $data = $this->user_m->array_from_post(['first_name', 'last_name', 'email', 'perfil', 'password']);
      
      // Si no se proporciona contraseña, no actualizarla
      if (empty($data['password'])) {
        unset($data['password']);
      }
      
      if ($this->user_m->update_user($id, $data)) {
        $this->session->set_flashdata('success', 'Usuario actualizado exitosamente');
        redirect('admin/config');
      } else {
        $this->session->set_flashdata('error', 'Error al actualizar el usuario');
      }
    }

    $data['user'] = $user;
    $data['subview'] = 'admin/config/form';
    $data['form_title'] = 'Editar Usuario';
    $data['form_action'] = 'admin/config/edit/' . $id;
    $this->load->view('admin/_main_layout', $data);
  }

  public function delete($id)
  {
    $user = $this->user_m->get_user($id);
    if (!$user) {
      $this->session->set_flashdata('error', 'Usuario no encontrado');
      redirect('admin/config');
    }

    // No permitir eliminar el usuario actual
    $current_user_id = $this->session->userdata('user_id');
    if ($id == $current_user_id) {
      $this->session->set_flashdata('error', 'No puedes eliminar tu propio usuario');
      redirect('admin/config');
    }

    if ($this->user_m->delete_user($id)) {
      $this->session->set_flashdata('success', 'Usuario eliminado exitosamente');
    } else {
      $this->session->set_flashdata('error', 'Error al eliminar el usuario');
    }
    
    redirect('admin/config');
  }

  public function toggle_status($id)
  {
    $user = $this->user_m->get_user($id);
    if (!$user) {
      $this->session->set_flashdata('error', 'Usuario no encontrado');
      redirect('admin/config');
    }

    if ($this->user_m->toggle_user_status($id)) {
      $new_status = $user->estado == 1 ? 'desactivado' : 'activado';
      $this->session->set_flashdata('success', 'Usuario ' . $new_status . ' exitosamente');
    } else {
      $this->session->set_flashdata('error', 'Error al cambiar el estado del usuario');
    }
    
    redirect('admin/config');
  }

  public function validate_email_unique($email, $exclude_id = null)
  {
    if ($this->user_m->validate_email_unique($email, $exclude_id)) {
      return TRUE;
    } else {
      $this->form_validation->set_message('validate_email_unique', 'El correo electrónico ya está registrado');
      return FALSE;
    }
  }

  public function ajax_toggle_status()
  {
    $id = $this->input->post('user_id');
    $user = $this->user_m->get_user($id);
    
    if (!$user) {
      $response = ['success' => false, 'message' => 'Usuario no encontrado'];
    } else {
      if ($this->user_m->toggle_user_status($id)) {
        $new_status = $user->estado == 1 ? 0 : 1; // ✅ CORREGIDO: Calcular nuevo estado correctamente
        $response = [
          'success' => true, 
          'message' => $new_status == 1 ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente', // ✅ CORREGIDO: Mensaje más claro
          'estado' => $new_status, // ✅ CORREGIDO: Usar 'estado' en lugar de 'new_status'
          'status_text' => $new_status == 1 ? 'Activo' : 'Inactivo' // ✅ CORREGIDO: Texto del estado
        ];
      } else {
        $response = ['success' => false, 'message' => 'Error al actualizar el estado'];
      }
    }
    
    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  public function ajax_delete()
  {
    $id = $this->input->post('user_id');
    $user = $this->user_m->get_user($id);
    
    if (!$user) {
      $response = ['success' => false, 'message' => 'Usuario no encontrado'];
    } else {
      // No permitir eliminar el usuario actual
      $current_user_id = $this->session->userdata('user_id');
      if ($id == $current_user_id) {
        $response = ['success' => false, 'message' => 'No puedes eliminar tu propio usuario'];
      } else {
        if ($this->user_m->delete_user($id)) {
          $response = ['success' => true, 'message' => 'Usuario eliminado exitosamente'];
        } else {
          $response = ['success' => false, 'message' => 'Error al eliminar el usuario'];
        }
      }
    }
    
    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  public function get_permissions()
  {
    $user_id = $this->input->post('user_id');

    // Verificar sesión y permisos
    if (!$this->session->userdata('loggedin')) {
      $response = ['success' => false, 'message' => 'No autorizado'];
    } else {
      $user = $this->user_m->get_user($user_id);

      if (!$user) {
        $response = ['success' => false, 'message' => 'Usuario no encontrado'];
      } else {
        // Obtener permisos del usuario
        $permissions = $this->user_m->get_permissions($user_id);

        $response = [
          'success' => true,
          'user' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'role' => isset($user->role) ? $user->role : $user->perfil
          ],
          'permissions' => $permissions
        ];
      }
    }

    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  public function save_permissions()
  {
    // Verificar sesión y permisos
    if (!$this->session->userdata('loggedin')) {
      $response = ['success' => false, 'message' => 'No autorizado'];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    $user_id = $this->input->post('user_id');
    $role = $this->input->post('role');
    $permissions = $this->input->post('permissions');

    // Validar datos de entrada
    if (empty($user_id) || !is_numeric($user_id)) {
      $response = ['success' => false, 'message' => 'ID de usuario inválido'];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    if (empty($role)) {
      $response = ['success' => false, 'message' => 'Rol es requerido'];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    if (!is_array($permissions) || empty($permissions)) {
      $response = ['success' => false, 'message' => 'Array de permisos inválido o vacío'];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    // Validar estructura de cada permiso
    foreach ($permissions as $perm) {
      if (!is_array($perm) || !isset($perm['permission_name']) || !isset($perm['value'])) {
        $response = ['success' => false, 'message' => 'Estructura de permisos inválida'];
        $this->output
          ->set_content_type('application/json')
          ->set_output(json_encode($response));
        return;
      }
    }

    $user = $this->user_m->get_user($user_id);

    if (!$user) {
      $response = ['success' => false, 'message' => 'Usuario no encontrado'];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    // Actualizar rol y permisos
    $update_data = ['role' => $role];

    // También actualizar perfil para compatibilidad
    if (isset($user->perfil)) {
      $update_data['perfil'] = $role;
    }

    if ($this->user_m->update_user($user_id, $update_data) &&
        $this->user_m->save_permissions($user_id, $permissions)) {
      $response = [
        'success' => true,
        'message' => 'Permisos guardados exitosamente'
      ];
    } else {
      $response = ['success' => false, 'message' => 'Error al guardar los permisos'];
    }

    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  private function get_permissions_by_role($role)
  {
    $permissions = [
      'admin' => [
        'dashboard' => true,
        'customers' => true,
        'coins' => true,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'config' => true
      ],
      'operador' => [
        'dashboard' => true,
        'customers' => true,
        'coins' => false,
        'loans' => true,
        'payments' => true,
        'reports' => true,
        'config' => false
      ],
      'viewer' => [
        'dashboard' => true,
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

  public function toggle_state()
  {
    $user_id = $this->input->post('user_id');
    
    // Verificar sesión y permisos
    if (!$this->session->userdata('loggedin')) {
      $response = ['success' => false, 'message' => 'No autorizado'];
    } else {
      $user = $this->user_m->get_user($user_id);
      
      if (!$user) {
        $response = ['success' => false, 'message' => 'Usuario no encontrado'];
      } else {
        $new_status = $this->user_m->toggle_state($user_id);
        
        if ($new_status !== false) {
          $status_text = $new_status == 1 ? 'activado' : 'desactivado';
          $response = [
            'success' => true,
            'estado' => $new_status,
            'message' => 'Usuario ' . $status_text . ' exitosamente'
          ];
        } else {
          $response = ['success' => false, 'message' => 'Error al actualizar el estado'];
        }
      }
    }
    
    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  public function delete_user()
  {
    $user_id = $this->input->post('user_id');
    
    // Verificar sesión y permisos
    if (!$this->session->userdata('loggedin')) {
      $response = ['success' => false, 'message' => 'No autorizado'];
    } else {
      $user = $this->user_m->get_user($user_id);
      
      if (!$user) {
        $response = ['success' => false, 'message' => 'Usuario no encontrado'];
      } else {
        // Verificar que no se elimine a sí mismo
        if ($user_id == $this->session->userdata('user_id')) {
          $response = ['success' => false, 'message' => 'No puedes eliminar tu propia cuenta'];
        } else {
          if ($this->user_m->delete_user($user_id)) {
            $response = [
              'success' => true,
              'message' => 'Usuario eliminado exitosamente'
            ];
          } else {
            $response = ['success' => false, 'message' => 'Error al eliminar el usuario'];
          }
        }
      }
    }
    
    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

  /**
   * Obtener tokens CSRF para JavaScript
   */
  public function get_csrf_tokens()
  {
    $this->output->set_content_type('application/json');
    echo json_encode([
      'success' => true,
      'csrf_name' => $this->security->get_csrf_token_name(),
      'csrf_hash' => $this->security->get_csrf_hash()
    ]);
  }
}