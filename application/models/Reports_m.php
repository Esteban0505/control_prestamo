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
   * Reporte de comisiones por cobrador - MOSTRAR TODOS LOS COBRADORES
   * Usa datos reales de collector_commissions y loan_interest_logs
   */
  public function get_interest_commissions($start_date = null, $end_date = null) {
      log_message('debug', 'Reports_m::get_interest_commissions - INICIO - start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

      // DEBUG: Verificar total de cobradores en la BD
      $this->db->select('COUNT(*) as total_cobradores');
      $this->db->from('users');
      $this->db->where('role', 'cobrador');
      $total_cobradores = $this->db->get()->row()->total_cobradores ?? 0;
      log_message('debug', 'Reports_m::get_interest_commissions - Total cobradores en BD: ' . $total_cobradores);

      // DEBUG: Verificar datos en collector_commissions
      $this->db->select('COUNT(*) as total_commissions, SUM(amount) as total_amount, SUM(commission) as total_commission');
      $this->db->from('collector_commissions');
      $commissions_data = $this->db->get()->row();
      log_message('debug', 'Reports_m::get_interest_commissions - Datos en collector_commissions: ' .
                 'Total: ' . ($commissions_data->total_commissions ?? 0) .
                 ', Amount: $' . number_format($commissions_data->total_amount ?? 0, 2) .
                 ', Commission: $' . number_format($commissions_data->total_commission ?? 0, 2));

      // CAMBIO PRINCIPAL: Usar datos reales de loan_items (que tiene interest_paid)
      $this->db->select("
          u.id as user_id,
          CONCAT(u.first_name, ' ', u.last_name) as user_name,
          COALESCE(SUM(li.fee_amount), 0) as total_amount_collected,
          COALESCE(SUM(li.interest_paid), 0) as total_interest_logged,
          COUNT(DISTINCT li.id) as total_payments,
          MAX(li.pay_date) as last_payment_date
      ");
      $this->db->from('users u');
      $this->db->join('loan_items li', 'u.id = li.paid_by AND li.status = 0', 'left'); // LEFT JOIN con loan_items
      $this->db->where('u.role', 'cobrador'); // Solo cobradores

      // DEBUG: Agregar logs para verificar el JOIN y filtros
      log_message('debug', 'Reports_m::get_interest_commissions - JOIN condition: u.id = li.paid_by AND li.status = 0');
      log_message('debug', 'Reports_m::get_interest_commissions - WHERE role = cobrador');

      if ($start_date) {
          $this->db->where('li.pay_date >=', $start_date);
          log_message('debug', 'Reports_m::get_interest_commissions - Aplicando filtro fecha inicio: ' . $start_date);
      }
      if ($end_date) {
          $this->db->where('li.pay_date <=', $end_date);
          log_message('debug', 'Reports_m::get_interest_commissions - Aplicando filtro fecha fin: ' . $end_date);
      }

      // IMPORTANTE: Solo incluir cobradores que han realizado pagos
      $this->db->having('total_payments > 0');

      $this->db->group_by('u.id');
      $this->db->order_by('u.first_name', 'ASC');

      $query = $this->db->get();
      $result = $query->result();

      log_message('debug', 'Reports_m::get_interest_commissions - QUERY ejecutada: ' . $this->db->last_query());
      log_message('debug', 'Reports_m::get_interest_commissions - Resultados obtenidos: ' . count($result));

      // Procesar resultados para cálculos finales
      foreach ($result as &$row) {
          // Calcular comisión al 40% basada en intereses registrados
          $row->commission_40 = $row->total_interest_logged * 0.4;

          // Determinar estado de envío basado en pagos realizados
          $row->estado_envio = ($row->total_payments > 0) ? 'Enviado' : 'Pendiente';

          // Formatear fecha último pago
          $row->ultimo_pago = $row->last_payment_date ? date('d/m/Y', strtotime($row->last_payment_date)) : 'N/A';

          // Formatear valores para mostrar en vista
          $row->total_interest_formatted = '$' . number_format($row->total_interest_logged, 2, ',', '.');
          $row->commission_40_formatted = '$' . number_format($row->commission_40, 2, ',', '.');
          $row->send_status = strtolower($row->estado_envio);
          $row->last_payment_date = $row->last_payment_date;
          $row->total_interest_paid = $row->total_interest_logged;
          $row->interest_commission_40 = $row->commission_40;

          log_message('debug', 'Reports_m::get_interest_commissions - Procesado ' . $row->user_name .
                     ' - Amount: $' . number_format($row->total_amount_collected, 2) .
                     ' - Interest: $' . number_format($row->total_interest_logged, 2) .
                     ' - Commission 40%: $' . number_format($row->commission_40, 2) .
                     ' - Estado: ' . $row->estado_envio .
                     ' - Pagos: ' . $row->total_payments);

          // DEBUG: Verificar cálculo de commission_40
          log_message('debug', 'Reports_m::get_interest_commissions - Cálculo commission_40: ' . $row->total_interest_logged . ' * 0.4 = ' . $row->commission_40);
      }

      // DEBUG: Verificar si hay cobradores sin resultados
      if ($total_cobradores > count($result)) {
          $faltantes = $total_cobradores - count($result);
          log_message('debug', 'Reports_m::get_interest_commissions - ALERTA: ' . $faltantes . ' cobradores faltan en los resultados');

          // Obtener lista de cobradores faltantes
          $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) as name');
          $this->db->from('users u');
          $this->db->where('u.role', 'cobrador');
          $todos_cobradores = $this->db->get()->result();

          $cobradores_en_resultado = array_column($result, 'user_id');
          $faltantes_lista = array_filter($todos_cobradores, function($c) use ($cobradores_en_resultado) {
              return !in_array($c->id, $cobradores_en_resultado);
          });

          log_message('debug', 'Reports_m::get_interest_commissions - Cobradores faltantes: ' . json_encode($faltantes_lista));
      }

      return $result;
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
     // DEBUG: Verificar qué roles existen en la base de datos
     $this->db->select('role');
     $this->db->distinct();
     $this->db->from('users');
     $roles_query = $this->db->get();
     $roles = $roles_query->result();
     log_message('debug', 'Reports_m::get_cobradores_list - ROLES existentes en BD: ' . json_encode($roles));

     // DEBUG: Contar usuarios por rol
     $this->db->select('role, COUNT(*) as count');
     $this->db->from('users');
     $this->db->group_by('role');
     $role_counts = $this->db->get()->result();
     log_message('debug', 'Reports_m::get_cobradores_list - Conteo por rol: ' . json_encode($role_counts));

     // Obtener TODOS los usuarios que han realizado pagos (sin filtrar por rol específico)
     $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) AS nombre, u.role');
     $this->db->distinct();
     $this->db->from('users u');
     $this->db->join('loan_items li', 'li.paid_by = u.id', 'inner'); // Solo usuarios que han realizado pagos
     $this->db->where('li.status', 0); // Pagos completados
     $this->db->order_by('u.first_name');

     $result = $this->db->get()->result();

     log_message('debug', 'Reports_m::get_cobradores_list - QUERY ejecutada: ' . $this->db->last_query());
     log_message('debug', 'Reports_m::get_cobradores_list - Encontrados ' . count($result) . ' usuarios con pagos realizados');
     if (!empty($result)) {
       foreach ($result as $user) {
         log_message('debug', 'Reports_m::get_cobradores_list - Usuario: ' . $user->nombre . ' (ID: ' . $user->id . ', Rol: ' . $user->role . ')');
       }
     } else {
       log_message('debug', 'Reports_m::get_cobradores_list - NO se encontraron usuarios con pagos realizados');
     }

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
       $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COALESCE(COUNT(li.id), 0) AS payments_count")
                ->from('users u')
                ->join('loan_items li', 'li.paid_by = u.id', 'left')
                ->where('u.role', 'cobrador') // Solo cobradores
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
       COALESCE(COUNT(DISTINCT li.id), 0) AS total_quotas_collected,
       COALESCE(COUNT(DISTINCT CASE WHEN li.status = 0 THEN li.id END), 0) AS quotas_paid,
       COALESCE(COUNT(DISTINCT CASE WHEN li.status = 1 THEN li.id END), 0) AS quotas_pending,
       COALESCE(SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END), 0) AS total_amount_collected,
       COALESCE(COUNT(DISTINCT l.id), 0) AS total_loans_handled,
       COALESCE(COUNT(DISTINCT c.id), 0) AS total_customers_handled
     ")
     ->from('users u')
     ->join('loan_items li', 'li.paid_by = u.id', 'left')
     ->join('loans l', 'l.id = li.loan_id', 'left')
     ->join('customers c', 'c.id = l.customer_id', 'left')
     ->where('u.role', 'cobrador'); // Solo cobradores

     if ($user_id) {
       $this->db->where('u.id', $user_id);
     }

     $this->db->group_by('u.id')
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
       COALESCE(l.id, 0) AS loan_id,
       COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Sin cliente') AS customer_name,
       COALESCE(c.dni, 'N/A') AS customer_dni,
       COALESCE(l.num_fee, 0) AS total_quotas,
       COALESCE(COUNT(CASE WHEN li.status = 0 THEN 1 END), 0) AS paid_quotas,
       COALESCE(SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END), 0) AS amount_collected
     ")
     ->from('users u')
     ->join('loan_items li', 'li.paid_by = u.id', 'left')
     ->join('loans l', 'l.id = li.loan_id', 'left')
     ->join('customers c', 'c.id = l.customer_id', 'left')
     ->where('u.role', 'cobrador'); // Solo cobradores

     if ($user_id) {
       $this->db->where('u.id', $user_id);
     }

     $this->db->group_by('u.id, l.id')
              ->order_by('u.id, l.id');

     return $this->db->get()->result();
   }

   /**
    * Obtener datos filtrados para gráficos por usuario
    */
   public function get_payments_by_customer_chart_filtered($user_id = null)
   {
     log_message('debug', 'Reports_m::get_payments_by_customer_chart_filtered called with user_id: ' . ($user_id ?: 'null'));

     $this->db->select("c.id AS customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name, COALESCE(COUNT(li.id), 0) AS payments_count, COALESCE(SUM(li.fee_amount), 0) AS total_paid")
              ->from('users u')
              ->join('loan_items li', 'li.paid_by = u.id', 'left')
              ->join('loans l', 'l.id = li.loan_id', 'left')
              ->join('customers c', 'c.id = l.customer_id', 'left')
              ->where('u.role', 'cobrador'); // Solo cobradores

     if ($user_id) {
       $this->db->where('u.id', $user_id);
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
       $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COALESCE(COUNT(li.id), 0) AS payments_count")
                ->from('users u')
                ->join('loan_items li', 'li.paid_by = u.id', 'left')
                ->where('u.role', 'cobrador'); // Solo cobradores

       if ($user_id) {
         $this->db->where('u.id', $user_id);
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

   /**
    * Obtener datos detallados de préstamos para el reporte con todas las columnas requeridas
    */
   public function get_detailed_loans_report($user_id = null, $start_date = null, $end_date = null)
   {
       log_message('debug', 'Reports_m::get_detailed_loans_report called with user_id: ' . ($user_id ?: 'null') . ', start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

       // CAMBIO PRINCIPAL: Obtener préstamos donde el usuario realizó pagos, no préstamos asignados
       $this->db->select("
           l.id as loan_id,
           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           c.dni,
           c.phone_fixed,
           l.credit_amount as monto_original,
           l.num_fee as total_cuotas,
           l.interest_amount as tasa_interes,
           COUNT(DISTINCT CASE WHEN li.status = 0 THEN li.id END) as pagos_realizados,
           ROUND((COUNT(DISTINCT CASE WHEN li.status = 0 THEN li.id END) / NULLIF(l.num_fee, 0)) * 100, 1) as progreso,
           COALESCE(SUM(CASE WHEN li.status = 0 THEN li.interest_paid END), 0) as interes_pagado,
           COALESCE(SUM(CASE WHEN li.status = 0 THEN li.capital_paid END), 0) as capital_pagado,
           MAX(CASE WHEN li.status = 0 THEN li.pay_date END) as ultimo_pago_fecha,
           MAX(CASE WHEN li.status = 0 THEN li.fee_amount END) as ultimo_pago_monto
       ")
       ->from('loan_items li')
       ->join('loans l', 'l.id = li.loan_id', 'left')
       ->join('customers c', 'c.id = l.customer_id', 'left')
       ->where('li.status', 0) // Solo pagos realizados
       ->where('li.paid_by IS NOT NULL'); // Solo pagos con cobrador asignado

       // Filtrar por cobrador específico si se proporciona
       if ($user_id && $user_id !== 'all') {
           $this->db->where('li.paid_by', $user_id);
           log_message('debug', 'REPORTS: Filtrando por cobrador ID: ' . $user_id);
       }

       if ($start_date) {
           $this->db->where('l.date >=', $start_date);
       }

       if ($end_date) {
           $this->db->where('l.date <=', $end_date);
       }

       // Solo préstamos activos o con pagos realizados
       $this->db->where('l.status IN (0, 1)');

       $this->db->group_by('l.id, c.id')
                ->order_by('l.date', 'DESC');

       $result = $this->db->get()->result();
       log_message('debug', 'REPORTS: Consulta ejecutada, resultados obtenidos: ' . count($result));

       // Procesar resultados para cálculos finales
       foreach ($result as &$row) {
           log_message('debug', 'REPORTS: Procesando préstamo ID ' . $row->loan_id . ' - pagos_realizados: ' . $row->pagos_realizados . ', interes_pagado: ' . $row->interes_pagado);

           // Si no hay intereses registrados pero hay pagos, calcular automáticamente
           if ($row->interes_pagado == 0 && $row->pagos_realizados > 0) {
               $calculated_interest = $this->_calculate_missing_interest($row->loan_id, $row->pagos_realizados, $row->ultimo_pago_monto, $row->tasa_interes, $row->monto_original, $row->total_cuotas);
               $row->interes_pagado = $calculated_interest;
               log_message('debug', 'REPORTS: Intereses calculados para préstamo ' . $row->loan_id . ': $' . $calculated_interest);
           }

           // Calcular comisión 40%
           $row->comision_40 = $row->interes_pagado * 0.4;

           // Determinar estado de comisión basado en si hay intereses calculados
           $row->estado_comision = ($row->interes_pagado > 0) ? 'Disponible' : 'Sin Intereses';

           // Formatear último pago
           if ($row->ultimo_pago_fecha && $row->ultimo_pago_monto) {
               $row->ultimo_pago = date('Y-m-d', strtotime($row->ultimo_pago_fecha)) . ': $' . number_format($row->ultimo_pago_monto, 2, ',', '.');
           } else {
               $row->ultimo_pago = 'N/A';
           }

           // Formatear montos
           $row->monto_original = '$' . number_format($row->monto_original, 2, ',', '.');
           $row->interes_pagado = '$' . number_format($row->interes_pagado, 2, ',', '.');
           $row->comision_40 = '$' . number_format($row->comision_40, 2, ',', '.');

           // Formatear progreso (asegurar que no sea null)
           $row->progreso = ($row->progreso !== null) ? $row->progreso . '%' : '0.0%';

           // Validar datos nulos
           $row->customer_name = $row->customer_name ?: 'Cliente sin nombre';
           $row->dni = $row->dni ?: 'N/A';
           $row->phone_fixed = $row->phone_fixed ?: 'N/A';
           $row->pagos_realizados = (int)$row->pagos_realizados;

           log_message('debug', 'REPORTS: Préstamo ' . $row->loan_id . ' final - pagos: ' . $row->pagos_realizados . ', interes: ' . $row->interes_pagado . ', progreso: ' . $row->progreso);
       }

       log_message('debug', 'Reports_m::get_detailed_loans_report result count: ' . count($result));
       return $result;
   }

   /**
    * Calcular intereses faltantes usando múltiples métodos
    */
   private function _calculate_missing_interest($loan_id, $pagos_realizados, $ultimo_pago_monto, $tasa_interes, $monto_original, $total_cuotas)
   {
       // Método 1: Calcular basado en tasa de interés del préstamo
       if ($tasa_interes > 0 && $total_cuotas > 0) {
           $monthly_rate = $tasa_interes / 100; // Convertir porcentaje a decimal
           $interest_per_quota = ($monto_original * $monthly_rate) / $total_cuotas;
           $calculated = $interest_per_quota * $pagos_realizados;
           log_message('debug', 'INTEREST_CALC: Método 1 - tasa: ' . $tasa_interes . '%, calculado: $' . $calculated);
           return round($calculated, 2);
       }

       // Método 2: Estimación basada en último pago (10% del pago es interés)
       if ($ultimo_pago_monto > 0) {
           $estimated_per_payment = $ultimo_pago_monto * 0.1; // 10% estimado
           $calculated = $estimated_per_payment * $pagos_realizados;
           log_message('debug', 'INTEREST_CALC: Método 2 - último pago: $' . $ultimo_pago_monto . ', calculado: $' . $calculated);
           return round($calculated, 2);
       }

       // Método 3: Estimación simple basada en monto original (2% mensual)
       if ($monto_original > 0 && $total_cuotas > 0) {
           $estimated_monthly_rate = 0.02; // 2% mensual estimado
           $interest_per_quota = ($monto_original * $estimated_monthly_rate) / $total_cuotas;
           $calculated = $interest_per_quota * $pagos_realizados;
           log_message('debug', 'INTEREST_CALC: Método 3 - estimación simple: $' . $calculated);
           return round($calculated, 2);
       }

       log_message('debug', 'INTEREST_CALC: No se pudieron calcular intereses para préstamo ' . $loan_id);
       return 0;
   }

}

/* End of file Reports_m.php */
/* Location: ./application/models/Reports_m.php */