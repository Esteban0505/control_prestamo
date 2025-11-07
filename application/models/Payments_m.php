<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_m extends CI_Model {

  /**
   * Asegura columnas de auditoría/descripcion en loan_items
   */
  public function ensure_aux_columns()
  {
    log_message('debug', 'Verificando columnas auxiliares en loan_items');
    if (!$this->db->field_exists('payment_desc', 'loan_items')) {
      log_message('debug', 'Agregando columna payment_desc a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN payment_desc VARCHAR(255) NULL AFTER extra_payment");
    } else {
      log_message('debug', 'Columna payment_desc ya existe en loan_items');
    }
    if (!$this->db->field_exists('paid_by', 'loan_items')) {
      log_message('debug', 'Agregando columna paid_by a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN paid_by INT NULL AFTER status");
    } else {
      log_message('debug', 'Columna paid_by ya existe en loan_items');
    }
    if (!$this->db->field_exists('pay_date', 'loan_items')) {
      log_message('debug', 'Agregando columna pay_date a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN pay_date DATETIME NULL AFTER date");
    } else {
      log_message('debug', 'Columna pay_date ya existe en loan_items');
    }
    if (!$this->db->field_exists('interest_paid', 'loan_items')) {
      log_message('debug', 'Agregando columna interest_paid a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN interest_paid DECIMAL(10,2) NULL DEFAULT 0 AFTER interest_amount");
    } else {
      log_message('debug', 'Columna interest_paid ya existe en loan_items');
    }
    if (!$this->db->field_exists('capital_paid', 'loan_items')) {
      log_message('debug', 'Agregando columna capital_paid a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN capital_paid DECIMAL(10,2) NULL DEFAULT 0 AFTER capital_amount");
    } else {
      log_message('debug', 'Columna capital_paid ya existe en loan_items');
    }
  }

  /**
   * Asegura la tabla collector_commissions
   */
  private function ensure_commissions_table()
  {
    if (!$this->db->table_exists('collector_commissions')) {
      log_message('debug', 'Creando tabla collector_commissions');
      $this->db->query(
        "CREATE TABLE collector_commissions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NULL,
          loan_item_id INT NULL,
          amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
      );
    } else {
      log_message('debug', 'Tabla collector_commissions ya existe');
    }
  }

  /**
   * Asegura la tabla payments
   */
  private function ensure_payments_table()
  {
    if (!$this->db->table_exists('payments')) {
      log_message('debug', 'Creando tabla payments');
      $this->db->query(
        "CREATE TABLE payments (
          id INT AUTO_INCREMENT PRIMARY KEY,
          loan_id INT NOT NULL,
          loan_item_id INT NULL,
          amount DECIMAL(15,2) NOT NULL,
          tipo_pago VARCHAR(50) NULL,
          monto_pagado DECIMAL(15,2) NOT NULL,
          interest_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          capital_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          payment_date DATETIME NOT NULL,
          payment_user_id INT NULL,
          method VARCHAR(50) NULL,
          notes TEXT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (loan_id) REFERENCES loans(id),
          FOREIGN KEY (loan_item_id) REFERENCES loan_items(id),
          FOREIGN KEY (payment_user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
      );
    } else {
      log_message('debug', 'Tabla payments ya existe');
      // Verificar si tiene las columnas tipo_pago y monto_pagado
      if (!$this->db->field_exists('tipo_pago', 'payments')) {
        log_message('debug', 'Agregando columna tipo_pago a payments');
        $this->db->query("ALTER TABLE payments ADD COLUMN tipo_pago VARCHAR(50) NULL AFTER amount");
      }
      if (!$this->db->field_exists('monto_pagado', 'payments')) {
        log_message('debug', 'Agregando columna monto_pagado a payments');
        $this->db->query("ALTER TABLE payments ADD COLUMN monto_pagado DECIMAL(15,2) NOT NULL AFTER tipo_pago");
      }
    }
  }

  public function get_payments()
  {
    $this->db->select("li.id, c.dni, concat(c.first_name,' ',c.last_name) AS name_cst, l.id AS loan_id, li.pay_date, li.num_quota, li.fee_amount");
    $this->db->from('loan_items li');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->where('li.status', 0);
    $this->db->order_by('li.pay_date', 'desc');

    return $this->db->get()->result(); 
  }

  public function get_searchCst($dni, $suggest = false)
  {
    $this->db->select("l.id as loan_id, l.customer_id, c.dni, concat(c.first_name, ' ', c.last_name) AS cst_name, l.credit_amount, l.payment_m, co.name as coin_name");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id', 'left');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->group_start();
    $this->db->or_like('c.dni', $dni);
    $this->db->or_like("CONCAT(c.first_name, ' ', c.last_name)", $dni);
    $this->db->group_end();
    if ($suggest) {
        $this->db->limit(10);
    } else {
        $this->db->limit(1);
    }

    if ($suggest) {
      return $this->db->get()->result();
    } else {
      return $this->db->get()->row();
    }
  }

  public function get_quotasCst($loan_id)
  {
    $loan_id = (int)$loan_id;
    $this->db->select('id, num_quota, date, fee_amount, status, interest_amount, capital_amount, interest_paid, capital_paid, balance');
    $this->db->where('loan_id', $loan_id);
    $this->db->order_by('num_quota', 'asc');

    $query = $this->db->get('loan_items');
    $quotas = [];

    foreach ($query->result() as $row) {
      $quotas[] = [
        'id' => $row->id,
        'n_cuota' => $row->num_quota,
        'fecha_pago' => $row->date,
        'monto_cuota' => $row->fee_amount,
        'status' => $row->status,
        'estado' => $row->status ? 'Pendiente' : 'Pagado',
        'interest_amount' => $row->interest_amount,
        'capital_amount' => $row->capital_amount,
        'interest_paid' => $row->interest_paid ?? 0,
        'capital_paid' => $row->capital_paid ?? 0,
        'balance' => $row->balance
      ];
    }

    return $quotas;
  }

  public function update_quota($data, $id)
  {
    $this->db->where('id', $id);
    $this->db->update('loan_items', $data); 
  }

  public function check_cstLoan($loan_id)
  {
    $this->db->where('loan_id', $loan_id);

    $query = $this->db->get('loan_items'); 

    $check = false;

    foreach ($query->result() as $row) {
      if ($row->status == 1) {
        $check = true;
        break;
      } 
    }

    return $check;
  }

  public function update_cstLoan($loan_id, $customer_id)
  {
    $this->db->where('id', $loan_id);
    $this->db->update('loans', ['status' => 0]);

    $this->db->where('id', $customer_id);
    $this->db->update('customers', ['loan_status' => 0]); 
  }

  public function get_quotasPaid($data)
  {
    $this->db->where_in('id', $data);
    return $this->db->get('loan_items')->result();
  }

  /**
   * Obtiene una cuota específica
   */
  public function get_loan_item($id)
  {
    $this->db->where('id', $id);
    return $this->db->get('loan_items')->row();
  }

  /**
   * Procesa pago manual sobre una cuota
   * - amount: monto positivo
   * - description: texto opcional guardado en payment_description
   * - payment_type: 'interest', 'capital', 'both', 'full'
   */
  public function process_manual_payment($loan_item_id, $amount, $description, $payment_type, $user_id = null)
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ((int)$loan_item->status === 0) {
      return ['success' => false, 'error' => 'La cuota ya está pagada'];
    }

    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a 0'];
    }

    switch ($payment_type) {
      case 'interest':
        $result = $this->pay_interest_only($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'capital':
        $result = $this->pay_capital_only($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'both':
        $result = $this->pay_both($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'full':
        $result = $this->pay_full_quota($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      default:
        $result = ['success' => false, 'error' => 'Tipo de pago no válido'];
    }

    return $result;
  }

  /**
   * Procesa un pago según el tipo especificado
   */
  public function process_payment($data)
  {
    log_message('debug', 'Iniciando process_payment con datos: ' . json_encode($data));

    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $this->db->trans_start();

    try {
      $loan_id = $data['loan_id'];
      $loan_item_id = $data['loan_item_id'];
      $amount = $data['amount'];
      $payment_type = $data['payment_type'];
      $payment_user_id = $data['payment_user_id'];
      $method = $data['method'] ?? 'efectivo';
      $notes = $data['notes'] ?? '';
      $loan = $data['loan'];
      $loan_item = $data['loan_item'];

      $result = ['success' => false, 'data' => null, 'error' => ''];

      switch ($payment_type) {
        case 'interest':
          $result = $this->process_interest_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'capital':
          $result = $this->process_capital_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'both':
          $result = $this->process_both_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'full':
          $result = $this->process_full_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        default:
          throw new Exception('Tipo de pago no válido');
      }

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de solo intereses
   */
  private function process_interest_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'interest')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar intereses'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // Verificar que el monto no exceda los intereses pendientes
    $interest_pending = $loan_item->interest_amount - ($loan_item->interest_paid ?? 0);
    if ($amount > $interest_pending) {
      return ['success' => false, 'error' => 'El monto excede los intereses pendientes'];
    }

    // Actualizar intereses pagados en la cuota
    $new_interest_paid = ($loan_item->interest_paid ?? 0) + $amount;
    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', ['interest_paid' => $new_interest_paid]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $amount,
      'capital_paid' => 0,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago de intereses: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $interest_pending - $amount,
        'remaining_capital' => $loan_item->capital_amount - ($loan_item->capital_paid ?? 0)
      ]
    ];
  }

  /**
   * Procesa pago de solo capital
   */
  private function process_capital_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'capital')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar capital'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // Verificar que el monto no exceda el capital pendiente
    $capital_pending = $loan_item->capital_amount - ($loan_item->capital_paid ?? 0);
    if ($amount > $capital_pending) {
      return ['success' => false, 'error' => 'El monto excede el capital pendiente'];
    }

    // Actualizar capital pagado en la cuota
    $new_capital_paid = ($loan_item->capital_paid ?? 0) + $amount;
    $new_balance = $loan_item->balance - $amount;
    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'capital_paid' => $new_capital_paid,
      'balance' => $new_balance
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => 0,
      'capital_paid' => $amount,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago de capital: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $loan_item->interest_amount - ($loan_item->interest_paid ?? 0),
        'remaining_capital' => $capital_pending - $amount
      ]
    ];
  }

  /**
   * Procesa pago de capital e intereses
   */
  private function process_both_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'both')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    $interest_pending = $loan_item->interest_amount - ($loan_item->interest_paid ?? 0);
    $capital_pending = $loan_item->capital_amount - ($loan_item->capital_paid ?? 0);
    $total_pending = $interest_pending + $capital_pending;

    if ($amount > $total_pending) {
      return ['success' => false, 'error' => 'El monto excede el total pendiente de la cuota'];
    }

    // Distribuir el pago: primero intereses, luego capital
    $interest_paid = min($amount, $interest_pending);
    $capital_paid = $amount - $interest_paid;

    // Actualizar la cuota
    $new_interest_paid = ($loan_item->interest_paid ?? 0) + $interest_paid;
    $new_capital_paid = ($loan_item->capital_paid ?? 0) + $capital_paid;
    $new_balance = $loan_item->balance - $capital_paid;

    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'interest_paid' => $new_interest_paid,
      'capital_paid' => $new_capital_paid,
      'balance' => $new_balance
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $interest_paid,
      'capital_paid' => $capital_paid,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago mixto: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $interest_pending - $interest_paid,
        'remaining_capital' => $capital_pending - $capital_paid
      ]
    ];
  }

  /**
   * Procesa pago total de una cuota
   */
  private function process_full_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'full')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    $interest_pending = $loan_item->interest_amount - ($loan_item->interest_paid ?? 0);
    $capital_pending = $loan_item->capital_amount - ($loan_item->capital_paid ?? 0);
    $total_pending = $interest_pending + $capital_pending;

    if ($amount < $total_pending) {
      return ['success' => false, 'error' => 'El monto es menor al total pendiente de la cuota'];
    }

    // Marcar la cuota como pagada
    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'status' => 0,
      'interest_paid' => $loan_item->interest_amount,
      'capital_paid' => $loan_item->capital_amount,
      'balance' => 0,
      'pay_date' => date('Y-m-d H:i:s')
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $interest_pending,
      'capital_paid' => $capital_pending,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago completo: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    // Verificar si el préstamo está completamente pagado
    $this->check_and_close_loan($loan_id);

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => 0,
        'remaining_capital' => 0,
        'quota_paid' => true
      ]
    ];
  }

  /**
   * Verifica si el préstamo está completamente pagado y lo cierra
   */
  private function check_and_close_loan($loan_id)
  {
    $this->db->where('loan_id', $loan_id);
    $this->db->where('status', 1);
    $pending_items = $this->db->get('loan_items')->num_rows();

    if ($pending_items == 0) {
      // Cerrar el préstamo
      $this->db->where('id', $loan_id);
      $this->db->update('loans', ['status' => 0]);

      // Obtener el customer_id para actualizar su estado
      $loan = $this->db->where('id', $loan_id)->get('loans')->row();
      if ($loan) {
        $this->db->where('id', $loan->customer_id);
        $this->db->update('customers', ['loan_status' => 0]);
      }
    }
  }

  /**
   * Procesa pago de solo intereses
   */
  public function pay_interest_only($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'interest')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_interest_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de solo capital con recálculo de amortización
   */
  public function pay_capital_only($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'capital')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_capital_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        // Recalcular amortización para las cuotas futuras
        $this->recalculate_amortization($loan_id, $loan_item_id);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de intereses y capital
   */
  public function pay_both($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'both')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_both_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago completo de una cuota
   */
  public function pay_full_quota($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'full')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_full_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Recalcula la amortización para las cuotas futuras después de un pago a capital
   */
  private function recalculate_amortization($loan_id, $current_loan_item_id)
  {
    // Cargar modelos necesarios
    $this->load->model('Loans_m');
    $this->load->library('Amortization');

    // Obtener datos del préstamo
    $loan = $this->Loans_m->get_loan($loan_id);
    if (!$loan) {
      throw new Exception('Préstamo no encontrado');
    }

    // Obtener todas las cuotas del préstamo
    $loan_items = $this->Loans_m->get_loanItems($loan_id);
    if (!$loan_items) {
      throw new Exception('No se encontraron cuotas para el préstamo');
    }

    // Encontrar la cuota actual
    $current_item = null;
    $future_items = [];
    foreach ($loan_items as $item) {
      if ($item->id == $current_loan_item_id) {
        $current_item = $item;
        break;
      }
    }

    if (!$current_item) {
      throw new Exception('Cuota actual no encontrada');
    }

    // Filtrar cuotas futuras
    $future_items = array_filter($loan_items, function($item) use ($current_item) {
      return $item->num_quota > $current_item->num_quota;
    });

    if (empty($future_items)) {
      // No hay cuotas futuras
      return;
    }

    // Calcular el nuevo balance después del pago
    $new_balance = $current_item->balance;

    // Número de cuotas restantes
    $remaining_periods = count($future_items);

    // Calcular frecuencia de pago
    $payment_frequency = $loan->payment_m; // mensual, quincenal, etc.

    // Calcular períodos por año
    switch ($payment_frequency) {
      case 'mensual':
        $periods_per_year = 12;
        break;
      case 'quincenal':
        $periods_per_year = 24;
        break;
      case 'semanal':
        $periods_per_year = 52;
        break;
      default:
        $periods_per_year = 12;
    }

    // Calcular tasa periódica (no se usa directamente aquí)
    // $periodic_rate = $this->Loans_m->get_period_rate($loan->interest_amount, $periods_per_year);

    // Recalcular amortización para las cuotas restantes
    $start_date = !empty($future_items) ? $future_items[0]->date : $current_item->date;
    $amortization_result = $this->amortization->calculate_french_amortization(
      $new_balance,
      $loan->interest_amount,
      $remaining_periods,
      $start_date, // fecha de la primera cuota futura como inicio
      $loan->amortization_type,
      $payment_frequency
    );

    if (!isset($amortization_result['table']) || empty($amortization_result['table'])) {
      throw new Exception('Error al recalcular amortización');
    }

    $new_table = $amortization_result['table'];

    // Actualizar las cuotas futuras
    $future_items_array = array_values($future_items); // reindex
    for ($i = 0; $i < $remaining_periods; $i++) {
      $future_item = $future_items_array[$i];
      $new_data = $new_table[$i];

      $update_data = [
        'interest_amount' => $new_data['interes'],
        'capital_amount' => $new_data['capital'],
        'fee_amount' => $new_data['cuota'],
        'balance' => $new_data['saldo']
      ];

      $this->db->where('id', $future_item->id);
      $this->db->update('loan_items', $update_data);
    }
  }

}

/* End of file Payments_m.php */
/* Location: ./application/models/Payments_m.php */