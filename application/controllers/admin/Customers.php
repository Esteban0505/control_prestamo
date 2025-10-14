<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('customers_m');
    $this->load->library('form_validation');
  }

  public function index()
  {
    $data['customers'] = $this->customers_m->get();
    $data['subview'] = 'admin/customers/index';
    $this->load->view('admin/_main_layout', $data);
  }

  public function edit($id = NULL)
  {
    if ($id) {
      $data['customer'] = $this->customers_m->get($id);
      $data['provinces'] = $this->customers_m->get_editProvinces($data['customer']->department_id);
      $data['districts'] = $this->customers_m->get_editDistricts($data['customer']->province_id);
    } else {
      $data['customer'] = $this->customers_m->get_new();
    }

    $data['departments'] = $this->customers_m->get_departments();
    $data['users'] = $this->customers_m->get_active_users();

    $rules = $this->customers_m->customer_rules;
   
    $this->form_validation->set_rules($rules);

    if ($this->form_validation->run() == TRUE) {

      $cst_data = $this->customers_m->array_from_post(['dni','first_name', 'last_name', 'gender', 'department_id', 'province_id', 'district_id', 'mobile', 'address', 'phone', 'user_id', 'ruc', 'company', 'tipo_cliente']);

      $save_result = $this->customers_m->save($cst_data, $id);

      if ($save_result === false) {
        $this->session->set_flashdata('error', '⚠️ El número de cédula ingresado ya existe. Verifica la información antes de continuar.');
      } else {
        if ($id) {
          $this->session->set_flashdata('msg', 'Cliente editado correctamente');
        } else {
          $this->session->set_flashdata('msg', 'Cliente agregado correctamente');
        }
        redirect('admin/customers');
      }

    }

    $data['subview'] = 'admin/customers/edit';
    $this->load->view('admin/_main_layout', $data);
  }

  public function ajax_getProvinces($dp_id)
  {
    echo $this->customers_m->get_provinces($dp_id);
  }

  public function ajax_getDistricts($pr_id)
  {
    echo $this->customers_m->get_districts($pr_id);
  }

  public function check_dni_ajax()
  {
    $dni = $this->input->post('dni');
    $id = $this->input->post('id');

    if (!$dni) {
      echo json_encode(['exists' => false]);
      return;
    }

    $this->db->where('dni', $dni);
    if ($id) {
      $this->db->where('id !=', $id);
    }
    $query = $this->db->get('customers');
    $exists = $query->num_rows() > 0;

    echo json_encode(['exists' => $exists]);
  }

  public function overdue()
  {
    $this->load->model('payments_m');
    $this->load->library('pagination');

    // Aplicar penalizaciones automáticas si es necesario
    $this->_apply_automatic_penalties();

    // Configuración de paginación
    $config['base_url'] = site_url('admin/customers/overdue');
    $config['per_page'] = 25; // 25 registros por página
    $config['uri_segment'] = 4;
    $config['reuse_query_string'] = TRUE;

    // Aplicar filtros si existen
    $search = $this->input->get('search');
    $risk_level = $this->input->get('risk_level');
    $min_amount = $this->input->get('min_amount');
    $max_amount = $this->input->get('max_amount');

    // Obtener total de registros para paginación
    $total_rows = $this->payments_m->count_overdue_clients($search, $risk_level, $min_amount, $max_amount);
    $config['total_rows'] = $total_rows;

    $this->pagination->initialize($config);

    // Obtener registros paginados
    $page = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
    $data['clients'] = $this->payments_m->get_overdue_clients_paginated($search, $risk_level, $min_amount, $max_amount, $config['per_page'], $page);

    // Obtener estadísticas optimizadas para el dashboard
    $data['statistics'] = $this->payments_m->get_overdue_statistics();

    $data['title'] = 'Clientes con Pagos Vencidos';
    $data['pagination_links'] = $this->pagination->create_links();
    $data['total_records'] = $total_rows;
    $data['current_page'] = $page;
    $data['per_page'] = $config['per_page'];
    $data['subview'] = 'admin/clients/overdue';
    $this->load->view('admin/_main_layout', $data);
  }

  public function export_overdue()
  {
    $this->load->model('payments_m');
    $this->load->library('excel');

    // Aplicar filtros
    $search = $this->input->post('search');
    $risk_level = $this->input->post('risk_level');
    $min_amount = $this->input->post('min_amount');
    $max_amount = $this->input->post('max_amount');
    $format = $this->input->post('format');

    $clients = $this->payments_m->get_overdue_clients($search, $risk_level, $min_amount, $max_amount);

    if ($format == 'excel') {
      // Crear archivo Excel
      $this->excel->setActiveSheetIndex(0);
      $sheet = $this->excel->getActiveSheet();
      $sheet->setTitle('Clientes Vencidos');

      // Headers
      $sheet->setCellValue('A1', 'Nombre del Cliente');
      $sheet->setCellValue('B1', 'Cédula');
      $sheet->setCellValue('C1', 'Cuotas Vencidas');
      $sheet->setCellValue('D1', 'Total Adeudado');
      $sheet->setCellValue('E1', 'Máx. Días Atraso');
      $sheet->setCellValue('F1', 'Nivel de Riesgo');

      // Datos
      $row = 2;
      foreach ($clients as $client) {
        $risk = $client->max_dias_atraso >= 60 ? 'Alto' : ($client->max_dias_atraso >= 30 ? 'Medio' : 'Bajo');

        $sheet->setCellValue('A'.$row, $client->client_name);
        $sheet->setCellValue('B'.$row, $client->client_cedula);
        $sheet->setCellValue('C'.$row, $client->cuotas_vencidas);
        $sheet->setCellValue('D'.$row, $client->total_adeudado);
        $sheet->setCellValue('E'.$row, $client->max_dias_atraso);
        $sheet->setCellValue('F'.$row, $risk);
        $row++;
      }

      // Estilos
      $headerStyle = array(
        'font' => array('bold' => true),
        'fill' => array('type' => 'solid', 'color' => array('rgb' => 'CCCCCC'))
      );
      $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

      // Auto-size columns
      foreach(range('A','F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
      }

      // Generar archivo
      $filename = 'clientes_vencidos_' . date('Y-m-d_H-i-s') . '.xlsx';
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="' . $filename . '"');
      header('Cache-Control: max-age=0');

      $writer = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
      $writer->save('php://output');
      exit;
    }
  }

  public function get_client_details()
  {
    $this->load->model('customers_m');
    $this->load->model('payments_m');

    $customer_id = $this->input->post('customer_id');
    $customer = $this->customers_m->get($customer_id);

    if (!$customer) {
      echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
      return;
    }

    // Obtener préstamos del cliente
    $this->db->select('l.*, co.name as coin_name, CONCAT(u.first_name, " ", u.last_name) as asesor_name');
    $this->db->from('loans l');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->join('users u', 'u.id = l.assigned_user_id', 'left');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->where('l.status', 1);
    $loans = $this->db->get()->result();

    // Generar HTML para el modal
    $html = '<div class="row">';
    $html .= '<div class="col-md-6">';
    $html .= '<h6>Información del Cliente</h6>';
    $html .= '<p><strong>Nombre:</strong> ' . htmlspecialchars($customer->first_name . ' ' . $customer->last_name) . '</p>';
    $html .= '<p><strong>Cédula:</strong> ' . htmlspecialchars($customer->dni) . '</p>';
    $html .= '<p><strong>Teléfono:</strong> ' . htmlspecialchars($customer->mobile) . '</p>';
    $html .= '<p><strong>Dirección:</strong> ' . htmlspecialchars($customer->address) . '</p>';
    $html .= '</div>';

    $html .= '<div class="col-md-6">';
    $html .= '<h6>Préstamos Activos</h6>';
    if (!empty($loans)) {
      foreach ($loans as $loan) {
        $html .= '<div class="border p-2 mb-2">';
        $html .= '<p><strong>ID Préstamo:</strong> ' . $loan->id . '</p>';
        $html .= '<p><strong>Monto:</strong> $' . number_format($loan->credit_amount, 2, ',', '.') . ' ' . $loan->coin_name . '</p>';
        $html .= '<p><strong>Colaborador:</strong> ' . htmlspecialchars($loan->asesor_name) . '</p>';
        $html .= '<p><strong>Estado:</strong> <span class="badge badge-warning">Activo</span></p>';
        $html .= '</div>';
      }
    } else {
      $html .= '<p>No hay préstamos activos</p>';
    }
    $html .= '</div>';
    $html .= '</div>';

    echo json_encode(['success' => true, 'html' => $html]);
  }

  public function send_notification()
  {
    $customer_id = $this->input->post('customer_id');

    // Aquí implementarías el envío de notificaciones (email, SMS, etc.)
    // Por ahora solo simulamos el envío

    $this->db->where('id', $customer_id);
    $customer = $this->db->get('customers')->row();

    if ($customer) {
      // Log de notificación enviada
      log_message('info', 'Notificación enviada a cliente: ' . $customer->first_name . ' ' . $customer->last_name . ' (ID: ' . $customer_id . ')');

      echo json_encode(['success' => true, 'message' => 'Notificación enviada exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
    }
  }

  public function apply_penalty()
  {
    $this->load->model('payments_m');

    $customer_id = $this->input->post('customer_id');

    // Aplicar penalización automática
    $result = $this->payments_m->apply_penalty_to_customer($customer_id);

    if ($result) {
      echo json_encode(['success' => true, 'message' => 'Penalización aplicada exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Error al aplicar penalización']);
    }
  }

  /**
   * Aplica penalizaciones automáticas a clientes con mora >60 días
   */
  private function _apply_automatic_penalties()
  {
    $this->load->model('payments_m');

    // Ejecutar penalizaciones automáticas
    $penalties_applied = $this->payments_m->apply_automatic_penalties();

    if ($penalties_applied > 0) {
      log_message('info', 'Penalizaciones automáticas aplicadas: ' . $penalties_applied . ' préstamos castigados');
    }
  }
  public function get_loan_details()
  {
    $this->load->model('loans_m');
    $this->load->model('payments_m');

    $loan_ids = $this->input->post('loan_ids');
    $loan_ids_array = explode(',', $loan_ids);

    $loans = [];
    foreach ($loan_ids_array as $loan_id) {
      $loan_id = trim($loan_id);
      if (!empty($loan_id)) {
        $loan = $this->loans_m->get_loan($loan_id);
        if ($loan) {
          // Obtener TODAS las cuotas del préstamo (no solo pendientes)
          $loan->all_quotas = $this->payments_m->get_quotasCst($loan_id);
  
          // También obtener cuotas pendientes para compatibilidad
          if (is_array($loan->all_quotas)) {
            $loan->pending_quotas = array_filter($loan->all_quotas, function($quota) {
              return isset($quota->status) && $quota->status == 1; // Solo cuotas no pagadas
            });
          } else {
            $loan->all_quotas = [];
            $loan->pending_quotas = [];
          }

          $loans[] = $loan;
        }
      }
    }

    if (!empty($loans)) {
      echo json_encode(['success' => true, 'loans' => $loans]);
    } else {
      echo json_encode(['success' => false, 'error' => 'No se encontraron préstamos']);
    }
  }

  public function send_bulk_notifications()
  {
    $this->load->model('payments_m');

    $risk_level = $this->input->post('risk_level');
    $message_type = $this->input->post('message_type');

    // Obtener clientes según nivel de riesgo
    $clients = $this->payments_m->get_clients_by_risk_level($risk_level);

    $sent_count = 0;
    $errors = [];

    foreach ($clients as $client) {
      try {
        // Aquí implementarías el envío real de notificaciones
        // Por ahora simulamos el envío
        log_message('info', 'Notificación masiva enviada a cliente: ' . $client->client_name . ' (ID: ' . $client->customer_id . ') - Tipo: ' . $message_type);

        // Simular envío de email/SMS
        $this->_send_notification($client, $message_type);

        $sent_count++;
      } catch (Exception $e) {
        $errors[] = 'Error con cliente ' . $client->client_name . ': ' . $e->getMessage();
      }
    }

    echo json_encode([
      'success' => true,
      'sent_count' => $sent_count,
      'errors' => $errors,
      'message' => 'Notificaciones enviadas: ' . $sent_count
    ]);
  }

  private function _send_notification($client, $message_type)
  {
    // Simulación de envío de notificaciones
    // En producción, aquí iría la integración con servicios de email/SMS

    $messages = [
      'reminder' => "Recordatorio: Tiene pagos pendientes por $" . number_format($client->total_adeudado, 2, ',', '.'),
      'warning' => "Advertencia: Sus pagos están atrasados. Contacte con nosotros para evitar penalizaciones.",
      'penalty' => "Penalización aplicada: Su cuenta ha sido marcada como de alto riesgo."
    ];

    $message = isset($messages[$message_type]) ? $messages[$message_type] : 'Notificación de pagos pendientes';

    // Log del envío (en producción enviar email/SMS real)
    log_message('info', 'Notificación enviada - Cliente: ' . $client->client_name . ', Tipo: ' . $message_type . ', Mensaje: ' . $message);

    return true;
  }

  public function get_alerts_summary()
  {
    $this->load->model('payments_m');

    $alerts = [
      'high_risk_count' => $this->payments_m->count_clients_by_risk('high'),
      'medium_risk_count' => $this->payments_m->count_clients_by_risk('medium'),
      'new_overdue_today' => $this->payments_m->count_new_overdue_today(),
      'total_overdue_amount' => $this->payments_m->get_total_overdue_amount()
    ];

    echo json_encode(['success' => true, 'alerts' => $alerts]);
  }

  public function reports()
  {
    $this->load->model('payments_m');

    // Obtener datos para reportes
    $data['monthly_trends'] = $this->payments_m->get_overdue_trends_by_month();
    $data['risk_distribution'] = $this->payments_m->get_risk_distribution();
    $data['top_overdue_clients'] = $this->payments_m->get_top_overdue_clients(10);
    $data['recovery_rate'] = $this->payments_m->get_recovery_rate();

    $data['title'] = 'Reportes de Mora y Recuperación';
    $data['subview'] = 'admin/clients/reports';
    $this->load->view('admin/_main_layout', $data);
  }

  public function get_report_data()
  {
    $this->load->model('payments_m');

    $data = [
      'monthly_trends' => $this->payments_m->get_overdue_trends_by_month(),
      'risk_distribution' => $this->payments_m->get_risk_distribution(),
      'top_clients' => $this->payments_m->get_top_overdue_clients(10),
      'total_clients' => $this->payments_m->count_overdue_clients(),
      'total_amount' => $this->payments_m->get_total_overdue_amount(),
      'recovery_rate' => $this->payments_m->get_recovery_rate(),
      'avg_days_overdue' => $this->payments_m->get_overdue_summary()['avg_days_overdue'] ?? 0
    ];

    echo json_encode(['success' => true, 'data' => $data]);
  }

  public function collection_tracking()
  {
    $this->load->model('payments_m');

    $data['title'] = 'Seguimiento de Cobranza';
    $data['pending_followups'] = $this->payments_m->get_pending_followups();
    $data['subview'] = 'admin/clients/collection_tracking';
    $this->load->view('admin/_main_layout', $data);
  }

  public function assign_collector()
  {
    $customer_id = $this->input->post('customer_id');
    $user_id = $this->input->post('user_id');

    $this->load->model('payments_m');
    $result = $this->payments_m->assign_collector($customer_id, $user_id);

    if ($result) {
      echo json_encode(['success' => true, 'message' => 'Cobrador asignado exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Error al asignar cobrador']);
    }
  }

  public function log_collection_action()
  {
    $tracking_id = $this->input->post('tracking_id');
    $action_data = [
      'action_type' => $this->input->post('action_type'),
      'action_description' => $this->input->post('action_description'),
      'contact_result' => $this->input->post('contact_result'),
      'amount_collected' => $this->input->post('amount_collected') ?: 0,
      'next_action_date' => $this->input->post('next_action_date'),
      'notes' => $this->input->post('notes'),
      'performed_by' => $this->session->userdata('user_id') ?? null
    ];

    $this->load->model('payments_m');
    $result = $this->payments_m->log_collection_action($tracking_id, $action_data);

    if ($result) {
      echo json_encode(['success' => true, 'message' => 'Acción registrada exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Error al registrar acción']);
    }
  }

  public function get_collection_details()
  {
    $customer_id = $this->input->post('customer_id');

    $this->load->model('payments_m');
    $tracking = $this->payments_m->get_collection_tracking($customer_id);

    if ($tracking) {
      echo json_encode(['success' => true, 'tracking' => $tracking]);
    } else {
      echo json_encode(['success' => false, 'error' => 'No se encontró seguimiento para este cliente']);
    }
  }

  public function update_collection_status()
  {
    $customer_id = $this->input->post('customer_id');
    $status = $this->input->post('status');
    $notes = $this->input->post('notes');

    $this->load->model('payments_m');
    $this->payments_m->update_collection_status($customer_id, $status, $notes);

    echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
  }

  public function export_overdue_report()
  {
    $this->load->model('payments_m');
    $this->load->library('excel');

    $type = $this->input->post('report_type');
    $start_date = $this->input->post('start_date');
    $end_date = $this->input->post('end_date');

    // Crear archivo Excel con múltiples hojas
    $this->excel->setActiveSheetIndex(0);

    // Hoja 1: Resumen Ejecutivo
    $sheet1 = $this->excel->getActiveSheet();
    $sheet1->setTitle('Resumen Ejecutivo');

    $summary_data = $this->payments_m->get_overdue_summary($start_date, $end_date);

    $sheet1->setCellValue('A1', 'REPORTE DE MORA Y RECUPERACIÓN');
    $sheet1->setCellValue('A3', 'Período:');
    $sheet1->setCellValue('B3', $start_date . ' - ' . $end_date);
    $sheet1->setCellValue('A5', 'Total Clientes Morosos:');
    $sheet1->setCellValue('B5', $summary_data['total_clients']);
    $sheet1->setCellValue('A6', 'Monto Total Adeudado:');
    $sheet1->setCellValue('B6', '$' . number_format($summary_data['total_amount'], 2));
    $sheet1->setCellValue('A7', 'Tasa de Recuperación:');
    $sheet1->setCellValue('B7', number_format($summary_data['recovery_rate'], 2) . '%');

    // Hoja 2: Tendencias Mensuales
    $this->excel->createSheet();
    $sheet2 = $this->excel->setActiveSheetIndex(1);
    $sheet2->setTitle('Tendencias Mensuales');

    $trends = $this->payments_m->get_overdue_trends_by_month();
    $sheet2->setCellValue('A1', 'Mes');
    $sheet2->setCellValue('B1', 'Clientes Morosos');
    $sheet2->setCellValue('C1', 'Monto Adeudado');

    $row = 2;
    foreach ($trends as $trend) {
      $sheet2->setCellValue('A'.$row, $trend->month);
      $sheet2->setCellValue('B'.$row, $trend->clients_count);
      $sheet2->setCellValue('C'.$row, $trend->total_amount);
      $row++;
    }

    // Hoja 3: Distribución por Riesgo
    $this->excel->createSheet();
    $sheet3 = $this->excel->setActiveSheetIndex(2);
    $sheet3->setTitle('Distribución por Riesgo');

    $distribution = $this->payments_m->get_risk_distribution();
    $sheet3->setCellValue('A1', 'Nivel de Riesgo');
    $sheet3->setCellValue('B1', 'Cantidad de Clientes');
    $sheet3->setCellValue('C1', 'Monto Adeudado');
    $sheet3->setCellValue('D1', 'Porcentaje');

    $row = 2;
    foreach ($distribution as $dist) {
      $sheet3->setCellValue('A'.$row, $dist->risk_level);
      $sheet3->setCellValue('B'.$row, $dist->clients_count);
      $sheet3->setCellValue('C'.$row, $dist->total_amount);
      $sheet3->setCellValue('D'.$row, number_format($dist->percentage, 2) . '%');
      $row++;
    }

    // Generar archivo
    $filename = 'reporte_mora_' . date('Y-m-d_H-i-s') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
    $writer->save('php://output');
    exit;
  }

}

/* End of file Customers.php */
/* Location: ./application/controllers/admin/Customers.php */