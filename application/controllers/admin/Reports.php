<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('coins_m');
    $this->load->model('reports_m');
    $this->load->model('payments_m');
    $this->load->model('customers_m');
    $this->load->model('user_m');
    $this->load->library('form_validation');
    $this->load->library('session');
    $this->load->database();
    $this->load->driver('cache', array('adapter' => 'file'));
  }

  public function index()
  {
    // Obtener filtros de fecha
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    // Validar fechas
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;

    // Obtener datos con caché
    $cache_key = 'reports_data_' . md5($start_date . $end_date);
    $cached_data = $this->cache->get($cache_key);

    if ($cached_data === FALSE) {
      // Datos principales
      $data['coins'] = $this->coins_m->get();
      $data['per_customer_payments'] = $this->reports_m->get_payments_by_customer();
      $data['top_collectors'] = $this->reports_m->get_top_collectors();
      $data['longest_streak'] = $this->reports_m->get_customer_with_longest_paid_streak();

      // Reporte de comisiones por cobrador
      if ($this->db->table_exists('collector_commissions')) {
        $data['collector_commissions'] = $this->reports_m->get_commissions_by_user();
        $data['detailed_commissions'] = $this->reports_m->get_detailed_commissions_report();
      } else {
        $data['collector_commissions'] = [];
        $data['detailed_commissions'] = [];
      }

      // Reporte de castigos
      if ($this->db->table_exists('loans_penalties')) {
        $data['penalties'] = $this->reports_m->get_penalties_report();
      } else {
        $data['penalties'] = [];
      }

      // Totales de cobranza para tarjetas
      if ($this->db->table_exists('collector_commissions')) {
        $data['cobranza_totals'] = $this->reports_m->get_cobranza_totals();
        $data['cobradores_list'] = $this->reports_m->get_cobradores_list();
      } else {
        $data['cobranza_totals'] = null;
        $data['cobradores_list'] = [];
      }

      // Nuevos reportes avanzados
      $data['user_performance'] = $this->_get_user_performance_report($validated_dates);
      $data['collection_tracking'] = $this->_get_collection_tracking_report($validated_dates);
      $data['recommendations'] = $this->_generate_recommendations($data);

      // Cache por 5 minutos
      $this->cache->save($cache_key, $data, 300);
    } else {
      $data = array_merge($data, $cached_data);
    }

    $data['subview'] = 'admin/reports/index';
    $this->load->view('admin/_main_layout', $data);
  }

  public function ajax_getCredits($coin_id)
  {
    $data['credits'] = $this->reports_m->get_reportLoan($coin_id);

    echo json_encode($data);
  }

  public function dates()
  {
    $data['coins'] = $this->coins_m->get();
    $data['subview'] = 'admin/reports/dates';
    
    $this->load->view('admin/_main_layout', $data);
  }

  public function dates_pdf($coin_id, $start_d, $end_d)
  {
    require_once APPPATH.'third_party/fpdf183/html_table.php';

    $reportCoin = $this->reports_m->get_reportCoin($coin_id);

    $pdf = new PDF();
    $pdf->AddPage('P','A4',0);
    $pdf->SetFont('Arial','B',13);
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Reporte de prestamos por rango de fechas',0,1,'C');

    $pdf->Ln(8);
    
    $pdf->SetFont('Arial','',10);
    $html = '<table border="0">
    <tr>
    <td width="110" height="30"><b>Fecha inicial:</b></td><td width="400" height="30">'.$start_d.'</td><td width="110" height="30"><b>Tipo moneda:</b></td><td width="55" height="30">'.$reportCoin->name.'('.$reportCoin->short_name.')</td>
    </tr>
    <tr>
    <td width="110" height="30"><b>Fecha final:</b></td><td width="400" height="30">'.$end_d.'</td><td width="110" height="30"></td><td width="55" height="30"></td>
    </tr>
    </table>';

    $pdf->WriteHTML($html);

    // $reportsDates = $this->reports_m->get_reportDates(1,'2021-03-07','2021-05-13');
    // print_r($reportsDates);
    $reportsDates = $this->reports_m->get_reportDates($coin_id,$start_d,$end_d);

    $pdf->Ln(7);
    $pdf->SetFont('Arial','',10);
    $html1 = '';
    $html1 .= '<table border="1">
    <tr>
    <td width="80" height="30"><b>N'.utf8_decode("°").'Prest.</b></td><td width="100" height="30"><b>Fecha prest.</b></td><td width="120" height="30"><b>Monto prest.</b></td><td width="65" height="30"><b>Int. %</b></td><td width="65" height="30"><b>N'.utf8_decode("°").'cuot.</b></td><td width="90" height="30"><b>Modalidad</b></td><td width="100" height="30"><b>Total con Int.</b></td><td width="79" height="30"><b>Estado</b></td>
    </tr>';
    $sum_m = 0; $sum_mi = 0;
    foreach ($reportsDates as $rd) {
      $sum_m = $sum_m + $rd->credit_amount;
      $sum_mi = $sum_mi + $rd->total_int;
      $html1 .= '
    <tr>
    <td width="80" height="30">'.$rd->id.'</td><td width="100" height="30">'.$rd->date.'</td><td width="120" height="30">'.$rd->credit_amount.'</td><td width="65" height="30">'.$rd->interest_amount.'</td><td width="65" height="30">'.$rd->num_fee.'</td><td width="90" height="30">'.$rd->payment_m.'</td><td width="100" height="30">'.$rd->total_int.'</td><td width="79" height="30">'.($rd->status ? "Pendiente" : "Cancelado").'</td>
    </tr>';
    }

    $html1 .= '
    <tr>
    <td width="80" height="30"><b>Total</b></td><td width="100" height="30">-----</td><td width="120" height="30"><b>'.number_format($sum_m, 2).'</b></td><td width="65" height="30">-----</td><td width="65" height="30">-----</td><td width="90" height="30">-----</td><td width="100" height="30"><b>'.number_format($sum_mi, 2).'</b></td><td width="79" height="30">-----</td>
    </tr>';
    $html1 .= '</table>';

    $pdf->WriteHTML($html1);

    $pdf->Output('reporteFechas.pdf' , 'I');
  }

  public function customers()
  {
    $data['customers'] = $this->reports_m->get_customers_report();
    $data['subview'] = 'admin/reports/customers';
    $this->load->view('admin/_main_layout', $data);
  }

  public function customer_pdf($customer_id)
  {
    require_once APPPATH.'third_party/fpdf183/html_table.php';

    $reportCst = $this->reports_m->get_reportLC($customer_id);
    //print_r($reportCst[0]->customer_name);

    $pdf = new PDF();
    $pdf->AddPage('P','A4',0);
    $pdf->SetFont('Arial','B',13);
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Reporte de prestamos por cliente - '.$reportCst[0]->customer_name,0,1,'C');

    $pdf->Ln(8);
  
    $pdf->SetFont('Arial','',10);

    foreach ($reportCst as $rc) {

    // Calcular total pagado y saldo restante
    $loanItems = $this->reports_m->get_reportLI($rc->id);
    $total_pagado = 0;
    $saldo_restante = 0;
    foreach ($loanItems as $li) {
      if ($li->status == 0) { // Pagado
        $total_pagado += $li->fee_amount;
      } else {
        $saldo_restante += $li->balance;
      }
    }

    $html = '<table border="0">
    <tr>
    <td width="120" height="30"><b>Monto credito:</b></td><td width="400" height="30">'.number_format($rc->credit_amount, 0, ',', '.').'</td><td width="120" height="30"><b>Numero Credito:</b></td><td width="55" height="30">'.$rc->id.'</td>
    </tr>
    <tr>
    <td width="120" height="30"><b>Interes credito:</b></td><td width="400" height="30">'.$rc->interest_amount.' %</td><td width="120" height="30"><b>Forma pago:</b></td><td width="55" height="30">'.$rc->payment_m.'</td>
    </tr>
    <tr>
    <td width="120" height="30"><b>Nro cuotas:</b></td><td width="400" height="30">'.$rc->num_fee.'</td><td width="120" height="30"><b>Fecha credito:</b></td><td width="55" height="30">'.$rc->date.'</td>
    </tr>
    <tr>
    <td width="120" height="30"><b>Monto cuota:</b></td><td width="400" height="30">'.number_format($rc->fee_amount, 0, ',', '.').'</td><td width="120" height="30"><b>Estado credito:</b></td><td width="55" height="30">'.($rc->status ? "Pendiente" : "Cancelado").'</td>
    </tr>
    <tr>
    <td width="120" height="30"><b>Total pagado:</b></td><td width="400" height="30">'.number_format($total_pagado, 0, ',', '.').'</td><td width="120" height="30"><b>Saldo restante:</b></td><td width="55" height="30">'.number_format($saldo_restante, 0, ',', '.').'</td>
    </tr>
    <tr>
    <td width="120" height="30"><b>Tipo moneda:</b></td><td width="400" height="30">'.$rc->name.'('.$rc->short_name.')</td><td width="120" height="30"><b></b></td><td width="55" height="30"></td>
    </tr>
    </table>';

    $pdf->WriteHTML($html);

    $pdf->Ln(7);
    $pdf->SetFont('Arial','',10);

    $html1 = '';
    $html1 .= '<table border="1">
    <tr>
    <td width="120" height="30"><b>Nro Cuota</b></td><td width="120" height="30"><b>Fecha pago</b></td><td width="120" height="30"><b>Total pagar</b></td><td width="120" height="30"><b>Estado</b></td>
    </tr>';

    $loanItems = $this->reports_m->get_reportLI($rc->id);
    foreach ($loanItems as $li) {
      $html1 .= '
    <tr>
    <td width="120" height="30">'.$li->num_quota.'</td><td width="120" height="30">'.$li->date.'</td><td width="120" height="30">'.number_format($li->fee_amount, 0, ',', '.').'</td><td width="120" height="30">'.($li->status ? "Pendiente" : "Pagado").'</td>
    </tr>';
    }

    $html1 .= '</table>';

    $pdf->WriteHTML($html1);

    $pdf->Ln(7);

    }

    $pdf->Output('reporte_global_cliente.pdf', 'I');
  }

  /**
   * Validar filtros de fecha
   */
  private function _validate_date_filters($start_date, $end_date)
  {
    $validated_dates = null;
    if ($start_date && $end_date) {
      $this->form_validation->set_data(['start_date' => $start_date, 'end_date' => $end_date]);
      $this->form_validation->set_rules('start_date', 'Fecha de inicio', 'required|valid_date');
      $this->form_validation->set_rules('end_date', 'Fecha de fin', 'required|valid_date');

      if ($this->form_validation->run() == TRUE && strtotime($start_date) <= strtotime($end_date)) {
        $validated_dates = ['start' => $start_date, 'end' => $end_date];
        log_message('debug', 'Reports: Fechas validadas correctamente');
      } else {
        log_message('error', 'Reports: Fechas inválidas - start: ' . $start_date . ', end: ' . $end_date);
      }
    }
    return $validated_dates;
  }

  /**
   * Obtener reporte de rendimiento de usuarios
   */
  private function _get_user_performance_report($validated_dates = null)
  {
    log_message('debug', 'Reports: Generando reporte de rendimiento de usuarios');

    // Usuarios que registraron clientes
    $this->db->select("
      u.id as user_id,
      CONCAT(u.first_name, ' ', u.last_name) as user_name,
      COUNT(DISTINCT c.id) as clients_registered,
      COUNT(DISTINCT l.id) as loans_created,
      SUM(l.credit_amount) as total_loan_amount,
      AVG(l.credit_amount) as avg_loan_amount
    ");
    $this->db->from('users u');
    $this->db->join('customers c', 'c.user_id = u.id', 'left');
    $this->db->join('loans l', 'l.customer_id = c.id', 'left');

    if ($validated_dates) {
      $this->db->where('c.created_at >=', $validated_dates['start']);
      $this->db->where('c.created_at <=', $validated_dates['end']);
    }

    $this->db->group_by('u.id');
    $this->db->having('clients_registered >', 0);
    $this->db->order_by('clients_registered', 'DESC');

    $user_registration_data = $this->db->get()->result();

    // Cobranzas realizadas por usuario
    $this->db->select("
      u.id as user_id,
      CONCAT(u.first_name, ' ', u.last_name) as user_name,
      COUNT(li.id) as collections_count,
      SUM(li.fee_amount) as total_collected,
      AVG(li.fee_amount) as avg_collection_amount,
      SUM(li.fee_amount) * 0.4 as commission_40_percent
    ");
    $this->db->from('users u');
    $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
    $this->db->where('li.status', 0); // Pagado

    if ($validated_dates) {
      $this->db->where('li.pay_date >=', $validated_dates['start']);
      $this->db->where('li.pay_date <=', $validated_dates['end']);
    }

    $this->db->group_by('u.id');
    $this->db->having('collections_count >', 0);
    $this->db->order_by('collections_count', 'DESC');

    $user_collection_data = $this->db->get()->result();

    // Monto pendiente por cobrar por usuario
    $this->db->select("
      u.id as user_id,
      CONCAT(u.first_name, ' ', u.last_name) as user_name,
      COUNT(li.id) as pending_collections,
      SUM(li.balance) as total_pending_amount,
      AVG(li.balance) as avg_pending_amount
    ");
    $this->db->from('users u');
    $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
    $this->db->where('li.status', 1); // Pendiente
    $this->db->where('li.balance >', 0);

    if ($validated_dates) {
      $this->db->where('li.date >=', $validated_dates['start']);
      $this->db->where('li.date <=', $validated_dates['end']);
    }

    $this->db->group_by('u.id');
    $this->db->having('pending_collections >', 0);
    $this->db->order_by('total_pending_amount', 'DESC');

    $user_pending_data = $this->db->get()->result();

    return [
      'registrations' => $user_registration_data,
      'collections' => $user_collection_data,
      'pending' => $user_pending_data
    ];
  }

  /**
   * Obtener reporte de seguimiento de cobranzas
   */
  private function _get_collection_tracking_report($validated_dates = null)
  {
    log_message('debug', 'Reports: Generando reporte de seguimiento de cobranzas');

    // Seguimiento de cobranzas pendientes
    $this->db->select("
      c.id as customer_id,
      CONCAT(c.first_name, ' ', c.last_name) as customer_name,
      c.dni,
      l.id as loan_id,
      l.credit_amount,
      COUNT(li.id) as total_quotas,
      COUNT(CASE WHEN li.status = 0 THEN 1 END) as paid_quotas,
      COUNT(CASE WHEN li.status = 1 THEN 1 END) as pending_quotas,
      SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END) as total_paid,
      SUM(CASE WHEN li.status = 1 THEN li.balance ELSE 0 END) as total_pending,
      MAX(li.date) as last_payment_date,
      DATEDIFF(NOW(), MAX(li.date)) as days_since_last_payment,
      u.first_name as assigned_user
    ");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id', 'left');
    $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
    $this->db->join('users u', 'u.id = c.user_id', 'left');
    $this->db->where('l.status', 1); // Préstamo activo
    $this->db->where('li.status', 1); // Al menos una cuota pendiente

    if ($validated_dates) {
      $this->db->where('li.date >=', $validated_dates['start']);
      $this->db->where('li.date <=', $validated_dates['end']);
    }

    $this->db->group_by('c.id, l.id');
    $this->db->having('pending_quotas >', 0);
    $this->db->order_by('total_pending', 'DESC');

    $tracking_data = $this->db->get()->result();

    // Clasificar por nivel de riesgo
    foreach ($tracking_data as $item) {
      $days_overdue = $item->days_since_last_payment ?? 0;

      if ($days_overdue >= 60) {
        $item->risk_level = 'Alto';
        $item->risk_color = 'danger';
      } elseif ($days_overdue >= 30) {
        $item->risk_level = 'Medio';
        $item->risk_color = 'warning';
      } else {
        $item->risk_level = 'Bajo';
        $item->risk_color = 'success';
      }

      // Calcular porcentaje de progreso
      $total_quotas = $item->total_quotas ?? 1;
      $item->progress_percentage = round(($item->paid_quotas / $total_quotas) * 100, 1);
    }

    return $tracking_data;
  }

  /**
   * Generar recomendaciones basadas en los datos
   */
  private function _generate_recommendations($data)
  {
    $recommendations = [];

    // Analizar rendimiento de usuarios
    if (!empty($data['user_performance']['collections'])) {
      $top_performer = $data['user_performance']['collections'][0] ?? null;
      if ($top_performer && $top_performer->collections_count > 10) {
        $recommendations[] = [
          'type' => 'success',
          'title' => 'Usuario de Alto Rendimiento Detectado',
          'message' => "El usuario {$top_performer->user_name} ha realizado {$top_performer->collections_count} cobranzas. Considere asignarle más clientes.",
          'action' => 'assign_more_clients',
          'user_id' => $top_performer->user_id
        ];
      }
    }

    // Analizar clientes de alto riesgo
    if (!empty($data['collection_tracking'])) {
      $high_risk_count = 0;
      foreach ($data['collection_tracking'] as $tracking) {
        if (($tracking->days_since_last_payment ?? 0) >= 60) {
          $high_risk_count++;
        }
      }

      if ($high_risk_count > 0) {
        $recommendations[] = [
          'type' => 'warning',
          'title' => 'Clientes de Alto Riesgo',
          'message' => "Se encontraron {$high_risk_count} clientes con mora superior a 60 días. Considere aplicar penalizaciones automáticas.",
          'action' => 'apply_penalties',
          'count' => $high_risk_count
        ];
      }
    }

    // Analizar distribución de carga de trabajo
    $total_users = count($data['user_performance']['collections'] ?? []);
    if ($total_users > 1) {
      $avg_collections = array_sum(array_column($data['user_performance']['collections'], 'collections_count')) / $total_users;
      $low_performers = array_filter($data['user_performance']['collections'], function($user) use ($avg_collections) {
        return $user->collections_count < ($avg_collections * 0.5);
      });

      if (!empty($low_performers)) {
        $recommendations[] = [
          'type' => 'info',
          'title' => 'Rebalanceo de Carga de Trabajo',
          'message' => count($low_performers) . " usuarios tienen rendimiento por debajo del promedio. Considere redistribuir clientes.",
          'action' => 'rebalance_workload',
          'users' => array_column($low_performers, 'user_id')
        ];
      }
    }

    return $recommendations;
  }

  /**
   * Calcular y mostrar el 40% de intereses por cobrador
   */
  public function interest_commissions()
  {
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    // Validar fechas
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;
    $data['collector_id'] = $collector_id;

    // Obtener datos de comisiones de intereses
    $data['interest_commissions'] = $this->_get_interest_commissions($validated_dates, $collector_id);
    $data['total_interest_commissions'] = $this->_calculate_total_interest_commissions($data['interest_commissions']);
    $data['cobradores_list'] = $this->reports_m->get_cobradores_list();

    log_message('debug', 'Reports: interest_commissions - collector_id: ' . ($collector_id ?: 'null') . ', cobradores_list count: ' . count($data['cobradores_list']) . ', interest_commissions count: ' . count($data['interest_commissions']));

    // Debug: mostrar nombres de cobradores disponibles
    if (!empty($data['cobradores_list'])) {
      $cobradores_names = array_column($data['cobradores_list'], 'nombre');
      log_message('debug', 'Reports: cobradores disponibles: ' . implode(', ', $cobradores_names));
    } else {
      log_message('debug', 'Reports: NO hay cobradores disponibles en la lista');
    }

    // Debug adicional: verificar qué usuarios tienen pagos (corregido)
    $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) as nombre, COUNT(li.id) as total_pagos');
    $this->db->from('users u');
    $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
    $this->db->where('li.status', 0);
    $this->db->where('li.paid_by IS NOT NULL', null, false);
    $this->db->group_by('u.id');
    $this->db->order_by('total_pagos', 'DESC');
    $all_users_with_payments = $this->db->get()->result();

    log_message('debug', 'Reports: TODOS los usuarios con pagos realizados: ' . count($all_users_with_payments));
    if (!empty($all_users_with_payments)) {
      foreach ($all_users_with_payments as $user) {
        log_message('debug', 'Reports: Usuario ' . $user->nombre . ' (ID: ' . $user->id . ') - Pagos: ' . $user->total_pagos);
      }
    }

    // Verificar si hay usuarios con intereses pagados
    $this->db->select('u.id, CONCAT(u.first_name, " ", u.last_name) as nombre, COUNT(li.id) as pagos_con_intereses, SUM(li.interest_paid) as total_intereses');
    $this->db->from('users u');
    $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
    $this->db->where('li.status', 0);
    $this->db->where('li.interest_paid >', 0);
    $this->db->where('li.paid_by IS NOT NULL', null, false);
    $this->db->group_by('u.id');
    $this->db->order_by('total_intereses', 'DESC');
    $users_with_interests = $this->db->get()->result();

    log_message('debug', 'Reports: Usuarios con intereses pagados: ' . count($users_with_interests));
    if (!empty($users_with_interests)) {
      foreach ($users_with_interests as $user) {
        log_message('debug', 'Reports: Usuario con intereses ' . $user->nombre . ' (ID: ' . $user->id . ') - Pagos con intereses: ' . $user->pagos_con_intereses . ' - Total intereses: ' . $user->total_intereses);
      }
    }

    $data['subview'] = 'admin/reports/interest_commissions';
    $this->load->view('admin/_main_layout', $data);
  }

  /**
   * Obtener comisiones del 40% de intereses por cobrador
   */
  private function _get_interest_commissions($validated_dates = null, $collector_id = null)
  {
    log_message('debug', 'Reports: Calculando comisiones del 40% de intereses');

    // CAMBIO PRINCIPAL: Mostrar TODOS los cobradores que han realizado pagos,
    // no solo aquellos con intereses pagados
    $this->db->select("
      u.id as user_id,
      CONCAT(u.first_name, ' ', u.last_name) as user_name,
      COUNT(DISTINCT li.id) as total_payments,
      COALESCE(SUM(li.interest_paid), 0) as total_interest_paid,
      COALESCE(SUM(li.interest_paid), 0) * 0.4 as interest_commission_40,
      SUM(li.fee_amount) as total_amount_collected,
      COUNT(DISTINCT l.customer_id) as customers_handled,
      COUNT(DISTINCT l.id) as loans_handled
    ");
    $this->db->from('users u');
    $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->where('li.status', 0); // Pagado
    $this->db->where('li.paid_by IS NOT NULL', null, false); // Asegurar que paid_by no sea null

    // REMOVER esta condición que filtra solo por intereses > 0
    // $this->db->where('li.interest_paid >', 0); // REMOVIDO

    if ($validated_dates) {
      $this->db->where('li.pay_date >=', $validated_dates['start']);
      $this->db->where('li.pay_date <=', $validated_dates['end']);
    }

    if ($collector_id) {
      $this->db->where('u.id', $collector_id);
    }

    $this->db->group_by('u.id');
    $this->db->having('total_payments >', 0); // Cambiar a total_payments > 0 en lugar de intereses
    $this->db->order_by('total_payments', 'DESC'); // Ordenar por pagos realizados

    $result = $this->db->get()->result();
    log_message('debug', 'Reports: Encontrados ' . count($result) . ' cobradores con pagos realizados');

    // Agregar información detallada por cliente para TODOS los cobradores
    // Esto asegura que todos los cobradores aparezcan en la lista
    foreach ($result as $user) {
      $user->client_details = $this->_get_user_client_interest_details($user->user_id, $validated_dates);
      log_message('debug', 'Reports: Cobrador ' . $user->user_name . ' tiene ' . count($user->client_details) . ' clientes');
    }

    return $result;
  }

  /**
   * Obtener detalles de intereses por cliente para un usuario
   */
  private function _get_user_client_interest_details($user_id, $validated_dates = null)
  {
    $this->db->select("
      c.id as customer_id,
      CONCAT(c.first_name, ' ', c.last_name) as customer_name,
      c.dni,
      l.id as loan_id,
      l.credit_amount,
      COUNT(li.id) as payments_made,
      SUM(li.interest_paid) as total_interest_paid,
      SUM(li.interest_paid) * 0.4 as interest_commission_40,
      SUM(li.fee_amount) as total_collected,
      MAX(li.pay_date) as last_payment_date
    ");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id', 'left');
    $this->db->join('loan_items li', 'li.loan_id = l.id AND li.paid_by = ' . $user_id, 'left');
    $this->db->where('li.status', 0); // Pagado
    $this->db->where('li.interest_paid >', 0);

    if ($validated_dates) {
      $this->db->where('li.pay_date >=', $validated_dates['start']);
      $this->db->where('li.pay_date <=', $validated_dates['end']);
    }

    $this->db->group_by('c.id, l.id');
    $this->db->having('total_interest_paid >', 0);
    $this->db->order_by('total_interest_paid', 'DESC');

    return $this->db->get()->result();
  }

  /**
   * Calcular totales de comisiones de intereses
   */
  private function _calculate_total_interest_commissions($commissions)
  {
    $totals = [
      'total_interest_paid' => 0,
      'total_commission_40' => 0,
      'total_amount_collected' => 0,
      'total_customers' => 0,
      'total_loans' => 0,
      'total_payments' => 0
    ];

    foreach ($commissions as $commission) {
      $totals['total_interest_paid'] += $commission->total_interest_paid ?? 0;
      $totals['total_commission_40'] += $commission->interest_commission_40 ?? 0;
      $totals['total_amount_collected'] += $commission->total_amount_collected ?? 0;
      $totals['total_customers'] += $commission->customers_handled ?? 0;
      $totals['total_loans'] += $commission->loans_handled ?? 0;
      $totals['total_payments'] += $commission->total_payments ?? 0;
    }

    return $totals;
  }

  /**
   * Exportar comisiones de intereses a Excel
   */
  public function export_interest_commissions_excel()
  {
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $validated_dates = $this->_validate_date_filters($start_date, $end_date);
    $commissions = $this->_get_interest_commissions($validated_dates, $collector_id);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=comisiones_intereses_40_{$start_date}_{$end_date}.xls");

    echo "<table border='1'>
          <tr><th>Cobrador</th><th>Pagos Realizados</th><th>Interés Total Pagado</th><th>Comisión 40%</th><th>Monto Total Cobrado</th><th>Clientes Atendidos</th><th>Préstamos Gestionados</th></tr>";

    foreach ($commissions as $commission) {
      echo "<tr>
            <td>{$commission->user_name}</td>
            <td>" . number_format($commission->total_payments, 0, ',', '.') . "</td>
            <td>$" . number_format($commission->total_interest_paid, 2, ',', '.') . "</td>
            <td>$" . number_format($commission->interest_commission_40, 2, ',', '.') . "</td>
            <td>$" . number_format($commission->total_amount_collected, 2, ',', '.') . "</td>
            <td>" . number_format($commission->customers_handled, 0, ',', '.') . "</td>
            <td>" . number_format($commission->loans_handled, 0, ',', '.') . "</td>
            </tr>";
    }

    echo "</table>";
  }

  /**
   * Aplicar penalizaciones automáticas a clientes de alto riesgo
   */
  public function apply_bulk_penalties()
  {
    $user_id = $this->input->post('user_id');
    $risk_level = $this->input->post('risk_level') ?: 'Alto'; // Alto por defecto

    if (!$user_id) {
      echo json_encode(['success' => false, 'error' => 'Usuario requerido']);
      return;
    }

    try {
      // Obtener clientes de alto riesgo asignados al usuario
      $this->db->select('c.id as customer_id, c.first_name, c.last_name, l.id as loan_id');
      $this->db->from('customers c');
      $this->db->join('loans l', 'l.customer_id = c.id', 'left');
      $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
      $this->db->where('c.user_id', $user_id);
      $this->db->where('l.status', 1); // Préstamo activo
      $this->db->where('li.status', 1); // Cuotas pendientes
      $this->db->where('DATEDIFF(NOW(), li.date) >=', $risk_level === 'Alto' ? 60 : 30);
      $this->db->group_by('c.id, l.id');

      $high_risk_clients = $this->db->get()->result();

      $applied_count = 0;
      foreach ($high_risk_clients as $client) {
        $result = $this->payments_m->apply_penalty_to_customer($client->customer_id);
        if ($result) {
          $applied_count++;
          log_message('info', "Penalización aplicada automáticamente a cliente {$client->customer_id} por usuario {$user_id}");
        }
      }

      echo json_encode([
        'success' => true,
        'message' => "Se aplicaron {$applied_count} penalizaciones automáticas",
        'applied_count' => $applied_count
      ]);

    } catch (Exception $e) {
      log_message('error', 'Error aplicando penalizaciones masivas: ' . $e->getMessage());
      echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
    }
  }

  /**
   * Reasignar clientes de usuarios de bajo rendimiento
   */
  public function reassign_clients()
  {
    $from_user_id = $this->input->post('from_user_id');
    $to_user_id = $this->input->post('to_user_id');
    $client_count = $this->input->post('client_count') ?: 5;

    if (!$from_user_id || !$to_user_id) {
      echo json_encode(['success' => false, 'error' => 'Usuarios requeridos']);
      return;
    }

    try {
      // Obtener clientes del usuario origen con menos actividad
      $this->db->select('c.id');
      $this->db->from('customers c');
      $this->db->join('loan_items li', 'li.loan_id IN (SELECT id FROM loans WHERE customer_id = c.id)', 'left');
      $this->db->where('c.user_id', $from_user_id);
      $this->db->where('li.status', 1); // Solo clientes con cuotas pendientes
      $this->db->group_by('c.id');
      $this->db->order_by('COUNT(li.id)', 'ASC'); // Menos actividad primero
      $this->db->limit($client_count);

      $clients_to_reassign = $this->db->get()->result();

      $reassigned_count = 0;
      foreach ($clients_to_reassign as $client) {
        $this->db->where('id', $client->id);
        $this->db->update('customers', ['user_id' => $to_user_id]);
        $reassigned_count++;
      }

      echo json_encode([
        'success' => true,
        'message' => "Se reasignaron {$reassigned_count} clientes",
        'reassigned_count' => $reassigned_count
      ]);

    } catch (Exception $e) {
      log_message('error', 'Error reasignando clientes: ' . $e->getMessage());
      echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
    }
  }

  /**
   * API para obtener estadísticas de comisiones en JSON
   */
  public function get_commission_stats()
  {
    if ($this->db->table_exists('collector_commissions')) {
      $stats = $this->reports_m->get_commission_stats();
      $totals = $this->reports_m->get_commission_totals();
      echo json_encode([
        'stats' => $stats,
        'totals' => $totals
      ]);
    } else {
      echo json_encode(['stats' => [], 'totals' => null]);
    }
  }

  /**
   * Exportar comisiones a Excel
   */
  public function export_commissions_excel()
  {
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $data = $this->reports_m->get_commission_stats_filtered($start_date, $end_date, $collector_id);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=comisiones_{$start_date}_{$end_date}.xls");

    echo "<table border='1'>
          <tr><th>Cliente</th><th>Cédula</th><th>Cobrador</th><th>Total Pagado</th><th>Interés</th><th>Comisión (40%)</th><th>Fecha</th></tr>";
    foreach ($data as $row) {
        echo "<tr>
              <td>{$row->client_name}</td>
              <td>{$row->client_cedula}</td>
              <td>{$row->user_name}</td>
              <td>" . number_format($row->total_paid, 2, ',', '.') . "</td>
              <td>" . number_format($row->interest_amount, 2, ',', '.') . "</td>
              <td>" . number_format($row->commission, 2, ',', '.') . "</td>
              <td>{$row->created_at}</td>
              </tr>";
    }
    echo "</table>";
  }

  /**
   * Exportar comisiones a PDF
   */
  public function export_commissions_pdf()
  {
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $data['commissions'] = $this->reports_m->get_commission_stats_filtered($start_date, $end_date, $collector_id);
    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;

    $this->load->library('pdf');
    $this->pdf->setPaper('A4', 'landscape');
    $this->pdf->load_view('admin/reports/commissions_pdf', $data);
    $this->pdf->render();
    $this->pdf->stream("comisiones_{$start_date}_{$end_date}.pdf", array("Attachment" => 1));
  }

  /**
   * API para obtener datos de gráficos
   */
  public function get_chart_data()
  {
    log_message('debug', 'Reports::get_chart_data called with type: ' . $this->input->get('type') . ', user_id: ' . $this->input->get('user_id'));

    $type = $this->input->get('type');
    $user_id = $this->input->get('user_id');

    try {
      switch ($type) {
        case 'payments_by_customer':
          log_message('debug', 'Reports::get_chart_data - calling get_payments_by_customer_chart_filtered with user_id: ' . ($user_id ?: 'null'));
          $data = $this->reports_m->get_payments_by_customer_chart_filtered($user_id);
          log_message('debug', 'Reports::get_chart_data - payments_by_customer data type: ' . gettype($data) . ', is_array: ' . (is_array($data) ? 'true' : 'false'));
          log_message('debug', 'Reports::get_chart_data - payments_by_customer data: ' . json_encode($data));
          break;
        case 'top_collectors':
          $data = $this->reports_m->get_top_collectors_chart_filtered($user_id);
          log_message('debug', 'Reports::get_chart_data - top_collectors data: ' . json_encode($data));
          break;
        case 'penalties':
          $data = $this->reports_m->get_penalties_chart_data();
          log_message('debug', 'Reports::get_chart_data - penalties data: ' . json_encode($data));
          break;
        case 'streaks':
          $data = $this->reports_m->get_streak_chart_data();
          log_message('debug', 'Reports::get_chart_data - streaks data: ' . json_encode($data));
          break;
        default:
          $data = [];
          log_message('debug', 'Reports::get_chart_data - unknown type, returning empty array');
      }

      // Asegurar que siempre retornamos un array, nunca null
      if ($data === null) {
        log_message('error', 'Reports::get_chart_data - method returned null for type: ' . $type . ', user_id: ' . ($user_id ?: 'null'));
        $data = [];
      }

      // Validar que sea un array
      if (!is_array($data)) {
        log_message('error', 'Reports::get_chart_data - data is not an array for type: ' . $type . ', data type: ' . gettype($data));
        $data = [];
      }

      // Set proper headers
      $this->output->set_content_type('application/json');
      $this->output->set_header('Access-Control-Allow-Origin: *');

      echo json_encode($data);
      log_message('debug', 'Reports::get_chart_data - response sent successfully for type: ' . $type);

    } catch (Exception $e) {
      log_message('error', 'Reports::get_chart_data - Exception: ' . $e->getMessage());
      $this->output->set_content_type('application/json');
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  /**
    * API para obtener información detallada de cobranzas por usuario
    */
   public function get_user_collection_details()
   {
     $user_id = $this->input->get('user_id');

     if (!$user_id) {
       echo json_encode(['error' => 'User ID is required']);
       return;
     }

     try {
       $collections = $this->reports_m->get_collections_by_user($user_id);
       $progress = $this->reports_m->get_user_collection_progress($user_id);

       $this->output->set_content_type('application/json');
       echo json_encode([
         'collections' => $collections,
         'progress' => $progress
       ]);

     } catch (Exception $e) {
       log_message('error', 'Reports::get_user_collection_details - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       echo json_encode(['error' => 'Internal server error']);
     }
   }

   /**
    * API para obtener detalles de intereses por cobrador
    */
   public function get_user_interest_details()
   {
     $user_id = $this->input->get('user_id');
     $start_date = $this->input->get('start_date');
     $end_date = $this->input->get('end_date');

     if (!$user_id) {
       echo json_encode(['error' => 'ID de cobrador requerido']);
       return;
     }

     try {
       // Validar fechas
       $validated_dates = $this->_validate_date_filters($start_date, $end_date);

       // Obtener detalles de intereses por cliente
       $clients = $this->_get_user_client_interest_details($user_id, $validated_dates);

       // Calcular totales
       $total_interest = 0;
       $total_commission = 0;

       foreach ($clients as $client) {
         $total_interest += $client->total_interest_paid ?? 0;
         $total_commission += $client->interest_commission_40 ?? 0;
       }

       $this->output->set_content_type('application/json');
       echo json_encode([
         'clients' => $clients,
         'total_interest' => $total_interest,
         'total_commission' => $total_commission
       ]);

     } catch (Exception $e) {
       log_message('error', 'Reports::get_user_interest_details - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       echo json_encode(['error' => 'Error interno del servidor']);
     }
   }

}

/* End of file Reports.php */
/* Location: ./application/controllers/admin/Reports.php */