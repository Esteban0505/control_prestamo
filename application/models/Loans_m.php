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
      'field' => 'num_months',
      'rules' => 'trim|required|numeric|greater_than[0]|less_than_equal_to[120]',
      'errors' => array(
                    'required' => 'El plazo en meses es requerido',
                    'numeric' => 'El plazo en meses debe ser numérico',
                    'greater_than' => 'El plazo en meses debe ser mayor a 0',
                    'less_than_equal_to' => 'El plazo en meses no puede exceder 120',
                ),
    ),
    array(
      'field' => 'amortization_type',
      'rules' => 'trim|required|in_list[francesa,estaunidense,mixta]',
      'errors' => array(
                    'required' => 'Debe seleccionar un tipo de amortización',
                    'in_list' => 'Tipo de amortización inválido',
                ),
    ),
    array(
      'field' => 'date',
      'rules' => 'trim|required',
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
    ),
    array(
      'field' => 'tipo_cliente',
      'rules' => 'trim|in_list[normal,especial]',
      'errors' => array(
                    'in_list' => 'Tipo de cliente inválido',
                ),
    )
  );


  public function get_loans()
  {
    // Verificar si la columna created_by existe
    $columns = $this->db->list_fields('loans');
    $has_created_by = in_array('created_by', $columns);
    
    if ($has_created_by) {
      $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, l.fee_amount, l.num_fee, co.short_name, l.status, l.amortization_type, l.tipo_cliente, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
      $this->db->join('users u', 'u.id = l.created_by', 'left');
    } else {
      $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, l.fee_amount, l.num_fee, co.short_name, l.status, l.amortization_type, l.tipo_cliente, 'N/A' AS created_by_name");
      $this->db->from('loans l');
      $this->db->join('customers c', 'c.id = l.customer_id', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    }
    
    $this->db->order_by('l.id', 'desc');
    $loans = $this->db->get()->result();
    log_message('debug', 'Raw loans from DB: ' . json_encode($loans));
    return $loans;
  }

  public function get_coins()
  {
    return $this->db->get('coins')->result(); 
  }

  public function get_searchCst($dni)
  {
    $this->db->select('c.*, u.first_name as asesor_name, COALESCE(pending.pending_count, 0) as loan_status, l.id as loan_id, l.credit_amount, l.payment_m, co.short_name as coin_name');
    $this->db->from('customers c');
    $this->db->join('users u', 'u.id = c.user_id', 'left');
    $this->db->join('loans l', 'l.customer_id = c.id AND l.status = 1', 'left');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->join('(SELECT l.customer_id, COUNT(*) as pending_count FROM loans l JOIN loan_items li ON li.loan_id = l.id WHERE l.status = 1 GROUP BY l.customer_id HAVING SUM(COALESCE(li.balance, 0)) > 0) pending', 'pending.customer_id = c.id', 'left');
    $this->db->where('c.dni', $dni);
    $this->db->or_where("CONCAT(c.first_name, ' ', c.last_name) LIKE", '%' . $dni . '%');
    $this->db->order_by('l.id', 'desc');
    $this->db->limit(1);
    $customer = $this->db->get()->row();

    if (!$customer) {
      return null;
    }

    if ($customer->loan_status > 0) {
      $customer->loan_status = '1'; // pendiente
    } else {
      $customer->loan_status = '0'; // no pendiente
    }

    return $customer;
  }

  public function add_loan($data, $items) {
    try {
      $this->db->trans_start();

      // Asegurar que no se inserte id manualmente
      unset($data['id']);

      // Logs para diagnosticar el error de duplicate '0' for PRIMARY
      log_message('debug', 'Checking for existing records with id=0 in loans');
      $check_zero = $this->db->where('id', 0)->get('loans')->num_rows();
      log_message('debug', 'Records with id=0 in loans: ' . $check_zero);

      $max_id = $this->db->select_max('id')->get('loans')->row()->id;
      log_message('debug', 'Max id in loans: ' . $max_id);

      $max_num = $this->db->select_max('num_prestamo')->get('loans')->row()->num_prestamo;
      $data['num_prestamo'] = $max_num ? $max_num + 1 : 1;
      log_message('debug', 'Calculando num_prestamo: ' . $data['num_prestamo']);

      // Verificar si num_prestamo ya existe
      $existing_loan = $this->db->where('num_prestamo', $data['num_prestamo'])->get('loans')->row();
      if ($existing_loan) {
        log_message('error', 'num_prestamo duplicado detectado: ' . $data['num_prestamo'] . ', loan_id existente: ' . $existing_loan->id);
        $this->db->trans_rollback();
        return false;
      }

      // Log adicional para verificar datos antes de insertar
      log_message('debug', 'Datos a insertar en loans: ' . json_encode($data));
      log_message('debug', 'Número de items a insertar: ' . count($items));
      log_message('debug', 'credit_amount type: ' . gettype($data['credit_amount']) . ', value: ' . $data['credit_amount']);
      log_message('debug', 'interest_amount type: ' . gettype($data['interest_amount']) . ', value: ' . $data['interest_amount']);

      log_message('debug', 'Data to insert in loans: ' . json_encode($data));
      $this->db->insert('loans', $data);
      $loan_id = $this->db->insert_id();
      log_message('debug', 'Préstamo insertado con ID: ' . $loan_id);

      $this->db->where('id', $data['customer_id']);
      $this->db->update('customers', ['loan_status' => 1]);

      foreach ($items as $item) {
        $item['loan_id'] = $loan_id;
        // Asegurar que no se inserte id manualmente para permitir autoincremento
        unset($item['id']);
        // Verificar si ya existe item para este loan_id y num_quota
        $existing_item = $this->db->where('loan_id', $loan_id)->where('num_quota', $item['num_quota'])->get('loan_items')->row();
        if ($existing_item) {
          log_message('error', 'Item duplicado detectado para loan_id: ' . $loan_id . ', num_quota: ' . $item['num_quota']);
          $this->db->trans_rollback();
          return false;
        }
        $this->db->insert('loan_items', $item);
        $inserted_id = $this->db->insert_id();
        log_message('debug', 'Item insertado: loan_id=' . $loan_id . ', num_quota=' . $item['num_quota'] . ', id=' . $inserted_id);
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        log_message('error', 'Transacción fallida en add_loan');
        return false;
      }

      log_message('info', 'Préstamo y items guardados exitosamente, loan_id: ' . $loan_id);
      return true;
    } catch (Exception $e) {
      log_message('error', 'Excepción en add_loan: ' . $e->getMessage());
      $this->db->trans_rollback();
      return false;
    }
  }

  public function update_loan($loan_id, $data, $items = null) {
    try {
      $this->db->trans_start();

      // Actualizar datos del préstamo
      $this->db->where('id', $loan_id);
      $this->db->update('loans', $data);
      log_message('debug', 'Préstamo actualizado con ID: ' . $loan_id);

      // Si se proporcionan items, actualizar la tabla de amortización
      if ($items !== null) {
        // Eliminar items existentes
        $this->db->where('loan_id', $loan_id);
        $this->db->delete('loan_items');
        log_message('debug', 'Items existentes eliminados para loan_id: ' . $loan_id);

        // Insertar nuevos items
        foreach ($items as $item) {
          $item['loan_id'] = $loan_id;
          $this->db->insert('loan_items', $item);
          log_message('debug', 'Item actualizado: loan_id=' . $loan_id . ', num_quota=' . $item['num_quota']);
        }
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        log_message('error', 'Transacción fallida en update_loan');
        return false;
      }

      log_message('info', 'Préstamo actualizado exitosamente, loan_id: ' . $loan_id);
      return true;
    } catch (Exception $e) {
      log_message('error', 'Excepción en update_loan: ' . $e->getMessage());
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


  /**
   * Obtiene el límite de préstamo actual para un cliente
   */
  public function get_customer_limit($customer_id) {
    // Obtener tipo_cliente desde DB
    $customer = $this->db->select('tipo_cliente')->where('id', $customer_id)->get('customers')->row();
    $tipo_cliente = $customer ? trim(strtolower($customer->tipo_cliente)) : 'normal';

    // Si especial, sin límite
    if ($tipo_cliente == 'especial') {
      return 999999999; // Sin límite
    }

    // Contar préstamos completados (balance = 0)
    $this->db->select('COUNT(*) as completed_count');
    $this->db->from('loans l');
    $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->group_by('l.id');
    $this->db->having('SUM(COALESCE(li.balance, 0)) = 0');
    $completed_query = $this->db->get();
    $completed_count = $completed_query->num_rows();

    // Calcular max_limit
    if ($completed_count >= 2) {
      return 5000000;
    } elseif ($completed_count == 1) {
      return 1200000;
    } else {
      return 500000;
    }
  }

  /**
   * Convierte valor de moneda colombiana a número
   */
  private function parse_colombian_currency($value) {
    log_message('debug', 'parse_colombian_currency called with value: ' . $value);
    if (!is_string($value) || empty($value)) {
      log_message('debug', 'parse_colombian_currency: value is not string or empty, returning false');
      return false;
    }

    // Remover símbolos y espacios
    $value = str_replace(['$', ' '], '', $value);
    log_message('debug', 'parse_colombian_currency: after remove symbols: ' . $value);

    // Convertir formato colombiano a numérico
    $value = str_replace('.', '', $value); // Remover separadores de miles
    log_message('debug', 'parse_colombian_currency: after remove dots: ' . $value);
    $value = str_replace(',', '.', $value); // Convertir coma decimal a punto
    log_message('debug', 'parse_colombian_currency: after replace comma: ' . $value);

    $numeric_value = floatval($value);
    log_message('debug', 'parse_colombian_currency: floatval result: ' . $numeric_value);

    $result = is_numeric($numeric_value) ? $numeric_value : false;
    log_message('debug', 'parse_colombian_currency: final result: ' . $result);
    return $result;
  }

}

/* End of file Loans_m.php */
/* Location: ./application/models/Loans_m.php */