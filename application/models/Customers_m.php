<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_m extends MY_Model {

  protected $_table_name = 'customers';

  public $customer_rules = array(
    array(
      'field' => 'dni',
      'label' => 'dni',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'first_name',
      'label' => 'nombre(s)',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'last_name',
      'label' => 'apellido(s)',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'gender',
      'label' => 'género',
      'rules' => 'trim|required|in_list[masculino,femenino]'
    ),
    array(
      'field' => 'tipo_cliente',
      'label' => 'tipo de cliente',
      'rules' => 'trim|required|in_list[normal,especial]'
    ),
    array(
      'field' => 'department_id',
      'label' => 'departamento',
      'rules' => 'trim|required|numeric'
    ),
    array(
      'field' => 'province_id',
      'label' => 'provincia',
      'rules' => 'trim|required|numeric'
    ),
    array(
      'field' => 'district_id',
      'label' => 'distrito',
      'rules' => 'trim|required|numeric'
    ),
    array(
      'field' => 'address',
      'label' => 'dirección',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'mobile',
      'label' => 'celular',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'phone_fixed',
      'label' => 'teléfono fijo',
      'rules' => 'trim|required'
    ),
    array(
      'field' => 'phone',
      'label' => 'correo electrónico',
      'rules' => 'trim|required|valid_email'
    ),
    array(
      'field' => 'user_id',
      'label' => 'usuario',
      'rules' => 'trim|required|numeric'
    )
  );

  public function get_new()
  {
    $customer = new stdClass(); //clase vacia
    $customer->dni = '';
    $customer->first_name = '';
    $customer->last_name = '';
    $customer->gender = 'none';
    $customer->department_id = 0;
    $customer->province_id = 0;
    $customer->district_id = 0;
    $customer->address = '';
    $customer->mobile = '';
    $customer->phone_fixed = '';
    $customer->phone = '';
    $customer->user_id = NULL;
    $customer->ruc = '';
    $customer->company = '';
    $customer->tope_manual = NULL;
    $customer->tipo_cliente = 'normal';

    return $customer;
  }

  public function get_departments()
  {
    return $this->db->get('ubigeo_departments')->result();
  }

  public function get_editProvinces($dp_id)
  {
    $this->db->where('department_id', $dp_id);
    return $this->db->get('ubigeo_provinces')->result();
  }

  public function get_editDistricts($pr_id)
  {
    $this->db->where('province_id', $pr_id);
    return $this->db->get('ubigeo_districts')->result();
  }

  public function get_provinces($dp_id)
  {
    $this->db->where('department_id', $dp_id);

    $query = $this->db->get('ubigeo_provinces'); //select * from ubigeo_proinces
    $output1 = '<option value="0">Seleccionar provincia</option>';

    foreach ($query->result() as $row) {
      $output1 .= '<option value="'.$row->id.'">'.$row->name.'</option>';
    }

    return $output1;
  }

  public function get_districts($pr_id)
  {
    $this->db->where('province_id', $pr_id);

    $query = $this->db->get('ubigeo_districts'); //select * from ubigeo_proinces
    $output1 = '<option value="0">Seleccionar distrito</option>';

    foreach ($query->result() as $row) {
      $output1 .= '<option value="'.$row->id.'">'.$row->name.'</option>';
    }

    return $output1;
  }

  public function get_active_users()
  {
    $this->load->model('user_m');
    $this->db->where('estado', 1);
    return $this->db->get('users')->result();
  }

  public function get($id = NULL, $single = FALSE)
  {
    $this->db->select('customers.*, CONCAT(u.first_name, " ", u.last_name) AS user_name');
    $this->db->join('users u', 'customers.user_id = u.id', 'left');

    if ($id != NULL) {
      $this->db->where('customers.' . $this->_primary_key, $id);
      $method = 'row';
    } elseif ($single == TRUE) {
      $method = 'row';
    } else {
      $method = 'result';
    }

    $this->db->order_by("customers.id", "desc");

    return $this->db->get($this->_table_name)->$method();
  }

  /**
   * Verificar si un cliente está en la lista negra
   */
  public function check_blacklist($customer_id)
  {
    $this->db->where('customer_id', $customer_id);
    $this->db->where('is_active', 1);
    $query = $this->db->get('customer_blacklist');
    return $query->num_rows() > 0;
  }

  /**
   * Agregar cliente a la lista negra
   */
  public function add_to_blacklist($customer_id, $reason = 'manual_block', $notes = '', $blocked_by = null)
  {
    $data = [
      'customer_id' => $customer_id,
      'reason' => $reason,
      'notes' => $notes,
      'blocked_by' => $blocked_by,
      'is_active' => 1
    ];

    return $this->db->insert('customer_blacklist', $data);
  }

  /**
   * Remover cliente de la lista negra
   */
  public function remove_from_blacklist($customer_id, $unblocked_by = null)
  {
    $this->db->where('customer_id', $customer_id);
    $this->db->where('is_active', 1);

    return $this->db->update('customer_blacklist', [
      'is_active' => 0,
      'unblocked_at' => date('Y-m-d H:i:s'),
      'unblocked_by' => $unblocked_by
    ]);
  }

  /**
   * Obtener estadísticas de blacklist
   */
  public function get_blacklist_stats()
  {
    $query = $this->db->query("
      SELECT
        COUNT(*) as total_blacklisted,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_blocks,
        SUM(CASE WHEN reason = 'overdue_payments' THEN 1 ELSE 0 END) as overdue_blocks,
        SUM(CASE WHEN reason = 'fraud' THEN 1 ELSE 0 END) as fraud_blocks,
        SUM(CASE WHEN reason = 'manual_block' THEN 1 ELSE 0 END) as manual_blocks
      FROM customer_blacklist
    ");

    return $query->row();
  }

  public function get_customer_loan_count($client_id)
  {
    $this->db->where('customer_id', $client_id);
    return $this->db->count_all_results('loans');
  }

  public function get_customer_quota($client_id)
  {
    $customer = $this->get($client_id);
    if (!$customer) return 0;

    $loan_count = $this->get_customer_loan_count($client_id);
    $base_quota = 0;
    if ($loan_count == 0) $base_quota = 500000; // 1er préstamo
    elseif ($loan_count == 1) $base_quota = 1200000; // 2do
    else $base_quota = 5000000; // 3+

    // Multiplicar por 2 si es cliente especial
    $multiplier = ($customer->tipo_cliente == 'especial') ? 2 : 1;
    return $base_quota * $multiplier;
  }

  public function get_customers_paginated($page = 1, $per_page = 25, $search = null, $status_filter = null, $tipo_filter = null)
  {
    $offset = ($page - 1) * $per_page;

    // Construir consulta base con JOIN para obtener nombre de usuario
    $this->db->select('customers.*, CONCAT(u.first_name, " ", u.last_name) AS user_name');
    $this->db->from('customers');
    $this->db->join('users u', 'customers.user_id = u.id', 'left');

    // Aplicar filtros de búsqueda
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like('customers.dni', $search);
      $this->db->or_like('customers.first_name', $search);
      $this->db->or_like('customers.last_name', $search);
      $this->db->or_like('CONCAT(customers.first_name, " ", customers.last_name)', $search);
      $this->db->or_like('customers.mobile', $search);
      $this->db->group_end();
    }

    // Filtro por estado de crédito
    if ($status_filter !== null && $status_filter !== '') {
      $this->db->where('customers.loan_status', $status_filter);
    }

    // Filtro por tipo de cliente
    if (!empty($tipo_filter)) {
      $this->db->where('customers.tipo_cliente', $tipo_filter);
    }

    // Obtener total de registros para paginación
    $total_records = $this->db->count_all_results('', false);

    // Aplicar ordenamiento y límites
    $this->db->order_by('customers.id', 'desc');
    $this->db->limit($per_page, $offset);

    $customers = $this->db->get()->result();

    return [
      'customers' => $customers,
      'total' => $total_records,
      'total_pages' => ceil($total_records / $per_page)
    ];
  }

  public function save($data, $id = NULL)
  {
    log_message('debug', '[DEBUG] Iniciando save() en modelo - ID: ' . ($id ?: 'NULL') . ', Datos: ' . json_encode($data));

    // Validar que el DNI no esté duplicado
    if (isset($data['dni']) && !empty($data['dni'])) {
      log_message('debug', '[DEBUG] Validando DNI duplicado: ' . $data['dni']);
      $this->db->where('dni', $data['dni']);
      if ($id !== NULL) {
        $this->db->where('id !=', $id);
      }
      $query = $this->db->get('customers');
      log_message('debug', '[DEBUG] Resultado consulta DNI: ' . $query->num_rows() . ' registros encontrados');
      if ($query->num_rows() > 0) {
        log_message('debug', '[DEBUG] DNI duplicado detectado - retornando FALSE');
        return false; // DNI duplicado
      }
    }

    $result = parent::save($data, $id);
    log_message('debug', '[DEBUG] Resultado parent::save(): ' . ($result ? 'TRUE' : 'FALSE'));

    return $result;
  }

  /**
   * Activar un cliente
   */
  public function activate_customer($customer_id, $activated_by = null)
  {
    // Verificar si la columna status existe
    try {
      $check_column = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_column->num_rows() == 0) {
        log_message('error', 'Columna status no existe en tabla customers');
        return false;
      }
    } catch (Exception $e) {
      log_message('error', 'Error verificando columna status: ' . $e->getMessage());
      return false;
    }
    
    $this->db->where('id', $customer_id);
    $result = $this->db->update('customers', [
      'status' => 1
    ]);
    
    if ($result) {
      log_message('debug', 'Cliente activado exitosamente - ID: ' . $customer_id);
    } else {
      log_message('error', 'Error al activar cliente - ID: ' . $customer_id . ' - Error: ' . $this->db->error()['message']);
    }
    
    return $result;
  }

  /**
   * Desactivar un cliente
   */
  public function deactivate_customer($customer_id, $deactivated_by = null)
  {
    // Verificar si la columna status existe
    try {
      $check_column = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_column->num_rows() == 0) {
        log_message('error', 'Columna status no existe en tabla customers');
        return false;
      }
    } catch (Exception $e) {
      log_message('error', 'Error verificando columna status: ' . $e->getMessage());
      return false;
    }
    
    $this->db->where('id', $customer_id);
    $result = $this->db->update('customers', [
      'status' => 0
    ]);
    
    if ($result) {
      log_message('debug', 'Cliente desactivado exitosamente - ID: ' . $customer_id);
    } else {
      log_message('error', 'Error al desactivar cliente - ID: ' . $customer_id . ' - Error: ' . ($this->db->error()['message'] ?? 'Desconocido'));
    }
    
    return $result;
  }

  /**
   * Verificar si un cliente está activo
   */
  public function is_customer_active($customer_id)
  {
    // Verificar si la columna status existe primero
    try {
      $check_column = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_column->num_rows() == 0) {
        // La columna no existe, retornar true por defecto (comportamiento legacy)
        log_message('warning', 'Columna status no existe, asumiendo cliente activo - ID: ' . $customer_id);
        return true;
      }
    } catch (Exception $e) {
      // Error al verificar, asumir que no existe
      log_message('error', 'Error verificando columna status en is_customer_active: ' . $e->getMessage());
      return true;
    }
    
    $this->db->select('status');
    $this->db->where('id', $customer_id);
    $query = $this->db->get('customers');
    $result = $query->row();
    
    // Si no hay resultado, el cliente no existe
    if (!$result) {
      log_message('warning', 'Cliente no encontrado en is_customer_active - ID: ' . $customer_id);
      return false;
    }
    
    // Si no tiene status definido, retornar true por defecto (comportamiento legacy)
    if (!isset($result->status)) {
      log_message('warning', 'Cliente sin campo status definido, asumiendo activo - ID: ' . $customer_id);
      return true;
    }
    
    $is_active = $result->status == 1;
    log_message('debug', 'Estado del cliente - ID: ' . $customer_id . ', Status: ' . $result->status . ', Activo: ' . ($is_active ? 'Sí' : 'No'));
    
    return $is_active;
  }

  /**
   * Agregar registro al historial de cambios de estado
   */
  public function add_status_history($customer_id, $old_status, $new_status, $action, $changed_by = null, $notes = null)
  {
    // Verificar si la tabla existe
    try {
      $query = $this->db->query("SHOW TABLES LIKE 'customer_status_history'");
      if ($query->num_rows() == 0) {
        // La tabla no existe, no registrar historial
        log_message('warning', 'Tabla customer_status_history no existe. No se puede registrar el historial.');
        return false;
      }
    } catch (Exception $e) {
      // Error al verificar tabla, no registrar historial
      log_message('error', 'Error verificando tabla customer_status_history: ' . $e->getMessage());
      return false;
    }

    $data = [
      'customer_id' => (int)$customer_id,
      'old_status' => (int)$old_status,
      'new_status' => (int)$new_status,
      'action' => $action, // 'activated' o 'deactivated'
      'changed_by' => $changed_by ? (int)$changed_by : null,
      'notes' => $notes ? $notes : null,
      'ip_address' => $this->input->ip_address() ? $this->input->ip_address() : null
    ];

    // changed_at se establece automáticamente por DEFAULT CURRENT_TIMESTAMP
    
    $result = $this->db->insert('customer_status_history', $data);
    
    if (!$result) {
      $error = $this->db->error();
      log_message('error', 'Error al insertar en customer_status_history: ' . json_encode($error));
      log_message('error', 'Datos intentados: ' . json_encode($data));
      log_message('error', 'Query ejecutada: ' . $this->db->last_query());
    } else {
      log_message('debug', 'Historial registrado exitosamente - Cliente ID: ' . $customer_id . ', Acción: ' . $action . ', ID registro: ' . $this->db->insert_id());
    }
    
    return $result;
  }

  /**
   * Obtener historial de cambios de estado de un cliente
   */
  public function get_status_history($customer_id, $limit = 10)
  {
    // Verificar si la tabla existe
    try {
      $query = $this->db->query("SHOW TABLES LIKE 'customer_status_history'");
      if ($query->num_rows() == 0) {
        log_message('debug', 'Tabla customer_status_history no existe. Retornando array vacío.');
        return [];
      }
    } catch (Exception $e) {
      log_message('error', 'Error verificando tabla customer_status_history: ' . $e->getMessage());
      return [];
    }

    $this->db->select('h.*, CONCAT(u.first_name, " ", u.last_name) as changed_by_name');
    $this->db->from('customer_status_history h');
    $this->db->join('users u', 'u.id = h.changed_by', 'left');
    $this->db->where('h.customer_id', $customer_id);
    $this->db->order_by('h.changed_at', 'DESC');
    $this->db->limit($limit);

    return $this->db->get()->result();
  }

  /**
   * Obtener todos los bloqueos activos (clientes desactivados)
   */
  public function get_active_blocks($limit = 50)
  {
    // Verificar si la tabla existe
    try {
      $query = $this->db->query("SHOW TABLES LIKE 'customer_status_history'");
      if ($query->num_rows() == 0) {
        log_message('debug', 'Tabla customer_status_history no existe. Retornando array vacío.');
        return [];
      }
    } catch (Exception $e) {
      log_message('error', 'Error verificando tabla customer_status_history: ' . $e->getMessage());
      return [];
    }

    // Obtener todos los cambios de estado recientes con información del cliente
    // Usar SQL directo para mejor compatibilidad
    // Mostrar TODOS los registros del historial, no solo el último por cliente
    $sql = "SELECT h.*, 
                   c.dni, 
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
                   CONCAT(u.first_name, ' ', u.last_name) as changed_by_name,
                   COALESCE(c.status, 1) as current_status
            FROM customer_status_history h
            INNER JOIN customers c ON c.id = h.customer_id
            LEFT JOIN users u ON u.id = h.changed_by
            ORDER BY h.changed_at DESC
            LIMIT " . (int)$limit;
    
    $query = $this->db->query($sql);
    return $query->result();
  }

}

/* End of file Customers_m.php */
/* Location: ./application/models/Customers_m.php */