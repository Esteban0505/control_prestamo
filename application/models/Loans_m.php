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
      'rules' => 'trim|required|callback_validate_currency_format|callback_validate_loan_limit',
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
                    'required' => 'La fecha y hora de emisión es requerida',
                ),
    ),
    array(
      'field' => 'payment_start_date',
      'rules' => 'trim|required',
      'errors' => array(
                    'required' => 'La fecha de inicio de cobros es requerida',
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


  public function get_loans($page = 1, $per_page = 25, $search = null, $status_filter = null)
    {
      // Validar parámetros de entrada
      $page = max(1, (int)$page);
      $per_page = in_array($per_page, [25, 50, 100]) ? (int)$per_page : 25;

      // Forzar actualización de estados antes de consultar
      $this->force_status_update();

      // Verificar si la columna created_by existe
      $columns = $this->db->list_fields('loans');
      $has_created_by = in_array('created_by', $columns);

     if ($has_created_by) {
       $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, l.fee_amount, l.num_fee, co.short_name, l.status, l.amortization_type, l.tipo_cliente, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name,
                         COALESCE(loan_balance.total_balance, 0) AS total_balance,
                         COALESCE(loan_balance.total_paid, 0) AS total_paid,
                         COALESCE(loan_balance.total_pending, 0) AS total_pending,
                         COALESCE(loan_balance.installments_paid, 0) AS installments_paid,
                         COALESCE(loan_balance.installments_pending, 0) AS installments_pending,
                         COALESCE(loan_balance.total_expected, 0) AS total_amount,
                         COALESCE(loan_balance.total_paid, 0) AS paid_amount,
                         COALESCE(loan_balance.balance_amount, 0) AS balance_amount");
       $this->db->from('loans l');
       $this->db->join('customers c', 'c.id = l.customer_id', 'left');
       $this->db->join('coins co', 'co.id = l.coin_id', 'left');
       $this->db->join('users u', 'u.id = l.created_by', 'left');
       // Subconsulta para calcular balances y pagos (excluyendo condonadas del total esperado)
       $this->db->join('(
         SELECT
           li.loan_id,
           SUM(COALESCE(li.balance, 0)) AS total_balance,
           SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
           SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
           SUM(COALESCE(li.fee_amount, 0)) AS total_fees,
           SUM(COALESCE(li.balance, 0)) AS total_pending,
           SUM(CASE WHEN li.status = 0 THEN 1 ELSE 0 END) AS installments_paid,
           SUM(CASE WHEN li.status IN (1, 3, 4) THEN 1 ELSE 0 END) AS installments_pending,
           GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount
         FROM loan_items li
         GROUP BY li.loan_id
       ) loan_balance', 'loan_balance.loan_id = l.id', 'left');
     } else {
       $this->db->select("l.id AS id, CONCAT(c.first_name, ' ', c.last_name) AS customer, l.credit_amount, l.interest_amount, l.fee_amount, l.num_fee, co.short_name, l.status, l.amortization_type, l.tipo_cliente, 'N/A' AS created_by_name,
                         COALESCE(loan_balance.total_balance, 0) AS total_balance,
                         COALESCE(loan_balance.total_paid, 0) AS total_paid,
                         COALESCE(loan_balance.total_pending, 0) AS total_pending,
                         COALESCE(loan_balance.installments_paid, 0) AS installments_paid,
                         COALESCE(loan_balance.installments_pending, 0) AS installments_pending,
                         COALESCE(loan_balance.total_expected, 0) AS total_amount,
                         COALESCE(loan_balance.total_paid, 0) AS paid_amount,
                         COALESCE(loan_balance.balance_amount, 0) AS balance_amount");
       $this->db->from('loans l');
       $this->db->join('customers c', 'c.id = l.customer_id', 'left');
       $this->db->join('coins co', 'co.id = l.coin_id', 'left');
       // Subconsulta para calcular balances y pagos (excluyendo condonadas del total esperado)
       $this->db->join('(
         SELECT
           li.loan_id,
           SUM(COALESCE(li.balance, 0)) AS total_balance,
           SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
           SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
           SUM(COALESCE(li.fee_amount, 0)) AS total_fees,
           SUM(COALESCE(li.balance, 0)) AS total_pending,
           SUM(CASE WHEN li.status = 0 THEN 1 ELSE 0 END) AS installments_paid,
           SUM(CASE WHEN li.status IN (1, 3, 4) THEN 1 ELSE 0 END) AS installments_pending,
           GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount
         FROM loan_items li
         GROUP BY li.loan_id
       ) loan_balance', 'loan_balance.loan_id = l.id', 'left');
     }

    // Aplicar filtros de búsqueda con sanitización
    if (!empty($search) && is_string($search)) {
      $search_clean = $this->db->escape_like_str(trim($search));
      $this->db->group_start();
      $this->db->like('CAST(l.id AS CHAR)', $search_clean);
      $this->db->or_like("CONCAT(c.first_name, ' ', c.last_name)", $search_clean);
      $this->db->or_like('c.dni', $search_clean);
      $this->db->group_end();
    }

    // Aplicar filtro de estado con validación
    if ($status_filter !== null && $status_filter !== '' && in_array($status_filter, ['0', '1'])) {
      $this->db->where('l.status', (int)$status_filter);
    }

    // Obtener total de registros para paginación
    $total_records = $this->db->count_all_results('', false);

    // Aplicar ordenamiento y paginación
    $this->db->order_by('l.id', 'desc');
    $offset = ($page - 1) * $per_page;
    $this->db->limit($per_page, $offset);

    $loans = $this->db->get()->result();
    log_message('debug', 'Raw loans from DB: ' . json_encode($loans));

    // Actualizar automáticamente el estado de los préstamos basado en balances
   foreach ($loans as $loan) {
     $current_status = $loan->status;
     $balance_amount = $loan->balance_amount ?? 0;

     // CORRECCIÓN: Solo marcar como completado si TODAS las cuotas están pagadas
     // Verificar que no hay cuotas pendientes (status = 1) excluyendo condonadas (extra_payment = 3)
    $pending_quotas = $this->db->select('COUNT(*) as count')
                                ->from('loan_items')
                                ->where('loan_id', $loan->id)
                                  ->where_in('status', [1, 3, 4])
                                ->where('extra_payment !=', 3) // Excluir condonadas
                                ->get()->row()->count ?? 0;

     log_message('debug', 'LOAN_STATUS_CHECK: loan_id=' . $loan->id . ', current_status=' . $current_status . ', balance_amount=' . $balance_amount . ', pending_quotas=' . $pending_quotas . ', total_installments=' . $loan->installments_pending);

     $new_status = ($pending_quotas == 0 && $balance_amount <= 0.01) ? 0 : 1;

     // Solo actualizar si el estado cambió
     if ($current_status != $new_status) {
       $this->db->where('id', $loan->id);
       $this->db->update('loans', ['status' => $new_status]);
       log_message('debug', 'Updated loan ' . $loan->id . ' status from ' . $current_status . ' to ' . $new_status . ' (balance_amount: ' . $balance_amount . ', pending_quotas: ' . $pending_quotas . ')');

       // Actualizar también el estado del cliente si es necesario
       if ($new_status == 0 && isset($loan->customer_id)) {
         $this->db->where('id', $loan->customer_id);
         $this->db->update('customers', ['loan_status' => 0]);
       }
     }
   }

    // Calcular total de páginas
    $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;

    return [
      'loans' => $loans,
      'total_records' => $total_records,
      'total_pages' => $total_pages,
      'current_page' => $page,
      'per_page' => $per_page
    ];
  }

  public function get_coins()
  {
    return $this->db->get('coins')->result(); 
  }

  public function get_searchCst($dni, $suggest = false)
  {
    if ($suggest) {
      // Para sugerencias, devolver múltiples resultados
      $this->db->select("c.id, c.dni, CONCAT(c.first_name, ' ', c.last_name) as cst_name, c.user_id, c.status, COALESCE(pending.pending_count, 0) as loan_status, l.id as loan_id, l.credit_amount, l.payment_m, co.short_name as coin_name, u.first_name as asesor_name, l.assigned_user_id");
      $this->db->from('customers c');
      $this->db->join('users u', 'u.id = c.user_id', 'left');
      $this->db->join('loans l', 'l.customer_id = c.id AND l.status = 1', 'left');
      $this->db->join('coins co', 'co.id = l.coin_id', 'left');
      $this->db->join('(SELECT l.customer_id, COUNT(*) as pending_count FROM loans l JOIN loan_items li ON li.loan_id = l.id WHERE l.status = 1 GROUP BY l.customer_id HAVING SUM(COALESCE(li.balance, 0)) > 0) pending', 'pending.customer_id = c.id', 'left');
      $this->db->where('c.dni LIKE', '%' . $dni . '%');
      $this->db->or_where("CONCAT(c.first_name, ' ', c.last_name) LIKE", '%' . $dni . '%');
      $this->db->order_by('c.dni', 'asc');
      $this->db->limit(10); // Limitar a 10 sugerencias
      $customers = $this->db->get()->result();

      // Formatear resultados para sugerencias
      $results = [];
      foreach ($customers as $customer) {
        $results[] = [
          'id' => $customer->id,
          'dni' => $customer->dni,
          'cst_name' => $customer->cst_name,
          'user_id' => $customer->user_id,
          'status' => isset($customer->status) ? $customer->status : 1,
          'loan_status' => $customer->loan_status > 0 ? '1' : '0',
          'loan_id' => $customer->loan_id,
          'credit_amount' => $customer->credit_amount,
          'payment_m' => $customer->payment_m,
          'coin_name' => $customer->coin_name,
          'asesor_name' => $customer->asesor_name,
          'assigned_user_id' => $customer->assigned_user_id
        ];
      }

      return ['cst' => $results];
    } else {
      // Para búsqueda exacta, devolver un solo resultado
      $this->db->select('c.*, u.first_name as asesor_name, COALESCE(pending.pending_count, 0) as loan_status, l.id as loan_id, l.credit_amount, l.payment_m, co.short_name as coin_name, l.assigned_user_id');
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

      // Asegurar que el campo status existe (por compatibilidad con bases de datos antiguas)
      if (!isset($customer->status)) {
        $customer->status = 1; // Por defecto activo si no existe el campo
      }

      if ($customer->loan_status > 0) {
        $customer->loan_status = '1'; // pendiente
      } else {
        $customer->loan_status = '0'; // no pendiente
      }

      return $customer;
    }
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
    // Seleccionar todos los campos de loan_items, ordenados por número de cuota
    $this->db->where('loan_id', $loan_id);
    $this->db->order_by('num_quota', 'asc');

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
     log_message('debug', 'parse_colombian_currency called with value: ' . $value . ', type: ' . gettype($value));
     if (is_numeric($value)) {
       log_message('debug', 'parse_colombian_currency: value is numeric, returning as float');
       return floatval($value);
     }
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

   /**
    * Obtiene la siguiente cuota pendiente de un préstamo
    */
   public function get_next_installment($loan_id, $current_quota) {
     $this->db->where('loan_id', $loan_id);
     $this->db->where('num_quota >', $current_quota);
     $this->db->where_in('status', [0, 3]); // Solo pendientes o no completas
     $this->db->order_by('num_quota', 'ASC');
     $this->db->limit(1);
     return $this->db->get('loan_items')->row_array();
   }

   /**
    * Incrementa el balance global del préstamo
    */
   public function increase_loan_balance($loan_id, $amount) {
     $this->db->set('balance', 'balance + ' . $amount, FALSE);
     $this->db->where('id', $loan_id);
     $this->db->update('loans');
   }

   /**
    * Registra logs de redistribución de saldos
    */
   public function log_redistribution($loan_id, $log) {
     // Crear tabla si no existe
     if (!$this->db->table_exists('redistribution_logs')) {
       $this->db->query("
         CREATE TABLE IF NOT EXISTS redistribution_logs (
           id INT AUTO_INCREMENT PRIMARY KEY,
           loan_id INT NOT NULL,
           log TEXT,
           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
           INDEX idx_loan_id (loan_id)
         )
       ");
     }

     $this->db->insert('redistribution_logs', [
       'loan_id' => $loan_id,
       'log' => json_encode($log),
       'created_at' => date('Y-m-d H:i:s')
     ]);
   }

   /**
    * Obtiene cuotas seleccionadas para pago personalizado
    */
   public function get_selected_installments($loan_id, $selected_ids) {
     $this->db->where('loan_id', $loan_id);
     $this->db->where_in('id', $selected_ids);
     $this->db->where_in('status', [0, 3]); // Solo pendientes o no completas
     $this->db->order_by('num_quota', 'ASC');
     return $this->db->get('loan_items')->result_array();
   }

   /**
    * Actualiza una cuota específica
    */
   public function update_installment($installment_id, $data) {
     $this->db->where('id', $installment_id);
     return $this->db->update('loan_items', $data);
   }

   /**
    * Obtiene una cuota específica por ID
    */
   public function get_loan_item($item_id) {
     $this->db->where('id', $item_id);
     return $this->db->get('loan_items')->row();
   }

   /**
    * Obtiene la primera cuota pendiente de un préstamo
    */
   public function obtenerPrimeraCuotaPendiente($prestamo_id) {
     $this->db->where('loan_id', $prestamo_id);
     $this->db->where_in('status', [0, 3]); // Pendiente o no completo
     $this->db->order_by('num_quota', 'ASC');
     $this->db->limit(1);
     return $this->db->get('loan_items')->row();
   }

   /**
    * Obtiene cuotas pendientes posteriores a una cuota específica
    */
   public function obtenerCuotasPendientesPosteriores($cuota_id, $prestamo_id) {
     // Primero obtener el número de cuota de referencia
     $cuota_ref = $this->db->where('id', $cuota_id)->get('loan_items')->row();
     if (!$cuota_ref) return [];

     $this->db->where('loan_id', $prestamo_id);
     $this->db->where('num_quota >', $cuota_ref->num_quota);
     $this->db->where_in('status', [0, 3]); // Solo pendientes o no completas
     $this->db->order_by('num_quota', 'ASC');
     return $this->db->get('loan_items')->result();
   }

   /**
    * Incrementa el saldo del préstamo
    */
   public function incrementarSaldoPrestamo($prestamo_id, $monto) {
     $this->db->set('balance', 'balance + ' . $monto, FALSE);
     $this->db->where('id', $prestamo_id);
     $this->db->update('loans');
   }

   /**
    * Actualiza el saldo total del préstamo
    */
   public function actualizarSaldoPrestamo($prestamo_id, $nuevo_saldo) {
     $this->db->where('id', $prestamo_id);
     $this->db->update('loans', ['balance' => $nuevo_saldo]);
   }

   /**
    * Calcula el saldo total actual del préstamo
    */
   public function calcularSaldoTotal($prestamo_id) {
     $this->db->select('SUM(COALESCE(balance, 0)) as total_balance');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $prestamo_id);
     $result = $this->db->get()->row();
     return $result ? $result->total_balance : 0;
   }

   /**
    * Registra un pago en la tabla payments
    */
   public function registrarPago($prestamo_id, $monto, $descripcion, $cuota_id = null) {
     $data = [
       'loan_id' => $prestamo_id,
       'amount' => $monto,
       'tipo_pago' => 'custom',
       'monto_pagado' => $monto,
       'payment_date' => date('Y-m-d H:i:s'),
       'method' => 'efectivo',
       'notes' => $descripcion
     ];

     if ($cuota_id) {
       $data['installment_id'] = $cuota_id;
     }

     $this->db->insert('payments', $data);
     return $this->db->insert_id();
   }

   /**
    * Verifica si una cuota es la última del préstamo
    */
   public function is_last_installment($loan_id, $installment_id) {
     // Primero obtener el num_quota de la cuota actual
     $current_quota = $this->db->where('id', $installment_id)->get('loan_items')->row();
     if (!$current_quota) return false;

     // Obtener la cuota con el num_quota más alto para este préstamo
     $this->db->where('loan_id', $loan_id);
     $this->db->order_by('num_quota', 'DESC');
     $last_quota = $this->db->get('loan_items')->row();

     return $last_quota && $last_quota->num_quota == $current_quota->num_quota;
   }

   /**
    * Obtiene cuotas pendientes posteriores a una cuota específica
    */
   public function get_pending_installments_after($loan_id, $installment_id) {
     // Obtener el número de cuota de referencia
     $cuota_ref = $this->db->where('id', $installment_id)->get('loan_items')->row();
     if (!$cuota_ref) return [];

     $this->db->where('loan_id', $loan_id);
     $this->db->where('num_quota >', $cuota_ref->num_quota);
     $this->db->where_in('status', [0, 3]); // Solo pendientes o no completas
     $this->db->order_by('num_quota', 'ASC');
     return $this->db->get('loan_items')->result_array();
   }

   /**
    * Crea una nueva cuota adicional al final del préstamo
    */
   public function create_new_installment($data) {
     return $this->db->insert('loan_items', $data);
   }

   /**
    * Fuerza la actualización de estados de todos los préstamos
    */
   public function force_status_update() {
     log_message('debug', 'force_status_update: Starting forced status update for all loans');

     // Obtener TODOS los préstamos con balances calculados correctamente (excluyendo condonadas)
     $this->db->select('l.id, l.status, l.customer_id,
                       COALESCE(calc.total_expected, 0) as total_expected,
                       COALESCE(calc.total_paid, 0) as total_paid,
                       COALESCE(calc.balance_amount, 0) as balance_amount');
     $this->db->from('loans l');
     $this->db->join('(
       SELECT
         li.loan_id,
         SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) AS total_expected,
         SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0)) AS total_paid,
         GREATEST(0, SUM(CASE WHEN li.extra_payment != 3 THEN COALESCE(li.fee_amount, 0) ELSE 0 END) - SUM(COALESCE(li.interest_paid, 0) + COALESCE(li.capital_paid, 0))) AS balance_amount
       FROM loan_items li
       GROUP BY li.loan_id
     ) calc', 'calc.loan_id = l.id', 'left');
     // Procesar TODOS los préstamos
     $all_loans = $this->db->get()->result();

     $updated_count = 0;
     $customer_updated_count = 0;

     foreach ($all_loans as $loan) {
       $current_status = $loan->status;
       $balance_amount = $loan->balance_amount ?? 0;

       // CORRECCIÓN: Solo marcar como completado si NO hay cuotas pendientes (excluyendo condonadas)
    $pending_quotas = $this->db->select('COUNT(*) as count')
                                  ->from('loan_items')
                                  ->where('loan_id', $loan->id)
                                  ->where_in('status', [1, 3, 4])
                                  ->where('extra_payment !=', 3) // Excluir condonadas
                                  ->get()->row()->count ?? 0;

       log_message('debug', 'FORCE_STATUS_UPDATE: loan_id=' . $loan->id . ', current_status=' . $current_status . ', balance_amount=' . $balance_amount . ', pending_quotas=' . $pending_quotas);

       $new_status = ($pending_quotas == 0 && $balance_amount <= 0.01) ? 0 : 1;

       // Solo actualizar si el estado cambió
       if ($current_status != $new_status) {
         $this->db->where('id', $loan->id);
         $this->db->update('loans', ['status' => $new_status]);
         $updated_count++;

         log_message('debug', 'force_status_update: Updated loan ' . $loan->id .
                    ' status from ' . $current_status . ' to ' . $new_status .
                    ' (balance: ' . $balance_amount . ', pending_quotas: ' . $pending_quotas . ')');
       }

       // Sincronizar estado del cliente con el estado del préstamo
       if (isset($loan->customer_id)) {
         // Obtener estado actual del cliente
         $customer = $this->db->select('loan_status')->where('id', $loan->customer_id)->get('customers')->row();

         if ($customer && $customer->loan_status != $new_status) {
           $this->db->where('id', $loan->customer_id);
           $this->db->update('customers', ['loan_status' => $new_status]);
           $customer_updated_count++;

           log_message('debug', 'force_status_update: Updated customer ' . $loan->customer_id .
                      ' loan_status from ' . $customer->loan_status . ' to ' . $new_status);
         }
       }
     }

     // Actualizar cuotas condonadas a estado pagado
     $condoned_updated = $this->update_condoned_installments();

     log_message('info', 'force_status_update: Completed - ' . $updated_count . ' loans, ' .
                $customer_updated_count . ' customers, ' . $condoned_updated . ' condoned installments updated');
     return $updated_count;
   }

   /**
    * Actualiza automáticamente el estado de las cuotas condonadas a pagado
    */
   private function update_condoned_installments() {
     log_message('debug', 'update_condoned_installments: Starting update of condoned installments');

     // Buscar TODAS las cuotas condonadas (extra_payment = 3), no solo las pendientes
     $this->db->select('id, loan_id, num_quota, extra_payment, status, interest_amount, capital_amount');
     $this->db->from('loan_items');
     $this->db->where('extra_payment', 3); // Condonadas
     $condoned_installments = $this->db->get()->result();

     $updated_count = 0;
     foreach ($condoned_installments as $installment) {
       // Si no está pagada, marcarla como pagada
       if ($installment->status != 0) {
         // Actualizar estado a pagado (status = 0)
         $this->db->where('id', $installment->id);
         $this->db->update('loan_items', [
           'status' => 0,
           'balance' => 0,
           'interest_paid' => $installment->interest_amount ?? 0,
           'capital_paid' => $installment->capital_amount ?? 0,
           'pay_date' => date('Y-m-d H:i:s')
         ]);

         $updated_count++;
         log_message('debug', 'update_condoned_installments: Updated condoned installment ' . $installment->id .
                    ' (loan: ' . $installment->loan_id . ', quota: ' . $installment->num_quota . ') to paid status');
       }
     }

     log_message('info', 'update_condoned_installments: Completed - ' . $updated_count . ' condoned installments updated to paid status');
     return $updated_count;
   }


}

/* End of file Loans_m.php */
/* Location: ./application/models/Loans_m.php */