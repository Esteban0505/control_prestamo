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
      'field' => 'tipo_cliente',
      'label' => 'tipo de cliente',
      'rules' => 'trim|required|in_list[normal,especial]'
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

  public function check_blacklist($client_id)
  {
    $this->db->where('client_id', $client_id);
    $query = $this->db->get('blacklist');
    return $query->num_rows() > 0;
  }

  public function add_to_blacklist($client_id, $reason)
  {
    $data = array(
      'client_id' => $client_id,
      'reason' => $reason
    );
    return $this->db->insert('blacklist', $data);
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

  public function save($data, $id = NULL)
  {
    // Validar que el DNI no esté duplicado
    if (isset($data['dni']) && !empty($data['dni'])) {
      $this->db->where('dni', $data['dni']);
      if ($id !== NULL) {
        $this->db->where('id !=', $id);
      }
      $query = $this->db->get('customers');
      if ($query->num_rows() > 0) {
        return false; // DNI duplicado
      }
    }

    return parent::save($data, $id);
  }

}

/* End of file Customers_m.php */
/* Location: ./application/models/Customers_m.php */