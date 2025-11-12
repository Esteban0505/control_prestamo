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
    $collector_id = $this->input->get('collector_id');

    // Validar fechas
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;
    $data['collector_id'] = $collector_id;

    // REMOVER: No necesitamos datos detallados de préstamos individuales
    // $data['loans_report'] = $this->reports_m->get_detailed_loans_report($collector_id ?: $current_user_id, $validated_dates['start'] ?? null, $validated_dates['end'] ?? null);

    // Obtener datos de comisiones para TODOS los cobradores (sin filtrar por usuario actual)
    $data['interest_commissions'] = $this->_get_interest_commissions($validated_dates, $collector_id ?: null);
    $data['total_interest_commissions'] = $this->_calculate_total_interest_commissions($data['interest_commissions']);

    // DEBUG: Verificar que se está llamando correctamente
    log_message('debug', 'Reports::index - Llamando _get_interest_commissions con collector_id: ' . ($collector_id ?: 'null'));
    log_message('debug', 'Reports::index - Resultado interest_commissions count: ' . count($data['interest_commissions']));

    // Obtener lista de cobradores para mostrar resumen general
    $data['cobradores_list'] = $this->reports_m->get_cobradores_list();

    // Debug: Mostrar información de depuración
    log_message('debug', 'REPORTS INDEX - loans_report count: ' . count($data['loans_report'] ?? []));
    log_message('debug', 'REPORTS INDEX - interest_commissions count: ' . count($data['interest_commissions'] ?? []));
    log_message('debug', 'REPORTS INDEX - cobradores_list count: ' . count($data['cobradores_list'] ?? []));

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
    $pdf->createCoverPage();

    // Agregar resumen ejecutivo después de la portada
    // $this->_create_executive_summary($pdf, $customer_info, $reportCst);

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
        // $this->_create_loan_section($pdf, $rc, $total_pagado, $saldo_restante, $loanItems);

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

    // DEBUG: Verificar que se está usando LEFT JOIN correctamente
    log_message('debug', 'Reports: _get_interest_commissions - JOIN: users u LEFT JOIN loan_items li ON li.paid_by = u.id');
    log_message('debug', 'Reports: _get_interest_commissions - WHERE: li.status = 0 AND li.paid_by IS NOT NULL');
    log_message('debug', 'Reports: _get_interest_commissions - GROUP BY: u.id');
    log_message('debug', 'Reports: _get_interest_commissions - HAVING: total_payments > 0');

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
    * API para enviar comisión del 40% (para cobradores) - SIN AUTENTICACIÓN
    */
   public function send_commission()
   {
     // Configurar respuesta JSON
     $this->output->set_content_type('application/json');
     $this->output->set_header('Access-Control-Allow-Origin: *');
     $this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');
     
     // Limpiar cualquier salida previa
     if (ob_get_level() > 0) {
       ob_clean();
     }

     try {
       // Obtener parámetros directamente
       $collector_id = isset($_POST['collector_id']) ? trim($_POST['collector_id']) : null;
       $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
       $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
       $selected_commissions = isset($_POST['selected_commissions']) ? trim($_POST['selected_commissions']) : null;

       if (!$collector_id) {
         $this->output->set_output(json_encode(['success' => false, 'message' => 'ID de cobrador requerido']));
         return;
       }

       // Usar CodeIgniter database
       $this->load->database();
       
       // Asegurar que las columnas necesarias existan
       $this->_ensure_commission_columns($this->db);
       
       // Obtener conexión mysqli para operaciones específicas
       $conn = $this->db->conn_id;
       if (!$conn) {
         throw new Exception('No se pudo obtener la conexión a la base de datos');
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

         if (json_last_error() !== JSON_ERROR_NONE) {
           $this->output->set_output(json_encode(['success' => false, 'message' => 'Datos de comisiones seleccionadas inválidos']));
           return;
         }

         foreach ($selected_data as $commission) {
           // Obtener información del cliente y préstamo
           $loan_id = isset($commission['loan_id']) ? (int)$commission['loan_id'] : null;
           $client_id = isset($commission['client_id']) ? (int)$commission['client_id'] : null;
           $interest = isset($commission['interest']) ? (float)$commission['interest'] : 0;
           $commission_amount = isset($commission['commission']) ? (float)$commission['commission'] : 0;
           
           if (!$loan_id || $commission_amount <= 0) {
             continue; // Saltar si faltan datos esenciales
           }
           
           // Obtener información del cliente desde la base de datos
           $sql_client = "SELECT c.id as customer_id, CONCAT(c.first_name, ' ', c.last_name) as client_name, c.dni as client_cedula
                         FROM loans l
                         JOIN customers c ON c.id = l.customer_id
                         WHERE l.id = ?";
           $stmt_client = $conn->prepare($sql_client);
           $stmt_client->bind_param('i', $loan_id);
           $stmt_client->execute();
           $result_client = $stmt_client->get_result();
           $client_info = $result_client->fetch_assoc();
           
           if (!$client_info) {
             continue; // Saltar si no se encuentra el cliente
           }
           
           // Verificar si ya existe registro para esta combinación específica
           $sql_check = "SELECT id FROM collector_commissions 
                         WHERE user_id = ? AND loan_id = ? 
                         AND period_start = ? AND period_end = ?";
           $stmt_check = $conn->prepare($sql_check);
           $period_start = $validated_dates ? $validated_dates['start'] : null;
           $period_end = $validated_dates ? $validated_dates['end'] : null;
           $stmt_check->bind_param('iiss', $collector_id, $loan_id, $period_start, $period_end);
           $stmt_check->execute();
           $result_check = $stmt_check->get_result();

           if ($result_check->num_rows > 0) {
             // Actualizar registro existente
             $row = $result_check->fetch_assoc();
             $sql_update = "UPDATE collector_commissions SET
                           amount = ?,
                           commission = ?,
                           status = 'enviado',
                           sent_at = NOW(),
                           client_name = ?,
                           client_cedula = ?
                           WHERE id = ?";
             $stmt_update = $conn->prepare($sql_update);
             $total_amount = $interest + ($interest * 0.6); // Interés + capital aproximado
             $stmt_update->bind_param('ddssi', $total_amount, $commission_amount, $client_info['client_name'], $client_info['client_cedula'], $row['id']);
             $stmt_update->execute();
           } else {
             // Crear nuevo registro específico
             $sql_insert = "INSERT INTO collector_commissions
                           (user_id, loan_id, client_name, client_cedula, amount, commission, status, sent_at, period_start, period_end)
                           VALUES (?, ?, ?, ?, ?, ?, 'enviado', NOW(), ?, ?)";
             $stmt_insert = $conn->prepare($sql_insert);
             $total_amount = $interest + ($interest * 0.6); // Interés + capital aproximado
             $stmt_insert->bind_param('iissddss', $collector_id, $loan_id, $client_info['client_name'], $client_info['client_cedula'], $total_amount, $commission_amount, $period_start, $period_end);
             $stmt_insert->execute();
           }

           $total_commission += $commission_amount;
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
           $this->output->set_output(json_encode(['success' => false, 'message' => 'No hay comisiones pendientes para enviar']));
           return;
         }

         // Verificar si ya existe un registro para este período
         $sql_check_period = "SELECT id, SUM(commission) as total_comm FROM collector_commissions 
                              WHERE user_id = ? AND period_start = ? AND period_end = ? 
                              GROUP BY user_id, period_start, period_end";
         $stmt_check_period = $conn->prepare($sql_check_period);
         $period_start = $validated_dates ? $validated_dates['start'] : null;
         $period_end = $validated_dates ? $validated_dates['end'] : null;
         $stmt_check_period->bind_param('iss', $collector_id, $period_start, $period_end);
         $stmt_check_period->execute();
         $result_check_period = $stmt_check_period->get_result();

         if ($result_check_period->num_rows > 0) {
           // Actualizar todos los registros del período a enviado
           $sql_update_period = "UPDATE collector_commissions SET
                               status = 'enviado',
                               sent_at = NOW()
                               WHERE user_id = ? AND period_start = ? AND period_end = ? AND status = 'pendiente'";
           $stmt_update_period = $conn->prepare($sql_update_period);
           $stmt_update_period->bind_param('iss', $collector_id, $period_start, $period_end);
           $stmt_update_period->execute();
         } else {
           // Crear registro agregado para el período completo
           // Obtener todos los loan_items del período para crear registros individuales
           $where_date = '';
           if ($validated_dates) {
             $where_date = " AND li.pay_date >= '{$validated_dates['start']} 00:00:00' AND li.pay_date <= '{$validated_dates['end']} 23:59:59'";
           }
           
           $sql_items = "SELECT li.id as loan_item_id, li.loan_id, li.interest_paid, 
                         CONCAT(c.first_name, ' ', c.last_name) as client_name, c.dni as client_cedula
                         FROM loan_items li
                         JOIN loans l ON l.id = li.loan_id
                         JOIN customers c ON c.id = l.customer_id
                         WHERE li.paid_by = ? AND li.status = 0 AND li.interest_paid > 0{$where_date}";
           $stmt_items = $conn->prepare($sql_items);
           $stmt_items->bind_param('i', $collector_id);
           $stmt_items->execute();
           $result_items = $stmt_items->get_result();
           
           while ($item = $result_items->fetch_assoc()) {
             $item_commission = $item['interest_paid'] * 0.4;
             $item_amount = $item['interest_paid'] + ($item['interest_paid'] * 0.6);
             
             $sql_insert_item = "INSERT INTO collector_commissions
                               (user_id, loan_id, loan_item_id, client_name, client_cedula, amount, commission, status, sent_at, period_start, period_end)
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'enviado', NOW(), ?, ?)";
             $stmt_insert_item = $conn->prepare($sql_insert_item);
             $stmt_insert_item->bind_param('iiissddss', $collector_id, $item['loan_id'], $item['loan_item_id'], 
                                           $item['client_name'], $item['client_cedula'], $item_amount, $item_commission,
                                           $period_start, $period_end);
             $stmt_insert_item->execute();
           }
         }
       }

       // Asegurar que la respuesta JSON sea válida
       $response = [
         'success' => true,
         'message' => 'Comisión enviada exitosamente al administrador',
         'commission_amount' => $total_commission,
         'processed_count' => isset($selected_data) ? count($selected_data) : 0
       ];

       $this->output->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
       return;

     } catch (Exception $e) {
       // Asegurar que siempre se devuelva JSON válido
       $error_response = [
         'success' => false, 
         'message' => 'Error interno del servidor: ' . $e->getMessage()
       ];
       $this->output->set_output(json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
       return;
     }
   }


   /**
    * API para obtener detalles de préstamos enviados por cobrador
    */
   public function get_sent_commissions_details()
   {
     $this->output->set_content_type('application/json');
     $this->output->set_header('Access-Control-Allow-Origin: *');
     $this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');

     try {
       $user_id = $this->input->get('user_id');
       $loan_id = $this->input->get('loan_id');
       $commission_id = $this->input->get('commission_id');
       $start_date = $this->input->get('start_date');
       $end_date = $this->input->get('end_date');

      // Si se proporciona loan_id o commission_id, buscar ese préstamo específico
      if (!empty($loan_id) || !empty($commission_id)) {
        $loan_id = $loan_id !== null ? (int) $loan_id : null;
        $commission_id = $commission_id !== null ? (int) $commission_id : null;
        $user_id_int = $user_id !== null ? (int) $user_id : null;

        $sql = "
          SELECT 
            cc.id AS commission_id,
            cc.user_id,
            cc.loan_id,
            cc.client_name,
            cc.client_cedula,
            cc.amount,
            cc.commission,
            cc.status,
            cc.sent_at,
            cc.period_start,
            cc.period_end,
            l.credit_amount,
            l.num_fee,
            c.id AS customer_id,
            c.phone_fixed,
            c.address
          FROM collector_commissions cc
          LEFT JOIN loans l ON l.id = cc.loan_id
          LEFT JOIN customers c ON c.id = l.customer_id
          WHERE cc.status = 'enviado'
        ";

        $params = [];
        if (!empty($commission_id)) {
          $sql .= " AND cc.id = ?";
          $params[] = $commission_id;
        }
        if (!empty($loan_id)) {
          $sql .= " AND cc.loan_id = ?";
          $params[] = $loan_id;
        }
        if (!empty($user_id_int)) {
          $sql .= " AND cc.user_id = ?";
          $params[] = $user_id_int;
        }

        $sql .= " ORDER BY cc.sent_at DESC LIMIT 1";

        $query = $this->db->query($sql, $params);
        if (!$query) {
          $db_error = $this->db->error();
          log_message(
            'error',
            'Reports::get_sent_commissions_details - Error al consultar comisión (loan_id: '
            . ($loan_id ?? 'null') . ', commission_id: ' . ($commission_id ?? 'null') . ', user_id: ' . ($user_id_int ?? 'null')
            . ') DB Error: ' . json_encode($db_error) . ' SQL: ' . $sql . ' Params: ' . json_encode($params)
          );
          throw new Exception('Error al consultar la comisión del préstamo');
        }

        if ($query->num_rows() === 0) {
          $this->output->set_output(json_encode(['error' => 'Préstamo no encontrado']));
          return;
        }

        $commission = $query->row();

        // Obtener información adicional de pagos
        $commission->payments_made = 0;
        $commission->total_interest_paid = floatval($commission->amount ?? 0);

        if (!empty($commission->loan_id)) {
          $payments_sql = "
            SELECT COUNT(*) AS payments_made, SUM(interest_paid) AS total_interest_paid
            FROM loan_items
            WHERE loan_id = ? AND status = 0
          ";
          $payments_params = [$commission->loan_id];

          if (!empty($user_id_int)) {
            $payments_sql .= " AND paid_by = ?";
            $payments_params[] = $user_id_int;
          }

          $payments_query = $this->db->query($payments_sql, $payments_params);
          if ($payments_query) {
            if ($payments_query->num_rows() > 0) {
            $payments_info = $payments_query->row();
            $commission->payments_made = (int) ($payments_info->payments_made ?? 0);
            if (!empty($payments_info->total_interest_paid)) {
              $commission->total_interest_paid = floatval($payments_info->total_interest_paid);
            }
            }
          } else {
            $db_error = $this->db->error();
            log_message(
              'error',
              'Reports::get_sent_commissions_details - Error al consultar loan_items (loan_id: '
              . $commission->loan_id . ', user_id: ' . ($user_id_int ?? 'null') . ') DB Error: ' . json_encode($db_error)
            );
          }
        }

        $this->output->set_output(json_encode([
          'commissions' => [$commission],
          'total_commission' => floatval($commission->commission ?? 0),
          'total_interest' => floatval($commission->total_interest_paid ?? 0),
          'count' => 1
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return;
      }

       if (!$user_id) {
         $this->output->set_output(json_encode(['error' => 'ID de cobrador requerido']));
         return;
       }

       // Validar fechas
       $validated_dates = $this->_validate_date_filters($start_date, $end_date);

       // Obtener comisiones enviadas desde collector_commissions
       $this->db->select('
         cc.id as commission_id,
         cc.loan_id,
         cc.client_name,
         cc.client_cedula,
         cc.amount,
         cc.commission,
         cc.status,
         cc.sent_at,
         cc.period_start,
        cc.period_end,
        l.credit_amount,
        l.num_fee,
         c.id as customer_id,
         c.phone_fixed,
         c.address
       ');
       $this->db->from('collector_commissions cc');
       $this->db->join('loans l', 'l.id = cc.loan_id', 'left');
       $this->db->join('customers c', 'c.id = l.customer_id', 'left');
       $this->db->where('cc.user_id', $user_id);
       $this->db->where('cc.status', 'enviado');
       
       // Aplicar filtros de fecha de forma más flexible
       if ($validated_dates && isset($validated_dates['start']) && isset($validated_dates['end'])) {
         $this->db->group_start();
         $this->db->where('cc.period_start <=', $validated_dates['end']);
         $this->db->where('cc.period_end >=', $validated_dates['start']);
         $this->db->group_end();
       } elseif ($validated_dates && isset($validated_dates['start'])) {
         $this->db->where('cc.period_end >=', $validated_dates['start']);
       } elseif ($validated_dates && isset($validated_dates['end'])) {
         $this->db->where('cc.period_start <=', $validated_dates['end']);
       }
       
       $this->db->order_by('cc.sent_at', 'DESC');
       
       $commissions = $this->db->get()->result();
       
       // Obtener información adicional de pagos para cada préstamo
       foreach ($commissions as &$comm) {
         if (!$comm->loan_id) {
           $comm->payments_made = 0;
           $comm->total_interest_paid = floatval($comm->amount ?? 0);
           continue;
         }
         
         $this->db->select('COUNT(*) as payments_made, SUM(interest_paid) as total_interest_paid');
         $this->db->from('loan_items');
         $this->db->where('loan_id', $comm->loan_id);
         $this->db->where('paid_by', $user_id);
         $this->db->where('status', 0);
         $payments_info = $this->db->get()->row();
         
         $comm->payments_made = intval($payments_info->payments_made ?? 0);
         $comm->total_interest_paid = floatval($payments_info->total_interest_paid ?? ($comm->amount ?? 0));
       }
       
       // Calcular totales
       $total_commission = 0;
       $total_interest = 0;
       foreach ($commissions as $comm) {
         $total_commission += floatval($comm->commission ?? 0);
         $total_interest += floatval($comm->total_interest_paid ?? 0);
       }

       $this->output->set_output(json_encode([
         'commissions' => $commissions,
         'total_commission' => $total_commission,
         'total_interest' => $total_interest,
         'count' => count($commissions)
       ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    } catch (Exception $e) {
      log_message('error', 'Reports::get_sent_commissions_details - Exception: ' . $e->getMessage());
      $this->output->set_output(json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]));
     }
   }

   /**
    * API para obtener detalles de intereses por cobrador (acceso público)
    */
   public function get_user_interest_details()
   {
     // Acceso público habilitado - Sin restricciones de rol

     // Configurar headers JSON antes de cualquier salida
     $this->output->set_content_type('application/json');
     $this->output->set_header('Access-Control-Allow-Origin: *');
     $this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');

     try {
       // Obtener parámetros usando CodeIgniter
       $user_id = $this->input->get('user_id');
       $start_date = $this->input->get('start_date');
       $end_date = $this->input->get('end_date');

       if (!$user_id) {
         $this->output->set_output(json_encode(['error' => 'ID de cobrador requerido']));
         return;
       }

       // Validar fechas
       $validated_dates = $this->_validate_date_filters($start_date, $end_date);

       // Obtener detalles de intereses por cliente
       $clients = $this->_get_user_client_interest_details($user_id, $validated_dates);

       // Calcular totales y verificar estado de envío
       $total_interest = 0;
       $total_commission = 0;
       $all_sent = true;
       $any_sent = false;
       
       foreach ($clients as $client) {
         $total_interest += $client->total_interest_paid ?? 0;
         $total_commission += $client->interest_commission_40 ?? 0;
         
         // Consultar estado de envío para este cliente/préstamo
         $period_start = !empty($validated_dates) && isset($validated_dates['start']) ? $validated_dates['start'] : null;
         $period_end = !empty($validated_dates) && isset($validated_dates['end']) ? $validated_dates['end'] : null;
         
         $client->send_status = 'pendiente';
         $client->sent_at = null;
         
         try {
           // Verificar si la columna status existe
           $column_check = $this->db->query("SHOW COLUMNS FROM collector_commissions LIKE 'status'");
           if ($column_check->num_rows() > 0) {
             $this->db->select('status, MAX(sent_at) as sent_at');
             $this->db->from('collector_commissions');
         $this->db->where('user_id', $user_id);
             $this->db->where('loan_id', $client->loan_id);
             if ($period_start) {
               $this->db->where('period_start', $period_start);
             }
             if ($period_end) {
               $this->db->where('period_end', $period_end);
             }
             $this->db->group_by('status');
             $this->db->order_by('sent_at', 'DESC');
         $this->db->limit(1);
             $status_query = $this->db->get();
             
             if ($status_query->num_rows() > 0) {
               $status_row = $status_query->row();
               if ($status_row->status == 'enviado') {
                 $client->send_status = 'enviado';
                 $client->sent_at = $status_row->sent_at;
                 $any_sent = true;
               } else {
                 $all_sent = false;
               }
             } else {
               $all_sent = false;
             }
           }
         } catch (Exception $e) {
           // Si hay error (columna no existe), mantener como pendiente
           log_message('debug', 'Error consultando estado de comisiones: ' . $e->getMessage());
           $all_sent = false;
         }
       }

       // Determinar estado general
       $send_status = $all_sent ? 'enviado' : ($any_sent ? 'parcial' : 'pendiente');

       // Respuesta JSON
       $response_data = [
         'clients' => $clients,
         'total_interest' => $total_interest,
         'total_commission' => $total_commission,
         'send_status' => $send_status
       ];

       $this->output->set_output(json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

     } catch (Exception $e) {
       log_message('error', 'Reports::get_user_interest_details - Exception: ' . $e->getMessage());
       $this->output->set_output(json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]));
     }
   }

   /**
    * API para obtener resumen de comisiones de cobradores (acceso público)
    * Ahora devuelve cada préstamo como una fila separada
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

       // Obtener préstamos enviados individualmente (no agrupados)
       $sql = "
         SELECT 
           cc.id as commission_id,
           cc.user_id as collector_id,
           u.first_name,
           u.last_name,
           cc.loan_id,
           cc.client_name,
           cc.client_cedula,
           cc.commission as commission_40,
           cc.amount as total_interest,
           cc.sent_at,
           l.credit_amount,
           l.num_fee
         FROM collector_commissions cc
         INNER JOIN users u ON u.id = cc.user_id
         LEFT JOIN loans l ON l.id = cc.loan_id
         WHERE cc.status = 'enviado'
       ";
       
       // Aplicar filtros de fecha
       if (!empty($validated_dates) && isset($validated_dates['start']) && isset($validated_dates['end'])) {
         $sql .= " AND (
           (cc.period_start <= '" . $this->db->escape_str($validated_dates['end']) . "' AND cc.period_end >= '" . $this->db->escape_str($validated_dates['start']) . "')
           OR cc.period_start IS NULL 
           OR cc.period_end IS NULL
         )";
       } elseif (!empty($validated_dates) && isset($validated_dates['start'])) {
         $sql .= " AND (cc.period_end >= '" . $this->db->escape_str($validated_dates['start']) . "' OR cc.period_end IS NULL)";
       } elseif (!empty($validated_dates) && isset($validated_dates['end'])) {
         $sql .= " AND (cc.period_start <= '" . $this->db->escape_str($validated_dates['end']) . "' OR cc.period_start IS NULL)";
       }

       // Filtrar por cobrador específico si se proporciona
       if ($collector_id) {
         $sql .= " AND cc.user_id = " . (int)$collector_id;
       }

       $sql .= " ORDER BY cc.sent_at DESC, u.first_name, u.last_name, cc.loan_id";

       $query = $this->db->query($sql);
       $results = $query->result();
       
       // Procesar resultados
       $loans = [];
       foreach ($results as $result) {
         $loan = new stdClass();
         $loan->commission_id = $result->commission_id;
         $loan->collector_id = $result->collector_id;
         $loan->collector_name = trim($result->first_name . ' ' . $result->last_name);
         $loan->loan_id = $result->loan_id;
         $loan->client_name = $result->client_name;
         $loan->client_cedula = $result->client_cedula;
         $loan->commission_40 = floatval($result->commission_40 ?? 0);
         $loan->total_interest = floatval($result->total_interest ?? 0);
         $loan->sent_at = $result->sent_at;
         $loan->credit_amount = floatval($result->credit_amount ?? 0);
         $loan->num_fee = $result->num_fee ?? 0;
         $loan->send_status = 'enviado';
         
         // Formatear montos
         $loan->total_interest_formatted = '$' . number_format($loan->total_interest, 0, ',', '.');
         $loan->commission_40_formatted = '$' . number_format($loan->commission_40, 0, ',', '.');
         
         // Badge de estado
         $loan->status_badge = '<span class="badge badge-success">Enviado</span>';
         if ($loan->sent_at) {
           $loan->status_badge .= '<br><small class="text-muted">' . date('d/m/Y H:i', strtotime($loan->sent_at)) . '</small>';
         }
         
         $loans[] = $loan;
       }
       
       // Calcular totales
       $total_to_pay = 0;
       $total_interest_sum = 0;
       foreach ($loans as $loan) {
         $total_to_pay += $loan->commission_40;
         $total_interest_sum += $loan->total_interest;
       }
       
       // Agrupar por cobrador para el resumen
       $collectors_summary = [];
       foreach ($loans as $loan) {
         if (!isset($collectors_summary[$loan->collector_id])) {
           $collectors_summary[$loan->collector_id] = [
             'collector_id' => $loan->collector_id,
             'collector_name' => $loan->collector_name,
             'total_interest' => 0,
             'commission_40' => 0,
             'loans_count' => 0
           ];
         }
         $collectors_summary[$loan->collector_id]['total_interest'] += $loan->total_interest;
         $collectors_summary[$loan->collector_id]['commission_40'] += $loan->commission_40;
         $collectors_summary[$loan->collector_id]['loans_count']++;
       }
       
       // Log para debugging
       log_message('debug', 'Reports::get_collector_commissions_summary - Total préstamos enviados: ' . count($loans));

       $this->output->set_content_type('application/json');
       $this->output->set_header('Access-Control-Allow-Origin: *');
       
       echo json_encode([
         'loans' => $loans, // Cada préstamo como fila separada
         'collectors' => array_values($collectors_summary), // Resumen por cobrador
         'summary' => [
           'total_loans' => count($loans),
           'total_collectors' => count($collectors_summary),
           'completed_sends' => count($loans),
           'pending_sends' => 0,
           'total_to_pay' => $total_to_pay,
           'total_interest' => $total_interest_sum
         ]
       ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

     } catch (Exception $e) {
       log_message('error', 'Reports::get_collector_commissions_summary - Exception: ' . $e->getMessage());
       $this->output->set_content_type('application/json');
       $this->output->set_header('Access-Control-Allow-Origin: *');
       echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
     }
   }

   /**
    * Enviar reporte de comisiones al administrador
    */
   public function send_report()
   {
       // Configurar headers JSON
       $this->output->set_content_type('application/json');
       $this->output->set_header('Access-Control-Allow-Origin: *');

       // Solo permitir POST
       if ($this->input->method() !== 'post') {
           log_message('error', 'REPORTS: Método no permitido - método usado: ' . $this->input->method());
           $this->output->set_output(json_encode(['success' => false, 'message' => 'Método no permitido']));
           return;
       }

       // Verificar que el usuario esté logueado
       $current_user_id = $this->session->userdata('user_id');
       if (!$current_user_id) {
           log_message('error', 'REPORTS: Usuario no autenticado - session data: ' . json_encode($this->session->all_userdata()));
           $this->output->set_output(json_encode(['success' => false, 'message' => 'Usuario no autenticado']));
           return;
       }

       log_message('debug', 'REPORTS: Iniciando envío de reporte - user_id: ' . $current_user_id);

       try {
           // Obtener datos del POST
           $selected_loans_json = $this->input->post('selected_loans');
           $total_commission = $this->input->post('total_commission');
           $start_date = $this->input->post('start_date');
           $end_date = $this->input->post('end_date');
           $collector_id = $this->input->post('collector_id');

           log_message('debug', 'REPORTS: Datos POST recibidos - selected_loans_json: ' . ($selected_loans_json ? 'presente' : 'null') .
                      ', total_commission: ' . $total_commission .
                      ', start_date: ' . $start_date .
                      ', end_date: ' . $end_date .
                      ', collector_id: ' . $collector_id);

           if (!$selected_loans_json) {
               log_message('error', 'REPORTS: No se seleccionaron préstamos');
               $this->output->set_output(json_encode(['success' => false, 'message' => 'No se seleccionaron préstamos']));
               return;
           }

           $selected_loans = json_decode($selected_loans_json, true);
           if (json_last_error() !== JSON_ERROR_NONE) {
               log_message('error', 'REPORTS: Error decodificando JSON - error: ' . json_last_error_msg() . ', json: ' . $selected_loans_json);
               $this->output->set_output(json_encode(['success' => false, 'message' => 'Datos de préstamos inválidos - error JSON']));
               return;
           }

           if (empty($selected_loans)) {
               log_message('error', 'REPORTS: Array de préstamos vacío');
               $this->output->set_output(json_encode(['success' => false, 'message' => 'No hay préstamos válidos para enviar']));
               return;
           }

           log_message('debug', 'REPORTS: Préstamos decodificados correctamente - count: ' . count($selected_loans));

           // Obtener información del cobrador actual
           $collector_info = $this->user_m->get($current_user_id);
           $collector_name = $collector_info ? $collector_info->first_name . ' ' . $collector_info->last_name : 'Usuario desconocido';

           log_message('debug', 'REPORTS: Información del cobrador obtenida - name: ' . $collector_name);

           // Preparar datos para el reporte
           $report_data = [
               'collector_id' => $current_user_id,
               'collector_name' => $collector_name,
               'selected_loans' => $selected_loans,
               'total_commission' => $total_commission,
               'start_date' => $start_date,
               'end_date' => $end_date,
               'sent_at' => date('Y-m-d H:i:s'),
               'loan_count' => count($selected_loans)
           ];

           log_message('debug', 'REPORTS: Datos del reporte preparados - ' . json_encode($report_data));

           // Intentar enviar notificación por email al administrador
           try {
               $this->_send_report_notification($report_data);
               log_message('info', 'REPORTS: Notificación por email enviada exitosamente');
           } catch (Exception $email_error) {
               log_message('error', 'REPORTS: Error enviando email: ' . $email_error->getMessage());
               // No fallar por error de email, continuar con el proceso
           }

           // Crear notificación interna - FORZAR creación incluso si hay errores
           $notification_created = false;
           try {
               $this->_create_internal_notification($report_data);
               log_message('info', 'REPORTS: Notificación interna creada exitosamente');
               $notification_created = true;
           } catch (Exception $notification_error) {
               log_message('error', 'REPORTS: Error creando notificación interna: ' . $notification_error->getMessage());
               // Intentar crear notificación básica si falla la detallada
               try {
                   $this->_create_basic_notification($report_data);
                   log_message('info', 'REPORTS: Notificación básica creada exitosamente');
                   $notification_created = true;
               } catch (Exception $basic_error) {
                   log_message('error', 'REPORTS: Error creando notificación básica: ' . $basic_error->getMessage());
               }
           }

           // Si no se pudo crear notificación interna, al menos loggear que el reporte fue enviado
           if (!$notification_created) {
               log_message('warning', 'REPORTS: No se pudo crear notificación interna, pero el reporte fue procesado correctamente');
           }

           // Log del envío exitoso
           log_message('info', 'REPORTS: Reporte enviado exitosamente por cobrador ' . $collector_name .
                      ' - Préstamos: ' . count($selected_loans) .
                      ' - Comisión total: $' . $total_commission);

           $this->output->set_output(json_encode([
               'success' => true,
               'message' => 'Reporte enviado exitosamente al administrador',
               'data' => $report_data
           ]));

       } catch (Exception $e) {
           log_message('error', 'REPORTS: Error enviando reporte: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
           $this->output->set_output(json_encode([
               'success' => false,
               'message' => 'Error interno del servidor: ' . $e->getMessage()
           ]));
       }
   }

   /**
    * Enviar notificación por email del reporte
    */
   private function _send_report_notification($report_data)
   {
       // Verificar configuración de email en la base de datos o config
       $email_config = $this->config->item('email_config');
       if ($email_config && isset($email_config['smtp_host'])) {
           // Configurar email si hay configuración disponible
           $this->load->library('email');

           $config = array(
               'protocol' => $email_config['protocol'] ?? 'smtp',
               'smtp_host' => $email_config['smtp_host'],
               'smtp_port' => $email_config['smtp_port'] ?? 587,
               'smtp_user' => $email_config['smtp_user'] ?? '',
               'smtp_pass' => $email_config['smtp_pass'] ?? '',
               'mailtype' => 'html',
               'charset' => 'utf-8',
               'wordwrap' => TRUE
           );

           $this->email->initialize($config);

           // Preparar contenido del email
           $subject = 'Reporte de Comisiones - ' . $report_data['collector_name'];
           $message = $this->_generate_report_email_content($report_data);

           // Enviar email (cambiar destinatarios según configuración)
           $admin_email = $this->config->item('admin_email') ?: 'admin@prestamos.com';
           $this->email->from('sistema@prestamos.com', 'Sistema de Préstamos');
           $this->email->to($admin_email);
           $this->email->subject($subject);
           $this->email->message($message);

           // Intentar enviar (no fallar si no hay configuración de email)
           try {
               if ($this->email->send()) {
                   log_message('info', 'REPORTS: Email enviado exitosamente a ' . $admin_email);
               } else {
                   log_message('error', 'REPORTS: Error enviando email: ' . $this->email->print_debugger());
               }
           } catch (Exception $e) {
               log_message('error', 'REPORTS: Error enviando email: ' . $e->getMessage());
           }
       } else {
           // Si no hay configuración de email, solo loggear
           log_message('info', 'REPORTS: Email no configurado - Reporte registrado solo en logs');
       }

       // También crear una notificación interna en el sistema (si existe tabla de notificaciones)
       $this->_create_internal_notification($report_data);
   }

   /**
    * Generar contenido HTML del email del reporte
    */
   private function _generate_report_email_content($report_data)
   {
       $html = '
       <html>
       <head>
           <style>
               body { font-family: Arial, sans-serif; }
               .header { background-color: #007bff; color: white; padding: 20px; }
               .content { padding: 20px; }
               .table { border-collapse: collapse; width: 100%; }
               .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
               .table th { background-color: #f2f2f2; }
               .total { font-weight: bold; background-color: #e9ecef; }
           </style>
       </head>
       <body>
           <div class="header">
               <h2>Reporte de Comisiones del 40%</h2>
               <p>Enviado por: ' . htmlspecialchars($report_data['collector_name']) . '</p>
           </div>

           <div class="content">
               <h3>Información del Reporte</h3>
               <p><strong>Fecha de envío:</strong> ' . date('d/m/Y H:i:s') . '</p>
               <p><strong>Período:</strong> ' . ($report_data['start_date'] ?: 'Sin límite') . ' - ' . ($report_data['end_date'] ?: 'Sin límite') . '</p>
               <p><strong>Total de préstamos:</strong> ' . $report_data['loan_count'] . '</p>
               <p><strong>Comisión total del 40%:</strong> $' . number_format($report_data['total_commission'], 2, ',', '.') . '</p>

               <h3>Detalle de Préstamos</h3>
               <table class="table">
                   <thead>
                       <tr>
                           <th>ID Préstamo</th>
                           <th>Cliente</th>
                           <th>Interés Pagado</th>
                           <th>Comisión 40%</th>
                       </tr>
                   </thead>
                   <tbody>';

       foreach ($report_data['selected_loans'] as $loan) {
           $html .= '
                       <tr>
                           <td>' . htmlspecialchars($loan['loan_id']) . '</td>
                           <td>' . htmlspecialchars($loan['customer_name']) . '</td>
                           <td>$' . number_format($loan['interest'], 2, ',', '.') . '</td>
                           <td>$' . number_format($loan['commission'], 2, ',', '.') . '</td>
                       </tr>';
       }

       $html .= '
                       <tr class="total">
                           <td colspan="3"><strong>TOTAL</strong></td>
                           <td><strong>$' . number_format($report_data['total_commission'], 2, ',', '.') . '</strong></td>
                       </tr>
                   </tbody>
               </table>

               <p style="margin-top: 20px;">
                   Este reporte ha sido generado automáticamente por el sistema de préstamos.
                   Por favor, revise y procese las comisiones correspondientes.
               </p>
           </div>
       </body>
       </html>';

       return $html;
   }

   /**
    * Crear notificación interna en el sistema
    */
   private function _create_internal_notification($report_data)
   {
       // Verificar y crear tabla notifications si no existe
       if (!$this->db->table_exists('notifications')) {
           $this->db->query("
               CREATE TABLE notifications (
                   id INT AUTO_INCREMENT PRIMARY KEY,
                   user_id INT NOT NULL,
                   title VARCHAR(255) NOT NULL,
                   message TEXT,
                   type VARCHAR(50) DEFAULT 'info',
                   data TEXT,
                   is_read TINYINT(1) DEFAULT 0,
                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
               )
           ");
           log_message('info', 'REPORTS: Tabla notifications creada automáticamente');
       }

       // Crear notificación interna
       $notification_data = [
           'user_id' => 1, // Administrador (asumiendo ID 1)
           'title' => 'Nuevo Reporte de Comisiones Recibido',
           'message' => 'El cobrador ' . $report_data['collector_name'] . ' ha enviado un reporte de comisiones por $' . number_format($report_data['total_commission'], 2, ',', '.') . ' (' . $report_data['loan_count'] . ' préstamos)',
           'type' => 'report',
           'data' => json_encode($report_data),
           'is_read' => 0,
           'created_at' => date('Y-m-d H:i:s')
       ];

       try {
           $this->db->insert('notifications', $notification_data);
           log_message('info', 'REPORTS: Notificación interna creada para administrador');
       } catch (Exception $e) {
           log_message('error', 'REPORTS: Error creando notificación interna: ' . $e->getMessage());
           throw $e; // Re-lanzar para que sea manejado por el método llamador
       }

       // Verificar y crear tabla reports si no existe
       if (!$this->db->table_exists('reports')) {
           $this->db->query("
               CREATE TABLE reports (
                   id INT AUTO_INCREMENT PRIMARY KEY,
                   collector_id INT NOT NULL,
                   collector_name VARCHAR(255) NOT NULL,
                   loan_count INT NOT NULL,
                   total_commission DECIMAL(10,2) NOT NULL,
                   start_date DATE,
                   end_date DATE,
                   selected_loans TEXT,
                   status ENUM('received', 'approved', 'rejected') DEFAULT 'received',
                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
               )
           ");
           log_message('info', 'REPORTS: Tabla reports creada automáticamente');
       }

       // Registrar en tabla de reportes
       $report_record = [
           'collector_id' => $report_data['collector_id'],
           'collector_name' => $report_data['collector_name'],
           'loan_count' => $report_data['loan_count'],
           'total_commission' => $report_data['total_commission'],
           'start_date' => $report_data['start_date'],
           'end_date' => $report_data['end_date'],
           'selected_loans' => json_encode($report_data['selected_loans']),
           'status' => 'received',
           'created_at' => date('Y-m-d H:i:s')
       ];

       try {
           $this->db->insert('reports', $report_record);
           log_message('info', 'REPORTS: Reporte guardado en tabla reports');
       } catch (Exception $e) {
           log_message('error', 'REPORTS: Error guardando reporte en BD: ' . $e->getMessage());
           throw $e; // Re-lanzar para que sea manejado por el método llamador
       }
   }

   /**
    * Crear notificación básica como respaldo
    */
   private function _create_basic_notification($report_data)
   {
       // Crear tabla de notificaciones si no existe
       $this->db->query("
           CREATE TABLE IF NOT EXISTS notifications (
               id INT AUTO_INCREMENT PRIMARY KEY,
               user_id INT NOT NULL,
               title VARCHAR(255) NOT NULL,
               message TEXT,
               type VARCHAR(50) DEFAULT 'info',
               data TEXT,
               is_read TINYINT(1) DEFAULT 0,
               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
           )
       ");

       // Crear tabla reports si no existe
       $this->db->query("
           CREATE TABLE IF NOT EXISTS reports (
               id INT AUTO_INCREMENT PRIMARY KEY,
               collector_id INT NOT NULL,
               collector_name VARCHAR(255) NOT NULL,
               loan_count INT NOT NULL,
               total_commission DECIMAL(10,2) NOT NULL,
               start_date DATE,
               end_date DATE,
               selected_loans TEXT,
               status ENUM('received', 'approved', 'rejected') DEFAULT 'received',
               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
           )
       ");

       $notification_data = [
           'user_id' => 1, // Administrador
           'title' => 'Reporte de Comisiones Recibido',
           'message' => 'Cobrador: ' . $report_data['collector_name'] . ' - Monto: $' . number_format($report_data['total_commission'], 2, ',', '.') . ' - Préstamos: ' . $report_data['loan_count'],
           'type' => 'report',
           'data' => json_encode(['collector_id' => $report_data['collector_id'], 'total_commission' => $report_data['total_commission']]),
           'is_read' => 0,
           'created_at' => date('Y-m-d H:i:s')
       ];

       $this->db->insert('notifications', $notification_data);
       log_message('info', 'REPORTS: Notificación básica creada exitosamente');

       // También guardar el reporte
       $report_record = [
           'collector_id' => $report_data['collector_id'],
           'collector_name' => $report_data['collector_name'],
           'loan_count' => $report_data['loan_count'],
           'total_commission' => $report_data['total_commission'],
           'start_date' => $report_data['start_date'],
           'end_date' => $report_data['end_date'],
           'selected_loans' => json_encode($report_data['selected_loans']),
           'status' => 'received',
           'created_at' => date('Y-m-d H:i:s')
       ];

       $this->db->insert('reports', $report_record);
       log_message('info', 'REPORTS: Reporte básico guardado exitosamente');
   }

   /**
    * Método auxiliar para obtener resumen de comisiones de cobradores
    */
   private function _get_collector_commissions_summary($validated_dates, $collector_id = null)
   {
     // Obtener directamente desde collector_commissions los cobradores con comisiones enviadas
     // Usar consulta SQL directa para mayor control
     $sql = "
       SELECT 
         cc.user_id as collector_id,
       u.first_name,
       u.last_name,
         SUM(cc.commission) as commission_40,
         SUM(cc.amount) as total_interest,
         MAX(cc.sent_at) as sent_at,
         COUNT(DISTINCT cc.loan_id) as loans_count
       FROM collector_commissions cc
       INNER JOIN users u ON u.id = cc.user_id
       WHERE cc.status = 'enviado'
     ";
     
     // Aplicar filtros de fecha de forma más flexible
     if (!empty($validated_dates) && isset($validated_dates['start']) && isset($validated_dates['end'])) {
       // Si hay fechas, buscar comisiones cuyo período se solape con el rango seleccionado
       // O que no tengan período definido (NULL)
       $sql .= " AND (
         (cc.period_start <= '" . $this->db->escape_str($validated_dates['end']) . "' AND cc.period_end >= '" . $this->db->escape_str($validated_dates['start']) . "')
         OR cc.period_start IS NULL 
         OR cc.period_end IS NULL
       )";
     } elseif (!empty($validated_dates) && isset($validated_dates['start'])) {
       $sql .= " AND (cc.period_end >= '" . $this->db->escape_str($validated_dates['start']) . "' OR cc.period_end IS NULL)";
     } elseif (!empty($validated_dates) && isset($validated_dates['end'])) {
       $sql .= " AND (cc.period_start <= '" . $this->db->escape_str($validated_dates['end']) . "' OR cc.period_start IS NULL)";
     }
     // Si no hay fechas, no aplicar filtro de fechas - mostrar todos los enviados

     // Filtrar por cobrador específico si se proporciona
     if ($collector_id) {
       $sql .= " AND cc.user_id = " . (int)$collector_id;
     }

     $sql .= " GROUP BY cc.user_id ORDER BY u.first_name, u.last_name";

     // Ejecutar consulta
     $query = $this->db->query($sql);
     $results = $query->result();
     
     // Log para debugging
     log_message('debug', 'Reports::_get_collector_commissions_summary - Cobradores encontrados: ' . count($results));
     if (!empty($results)) {
       foreach ($results as $r) {
         log_message('debug', 'Reports::_get_collector_commissions_summary - Cobrador: ' . $r->first_name . ' ' . $r->last_name . ' - Comisión: ' . $r->commission_40);
       }
     }

     // Si se filtra por un cobrador específico y no hay resultados,
     // devolver un array vacío
     if ($collector_id && empty($results)) {
       return [];
     }

     // Procesar resultados - todos ya están enviados
     foreach ($results as &$result) {
       $result->send_status = 'enviado';
       $result->last_payment_date = $result->sent_at; // Usar sent_at como fecha de referencia
       
       // Asegurar que los valores numéricos sean correctos
       $result->total_interest = floatval($result->total_interest ?? 0);
       $result->commission_40 = floatval($result->commission_40 ?? 0);

       // Crear badge de estado
       $result->status_badge = '<span class="badge badge-success">Enviado</span>';
       if ($result->sent_at) {
         $result->status_badge .= '<br><small class="text-muted">' . date('d/m/Y H:i', strtotime($result->sent_at)) . '</small>';
       }

       // Formatear montos
       $result->total_interest_formatted = '$' . number_format($result->total_interest, 0, ',', '.');
       $result->commission_40_formatted = '$' . number_format($result->commission_40, 0, ',', '.');
       $result->collector_name = trim($result->first_name . ' ' . $result->last_name);
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
    * Método para enviar reportes múltiples de cobradores (bulk submission)
    */
   public function send_bulk_reports()
   {
       log_message('debug', 'Reports::send_bulk_reports called');

       // Verificar método POST
       if ($this->input->method() !== 'post') {
           $this->output->set_content_type('application/json');
           $this->output->set_output(json_encode(['success' => false, 'message' => 'Método no permitido']));
           return;
       }

       // Verificar usuario logueado
       $current_user_id = $this->session->userdata('user_id');
       if (!$current_user_id) {
           log_message('error', 'REPORTS: Usuario no logueado intentando enviar reportes');
           $this->output->set_content_type('application/json');
           $this->output->set_output(json_encode(['success' => false, 'message' => 'Usuario no autorizado']));
           return;
       }

       try {
           // Obtener y validar datos del POST
           $selected_reports = $this->input->post('selected_reports');
           $total_commission = $this->input->post('total_commission');
           $start_date = $this->input->post('start_date');
           $end_date = $this->input->post('end_date');

           // Validaciones básicas
           if (empty($selected_reports)) {
               throw new Exception('No se seleccionaron reportes para enviar');
           }

           if (!is_numeric($total_commission) || $total_commission <= 0) {
               throw new Exception('Monto total de comisión inválido');
           }

           // Decodificar JSON de reportes seleccionados
           $reports_data = json_decode($selected_reports, true);
           if (json_last_error() !== JSON_ERROR_NONE) {
               throw new Exception('Datos de reportes inválidos');
           }

           if (empty($reports_data) || !is_array($reports_data)) {
               throw new Exception('No hay reportes válidos para procesar');
           }

           // Validar fechas si se proporcionan
           $validated_dates = null;
           if ($start_date && $end_date) {
               $validated_dates = $this->_validate_date_filters($start_date, $end_date);
           }

           // Procesar cada reporte seleccionado
           $processed_reports = [];
           $total_processed_commission = 0;

           foreach ($reports_data as $report) {
               // Validar datos del reporte individual
               if (!isset($report['collector_id']) || !isset($report['commission'])) {
                   log_message('warning', 'REPORTS: Reporte inválido omitido: ' . json_encode($report));
                   continue;
               }

               $collector_id = (int)$report['collector_id'];
               $commission = (float)$report['commission'];

               if ($collector_id <= 0 || $commission < 0) {
                   log_message('warning', 'REPORTS: Datos inválidos en reporte: collector_id=' . $collector_id . ', commission=' . $commission);
                   continue;
               }

               // Preparar datos para el reporte
               $report_data = [
                   'collector_id' => $collector_id,
                   'collector_name' => $report['collector_name'] ?? 'Cobrador ' . $collector_id,
                   'loan_count' => 1, // Cada reporte representa un cobrador
                   'total_commission' => $commission,
                   'start_date' => $validated_dates['start'] ?? null,
                   'end_date' => $validated_dates['end'] ?? null,
                   'selected_loans' => json_encode([]), // Para compatibilidad
                   'loan_count' => count($reports_data),
                   'selected_loans' => json_encode($reports_data)
               ];

               // Crear notificación interna
               $this->_create_internal_notification($report_data);

               $processed_reports[] = $report_data;
               $total_processed_commission += $commission;

               log_message('info', 'REPORTS: Reporte procesado para cobrador ' . $collector_id . ' - Comisión: $' . $commission);
           }

           if (empty($processed_reports)) {
               throw new Exception('No se pudieron procesar los reportes seleccionados');
           }

           // Respuesta exitosa
           $response = [
               'success' => true,
               'message' => 'Reportes enviados exitosamente',
               'processed_count' => count($processed_reports),
               'total_commission' => $total_processed_commission,
               'reports' => $processed_reports
           ];

           log_message('info', 'REPORTS: Bulk reports enviado exitosamente - ' . count($processed_reports) . ' reportes, total $' . $total_processed_commission);

           $this->output->set_content_type('application/json');
           $this->output->set_output(json_encode($response));

       } catch (Exception $e) {
           log_message('error', 'REPORTS: Error en send_bulk_reports: ' . $e->getMessage());

           $this->output->set_content_type('application/json');
           $this->output->set_output(json_encode([
               'success' => false,
               'message' => 'Error al procesar los reportes: ' . $e->getMessage()
           ]));
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

  /**
   * Asegurar que las columnas necesarias existan en collector_commissions (versión mysqli)
   */
  private function _ensure_commission_columns_mysqli($conn)
  {
    try {
      // Verificar si existe la columna status
      $result = $conn->query("SHOW COLUMNS FROM collector_commissions LIKE 'status'");
      if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `status` enum('pendiente','enviado','pagado') DEFAULT 'pendiente' 
                     COMMENT 'Estado de la comisión' AFTER `commission`");
      }

      // Verificar si existe la columna sent_at
      $result = $conn->query("SHOW COLUMNS FROM collector_commissions LIKE 'sent_at'");
      if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `sent_at` datetime DEFAULT NULL 
                     COMMENT 'Fecha de envío al administrador' AFTER `status`");
      }

      // Verificar si existe la columna period_start
      $result = $conn->query("SHOW COLUMNS FROM collector_commissions LIKE 'period_start'");
      if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `period_start` date DEFAULT NULL 
                     COMMENT 'Inicio del período' AFTER `sent_at`");
      }

      // Verificar si existe la columna period_end
      $result = $conn->query("SHOW COLUMNS FROM collector_commissions LIKE 'period_end'");
      if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `period_end` date DEFAULT NULL 
                     COMMENT 'Fin del período' AFTER `period_start`");
      }

      // Verificar si existe la columna updated_at
      $result = $conn->query("SHOW COLUMNS FROM collector_commissions LIKE 'updated_at'");
      if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                     AFTER `period_end`");
      }

      // Agregar índices si no existen
      $indexes = $conn->query("SHOW INDEXES FROM collector_commissions WHERE Key_name = 'idx_status'");
      if ($indexes && $indexes->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions ADD INDEX `idx_status` (`status`)");
      }

      $indexes = $conn->query("SHOW INDEXES FROM collector_commissions WHERE Key_name = 'idx_period'");
      if ($indexes && $indexes->num_rows == 0) {
        $conn->query("ALTER TABLE collector_commissions ADD INDEX `idx_period` (`period_start`, `period_end`)");
      }

      // Actualizar registros existentes sin status
      $conn->query("UPDATE collector_commissions SET status = 'pendiente' WHERE status IS NULL OR status = ''");

    } catch (Exception $e) {
      // No lanzar excepción, continuar con el proceso
      error_log('Error asegurando columnas de collector_commissions: ' . $e->getMessage());
    }
  }

  /**
   * Asegurar que las columnas necesarias existan en collector_commissions (versión CodeIgniter)
   */
  private function _ensure_commission_columns($db = null)
  {
    try {
      // Usar el parámetro o $this->db por defecto
      $db_instance = $db ? $db : $this->db;
      
      // Verificar si existe la columna status
      $result = $db_instance->query("SHOW COLUMNS FROM collector_commissions LIKE 'status'");
      if ($result && $result->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `status` enum('pendiente','enviado','pagado') DEFAULT 'pendiente' 
                     COMMENT 'Estado de la comisión' AFTER `commission`");
      }

      // Verificar si existe la columna sent_at
      $result = $db_instance->query("SHOW COLUMNS FROM collector_commissions LIKE 'sent_at'");
      if ($result && $result->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `sent_at` datetime DEFAULT NULL 
                     COMMENT 'Fecha de envío al administrador' AFTER `status`");
      }

      // Verificar si existe la columna period_start
      $result = $db_instance->query("SHOW COLUMNS FROM collector_commissions LIKE 'period_start'");
      if ($result && $result->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `period_start` date DEFAULT NULL 
                     COMMENT 'Inicio del período' AFTER `sent_at`");
      }

      // Verificar si existe la columna period_end
      $result = $db_instance->query("SHOW COLUMNS FROM collector_commissions LIKE 'period_end'");
      if ($result && $result->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `period_end` date DEFAULT NULL 
                     COMMENT 'Fin del período' AFTER `period_start`");
      }

      // Verificar si existe la columna updated_at
      $result = $db_instance->query("SHOW COLUMNS FROM collector_commissions LIKE 'updated_at'");
      if ($result && $result->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions 
                     ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                     AFTER `period_end`");
      }

      // Agregar índices si no existen
      $indexes = $db_instance->query("SHOW INDEXES FROM collector_commissions WHERE Key_name = 'idx_status'");
      if ($indexes && $indexes->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions ADD INDEX `idx_status` (`status`)");
      }

      $indexes = $db_instance->query("SHOW INDEXES FROM collector_commissions WHERE Key_name = 'idx_period'");
      if ($indexes && $indexes->num_rows() == 0) {
        $db_instance->query("ALTER TABLE collector_commissions ADD INDEX `idx_period` (`period_start`, `period_end`)");
      }

      // Actualizar registros existentes sin status
      $db_instance->query("UPDATE collector_commissions SET status = 'pendiente' WHERE status IS NULL OR status = ''");

    } catch (Exception $e) {
      log_message('error', 'Error asegurando columnas de collector_commissions: ' . $e->getMessage());
      // No lanzar excepción, continuar con el proceso
    }
  }
}

/* End of file Reports.php */
/* Location: ./application/controllers/admin/Reports.php */