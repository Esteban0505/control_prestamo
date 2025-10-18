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
    // Restricción deshabilitada temporalmente - Acceso público habilitado
    // if (!in_array($this->session->userdata('role'), ['admin', 'cobrador'])) {
    //     show_error('Acceso denegado. Esta sección requiere permisos de cobrador o administrador.');
    // }
  }

  public function index()
  {
    // Acceso público habilitado - Sin restricciones de rol

    // Obtener filtros de fecha
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    // Validar fechas
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;

    // Obtener datos de comisiones para el cobrador actual (si está logueado)
    $current_user_id = $this->session->userdata('user_id');
    $data['interest_commissions'] = $this->_get_interest_commissions($validated_dates, $current_user_id);
    $data['total_interest_commissions'] = $this->_calculate_total_interest_commissions($data['interest_commissions']);

    // Obtener lista de cobradores para mostrar resumen general
    $data['cobradores_list'] = $this->reports_m->get_cobradores_list();

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
    // Acceso público habilitado - Sin restricciones de rol

    // Obtener filtros
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    // Validar fechas
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;
    $data['collector_id'] = $collector_id;

    // Obtener resumen de cobradores con estado de envío de comisiones
    $data['collector_commissions_summary'] = $this->_get_collector_commissions_summary($validated_dates, $collector_id);
    $data['cobradores_list'] = $this->reports_m->get_cobradores_list();

    $data['subview'] = 'admin/reports/dates';
    $this->load->view('admin/_main_layout', $data);
  }

  public function dates_pdf($coin_id, $start_d, $end_d)
  {
    require_once APPPATH.'third_party/fpdf183/pdf_apa.php';

    $reportCoin = $this->reports_m->get_reportCoin($coin_id);

    // Obtener nombre del usuario logueado
    $current_user = $this->user_m->get_current_user();
    $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

    // Crear instancia PDF con formato APA
    $pdf = new PDF_APA('P', 'mm', 'A4');
    $pdf->setTitle('Reporte de Préstamos por Rango de Fechas - Sistema de Préstamos');
    $pdf->setAuthor($user_name);

    // Agregar referencias APA si es necesario
    $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Reporte de préstamos por fechas generada automáticamente.');
    $pdf->addReference('Normas APA. (2023). Manual de publicaciones de la American Psychological Association (7ª ed.).');

    // Crear portada
    $pdf->createCoverPage();

    // Agregar logo en la primera página de contenido
    $logoPath = FCPATH . 'assets/img/log.png';
    if(file_exists($logoPath)) {
        $pdf->Image($logoPath, $pdf->getMarginLeft(), $pdf->GetY(), 30);
        $pdf->Ln(35);
    }

    // Crear secciones con formato APA
    $pdf->createSection('Información del Reporte', 1);

    // Información del reporte
    $report_info = [
        ['Fecha Inicial:', $pdf->formatDate($start_d)],
        ['Fecha Final:', $pdf->formatDate($end_d)],
        ['Tipo de Moneda:', $reportCoin->name . ' (' . $reportCoin->short_name . ')']
    ];

    foreach ($report_info as $info) {
        $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
    }

    $pdf->Ln(5);

    // Obtener datos del reporte
    $reportsDates = $this->reports_m->get_reportDates($coin_id, $start_d, $end_d);

    // Crear sección de tabla
    $pdf->createSection('Detalle de Préstamos', 1);

    // Preparar datos de tabla
    $headers = ['N° Prést.', 'Fecha Prést.', 'Monto Prést.', 'Int. %', 'N° Cuot.', 'Modalidad', 'Total con Int.', 'Estado'];
    $table_data = [];
    $sum_m = 0;
    $sum_mi = 0;

    foreach ($reportsDates as $rd) {
        $sum_m += $rd->credit_amount;
        $sum_mi += $rd->total_int;
        $table_data[] = [
            $rd->id,
            $pdf->formatDate($rd->date),
            $pdf->formatCurrency($rd->credit_amount),
            number_format($rd->interest_amount, 2, ',', '.') . '%',
            $rd->num_fee,
            ucfirst($rd->payment_m),
            $pdf->formatCurrency($rd->total_int),
            ($rd->status ? "Pendiente" : "Cancelado")
        ];
    }

    // Agregar fila de totales
    $table_data[] = [
        'Total',
        '-----',
        $pdf->formatCurrency($sum_m),
        '-----',
        '-----',
        '-----',
        $pdf->formatCurrency($sum_mi),
        '-----'
    ];

    // Definir anchos de columna
    $widths = [25, 25, 30, 20, 20, 25, 30, 25];

    // Crear tabla con formato APA
    $pdf->createTable($headers, $table_data, $widths);

    // Agregar sección de referencias si hay referencias
    $pdf->createReferencesPage();

    // Generar y enviar el PDF
    $pdf_content = $pdf->Output('', 'S');

    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_prestamos_fechas_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_content;
    exit;
  }

  public function customers()
  {
    $data['customers'] = $this->reports_m->get_customers_report();
    $data['subview'] = 'admin/reports/customers';
    $this->load->view('admin/_main_layout', $data);
  }

  public function customer_pdf($customer_id)
  {
    require_once APPPATH.'third_party/fpdf183/pdf_apa.php';

    $reportCst = $this->reports_m->get_reportLC($customer_id);

    // Obtener nombre del usuario logueado
    $current_user = $this->user_m->get_current_user();
    $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

    // Obtener información detallada del cliente
    $customer_info = $this->customers_m->get($customer_id);

    // Crear instancia PDF con formato APA
    $pdf = new PDF_APA('P', 'mm', 'A4');
    $pdf->setTitle('Estado de Cuenta - ' . ($customer_info->first_name ?? 'Cliente') . ' ' . ($customer_info->last_name ?? '') . ' - Sistema de Préstamos');
    $pdf->setAuthor($user_name);

    // Agregar referencias APA si es necesario
    $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Estado de cuenta generado automáticamente.');
    $pdf->addReference('Normas APA. (2023). Manual de publicaciones de la American Psychological Association (7ª ed.).');

    // Crear portada mejorada con información del cliente
    $this->_create_customer_cover_page($pdf, $customer_info, $reportCst);

    // Agregar resumen ejecutivo después de la portada
    $this->_create_executive_summary($pdf, $customer_info, $reportCst);

    // Agregar logo en la primera página de contenido
    $logoPath = FCPATH . 'assets/img/log.png';
    if(file_exists($logoPath)) {
        $pdf->Image($logoPath, $pdf->getMarginLeft(), $pdf->GetY(), 30);
        $pdf->Ln(35);
    }

    // Procesar cada préstamo con mejor formato
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

        // Crear sección para cada préstamo con mejor formato visual
        $this->_create_loan_section($pdf, $rc, $total_pagado, $saldo_restante, $loanItems);

        // Crear sección de tabla de cuotas
        $pdf->createSection('Detalle de Cuotas', 2);

        // Preparar datos de tabla
        $headers = ['Nro Cuota', 'Fecha de Pago', 'Total a Pagar', 'Estado'];
        $table_data = [];

        foreach ($loanItems as $li) {
            $table_data[] = [
                $li->num_quota,
                $pdf->formatDate($li->date),
                $pdf->formatCurrency($li->fee_amount),
                ($li->status ? "Pendiente" : "Pagado")
            ];
        }

        // Definir anchos de columna
        $widths = [30, 40, 50, 40];

        // Crear tabla con formato APA
        $pdf->createTable($headers, $table_data, $widths);

        $pdf->Ln(10);
    }

    // Agregar sección de referencias si hay referencias
    $pdf->createReferencesPage();

    // Generar y enviar el PDF
    $pdf_content = $pdf->Output('', 'S');

    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_prestamos_cliente_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_content;
    exit;
  }

  /**
   * Generar PDF de detalles de préstamo específico
   */
  public function loan_pdf($loan_id)
  {
    require_once APPPATH.'third_party/fpdf183/pdf_apa.php';

    // Obtener datos del préstamo
    $this->db->select('l.*, co.name as coin_name, co.short_name as coin_short,
                      CONCAT(c.first_name, " ", c.last_name) AS customer_name, c.dni,
                      CONCAT(u.first_name, " ", u.last_name) as asesor_name');
    $this->db->from('loans l');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->join('users u', 'u.id = l.assigned_user_id', 'left');
    $this->db->where('l.id', $loan_id);
    $loan = $this->db->get()->row();

    if (!$loan) {
      show_error('Préstamo no encontrado', 404);
      return;
    }

    // Obtener nombre del usuario logueado
    $current_user = $this->user_m->get_current_user();
    $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

    // Crear instancia PDF con formato APA
    $pdf = new PDF_APA('P', 'mm', 'A4');
    $pdf->setTitle('Detalle de Préstamo - ID: ' . $loan_id . ' - Sistema de Préstamos');
    $pdf->setAuthor($user_name);

    // Agregar referencias APA si es necesario
    $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Reporte de préstamo individual generada automáticamente.');
    $pdf->addReference('Normas APA. (2023). Manual de publicaciones de la American Psychological Association (7ª ed.).');

    // Crear portada
    $pdf->createCoverPage();

    // Agregar logo en la primera página de contenido
    $logoPath = FCPATH . 'assets/img/log.png';
    if(file_exists($logoPath)) {
        $pdf->Image($logoPath, $pdf->getMarginLeft(), $pdf->GetY(), 30);
        $pdf->Ln(35);
    }

    // Crear sección de información del préstamo
    $pdf->createSection('Información del Préstamo', 1);

    // Información del préstamo
    $loan_info = [
        ['ID del Préstamo:', $loan->id],
        ['Cliente:', $loan->customer_name],
        ['Cédula:', $loan->dni],
        ['Asesor:', $loan->asesor_name ?: 'No asignado'],
        ['Monto del Crédito:', $pdf->formatCurrency($loan->credit_amount)],
        ['Interés del Crédito:', number_format($loan->interest_amount, 2, ',', '.') . '%'],
        ['Forma de Pago:', ucfirst($loan->payment_m)],
        ['Número de Cuotas:', $loan->num_fee],
        ['Fecha del Crédito:', $pdf->formatDate($loan->date)],
        ['Monto de Cuota:', $pdf->formatCurrency($loan->fee_amount)],
        ['Estado del Crédito:', ($loan->status ? "Pendiente" : "Cancelado")],
        ['Tipo de Moneda:', $loan->coin_name . ' (' . $loan->coin_short . ')']
    ];

    foreach ($loan_info as $info) {
        $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
    }

    $pdf->Ln(5);

    // Obtener cuotas del préstamo
    $loanItems = $this->reports_m->get_reportLI($loan_id);

    // Calcular totales
    $total_pagado = 0;
    $saldo_restante = 0;
    foreach ($loanItems as $li) {
        if ($li->status == 0) { // Pagado
            $total_pagado += $li->fee_amount;
        } else {
            $saldo_restante += $li->balance;
        }
    }

    // Información de resumen de pagos
    $summary_info = [
        ['Total Pagado:', $pdf->formatCurrency($total_pagado)],
        ['Saldo Restante:', $pdf->formatCurrency($saldo_restante)],
        ['Progreso:', count($loanItems) > 0 ? round(($total_pagado / ($total_pagado + $saldo_restante)) * 100, 1) . '%' : '0%']
    ];

    foreach ($summary_info as $info) {
        $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
    }

    $pdf->Ln(5);

    // Crear sección de tabla de cuotas
    $pdf->createSection('Detalle de Cuotas', 2);

    // Preparar datos de tabla
    $headers = ['Nro Cuota', 'Fecha de Pago', 'Total a Pagar', 'Estado', 'Fecha de Pago Real'];
    $table_data = [];

    foreach ($loanItems as $li) {
        $estado = ($li->status == 0) ? "Pagado" : "Pendiente";
        $fecha_pago_real = ($li->status == 0 && $li->pay_date) ? $pdf->formatDate($li->pay_date) : "N/A";

        $table_data[] = [
            $li->num_quota,
            $pdf->formatDate($li->date),
            $pdf->formatCurrency($li->fee_amount),
            $estado,
            $fecha_pago_real
        ];
    }

    // Definir anchos de columna
    $widths = [25, 35, 35, 30, 35];

    // Crear tabla con formato APA
    $pdf->createTable($headers, $table_data, $widths);

    // Agregar sección de referencias si hay referencias
    $pdf->createReferencesPage();

    // Generar y enviar el PDF
    $pdf_content = $pdf->Output('', 'S');

    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="detalle_prestamo_' . $loan_id . '_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_content;
    exit;
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

    // Agregar información de teléfono secundario a los resultados principales
    foreach ($result as $user) {
      if (!empty($user->client_details)) {
        foreach ($user->client_details as $client) {
          // Agregar phone_fixed si está disponible
          $client->phone_fixed = $this->_get_client_phone_fixed($client->customer_id);
        }
      }
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
      c.phone_fixed,
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
   * Obtener teléfono fijo de un cliente
   */
  private function _get_client_phone_fixed($customer_id)
  {
    $this->db->select('phone_fixed');
    $this->db->from('customers');
    $this->db->where('id', $customer_id);
    $result = $this->db->get()->row();
    return $result ? $result->phone_fixed : null;
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
   * Exportar comisiones a Excel para cobradores
   */
  public function export_collector_commissions_excel()
  {
    // Acceso público habilitado - Sin restricciones de rol

    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $current_user_id = $this->session->userdata('user_id');

    $validated_dates = $this->_validate_date_filters($start_date, $end_date);
    $commissions = $this->_get_interest_commissions($validated_dates, $current_user_id);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=comisiones_cobrador_{$start_date}_{$end_date}.xls");

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
   * Exportar comisiones a Excel para administradores
   */
  public function export_admin_commissions_excel()
  {
    // Acceso público habilitado - Sin restricciones de rol

    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $validated_dates = $this->_validate_date_filters($start_date, $end_date);
    $commissions = $this->_get_interest_commissions($validated_dates, $collector_id);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=comisiones_admin_{$start_date}_{$end_date}.xls");

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
   * Exportar comisiones a PDF para administradores
   */
  public function export_admin_commissions_pdf()
  {
    // Acceso público habilitado - Sin restricciones de rol

    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $validated_dates = $this->_validate_date_filters($start_date, $end_date);
    $commissions = $this->_get_interest_commissions($validated_dates, $collector_id);

    // Obtener nombre del usuario logueado
    $current_user = $this->user_m->get_current_user();
    $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

    // Crear instancia PDF con formato APA
    require_once APPPATH.'third_party/fpdf183/pdf_apa.php';
    $pdf = new PDF_APA('L', 'mm', 'A4'); // Landscape para mejor visualización de tablas
    $pdf->setTitle('Reporte Administrativo de Comisiones - Sistema de Préstamos');
    $pdf->setAuthor($user_name);

    // Agregar referencias APA si es necesario
    $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Reporte administrativo de comisiones generada automáticamente.');
    $pdf->addReference('Normas APA. (2023). Manual de publicaciones de la American Psychological Association (7ª ed.).');

    // Crear portada
    $pdf->createCoverPage();

    // Agregar logo en la primera página de contenido
    $logoPath = FCPATH . 'assets/img/log.png';
    if(file_exists($logoPath)) {
        $pdf->Image($logoPath, $pdf->getMarginLeft(), $pdf->GetY(), 30);
        $pdf->Ln(35);
    } else {
        // Si no existe el logo, continuar sin error
        $pdf->Ln(10);
    }

    // Crear secciones con formato APA
    $pdf->createSection('Información del Reporte', 1);

    // Información del reporte
    $report_info = [
        ['Fecha Inicio:', $start_date ?: 'Sin límite'],
        ['Fecha Fin:', $end_date ?: 'Sin límite'],
        ['Cobrador:', $collector_id ? 'ID: ' . $collector_id : 'Todos los cobradores'],
        ['Fecha de Generación:', date('d/m/Y H:i:s')]
    ];

    foreach ($report_info as $info) {
        $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
    }

    $pdf->Ln(5);

    if (!empty($commissions)) {
        // Crear sección de tabla
        $pdf->createSection('Detalle de Comisiones por Cobrador', 1);

        // Preparar datos de tabla
        $headers = ['Cobrador', 'Pagos Realizados', 'Interés Total Pagado', 'Comisión 40%', 'Monto Total Cobrado', 'Clientes Atendidos', 'Préstamos Gestionados'];
        $table_data = [];

        $total_payments = 0;
        $total_interest = 0;
        $total_commission = 0;
        $total_collected = 0;
        $total_customers = 0;
        $total_loans = 0;

        foreach ($commissions as $commission) {
            $total_payments += $commission->total_payments ?? 0;
            $total_interest += $commission->total_interest_paid ?? 0;
            $total_commission += $commission->interest_commission_40 ?? 0;
            $total_collected += $commission->total_amount_collected ?? 0;
            $total_customers += $commission->customers_handled ?? 0;
            $total_loans += $commission->loans_handled ?? 0;

            $table_data[] = [
                $commission->user_name,
                number_format($commission->total_payments, 0, ',', '.'),
                $pdf->formatCurrency($commission->total_interest_paid),
                $pdf->formatCurrency($commission->interest_commission_40),
                $pdf->formatCurrency($commission->total_amount_collected),
                number_format($commission->customers_handled, 0, ',', '.'),
                number_format($commission->loans_handled, 0, ',', '.')
            ];
        }

        // Agregar fila de totales
        $table_data[] = [
            'TOTALES',
            number_format($total_payments, 0, ',', '.'),
            $pdf->formatCurrency($total_interest),
            $pdf->formatCurrency($total_commission),
            $pdf->formatCurrency($total_collected),
            number_format($total_customers, 0, ',', '.'),
            number_format($total_loans, 0, ',', '.')
        ];

        // Definir anchos de columna
        $widths = [40, 30, 35, 35, 35, 30, 30];

        // Crear tabla con formato APA
        $pdf->createTable($headers, $table_data, $widths);

        // Resumen ejecutivo
        $pdf->createSection('Resumen Ejecutivo', 1);
        $pdf->MultiCell(0, 6, utf8_decode('Este reporte administrativo muestra el detalle completo de las comisiones del 40% calculadas sobre los intereses pagados por los clientes. Los datos incluyen información consolidada por cobrador con métricas detalladas de rendimiento.'), 0, 'J');
        $pdf->Ln(5);

        $summary_info = [
            ['Total de Cobradores Activos:', count($commissions)],
            ['Comisión Total a Pagar:', $pdf->formatCurrency($total_commission)],
            ['Clientes Gestionados:', number_format($total_customers, 0, ',', '.')],
            ['Préstamos Activos:', number_format($total_loans, 0, ',', '.')]
        ];

        foreach ($summary_info as $info) {
            $pdf->Cell(80, 8, utf8_decode($info[0]), 0, 0);
            $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
        }
    } else {
        $pdf->createSection('Sin Datos Disponibles', 1);
        $pdf->MultiCell(0, 6, utf8_decode('No se encontraron registros de comisiones para los filtros seleccionados.'), 0, 'L');
        $pdf->Ln(5);

        $filters_info = [
            ['Filtros aplicados:'],
            ['Fecha inicio:', $start_date ?: 'Sin límite'],
            ['Fecha fin:', $end_date ?: 'Sin límite'],
            ['Cobrador:', $collector_id ? 'ID: ' . $collector_id : 'Todos']
        ];

        foreach ($filters_info as $info) {
            $pdf->Cell(0, 8, utf8_decode(implode(' ', $info)), 0, 1);
        }
    }

    // Agregar sección de referencias si hay referencias
    $pdf->createReferencesPage();

    // Generar y enviar el PDF
    $pdf_content = $pdf->Output('', 'S');

    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="comisiones_admin_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_content;
    exit;
  }

  /**
   * Exportar comisiones a PDF
   */
  public function export_commissions_pdf()
  {
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');
    $collector_id = $this->input->get('collector_id');

    $commissions = $this->reports_m->get_commission_stats_filtered($start_date, $end_date, $collector_id);

    // Obtener nombre del usuario logueado
    $current_user = $this->user_m->get_current_user();
    $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

    // Crear instancia PDF con formato APA
    require_once APPPATH.'third_party/fpdf183/pdf_apa.php';
    $pdf = new PDF_APA('L', 'mm', 'A4'); // Landscape para mejor visualización de tablas
    $pdf->setTitle('Reporte de Comisiones (40%) - Sistema de Préstamos');
    $pdf->setAuthor($user_name);

    // Agregar referencias APA si es necesario
    $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Reporte de comisiones del 40% generada automáticamente.');
    $pdf->addReference('Normas APA. (2023). Manual de publicaciones de la American Psychological Association (7ª ed.).');

    // Crear portada
    $pdf->createCoverPage();

    // Agregar logo en la primera página de contenido
    $logoPath = FCPATH . 'assets/img/log.png';
    if(file_exists($logoPath)) {
        $pdf->Image($logoPath, $pdf->getMarginLeft(), $pdf->GetY(), 30);
        $pdf->Ln(35);
    }

    // Crear secciones con formato APA
    $pdf->createSection('Información del Reporte', 1);

    // Información del reporte
    $report_info = [
        ['Desde:', $start_date ?: 'Sin límite'],
        ['Hasta:', $end_date ?: 'Sin límite'],
        ['Fecha de Generación:', date('d/m/Y H:i:s')]
    ];

    foreach ($report_info as $info) {
        $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
    }

    $pdf->Ln(5);

    if (!empty($commissions)) {
        // Crear sección de tabla
        $pdf->createSection('Detalle de Comisiones', 1);

        // Preparar datos de tabla
        $headers = ['Cliente', 'Cédula', 'Cobrador', 'Total Pagado', 'Interés', 'Comisión (40%)', 'Fecha'];
        $table_data = [];

        foreach ($commissions as $row) {
            $table_data[] = [
                $row->client_name ?? '',
                $row->client_cedula ?? '',
                $row->user_name ?? '',
                $pdf->formatCurrency($row->total_paid ?? 0),
                number_format($row->interest_amount ?? 0, 2, ',', '.') . '%',
                $pdf->formatCurrency($row->commission ?? 0),
                date('d/m/Y H:i', strtotime($row->created_at ?? 'now'))
            ];
        }

        // Definir anchos de columna
        $widths = [35, 25, 35, 30, 20, 30, 35];

        // Crear tabla con formato APA
        $pdf->createTable($headers, $table_data, $widths);

        // Calcular totales
        $total_commission = array_sum(array_column($commissions, 'commission'));

        // Resumen
        $pdf->createSection('Resumen', 1);
        $pdf->Cell(60, 8, utf8_decode('Total de Comisiones:'), 0, 0);
        $pdf->Cell(0, 8, utf8_decode($pdf->formatCurrency($total_commission)), 0, 1);
        $pdf->Cell(60, 8, utf8_decode('Número de Registros:'), 0, 0);
        $pdf->Cell(0, 8, utf8_decode(count($commissions)), 0, 1);
    } else {
        $pdf->createSection('Sin Datos Disponibles', 1);
        $pdf->MultiCell(0, 6, utf8_decode('No se encontraron registros de comisiones para los filtros seleccionados.'), 0, 'L');
    }

    // Agregar sección de referencias si hay referencias
    $pdf->createReferencesPage();

    // Generar y enviar el PDF
    $pdf_content = $pdf->Output('', 'S');

    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="comisiones_' . date('Y-m-d_H-i-s') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_content;
    exit;
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
    * API para enviar comisión del 40% (para cobradores)
    */
   public function send_commission()
   {
     // Script completamente independiente - no usar CodeIgniter
     // Headers JSON
     header('Content-Type: application/json');
     header('Access-Control-Allow-Origin: *');
     header('Cache-Control: no-cache, no-store, must-revalidate');

     // Obtener parámetros directamente
     $collector_id = isset($_POST['collector_id']) ? trim($_POST['collector_id']) : null;
     $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
     $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
     $selected_commissions = isset($_POST['selected_commissions']) ? trim($_POST['selected_commissions']) : null;

     if (!$collector_id) {
       echo json_encode(['success' => false, 'message' => 'ID de cobrador requerido']);
       exit;
     }

     try {
       // Conexión directa a MySQL
       $host = 'localhost';
       $user = 'root';
       $pass = '';
       $db = 'prestamo';

       $conn = new mysqli($host, $user, $pass, $db);
       if ($conn->connect_error) {
         throw new Exception('Error de conexión: ' . $conn->connect_error);
       }

       // Validar fechas
       $validated_dates = null;
       if ($start_date && $end_date) {
         $start_timestamp = strtotime($start_date);
         $end_timestamp = strtotime($end_date);
         if ($start_timestamp && $end_timestamp && $start_timestamp <= $end_timestamp) {
           $validated_dates = [
             'start' => date('Y-m-d', $start_timestamp),
             'end' => date('Y-m-d', $end_timestamp)
           ];
         }
       }

       $total_commission = 0;

       // Si hay comisiones seleccionadas específicas, procesar solo esas
       if ($selected_commissions) {
         $selected_data = json_decode($selected_commissions, true);

         foreach ($selected_data as $commission) {
           // Verificar si ya existe registro para esta combinación específica
           $sql_check = "SELECT id FROM collector_commissions WHERE user_id = ? AND loan_id = ? AND client_id = ?";
           $stmt_check = $conn->prepare($sql_check);
           $stmt_check->bind_param('iii', $collector_id, $commission['loan_id'], $commission['client_id']);
           $stmt_check->execute();
           $result_check = $stmt_check->get_result();

           if ($result_check->num_rows > 0) {
             // Actualizar registro existente
             $row = $result_check->fetch_assoc();
             $sql_update = "UPDATE collector_commissions SET
                           total_interest = ?,
                           commission_40 = ?,
                           status = 'enviado',
                           sent_at = NOW(),
                           period_start = ?,
                           period_end = ?
                           WHERE id = ?";
             $stmt_update = $conn->prepare($sql_update);
             $period_start = $validated_dates ? $validated_dates['start'] : null;
             $period_end = $validated_dates ? $validated_dates['end'] : null;
             $stmt_update->bind_param('ddssi', $commission['interest'], $commission['commission'], $period_start, $period_end, $row['id']);
             $stmt_update->execute();
           } else {
             // Crear nuevo registro específico
             $sql_insert = "INSERT INTO collector_commissions
                           (user_id, loan_id, client_id, total_interest, commission_40, status, sent_at, period_start, period_end)
                           VALUES (?, ?, ?, ?, ?, 'enviado', NOW(), ?, ?)";
             $stmt_insert = $conn->prepare($sql_insert);
             $period_start = $validated_dates ? $validated_dates['start'] : null;
             $period_end = $validated_dates ? $validated_dates['end'] : null;
             $stmt_insert->bind_param('iiiddss', $collector_id, $commission['loan_id'], $commission['client_id'], $commission['interest'], $commission['commission'], $period_start, $period_end);
             $stmt_insert->execute();
           }

           $total_commission += $commission['commission'];
         }
       } else {
         // Lógica anterior para envío general (sin selección específica)
         // Obtener totales de intereses y comisión
         $where_date = '';
         if ($validated_dates) {
           $where_date = " AND li.pay_date >= '{$validated_dates['start']} 00:00:00' AND li.pay_date <= '{$validated_dates['end']} 23:59:59'";
         }

         $sql_totals = "SELECT
                       COALESCE(SUM(li.interest_paid), 0) as total_interest,
                       COALESCE(SUM(li.interest_paid) * 0.4, 0) as total_commission
                       FROM loan_items li
                       WHERE li.paid_by = ? AND li.status = 0 AND li.interest_paid > 0{$where_date}";

         $stmt_totals = $conn->prepare($sql_totals);
         $stmt_totals->bind_param('i', $collector_id);
         $stmt_totals->execute();
         $result_totals = $stmt_totals->get_result();
         $totals = $result_totals->fetch_assoc();

         $total_interest = $totals['total_interest'];
         $total_commission = $totals['total_commission'];

         if ($total_commission <= 0) {
           echo json_encode(['success' => false, 'message' => 'No hay comisiones pendientes para enviar']);
           exit;
         }

         // Verificar si ya existe un registro pendiente para este período
         $sql_check_period = "SELECT id FROM collector_commissions WHERE user_id = ? AND period_start = ? AND period_end = ? AND status = 'pendiente'";
         $stmt_check_period = $conn->prepare($sql_check_period);
         $period_start = $validated_dates ? $validated_dates['start'] : null;
         $period_end = $validated_dates ? $validated_dates['end'] : null;
         $stmt_check_period->bind_param('iss', $collector_id, $period_start, $period_end);
         $stmt_check_period->execute();
         $result_check_period = $stmt_check_period->get_result();

         if ($result_check_period->num_rows > 0) {
           // Actualizar registro existente
           $row = $result_check_period->fetch_assoc();
           $sql_update_period = "UPDATE collector_commissions SET
                               total_interest = ?,
                               commission_40 = ?,
                               status = 'enviado',
                               sent_at = NOW()
                               WHERE id = ?";
           $stmt_update_period = $conn->prepare($sql_update_period);
           $stmt_update_period->bind_param('ddi', $total_interest, $total_commission, $row['id']);
           $stmt_update_period->execute();
         } else {
           // Crear nuevo registro
           $sql_insert_period = "INSERT INTO collector_commissions
                               (user_id, total_interest, commission_40, status, sent_at, period_start, period_end)
                               VALUES (?, ?, ?, 'enviado', NOW(), ?, ?)";
           $stmt_insert_period = $conn->prepare($sql_insert_period);
           $stmt_insert_period->bind_param('iddss', $collector_id, $total_interest, $total_commission, $period_start, $period_end);
           $stmt_insert_period->execute();
         }
       }

       $conn->close();

       echo json_encode([
         'success' => true,
         'message' => 'Comisión enviada exitosamente al administrador',
         'commission_amount' => $total_commission
       ]);

     } catch (Exception $e) {
       echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
     }

     exit;
   }


   /**
    * API para obtener detalles de intereses por cobrador (acceso público)
    */
   public function get_user_interest_details()
   {
     // COMPLETAMENTE INDEPENDIENTE - NO USAR CODEIGNITER
     // Detener cualquier procesamiento de CI
     if (function_exists('get_instance')) {
       $CI =& get_instance();
       if (isset($CI->output)) {
         $CI->output->_display();
         exit;
       }
     }

     // Limpiar buffers
     while (ob_get_level()) {
       ob_end_clean();
     }

     // Headers JSON estrictos
     header('Content-Type: application/json; charset=utf-8');
     header('Access-Control-Allow-Origin: *');
     header('Cache-Control: no-cache, no-store, must-revalidate');
     header('X-Content-Type-Options: nosniff');

     try {
       // Obtener parámetros directamente
       $user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
       $start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
       $end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

       if (!$user_id) {
         echo json_encode(['error' => 'ID de cobrador requerido'], JSON_UNESCAPED_UNICODE);
         exit;
       }

       // Conexión directa a MySQL
       $host = 'localhost';
       $user = 'root';
       $pass = '';
       $db = 'prestamo';

       $conn = new mysqli($host, $user, $pass, $db);
       if ($conn->connect_error) {
         throw new Exception('Error de conexión: ' . $conn->connect_error);
       }

       $conn->set_charset('utf8mb4');

       // Validar fechas
       $validated_dates = null;
       if ($start_date && $end_date) {
         $start_timestamp = strtotime($start_date);
         $end_timestamp = strtotime($end_date);
         if ($start_timestamp && $end_timestamp && $start_timestamp <= $end_timestamp) {
           $validated_dates = [
             'start' => date('Y-m-d', $start_timestamp),
             'end' => date('Y-m-d', $end_timestamp)
           ];
         }
       }

       // Obtener detalles de intereses por cliente
       $clients = [];
       $where_date = '';
       if ($validated_dates) {
         $where_date = " AND li.pay_date >= '{$validated_dates['start']} 00:00:00' AND li.pay_date <= '{$validated_dates['end']} 23:59:59'";
       }

       $sql = "SELECT
         c.id as customer_id,
         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
         c.dni,
         l.id as loan_id,
         l.credit_amount,
         COUNT(li.id) as payments_made,
         COALESCE(SUM(li.interest_paid), 0) as total_interest_paid,
         COALESCE(SUM(li.interest_paid), 0) * 0.4 as interest_commission_40,
         SUM(li.fee_amount) as total_collected,
         MAX(li.pay_date) as last_payment_date
       FROM customers c
       LEFT JOIN loans l ON l.customer_id = c.id
       LEFT JOIN loan_items li ON li.loan_id = l.id AND li.paid_by = ? AND li.status = 0{$where_date}
       WHERE li.id IS NOT NULL
       GROUP BY c.id, l.id
       HAVING total_interest_paid > 0
       ORDER BY total_interest_paid DESC";

       $stmt = $conn->prepare($sql);
       if (!$stmt) {
         throw new Exception('Error preparando consulta: ' . $conn->error);
       }

       $stmt->bind_param('i', $user_id);
       $stmt->execute();
       $result = $stmt->get_result();

       while ($row = $result->fetch_object()) {
         $clients[] = $row;
       }
       $stmt->close();

       // Calcular totales
       $total_interest = 0;
       $total_commission = 0;
       foreach ($clients as $client) {
         $total_interest += $client->total_interest_paid ?? 0;
         $total_commission += $client->interest_commission_40 ?? 0;
       }

       // Verificar estado de envío de comisión
       $send_status = 'pendiente';
       $where_date_commission = '';
       if ($validated_dates) {
         $where_date_commission = " AND created_at >= '{$validated_dates['start']} 00:00:00' AND created_at <= '{$validated_dates['end']} 23:59:59'";
       }

       $sql_commission = "SELECT status FROM collector_commissions WHERE user_id = ?{$where_date_commission} ORDER BY created_at DESC LIMIT 1";
       $stmt_commission = $conn->prepare($sql_commission);
       if ($stmt_commission) {
         $stmt_commission->bind_param('i', $user_id);
         $stmt_commission->execute();
         $result_commission = $stmt_commission->get_result();
         if ($row_commission = $result_commission->fetch_object()) {
           $send_status = $row_commission->status ?? 'pendiente';
         }
         $stmt_commission->close();
       }

       $conn->close();

       // Respuesta JSON
       $response_data = [
         'clients' => $clients,
         'total_interest' => $total_interest,
         'total_commission' => $total_commission,
         'send_status' => $send_status
       ];

       echo json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

     } catch (Exception $e) {
       $error_data = ['error' => 'Error interno del servidor: ' . $e->getMessage()];
       echo json_encode($error_data, JSON_UNESCAPED_UNICODE);
     }

     exit;
   }

   /**
    * API para obtener resumen de comisiones de cobradores (acceso público)
    */
   public function get_collector_commissions_summary()
   {
     // Acceso público habilitado - Sin restricciones de rol

     $start_date = $this->input->get('start_date');
     $end_date = $this->input->get('end_date');
     $collector_id = $this->input->get('collector_id');

     try {
       // Validar fechas
       $validated_dates = $this->_validate_date_filters($start_date, $end_date);

       // Obtener resumen de comisiones
       $summary = $this->_get_collector_commissions_summary($validated_dates, $collector_id);

       $this->output->set_content_type('application/json');
       $this->output->set_header('Access-Control-Allow-Origin: *');
       echo json_encode([
         'collectors' => $summary,
         'summary' => [
           'total_collectors' => count($summary),
           'completed_sends' => count(array_filter($summary, function($c) { return $c->send_status == 'enviado'; })),
           'pending_sends' => count(array_filter($summary, function($c) { return $c->send_status != 'enviado'; })),
           'total_to_pay' => array_sum(array_column($summary, 'commission_40'))
         ]
       ]);

     } catch (Exception $e) {
       log_message('error', 'Reports::get_collector_commissions_summary - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       $this->output->set_header('Access-Control-Allow-Origin: *');
       echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
     }
   }

   /**
    * Método auxiliar para obtener resumen de comisiones de cobradores
    */
   private function _get_collector_commissions_summary($validated_dates, $collector_id = null)
   {
     // Obtener todos los cobradores con sus comisiones
     $this->db->select('
       u.id as collector_id,
       u.first_name,
       u.last_name,
       COALESCE(SUM(li.interest_paid), 0) as total_interest,
       COALESCE(SUM(li.interest_paid) * 0.4, 0) as commission_40,
       MAX(li.pay_date) as last_payment_date
     ');
     $this->db->from('users u');
     $this->db->join('loan_items li', 'li.paid_by = u.id', 'left');
     $this->db->join('loans l', 'l.id = li.loan_id', 'left');

     // Aplicar filtros de fecha
     if (!empty($validated_dates) && isset($validated_dates['start'])) {
       $this->db->where('li.pay_date >=', $validated_dates['start'] . ' 00:00:00');
     }
     if (!empty($validated_dates) && isset($validated_dates['end'])) {
       $this->db->where('li.pay_date <=', $validated_dates['end'] . ' 23:59:59');
     }

     // Filtrar por cobrador específico si se proporciona
     if ($collector_id) {
       $this->db->where('u.id', $collector_id);
     }

     // Solo usuarios que han realizado pagos (sin filtrar por rol específico)
     $this->db->where('li.status', 0); // Solo pagos completados
     $this->db->where('li.interest_paid >', 0); // Solo con intereses pagados

     $this->db->group_by('u.id');
     $this->db->order_by('u.first_name, u.last_name');

     $results = $this->db->get()->result();

     // Si se filtra por un cobrador específico y no hay resultados,
     // devolver un array vacío en lugar de intentar procesar resultados nulos
     if ($collector_id && empty($results)) {
       return [];
     }

     // Procesar resultados para determinar estado de envío
     foreach ($results as &$result) {
       // La tabla collector_commissions no tiene campos status ni sent_at según la estructura real
       // Por ahora, marcar todos como pendientes ya que no hay tracking de envío
       $result->send_status = 'pendiente';
       $result->sent_at = null;

       // Crear badge de estado
       $result->status_badge = '<span class="badge badge-warning">Pendiente</span>';

       // Formatear montos
       $result->total_interest_formatted = '$' . number_format($result->total_interest, 0, ',', '.');
       $result->commission_40_formatted = '$' . number_format($result->commission_40, 0, ',', '.');
       $result->collector_name = $result->first_name . ' ' . $result->last_name;
     }

     return $results;
   }

   /**
    * API para obtener detalles administrativos de cobrador (acceso público)
    */
   public function get_admin_user_details()
   {
     // Acceso público habilitado - Sin restricciones de rol

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

       // Obtener información adicional del cobrador
       $this->db->select('MAX(li.pay_date) as last_activity');
       $this->db->from('loan_items li');
       $this->db->where('li.paid_by', $user_id);
       $this->db->where('li.status', 0);
       $last_activity = $this->db->get()->row();

       $this->output->set_content_type('application/json');
       echo json_encode([
         'clients' => $clients,
         'total_interest' => $total_interest,
         'total_commission' => $total_commission,
         'last_activity' => $last_activity->last_activity ?? null
       ]);

     } catch (Exception $e) {
       log_message('error', 'Reports::get_admin_user_details - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       echo json_encode(['error' => 'Error interno del servidor']);
     }
   }
 
   /**
    * API para obtener datos detallados de comisiones de intereses para la vista modal
    */
   public function get_detailed_interest_commissions_data()
   {
     $start_date = $this->input->get('start_date');
     $end_date = $this->input->get('end_date');
     $collector_id = $this->input->get('collector_id');
 
     try {
       // Validar fechas
       $validated_dates = $this->_validate_date_filters($start_date, $end_date);
 
       // Obtener comisiones detalladas
       $commissions = $this->_get_interest_commissions($validated_dates, $collector_id);
 
       // Preparar datos para gráficos
       $collectors_chart = [
         'labels' => array_column($commissions, 'user_name'),
         'data' => array_column($commissions, 'interest_commission_40')
       ];
 
       // Datos de tendencia mensual (últimos 6 meses)
       $trend_data = $this->_get_monthly_trend_data($validated_dates, $collector_id);
       $trend_chart = [
         'labels' => $trend_data['labels'],
         'data' => $trend_data['data']
       ];
 
       // Detalles completos para la tabla
       $details = [];
       foreach ($commissions as $commission) {
         if (!empty($commission->client_details)) {
           foreach ($commission->client_details as $client) {
             $details[] = (object) [
               'user_name' => $commission->user_name,
               'customer_name' => $client->customer_name,
               'dni' => $client->dni,
               'loan_id' => $client->loan_id,
               'credit_amount' => $client->credit_amount,
               'payments_made' => $client->payments_made,
               'total_interest_paid' => $client->total_interest_paid,
               'interest_commission_40' => $client->interest_commission_40,
               'last_payment_date' => $client->last_payment_date
             ];
           }
         }
       }
 
       $this->output->set_content_type('application/json');
       echo json_encode([
         'commissions' => $commissions,
         'collectors_chart' => $collectors_chart,
         'trend_chart' => $trend_chart,
         'details' => $details
       ]);
 
     } catch (Exception $e) {
       log_message('error', 'Reports::get_detailed_interest_commissions_data - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       echo json_encode(['error' => 'Error interno del servidor']);
     }
   }
 
   /**
    * Obtener datos de tendencia mensual para comisiones
    */
   private function _get_monthly_trend_data($validated_dates = null, $collector_id = null)
   {
     // Generar últimos 6 meses
     $labels = [];
     $data = [];
 
     for ($i = 5; $i >= 0; $i--) {
       $date = date('Y-m', strtotime("-$i months"));
       $start_of_month = date('Y-m-01', strtotime($date));
       $end_of_month = date('Y-m-t', strtotime($date));
 
       $labels[] = date('M Y', strtotime($date));
 
       // Consulta para obtener comisiones del mes
       $this->db->select('COALESCE(SUM(li.interest_paid * 0.4), 0) as monthly_commission');
       $this->db->from('loan_items li');
       $this->db->join('users u', 'u.id = li.paid_by', 'left');
       $this->db->where('li.status', 0);
       $this->db->where('li.pay_date >=', $start_of_month);
       $this->db->where('li.pay_date <=', $end_of_month);
 
       if ($collector_id) {
         $this->db->where('li.paid_by', $collector_id);
       }
 
       $result = $this->db->get()->row();
       $data[] = floatval($result->monthly_commission ?? 0);
     }
 
     return ['labels' => $labels, 'data' => $data];
   }
 
   /**
    * Exportar datos detallados de comisiones de intereses a Excel
    */
   public function export_detailed_interest_commissions_excel()
   {
     $start_date = $this->input->get('start_date');
     $end_date = $this->input->get('end_date');
     $collector_id = $this->input->get('collector_id');
 
     $validated_dates = $this->_validate_date_filters($start_date, $end_date);
 
     // Obtener datos detallados
     $data = $this->_get_detailed_export_data($validated_dates, $collector_id);
 
     header("Content-Type: application/vnd.ms-excel");
     header("Content-Disposition: attachment; filename=comisiones_intereses_detalladas_{$start_date}_{$end_date}.xls");
 
     echo "<table border='1'>
           <tr>
             <th>Cobrador</th>
             <th>Cliente</th>
             <th>Cédula</th>
             <th>ID Préstamo</th>
             <th>Monto Original</th>
             <th>Pagos Realizados</th>
             <th>Interés Pagado</th>
             <th>Comisión 40%</th>
             <th>Último Pago</th>
           </tr>";
 
     foreach ($data['details'] as $detail) {
       echo "<tr>
             <td>{$detail->user_name}</td>
             <td>{$detail->customer_name}</td>
             <td>{$detail->dni}</td>
             <td>{$detail->loan_id}</td>
             <td>$" . number_format($detail->credit_amount, 0, ',', '.') . "</td>
             <td>" . number_format($detail->payments_made, 0, ',', '.') . "</td>
             <td>$" . number_format($detail->total_interest_paid, 2, ',', '.') . "</td>
             <td>$" . number_format($detail->interest_commission_40, 2, ',', '.') . "</td>
             <td>" . ($detail->last_payment_date ? date('d/m/Y', strtotime($detail->last_payment_date)) : 'N/A') . "</td>
             </tr>";
     }
 
     // Agregar fila de totales
     echo "<tr style='font-weight: bold; background-color: #f0f0f0;'>
           <td colspan='6'>TOTALES</td>
           <td>$" . number_format($data['totals']['total_interest'], 2, ',', '.') . "</td>
           <td>$" . number_format($data['totals']['total_commission'], 2, ',', '.') . "</td>
           <td></td>
           </tr>";
 
     echo "</table>";
   }
 
   /**
    * Obtener datos para exportación detallada
    */
   private function _get_detailed_export_data($validated_dates = null, $collector_id = null)
   {
     $commissions = $this->_get_interest_commissions($validated_dates, $collector_id);
 
     $details = [];
     $totals = ['total_interest' => 0, 'total_commission' => 0];
 
     foreach ($commissions as $commission) {
       if (!empty($commission->client_details)) {
         foreach ($commission->client_details as $client) {
           $details[] = (object) [
             'user_name' => $commission->user_name,
             'customer_name' => $client->customer_name,
             'dni' => $client->dni,
             'loan_id' => $client->loan_id,
             'credit_amount' => $client->credit_amount,
             'payments_made' => $client->payments_made,
             'total_interest_paid' => $client->total_interest_paid,
             'interest_commission_40' => $client->interest_commission_40,
             'last_payment_date' => $client->last_payment_date
           ];
 
           $totals['total_interest'] += $client->total_interest_paid ?? 0;
           $totals['total_commission'] += $client->interest_commission_40 ?? 0;
         }
       }
     }
 
     return ['details' => $details, 'totals' => $totals];
   }

}

/* End of file Reports.php */
/* Location: ./application/controllers/admin/Reports.php */