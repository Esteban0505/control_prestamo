<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Loans_m extends MY_Model {

  protected $_table_name = 'loans';

  public $loan_rules = array(
    array(
      'field' => 'customer_id',
      'rules' => 'trim|required|numeric',
      'errors' => array(
                    'required' => 'Debe buscar una persona para realizar el préstamo',
                    'numeric' => 'ID de cliente inválido',
                ),
    ),
    array(
      'field' => 'credit_amount',
      'rules' => 'trim|required|callback_validate_currency_format',
      'errors' => array(
                    'required' => 'El monto del crédito es requerido',
                    'validate_currency_format' => 'Formato de moneda inválido. Use formato: 1.000.000',
                ),
    ),
    array(
      'field' => 'interest_amount',
      'rules' => 'trim|required|callback_validate_currency_format',
      'errors' => array(
                    'required' => 'La tasa de interés es requerida',
                    'validate_currency_format' => 'Formato de tasa de interés inválido. Use formato: 15,50',
                ),
    ),
    array(
      'field' => 'num_fee',
      'rules' => 'trim|required|numeric|greater_than[0]|less_than_equal_to[120]',
      'errors' => array(
                    'required' => 'El número de cuotas es requerido',
                    'numeric' => 'El número de cuotas debe ser numérico',
                    'greater_than' => 'El número de cuotas debe ser mayor a 0',
                    'less_than_equal_to' => 'El número de cuotas no puede exceder 120',
                ),
    ),
    array(
      'field' => 'amortization_type',
      'rules' => 'trim|required|in_list[francesa,estadounidense,mixta]',
      'errors' => array(
                    'required' => 'Debe seleccionar un tipo de amortización',
                    'in_list' => 'Tipo de amortización inválido',
                ),
    ),
    array(
      'field' => 'date',
      'rules' => 'trim|required|callback_check_date_not_future',
      'errors' => array(
                    'required' => 'La fecha de emisión es requerida',
                ),
    ),
    array(
      'field' => 'tasa_tipo',
      'rules' => 'trim|required|in_list[TNA,periodica]',
      'errors' => array(
                    'required' => 'El tipo de tasa es requerido',
                    'in_list' => 'Tipo de tasa inválido',
                ),
    )
  );


  public function get_loans()
  {
    // Verificar si la columna created_by existe
    $columns = $this->db->list_fields('loans');
    $has_created_by = in_array('created_by', $columns);
    
    if ($has_created_by) {
      $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, co.short_name, l.status, l.amortization_type, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
      $this->db->join('users u', 'u.id = l.created_by', 'left');
    } else {
      $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, co.short_name, l.status, l.amortization_type, 'N/A' AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    }
    
    $this->db->order_by('l.id', 'desc');
    return $this->db->get()->result(); 
  }

  public function get_coins()
  {
    return $this->db->get('coins')->result(); 
  }

  public function get_searchCst($dni)
  {
    $this->db->like('dni', $dni);
    $this->db->or_like("CONCAT(first_name, ' ', last_name)", $dni);
    $this->db->limit(1);
    return $this->db->get('customers')->row();
  }

  public function add_loan($data, $items) {
    try {
      $this->db->trans_start();

      $max_num = $this->db->select_max('num_prestamo')->get('loans')->row()->num_prestamo;
      $data['num_prestamo'] = $max_num ? $max_num + 1 : 1;

      $this->db->insert('loans', $data);
      $loan_id = $this->db->insert_id();

      $this->db->where('id', $data['customer_id']);
      $this->db->update('customers', ['loan_status' => 1]);

      foreach ($items as $item) {
        $item['loan_id'] = $loan_id;
        $this->db->insert('loan_items', $item);
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        return false;
      }

      return true;
    } catch (Exception $e) {
      $this->db->trans_rollback();
      return false;
    }
  }

  public function get_loan($loan_id)
  {
    // Verificar si la columna created_by existe
    $columns = $this->db->list_fields('loans');
    $has_created_by = in_array('created_by', $columns);
    
    if ($has_created_by) {
      $this->db->select("l.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, co.short_name, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
      $this->db->join('users u', 'u.id = l.created_by', 'left');
    } else {
      $this->db->select("l.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, co.short_name, 'N/A' AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    }
    
    $this->db->where('l.id', $loan_id);
    return $this->db->get()->row(); 
  }

  public function get_loanItems($loan_id)
  {
    $this->db->where('loan_id', $loan_id);

    return $this->db->get('loan_items')->result();
  }

  /**
   * Calcula la tasa periódica según el tipo
   */
  public function get_period_rate($rate, $periods_per_year, $tasa_tipo = 'TNA') {
    $rate_decimal = $rate / 100.0;

    if (strtolower($tasa_tipo) === 'tna') {
      return $rate_decimal / $periods_per_year;
    } elseif (strtolower($tasa_tipo) === 'periodica') {
      return $rate_decimal; // ya es periódica
    } else {
      // default TNA
      return $rate_decimal / $periods_per_year;
    }
  }

}

/* End of file Loans_m.php */
/* Location: ./application/models/Loans_m.php */