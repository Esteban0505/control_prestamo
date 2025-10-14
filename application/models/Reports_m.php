<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports_m extends CI_Model {

  public function get_reportLoan($coin_id)
  {
    $this->db->select('c.short_name, sum(l.credit_amount) as sum_credit');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where('l.coin_id', $coin_id);
    $cr = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interest');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where('l.coin_id', $coin_id);
    $cr_interest = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interestPaid');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where(['l.coin_id' => $coin_id, 'l.status' => 0]);
    $cr_interestPaid = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interestPay');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where(['l.coin_id' => $coin_id, 'l.status' => 1]);
    $cr_interestPay = $this->db->get('loans l')->row();

    $credits = [$cr, $cr_interest, $cr_interestPaid, $cr_interestPay];

    return $credits;
  }

  public function get_reportCoin($coin_id)
  {
    $this->db->where('id', $coin_id);

    return $this->db->get('coins')->row(); 
  }

  public function get_reportDates($coin_id, $start_date, $end_date)
  {
    $this->db->select("id, date, credit_amount, interest_amount, num_fee, payment_m,
     (num_fee*fee_amount) AS total_int, status");
    $this->db->from('loans');
    $this->db->where('coin_id', $coin_id);
    $this->db->where("date BETWEEN '{$start_date}' AND '{$end_date}'");

    return $this->db->get()->result(); 
  }

  public function get_reportCsts()
  {
    $this->db->select("id, dni, CONCAT(first_name, ' ',last_name) AS customer");
    $this->db->from('customers');
    $this->db->where('loan_status', 1);

    return $this->db->get()->result();
  }

  /**
   * Reporte global por clientes: muestra todos los clientes con al menos un préstamo,
   * incluyendo total de préstamos, monto total prestado, total pagado, fecha último préstamo
   */
  public function get_customers_report()
  {
    $this->db->select("c.id, c.dni, CONCAT(c.first_name, ' ', c.last_name) AS nombre_cliente, COUNT(l.id) AS total_prestamos, SUM(l.credit_amount) AS total_prestado, SUM(COALESCE(li.fee_amount, 0)) AS total_pagado, MAX(l.date) AS ultimo_prestamo");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id', 'left');
    $this->db->join('loan_items li', 'li.loan_id = l.id AND li.status = 0', 'left'); // Solo pagos realizados
    $this->db->group_by('c.id');
    $this->db->having('COUNT(l.id) > 0'); // Solo clientes con al menos un préstamo
    $this->db->order_by('total_prestamos', 'DESC');

    return $this->db->get()->result();
  }

  public function get_reportLC($customer_id)
  {
    $this->db->select("l.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, co.short_name, co.name");
    $this->db->from('loans l');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->where('l.customer_id', $customer_id);

    return $this->db->get()->result(); 
  }

  public function get_reportLI($loan_id)
  {
    $this->db->where('loan_id', $loan_id);

    return $this->db->get('loan_items')->result(); 
  }

  /**
   * Resumen de pagos por cliente
   * Retorna: customer_id, customer_name, payments_count, total_paid, last_payment
   */
  public function get_payments_by_customer()
  {
    // Basado en loan_items (status=0 pagado)
    $this->db->select("c.id AS customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name, COUNT(li.id) AS payments_count, SUM(li.fee_amount) AS total_paid, MAX(li.pay_date) AS last_payment")
             ->from('loan_items li')
             ->join('loans l', 'l.id = li.loan_id', 'left')
             ->join('customers c', 'c.id = l.customer_id', 'left')
             ->where('li.status', 0)
             ->group_by('c.id')
             ->order_by('payments_count', 'DESC');

    return $this->db->get()->result();
  }

  /**
   * Top usuarios cobradores por cantidad de cobranzas
   * Retorna: user_id, user_name, payments_count
   */
  public function get_top_collectors()
  {
    // Si existe columna 'paid_by' en loan_items, se usa para atribuir la cobranza
    if ($this->db->field_exists('paid_by', 'loan_items')) {
      $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COUNT(li.id) AS payments_count")
               ->from('loan_items li')
               ->join('users u', 'u.id = li.paid_by', 'left')
               ->where('li.status', 0)
               ->where('li.paid_by IS NOT NULL', null, false)
               ->group_by('u.id')
               ->order_by('payments_count', 'DESC');
      return $this->db->get()->result();
    }

    // Sin columna de auditoría no se puede atribuir al usuario
    return [];
  }

  /**
   * Cliente con mayor racha (consecutivos) de pagos realizados por número de cuota dentro de cada préstamo
   * Calcula en PHP: por cada cliente y préstamo, cuenta secuencias consecutivas de loan_items pagados (status=0)
   * Retorna array con: customer_id, customer_name, max_streak, details (opcional)
   */
  public function get_customer_with_longest_paid_streak()
  {
    // Traer cuotas pagadas con su cliente y préstamo
    $this->db->select('c.id AS customer_id, c.first_name, c.last_name, l.id AS loan_id, li.num_quota, li.status')
             ->from('loan_items li')
             ->join('loans l', 'l.id = li.loan_id', 'left')
             ->join('customers c', 'c.id = l.customer_id', 'left')
             ->where('li.status', 0)
             ->order_by('c.id ASC, l.id ASC, li.num_quota ASC');

    $rows = $this->db->get()->result();
    if (empty($rows)) {
      return null;
    }

    $maxByCustomer = [];
    $currentCustomer = null;
    $currentLoan = null;
    $prevQuota = null;
    $currentStreak = 0;

    foreach ($rows as $row) {
      $customerKey = $row->customer_id;
      $loanKey = $row->loan_id;

      if ($currentCustomer !== $customerKey || $currentLoan !== $loanKey) {
        // Reiniciar para nuevo préstamo o cliente
        $currentCustomer = $customerKey;
        $currentLoan = $loanKey;
        $prevQuota = null;
        $currentStreak = 0;
      }

      if ($prevQuota !== null && ((int)$row->num_quota === (int)$prevQuota + 1)) {
        $currentStreak += 1;
      } else {
        // Inicia una nueva racha de al menos 1
        $currentStreak = 1;
      }

      $prevQuota = (int)$row->num_quota;

      if (!isset($maxByCustomer[$customerKey])) {
        $maxByCustomer[$customerKey] = [
          'customer_id' => $row->customer_id,
          'customer_name' => trim($row->first_name.' '.$row->last_name),
          'max_streak' => $currentStreak,
        ];
      } else if ($currentStreak > $maxByCustomer[$customerKey]['max_streak']) {
        $maxByCustomer[$customerKey]['max_streak'] = $currentStreak;
      }
    }

    // Encontrar el máximo global
    $top = null;
    foreach ($maxByCustomer as $entry) {
      if ($top === null || $entry['max_streak'] > $top['max_streak']) {
        $top = $entry;
      }
    }

    return $top; // null o ['customer_id'=>, 'customer_name'=>, 'max_streak'=>]
  }

  /**
   * Estadísticas para dashboard: Total de préstamos por tipo de moneda
   */
  public function get_currency_loans($start_date = null, $end_date = null)
  {
    log_message('debug', 'Reports_m::get_currency_loans called with start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    $this->db->select('c.name, SUM(l.credit_amount) AS total_amount');
    $this->db->from('loans l');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');

    if ($start_date && $end_date) {
      $this->db->where('l.date >=', $start_date);
      $this->db->where('l.date <=', $end_date);
      log_message('debug', 'Reports_m::get_currency_loans applying date filter: ' . $start_date . ' to ' . $end_date);
    } else {
      log_message('debug', 'Reports_m::get_currency_loans no date filter applied');
    }

    $this->db->group_by('c.name');
    $result = $this->db->get()->result();
    log_message('debug', 'Reports_m::get_currency_loans result count: ' . count($result));

    return $result;
  }

  /**
   * Estadísticas para dashboard: Monto total prestado por mes
   */
  public function get_loan_amounts_by_month($start_date = null, $end_date = null)
  {
    log_message('debug', 'Reports_m::get_loan_amounts_by_month called with start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    $this->db->select('MONTH(date) AS month, YEAR(date) AS year, SUM(credit_amount) AS total_amount');
    $this->db->from('loans');

    if ($start_date && $end_date) {
      $this->db->where('date >=', $start_date);
      $this->db->where('date <=', $end_date);
      log_message('debug', 'Reports_m::get_loan_amounts_by_month applying date filter: ' . $start_date . ' to ' . $end_date);
    } else {
      log_message('debug', 'Reports_m::get_loan_amounts_by_month no date filter applied');
    }

    $this->db->group_by('YEAR(date), MONTH(date)');
    $this->db->order_by('YEAR(date) ASC, MONTH(date) ASC');
    $result = $this->db->get()->result();
    log_message('debug', 'Reports_m::get_loan_amounts_by_month result count: ' . count($result));

    return $result;
  }

  /**
   * Estadísticas para dashboard: Pagos recibidos por mes
   */
  public function get_received_payments_by_month($start_date = null, $end_date = null)
  {
    log_message('debug', 'Reports_m::get_received_payments_by_month called with start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    $this->db->select('MONTH(pay_date) AS month, YEAR(pay_date) AS year, SUM(fee_amount) AS total_payments');
    $this->db->from('loan_items');
    $this->db->where('status', 0); // Pagado

    if ($start_date && $end_date) {
      $this->db->where('pay_date >=', $start_date);
      $this->db->where('pay_date <=', $end_date);
      log_message('debug', 'Reports_m::get_received_payments_by_month applying date filter: ' . $start_date . ' to ' . $end_date);
    } else {
      log_message('debug', 'Reports_m::get_received_payments_by_month no date filter applied');
    }

    $this->db->group_by('YEAR(pay_date), MONTH(pay_date)');
    $this->db->order_by('YEAR(pay_date) ASC, MONTH(pay_date) ASC');
    $result = $this->db->get()->result();
    log_message('debug', 'Reports_m::get_received_payments_by_month result count: ' . count($result));

    return $result;
  }

  /**
   * Estadísticas para dashboard: Pagos esperados por mes
   */
  public function get_expected_payments_by_month($start_date = null, $end_date = null)
  {
    log_message('debug', 'Reports_m::get_expected_payments_by_month called with start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    $this->db->select('MONTH(date) AS month, YEAR(date) AS year, SUM(fee_amount) AS total_expected');
    $this->db->from('loan_items');

    if ($start_date && $end_date) {
      $this->db->where('date >=', $start_date);
      $this->db->where('date <=', $end_date);
      log_message('debug', 'Reports_m::get_expected_payments_by_month applying date filter: ' . $start_date . ' to ' . $end_date);
    } else {
      log_message('debug', 'Reports_m::get_expected_payments_by_month no date filter applied');
    }

    $this->db->group_by('YEAR(date), MONTH(date)');
    $this->db->order_by('YEAR(date) ASC, MONTH(date) ASC');
    $result = $this->db->get()->result();
    log_message('debug', 'Reports_m::get_expected_payments_by_month result count: ' . count($result));

    return $result;
  }

  /**
    * Reporte de comisiones por cobrador
    */
   public function get_commissions_by_user()
   {
     $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) AS user_name, SUM(cc.commission) AS total_commission, COUNT(cc.id) AS payments_count');
     $this->db->from('collector_commissions cc');
     $this->db->join('users u', 'u.id = cc.user_id', 'left');
     $this->db->group_by('cc.user_id');
     $this->db->order_by('total_commission', 'DESC');
     return $this->db->get()->result();
   }

   /**
    * Reporte detallado de comisiones con datos del cliente y cobrador
    */
   public function get_detailed_commissions_report()
   {
     $this->db->select('cc.id, cc.client_name, cc.client_cedula, cc.amount, cc.commission, cc.created_at, CONCAT(u.first_name, " ", u.last_name) AS collector_name, li.fee_amount AS total_paid, li.interest_amount AS interest_total');
     $this->db->from('collector_commissions cc');
     $this->db->join('users u', 'u.id = cc.user_id', 'left');
     $this->db->join('loan_items li', 'li.id = cc.loan_item_id', 'left');
     $this->db->order_by('cc.created_at', 'DESC');
     return $this->db->get()->result();
   }

   /**
    * Estadísticas de comisiones agrupadas por cobrador para gráfica
    */
   public function get_commission_stats()
   {
     $this->db->select('CONCAT(u.first_name, " ", u.last_name) AS user_name, SUM(cc.commission) AS total_commission, SUM(cc.amount) AS total_amount, COUNT(cc.id) AS num_cobros');
     $this->db->from('collector_commissions cc');
     $this->db->join('users u', 'u.id = cc.user_id', 'left');
     $this->db->group_by('cc.user_id');
     $this->db->order_by('total_commission', 'DESC');
     return $this->db->get()->result();
   }

   /**
    * Totales generales de comisiones
    */
   public function get_commission_totals()
   {
     $this->db->select('SUM(commission) AS total_commission, SUM(amount) AS total_amount, COUNT(id) AS total_cobros');
     $this->db->from('collector_commissions');
     return $this->db->get()->row();
   }

   /**
    * Reporte detallado de comisiones con filtros
    */
   public function get_commission_stats_filtered($start_date = null, $end_date = null, $collector_id = null)
   {
     $this->db->select('cc.id, cc.client_name, cc.client_cedula, cc.amount, cc.commission, cc.created_at, CONCAT(u.first_name, " ", u.last_name) AS user_name, li.fee_amount AS total_paid, li.interest_amount AS interest_amount');
     $this->db->from('collector_commissions cc');
     $this->db->join('users u', 'u.id = cc.user_id', 'left');
     $this->db->join('loan_items li', 'li.id = cc.loan_item_id', 'left');

     if ($start_date) {
       $this->db->where('DATE(cc.created_at) >=', $start_date);
     }
     if ($end_date) {
       $this->db->where('DATE(cc.created_at) <=', $end_date);
     }
     if ($collector_id) {
       $this->db->where('cc.user_id', $collector_id);
     }

     $this->db->order_by('cc.created_at', 'DESC');
     return $this->db->get()->result();
   }

   /**
    * Reporte de castigos registrados
    */
   public function get_penalties_report()
   {
     $this->db->select('lp.id, lp.loan_id, lp.customer_id, lp.penalty_reason, lp.created_at, CONCAT(c.first_name, " ", c.last_name) AS client_name, c.dni, l.credit_amount');
     $this->db->from('loans_penalties lp');
     $this->db->join('customers c', 'c.id = lp.customer_id', 'left');
     $this->db->join('loans l', 'l.id = lp.loan_id', 'left');
     $this->db->order_by('lp.created_at', 'DESC');
     return $this->db->get()->result();
   }

   /**
    * Totales de cobranza para tarjetas de resumen
    */
   public function get_cobranza_totals()
   {
     $result = [];

     // Total cobros realizados
     $this->db->select('COUNT(*) as total_cobros, SUM(amount) as total_recaudado');
     $this->db->from('collector_commissions');
     $result['total_cobros'] = $this->db->get()->row();

     // Cobros por usuario
     $this->db->select('COUNT(*) as cobros_por_usuario');
     $this->db->from('collector_commissions');
     $this->db->group_by('user_id');
     $result['cobros_por_usuario'] = $this->db->get()->num_rows();

     // Pagos completos (asumiendo que si el balance llega a 0, es completo)
     $this->db->select('COUNT(DISTINCT cc.id) as pagos_completos');
     $this->db->from('collector_commissions cc');
     $this->db->join('loan_items li', 'li.id = cc.loan_item_id', 'left');
     $this->db->where('li.balance', 0);
     $result['pagos_completos'] = $this->db->get()->row()->pagos_completos;

     // Pagos parciales (balance > 0)
     $this->db->select('COUNT(DISTINCT cc.id) as pagos_parciales');
     $this->db->from('collector_commissions cc');
     $this->db->join('loan_items li', 'li.id = cc.loan_item_id', 'left');
     $this->db->where('li.balance >', 0);
     $result['pagos_parciales'] = $this->db->get()->row()->pagos_parciales;

     return $result;
   }

   /**
    * Obtener lista de cobradores para filtro
    */
   public function get_cobradores_list()
   {
     // Obtener TODOS los usuarios que han realizado al menos una cobranza (con o sin intereses)
     $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) AS nombre');
     $this->db->from('users u');
     $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
     $this->db->where('li.status', 0); // Pagado
     $this->db->where('li.paid_by IS NOT NULL', null, false); // Asegurar que paid_by no sea null
     $this->db->group_by('u.id'); // Agrupar por usuario para evitar duplicados
     $this->db->order_by('u.first_name');

     $result = $this->db->get()->result();

     log_message('debug', 'Reports_m::get_cobradores_list - Encontrados ' . count($result) . ' cobradores');
     if (!empty($result)) {
       $nombres = array_column($result, 'nombre');
       log_message('debug', 'Reports_m::get_cobradores_list - Nombres: ' . implode(', ', $nombres));
     }

     // Si no hay resultados, devolver lista vacía pero no error
     return $result ?: [];
   }

   /**
    * Datos para gráfico de pagos por cliente (Top 10)
    */
   public function get_payments_by_customer_chart()
   {
     log_message('debug', 'Reports_m::get_payments_by_customer_chart called');

     $this->db->select("c.id AS customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name, COUNT(li.id) AS payments_count, SUM(li.fee_amount) AS total_paid")
              ->from('loan_items li')
              ->join('loans l', 'l.id = li.loan_id', 'left')
              ->join('customers c', 'c.id = l.customer_id', 'left')
              ->where('li.status', 0)
              ->group_by('c.id')
              ->order_by('payments_count', 'DESC')
              ->limit(10);

     $result = $this->db->get()->result();
     log_message('debug', 'Reports_m::get_payments_by_customer_chart result: ' . json_encode($result));

     return $result;
   }

   /**
    * Datos para gráfico de usuarios cobradores (Top 10)
    */
   public function get_top_collectors_chart()
   {
     if ($this->db->field_exists('paid_by', 'loan_items')) {
       $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COUNT(li.id) AS payments_count")
                ->from('loan_items li')
                ->join('users u', 'u.id = li.paid_by', 'left')
                ->where('li.status', 0)
                ->where('li.paid_by IS NOT NULL', null, false)
                ->group_by('u.id')
                ->order_by('payments_count', 'DESC')
                ->limit(10);
       return $this->db->get()->result();
     }
     return [];
   }

   /**
    * Datos para gráfico de castigos por motivo
    */
   public function get_penalties_chart_data()
   {
     log_message('debug', 'Reports_m::get_penalties_chart_data called');

     // Verificar si la tabla existe
     if (!$this->db->table_exists('loans_penalties')) {
       log_message('debug', 'Reports_m::get_penalties_chart_data - table loans_penalties does not exist');
       return [];
     }

     $this->db->select('penalty_reason, COUNT(*) as count, SUM(credit_amount) as total_amount')
              ->from('loans_penalties')
              ->group_by('penalty_reason')
              ->order_by('count', 'DESC');

     $result = $this->db->get()->result();
     log_message('debug', 'Reports_m::get_penalties_chart_data result: ' . json_encode($result));

     return $result;
   }

   /**
    * Datos para gráfico de racha de pagos (Top 5 clientes)
    */
   public function get_streak_chart_data()
   {
     // Obtener las rachas calculadas
     $streaks = $this->get_customer_with_longest_paid_streak();
     if (!$streaks) return [];

     // Obtener top 5 con rachas
     $this->db->select('c.id AS customer_id, c.first_name, c.last_name, l.id AS loan_id, li.num_quota, li.status')
              ->from('loan_items li')
              ->join('loans l', 'l.id = li.loan_id', 'left')
              ->join('customers c', 'c.id = l.customer_id', 'left')
              ->where('li.status', 0)
              ->order_by('c.id ASC, l.id ASC, li.num_quota ASC');

     $rows = $this->db->get()->result();
     if (empty($rows)) return [];

     $customerStreaks = [];
     $currentCustomer = null;
     $currentLoan = null;
     $prevQuota = null;
     $currentStreak = 0;
     $currentCustomerData = null;

     foreach ($rows as $row) {
       $customerKey = $row->customer_id;
       $loanKey = $row->loan_id;

       if ($currentCustomer !== $customerKey || $currentLoan !== $loanKey) {
         if ($currentCustomer !== null && $currentCustomerData) {
           $customerName = trim($currentCustomerData->first_name.' '.$currentCustomerData->last_name);
           if (!isset($customerStreaks[$customerName])) {
             $customerStreaks[$customerName] = 0;
           }
           $customerStreaks[$customerName] = max($customerStreaks[$customerName], $currentStreak);
         }

         $currentCustomer = $customerKey;
         $currentCustomerData = $row;
         $currentLoan = $loanKey;
         $prevQuota = null;
         $currentStreak = 0;
       }

       if ($prevQuota !== null && ((int)$row->num_quota === (int)$prevQuota + 1)) {
         $currentStreak += 1;
       } else {
         $currentStreak = 1;
       }

       $prevQuota = (int)$row->num_quota;
     }

     // Último cliente
     if ($currentCustomer !== null && $currentCustomerData) {
       $customerName = trim($currentCustomerData->first_name.' '.$currentCustomerData->last_name);
       if (!isset($customerStreaks[$customerName])) {
         $customerStreaks[$customerName] = 0;
       }
       $customerStreaks[$customerName] = max($customerStreaks[$customerName], $currentStreak);
     }

     // Ordenar y tomar top 5
     arsort($customerStreaks);
     return array_slice($customerStreaks, 0, 5, true);
   }

   /**
    * Obtener información detallada de cobranzas por usuario
    */
   public function get_collections_by_user($user_id = null)
   {
     $this->db->select("
       u.id AS user_id,
       CONCAT(u.first_name, ' ', u.last_name) AS user_name,
       COUNT(DISTINCT li.id) AS total_quotas_collected,
       COUNT(DISTINCT CASE WHEN li.status = 0 THEN li.id END) AS quotas_paid,
       COUNT(DISTINCT CASE WHEN li.status = 1 THEN li.id END) AS quotas_pending,
       SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END) AS total_amount_collected,
       COUNT(DISTINCT l.id) AS total_loans_handled,
       COUNT(DISTINCT c.id) AS total_customers_handled
     ")
     ->from('loan_items li')
     ->join('loans l', 'l.id = li.loan_id', 'left')
     ->join('customers c', 'c.id = l.customer_id', 'left')
     ->join('users u', 'u.id = li.paid_by', 'left');

     if ($user_id) {
       $this->db->where('li.paid_by', $user_id);
     }

     $this->db->where('li.paid_by IS NOT NULL', null, false)
              ->group_by('u.id')
              ->order_by('total_amount_collected', 'DESC');

     return $this->db->get()->result();
   }

   /**
    * Obtener progreso de cobranzas por usuario (cuotas cobradas/total por préstamo)
    */
   public function get_user_collection_progress($user_id = null)
   {
     $this->db->select("
       u.id AS user_id,
       CONCAT(u.first_name, ' ', u.last_name) AS user_name,
       l.id AS loan_id,
       CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
       c.dni AS customer_dni,
       l.num_fee AS total_quotas,
       COUNT(CASE WHEN li.status = 0 THEN 1 END) AS paid_quotas,
       SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END) AS amount_collected
     ")
     ->from('loan_items li')
     ->join('loans l', 'l.id = li.loan_id', 'left')
     ->join('customers c', 'c.id = l.customer_id', 'left')
     ->join('users u', 'u.id = li.paid_by', 'left');

     if ($user_id) {
       $this->db->where('li.paid_by', $user_id);
     }

     $this->db->where('li.paid_by IS NOT NULL', null, false)
              ->group_by('u.id, l.id')
              ->order_by('u.id, l.id');

     return $this->db->get()->result();
   }

   /**
    * Obtener datos filtrados para gráficos por usuario
    */
   public function get_payments_by_customer_chart_filtered($user_id = null)
   {
     log_message('debug', 'Reports_m::get_payments_by_customer_chart_filtered called with user_id: ' . ($user_id ?: 'null'));

     $this->db->select("c.id AS customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name, COUNT(li.id) AS payments_count, SUM(li.fee_amount) AS total_paid")
              ->from('loan_items li')
              ->join('loans l', 'l.id = li.loan_id', 'left')
              ->join('customers c', 'c.id = l.customer_id', 'left')
              ->where('li.status', 0);

     if ($user_id) {
       $this->db->where('li.paid_by', $user_id);
       log_message('debug', 'Reports_m::get_payments_by_customer_chart_filtered - filtering by user_id: ' . $user_id);
     }

     $this->db->group_by('c.id')
              ->order_by('payments_count', 'DESC')
              ->limit(10);

     $result = $this->db->get()->result();
     log_message('debug', 'Reports_m::get_payments_by_customer_chart_filtered result count: ' . count($result));
     log_message('debug', 'Reports_m::get_payments_by_customer_chart_filtered result: ' . json_encode($result));

     return $result ?: [];
   }

   /**
    * Obtener datos de top cobradores filtrados
    */
   public function get_top_collectors_chart_filtered($user_id = null)
   {
     if ($this->db->field_exists('paid_by', 'loan_items')) {
       $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COUNT(li.id) AS payments_count")
                ->from('loan_items li')
                ->join('users u', 'u.id = li.paid_by', 'left')
                ->where('li.status', 0)
                ->where('li.paid_by IS NOT NULL', null, false);

       if ($user_id) {
         $this->db->where('li.paid_by', $user_id);
       }

       $this->db->group_by('u.id')
                ->order_by('payments_count', 'DESC')
                ->limit(10);
       return $this->db->get()->result();
     }
     return [];
   }

   /**
    * Obtener monto total de cartera activa (préstamos con saldo pendiente)
    */
   public function get_total_active_portfolio()
   {
     log_message('debug', 'Reports_m::get_total_active_portfolio called');

     // Suma de todos los saldos pendientes en loan_items donde status = 1 (pendiente)
     $this->db->select('SUM(fee_amount) as total_portfolio')
              ->from('loan_items')
              ->where('status', 1); // Solo cuotas pendientes

     $result = $this->db->get()->row();
     $total = $result->total_portfolio ?? 0;

     log_message('debug', 'Reports_m::get_total_active_portfolio result: ' . $total);
     return $total;
   }

   /**
    * Calcular tasa de morosidad (préstamos vencidos vs total)
    */
   public function get_delinquency_rate()
   {
     log_message('debug', 'Reports_m::get_delinquency_rate called');

     // Total de cuotas pendientes
     $this->db->select('COUNT(*) as total_pending')
              ->from('loan_items')
              ->where('status', 1);
     $total_pending = $this->db->get()->row()->total_pending ?? 0;

     // Cuotas vencidas (fecha actual > date y status = 1)
     $this->db->select('COUNT(*) as total_overdue')
              ->from('loan_items')
              ->where('status', 1)
              ->where('date <', date('Y-m-d'));
     $total_overdue = $this->db->get()->row()->total_overdue ?? 0;

     $rate = $total_pending > 0 ? ($total_overdue / $total_pending) * 100 : 0;

     log_message('debug', 'Reports_m::get_delinquency_rate - overdue: ' . $total_overdue . ', pending: ' . $total_pending . ', rate: ' . $rate);
     return round($rate, 2);
   }

   /**
    * Calcular promedio de préstamo por cliente
    */
   public function get_average_loan_per_customer()
   {
     log_message('debug', 'Reports_m::get_average_loan_per_customer called');

     // Obtener el promedio de credit_amount por cliente que tiene al menos un préstamo
     $this->db->select('AVG(avg_amount) as average_loan')
              ->from('(SELECT c.id, AVG(l.credit_amount) as avg_amount
                      FROM customers c
                      LEFT JOIN loans l ON l.customer_id = c.id
                      WHERE l.id IS NOT NULL
                      GROUP BY c.id) as customer_avgs');

     $result = $this->db->get()->row();
     $average = $result->average_loan ?? 0;

     log_message('debug', 'Reports_m::get_average_loan_per_customer result: ' . $average);
     return round($average, 2);
   }

}

/* End of file Reports_m.php */
/* Location: ./application/models/Reports_m.php */