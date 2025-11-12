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
    // Obtener parámetros de paginación y filtros
    $page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
    $per_page = $this->input->get('per_page') ? (int)$this->input->get('per_page') : 25;
    $search = $this->input->get('search');
    $status_filter = $this->input->get('status');
    $tipo_filter = $this->input->get('tipo');

    // Obtener datos paginados
    $result = $this->customers_m->get_customers_paginated($page, $per_page, $search, $status_filter, $tipo_filter);

    $data['customers'] = $result['customers'];
    $data['total_records'] = $result['total'];
    $data['total_pages'] = $result['total_pages'];
    $data['current_page'] = $page;
    $data['per_page'] = $per_page;
    $data['search'] = $search;
    $data['status_filter'] = $status_filter;
    $data['tipo_filter'] = $tipo_filter;

    $data['subview'] = 'admin/customers/index';
    $this->load->view('admin/_main_layout', $data);
  }

  public function edit($id = NULL)
  {
    log_message('debug', '[DEBUG] Iniciando método edit() - ID: ' . ($id ?: 'NULL'));

    if ($id) {
      $data['customer'] = $this->customers_m->get($id);
      $data['provinces'] = $this->customers_m->get_editProvinces($data['customer']->department_id);
      $data['districts'] = $this->customers_m->get_editDistricts($data['customer']->province_id);
      log_message('debug', '[DEBUG] Editando cliente existente - ID: ' . $id . ', DNI: ' . $data['customer']->dni);
    } else {
      $data['customer'] = $this->customers_m->get_new();
      log_message('debug', '[DEBUG] Creando nuevo cliente');
    }

    $data['departments'] = $this->customers_m->get_departments();
    $data['users'] = $this->customers_m->get_active_users();

    $rules = $this->customers_m->customer_rules;

    $this->form_validation->set_rules($rules);

    log_message('debug', '[DEBUG] Datos POST recibidos: ' . json_encode($this->input->post()));

    if ($this->form_validation->run() == TRUE) {
      log_message('debug', '[DEBUG] Validación del formulario PASÓ');

      $cst_data = $this->customers_m->array_from_post(['dni','first_name', 'last_name', 'gender', 'department_id', 'province_id', 'district_id', 'mobile', 'phone_fixed', 'address', 'phone', 'user_id', 'ruc', 'company', 'tipo_cliente']);
      log_message('debug', '[DEBUG] Datos extraídos del POST: ' . json_encode($cst_data));

      $save_result = $this->customers_m->save($cst_data, $id);
      log_message('debug', '[DEBUG] Resultado del save: ' . ($save_result === false ? 'FALSE (DNI duplicado)' : 'TRUE'));

      if ($save_result === false) {
        $this->session->set_flashdata('error', '⚠️ El número de cédula ingresado ya existe. Verifica la información antes de continuar.');
        log_message('debug', '[DEBUG] Error: DNI duplicado detectado');
      } else {
        if ($id) {
          $this->session->set_flashdata('msg', 'Cliente editado correctamente');
          log_message('debug', '[DEBUG] Cliente editado exitosamente - ID: ' . $id);
        } else {
          $this->session->set_flashdata('msg', 'Cliente agregado correctamente');
          log_message('debug', '[DEBUG] Cliente creado exitosamente');
        }
        redirect('admin/customers');
      }

    } else {
      log_message('debug', '[DEBUG] Validación del formulario FALLÓ - Errores: ' . json_encode($this->form_validation->error_array()));
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

  public function view($id = NULL)
  {
    if (!$id) {
      show_error('ID de cliente no especificado', 400);
      return;
    }

    log_message('debug', '[DEBUG] Iniciando método view() - ID: ' . $id);

    // Obtener datos del cliente
    $data['customer'] = $this->customers_m->get($id);
    if (!$data['customer']) {
      show_error('Cliente no encontrado', 404);
      return;
    }

    // Cargar modelos necesarios
    $this->load->model('loans_m');
    $this->load->model('payments_m');

    // Obtener préstamos del cliente con información detallada
    $this->db->select('l.*, co.name as coin_name, co.short_name as coin_short,
                      CONCAT(u.first_name, " ", u.last_name) as asesor_name');
    $this->db->from('loans l');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->join('users u', 'u.id = l.assigned_user_id', 'left');
    $this->db->where('l.customer_id', $id);
    $this->db->order_by('l.date', 'desc');
    $query = $this->db->get();
    $loans = $query->result();

    // Agregar status_text manualmente para evitar problemas de sintaxis
    foreach ($loans as $loan) {
        switch ($loan->status) {
            case 0:
                $loan->status_text = "Pagado";
                break;
            case 1:
                $loan->status_text = "Activo";
                break;
            case 2:
                $loan->status_text = "Castigado";
                break;
            default:
                $loan->status_text = "Desconocido";
        }
    }

    // Preparar datos resumidos de préstamos
    $loan_summary = [
      'total_loans' => count($loans),
      'active_loans' => 0,
      'paid_loans' => 0,
      'penalized_loans' => 0,
      'total_amount' => 0,
      'total_balance' => 0,
      'total_paid' => 0
    ];

    foreach ($loans as $loan) {
      $loan_summary['total_amount'] += $loan->credit_amount;

      if ($loan->status == 1) {
        $loan_summary['active_loans']++;
      } elseif ($loan->status == 0) {
        $loan_summary['paid_loans']++;
      } elseif ($loan->status == 2) {
        $loan_summary['penalized_loans']++;
      }

      // Calcular balance pendiente para préstamos activos
      if ($loan->status == 1) {
        $this->db->select('SUM(COALESCE(balance, 0)) as balance');
        $this->db->from('loan_items');
        $this->db->where('loan_id', $loan->id);
        $balance_result = $this->db->get()->row();
        $loan->current_balance = $balance_result ? $balance_result->balance : 0;
        $loan_summary['total_balance'] += $loan->current_balance;
        $loan_summary['total_paid'] += ($loan->credit_amount - $loan->current_balance);
      } else {
        $loan->current_balance = 0;
      }
    }

    $data['loans'] = $loans;
    $data['loan_summary'] = $loan_summary;

    // Obtener información adicional del cliente
    $data['customer']->quota = $this->customers_m->get_customer_quota($id);
    $data['customer']->loan_count = $this->customers_m->get_customer_loan_count($id);
    $data['customer']->is_blacklisted = $this->customers_m->check_blacklist($id);

    // Obtener estadísticas de pagos vencidos si tiene préstamos activos
    if ($loan_summary['active_loans'] > 0) {
      $overdue_info = $this->payments_m->get_overdue_clients(null, null, null, null);
      $data['overdue_info'] = array_filter($overdue_info, function($client) use ($id) {
        return $client->customer_id == $id;
      });
      $data['overdue_info'] = !empty($data['overdue_info']) ? reset($data['overdue_info']) : null;
    }

    $data['title'] = 'Detalles del Cliente: ' . $data['customer']->first_name . ' ' . $data['customer']->last_name;
    $data['subview'] = 'admin/customers/view';
    $this->load->view('admin/_main_layout', $data);
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

    // Obtener historial de bloqueos activos
    $data['block_history'] = $this->customers_m->get_active_blocks(50);

    $data['title'] = 'Clientes con Pagos Vencidos';
    $data['pagination_links'] = $this->pagination->create_links();
    $data['total_records'] = $total_rows;
    $data['current_page'] = $page;
    $data['per_page'] = $config['per_page'];
    $data['subview'] = 'admin/clients/overdue';
    $this->load->view('admin/_main_layout', $data);
  }

  /**
   * Obtener historial de bloqueos de un cliente (AJAX)
   */
  public function ajax_get_customer_block_history()
  {
    header('Content-Type: application/json');
    
    $customer_id = $this->input->post('customer_id');
    
    if (!$customer_id) {
      echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado']);
      return;
    }

    $history = $this->customers_m->get_status_history($customer_id, 20);
    
    echo json_encode(['success' => true, 'history' => $history]);
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
    $message_type = $this->input->post('message_type') ?: 'reminder';

    // Obtener datos del cliente
    $this->db->where('id', $customer_id);
    $customer = $this->db->get('customers')->row();

    if (!$customer) {
      echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
      return;
    }

    // Obtener información de mora del cliente
    $this->load->model('payments_m');
    $overdue_info = $this->payments_m->get_customer_overdue_info($customer_id);

    // Preparar mensaje según tipo
    $messages = [
      'reminder' => "Recordatorio: Tiene pagos pendientes por $" . number_format($overdue_info->total_adeudado ?? 0, 2, ',', '.'),
      'warning' => "Advertencia: Sus pagos están atrasados. Contacte con nosotros para evitar penalizaciones.",
      'penalty' => "Penalización aplicada: Su cuenta ha sido marcada como de alto riesgo.",
      'payment_due' => "Su pago está próximo a vencer. Evite intereses adicionales pagando a tiempo."
    ];

    $message = isset($messages[$message_type]) ? $messages[$message_type] : 'Notificación de pagos pendientes';

    // Intentar envío por email
    $email_sent = false;
    if (!empty($customer->phone)) { // Usamos el campo phone como email
      $email_sent = $this->_send_email_notification($customer, $message, $message_type);
    }

    // Intentar envío por SMS (simulado por ahora)
    $sms_sent = $this->_send_sms_notification($customer, $message);

    // Log de notificación enviada
    log_message('info', 'Notificación enviada a cliente: ' . $customer->first_name . ' ' . $customer->last_name .
                       ' (ID: ' . $customer_id . ') - Tipo: ' . $message_type .
                       ' - Email: ' . ($email_sent ? 'ENVIADO' : 'FALLÓ') .
                       ' - SMS: ' . ($sms_sent ? 'ENVIADO' : 'FALLÓ'));

    // Registrar en base de datos
    $this->_log_notification($customer_id, $message_type, $message, $email_sent, $sms_sent);

    $response_message = 'Notificación enviada exitosamente';
    if (!$email_sent && !$sms_sent) {
      $response_message = 'Notificación registrada pero no se pudo enviar (sin métodos de contacto)';
    }

    echo json_encode([
      'success' => true,
      'message' => $response_message,
      'email_sent' => $email_sent,
      'sms_sent' => $sms_sent
    ]);
  }

  public function apply_penalty()
  {
    $this->load->model('payments_m');

    $customer_id = $this->input->post('customer_id');
    $penalty_type = $this->input->post('penalty_type') ?: 'interest_increase';

    // Verificar confirmación del usuario
    $confirmation = $this->input->post('confirmation');
    if ($confirmation !== 'APLICAR_PENALIZACION') {
      echo json_encode(['success' => false, 'error' => 'Confirmación requerida para aplicar penalización']);
      return;
    }

    // Obtener información del cliente
    $customer = $this->customers_m->get($customer_id);
    if (!$customer) {
      echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
      return;
    }

    // Aplicar penalización según tipo
    $result = false;
    $penalty_details = [];

    switch ($penalty_type) {
      case 'interest_increase':
        // Aumentar intereses en cuotas pendientes
        $result = $this->payments_m->apply_interest_penalty($customer_id);
        $penalty_details = ['type' => 'Aumento de intereses', 'description' => 'Intereses aumentados en 5% en cuotas pendientes'];
        break;

      case 'late_fee':
        // Aplicar multa por mora
        $result = $this->payments_m->apply_late_fee_penalty($customer_id);
        $penalty_details = ['type' => 'Multa por mora', 'description' => 'Multa adicional aplicada por atraso'];
        break;

      case 'temporary_block':
        // Bloqueo temporal de nuevos préstamos
        $result = $this->customers_m->add_to_blacklist($customer_id, 'manual_block', 'Bloqueo temporal por penalización', $this->session->userdata('user_id'));
        $penalty_details = ['type' => 'Bloqueo temporal', 'description' => 'Cliente bloqueado temporalmente para nuevos préstamos'];
        break;

      case 'permanent_block':
        // Bloqueo permanente
        $result = $this->customers_m->add_to_blacklist($customer_id, 'fraud', 'Bloqueo permanente por reincidencia', $this->session->userdata('user_id'));
        $penalty_details = ['type' => 'Bloqueo permanente', 'description' => 'Cliente bloqueado permanentemente'];
        break;

      default:
        echo json_encode(['success' => false, 'error' => 'Tipo de penalización no válido']);
        return;
    }

    if ($result) {
      // Log de penalización aplicada
      log_message('info', 'Penalización aplicada - Cliente: ' . $customer->first_name . ' ' . $customer->last_name .
                         ' (ID: ' . $customer_id . ') - Tipo: ' . $penalty_type);

      // Enviar notificación al cliente
      $this->_send_penalty_notification($customer, $penalty_details);

      echo json_encode([
        'success' => true,
        'message' => 'Penalización aplicada exitosamente: ' . $penalty_details['description'],
        'penalty_type' => $penalty_type,
        'penalty_details' => $penalty_details
      ]);
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

  /**
   * Enviar notificación por email
   */
  private function _send_email_notification($customer, $message, $type)
  {
    // Configuración básica de email (mejorar con configuración real)
    $this->load->library('email');

    $config = [
      'protocol' => 'smtp',
      'smtp_host' => 'localhost',
      'smtp_port' => 587,
      'mailtype' => 'html',
      'charset' => 'utf-8'
    ];

    $this->email->initialize($config);

    $this->email->from('sistema@prestamos.com', 'Sistema de Préstamos');
    $this->email->to($customer->phone); // Usando campo phone como email
    $this->email->subject('Notificación de ' . ucfirst($type) . ' - Sistema de Préstamos');

    $email_body = "
    <html>
    <body>
      <h2>Notificación del Sistema de Préstamos</h2>
      <p>Estimado(a) {$customer->first_name} {$customer->last_name},</p>
      <p>{$message}</p>
      <p>Por favor, contacte con nosotros para resolver esta situación.</p>
      <br>
      <p>Atentamente,<br>Sistema de Préstamos</p>
    </body>
    </html>";

    $this->email->message($email_body);

    return $this->email->send();
  }

  /**
   * Enviar notificación por SMS (simulado)
   */
  private function _send_sms_notification($customer, $message)
  {
    // Simulación de envío SMS - integrar con servicio real como Twilio
    log_message('info', 'SMS simulado enviado a ' . $customer->mobile . ': ' . $message);

    // Aquí iría la integración real con servicio de SMS
    // Por ahora retornamos true para simular envío exitoso
    return true;
  }

  /**
   * Registrar notificación en base de datos
   */
  private function _log_notification($customer_id, $type, $message, $email_sent, $sms_sent)
  {
    $data = [
      'customer_id' => $customer_id,
      'type' => $type,
      'message' => $message,
      'email_sent' => $email_sent,
      'sms_sent' => $sms_sent,
      'sent_at' => date('Y-m-d H:i:s'),
      'sent_by' => $this->session->userdata('user_id')
    ];

    // Crear tabla si no existe
    if (!$this->db->table_exists('notification_logs')) {
      $this->db->query("
        CREATE TABLE notification_logs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          customer_id INT NOT NULL,
          type VARCHAR(50) NOT NULL,
          message TEXT,
          email_sent BOOLEAN DEFAULT FALSE,
          sms_sent BOOLEAN DEFAULT FALSE,
          sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          sent_by INT,
          FOREIGN KEY (customer_id) REFERENCES customers(id),
          FOREIGN KEY (sent_by) REFERENCES users(id)
        )
      ");
    }

    $this->db->insert('notification_logs', $data);
  }

  /**
   * Enviar notificación de penalización
   */
  private function _send_penalty_notification($customer, $penalty_details)
  {
    $message = "Penalización aplicada: {$penalty_details['description']}. Contacte con nosotros para más información.";

    $this->_send_email_notification($customer, $message, 'penalty');
    $this->_send_sms_notification($customer, $message);
  }
  public function get_loan_details()
  {
    header('Content-Type: application/json');
    
    $this->load->model('loans_m');
    $this->load->model('payments_m');

    $loan_ids = $this->input->post('loan_ids');
    $customer_id = $this->input->post('customer_id');
    $loan_ids_array = explode(',', $loan_ids);

    // Obtener información del cliente si se proporciona customer_id
    $customer_info = null;
    if ($customer_id) {
      $customer_info = $this->customers_m->get($customer_id);
    }

    $loans = [];
    foreach ($loan_ids_array as $loan_id) {
      $loan_id = trim($loan_id);
      if (!empty($loan_id)) {
        $loan = $this->loans_m->get_loan($loan_id);
        if ($loan) {
          // Si no tenemos info del cliente, obtenerla del préstamo
          if (!$customer_info && $loan->customer_id) {
            $customer_info = $this->customers_m->get($loan->customer_id);
          }
          
          // Obtener TODAS las cuotas del préstamo (no solo pendientes)
          $this->db->select('id, loan_id, date, num_quota, fee_amount, interest_amount, capital_amount, balance, status, COALESCE(interest_paid, 0) as interest_paid, COALESCE(capital_paid, 0) as capital_paid');
          $this->db->where('loan_id', $loan_id);
          $this->db->where('extra_payment !=', 3); // Excluir cuotas condonadas
          $this->db->order_by('num_quota', 'asc');
          $query = $this->db->get('loan_items');
          
          $loan->all_quotas = [];
          foreach ($query->result() as $row) {
            $loan->all_quotas[] = [
              'id' => $row->id,
              'loan_id' => $row->loan_id,
              'date' => $row->date,
              'num_quota' => $row->num_quota,
              'fee_amount' => $row->fee_amount,
              'interest_amount' => $row->interest_amount,
              'capital_amount' => $row->capital_amount,
              'balance' => $row->balance,
              'status' => $row->status,
              'paid' => ($row->status == 0) ? 1 : 0, // status 0 = pagada
              'interest_paid' => $row->interest_paid,
              'capital_paid' => $row->capital_paid
            ];
          }
  
          // También obtener cuotas pendientes para compatibilidad
          if (is_array($loan->all_quotas)) {
            $loan->pending_quotas = array_filter($loan->all_quotas, function($quota) {
              return isset($quota['status']) && $quota['status'] == 1; // Solo cuotas no pagadas
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
      echo json_encode(['success' => true, 'loans' => $loans, 'customer' => $customer_info]);
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

  /**
   * Activar cliente (AJAX)
   */
  public function ajax_activate_customer()
  {
    $customer_id = $this->input->post('customer_id');
    $user_id = $this->session->userdata('user_id');

    if (!$customer_id) {
      echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado']);
      return;
    }

    $result = $this->customers_m->activate_customer($customer_id, $user_id);

    if ($result) {
      echo json_encode(['success' => true, 'message' => 'Cliente activado exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Error al activar el cliente']);
    }
  }

  /**
   * Desactivar cliente (AJAX)
   */
  public function ajax_deactivate_customer()
  {
    $customer_id = $this->input->post('customer_id');
    $user_id = $this->session->userdata('user_id');

    if (!$customer_id) {
      echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado']);
      return;
    }

    $result = $this->customers_m->deactivate_customer($customer_id, $user_id);

    if ($result) {
      echo json_encode(['success' => true, 'message' => 'Cliente desactivado exitosamente']);
    } else {
      echo json_encode(['success' => false, 'error' => 'Error al desactivar el cliente']);
    }
  }

  /**
   * Toggle estado del cliente (AJAX)
   */
  public function ajax_toggle_customer_status()
  {
    // Desactivar el display_errors de PHP para evitar HTML en respuestas AJAX
    @ini_set('display_errors', 0);
    
    // Establecer headers para JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Capturar cualquier salida de error
    ob_start();
    
    try {
      $customer_id = $this->input->post('customer_id');
      $user_id = $this->session->userdata('user_id');
      $notes = $this->input->post('notes'); // Motivo del bloqueo/desbloqueo

      if (!$customer_id) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado']);
        return;
      }

      log_message('debug', 'ajax_toggle_customer_status - Cliente ID: ' . $customer_id . ', Usuario ID: ' . $user_id . ', Notes: ' . ($notes ? $notes : 'N/A'));

      // Verificar que la columna status existe ANTES de continuar
      $check_column = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_column->num_rows() == 0) {
        ob_end_clean();
        log_message('error', 'Columna status no existe en tabla customers');
        echo json_encode([
          'success' => false, 
          'error' => 'La columna "status" no existe en la tabla customers. Por favor ejecuta: http://localhost/prestamo-1/add_customer_status_field.php'
        ]);
        return;
      }

      // Verificar que el cliente existe
      $customer = $this->customers_m->get($customer_id);
      if (!$customer) {
        ob_end_clean();
        log_message('error', 'Cliente no encontrado - ID: ' . $customer_id);
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        return;
      }

      // Obtener estado actual antes del cambio
      $old_status = $this->customers_m->is_customer_active($customer_id) ? 1 : 0;
      log_message('debug', 'Estado actual del cliente: ' . $old_status);
      
      $is_active = $old_status == 1;

      if ($is_active) {
        log_message('debug', 'Desactivando cliente - ID: ' . $customer_id);
        $result = $this->customers_m->deactivate_customer($customer_id, $user_id);
        $new_status = 0;
        $action = 'deactivated';
        $message = 'Cliente desactivado/bloqueado exitosamente';
      } else {
        log_message('debug', 'Activando cliente - ID: ' . $customer_id);
        $result = $this->customers_m->activate_customer($customer_id, $user_id);
        $new_status = 1;
        $action = 'activated';
        $message = 'Cliente activado/desbloqueado exitosamente';
      }

      // Limpiar cualquier salida de error
      ob_end_clean();

      if ($result) {
        log_message('debug', 'Cambio de estado exitoso - Cliente ID: ' . $customer_id . ', Nuevo estado: ' . $new_status);
        
        // Registrar en historial
        log_message('debug', 'Intentando registrar historial - Cliente: ' . $customer_id . ', Old: ' . $old_status . ', New: ' . $new_status . ', Action: ' . $action . ', User: ' . ($user_id ? $user_id : 'NULL') . ', Notes: ' . ($notes ? $notes : 'N/A'));
        $history_result = $this->customers_m->add_status_history($customer_id, $old_status, $new_status, $action, $user_id, $notes);
        if ($history_result) {
          log_message('info', 'Historial registrado exitosamente - Cliente ID: ' . $customer_id);
        } else {
          log_message('error', 'No se pudo registrar en el historial - Cliente ID: ' . $customer_id . ', User ID: ' . ($user_id ? $user_id : 'NULL'));
          // Verificar si la tabla existe
          $check_table = $this->db->query("SHOW TABLES LIKE 'customer_status_history'");
          if ($check_table->num_rows() == 0) {
            log_message('error', 'La tabla customer_status_history NO EXISTE en la base de datos');
          } else {
            log_message('error', 'La tabla customer_status_history existe, pero falló la inserción. Verificar logs de BD.');
            $db_error = $this->db->error();
            log_message('error', 'Error de BD: ' . json_encode($db_error));
          }
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'status' => $new_status, 'history_saved' => $history_result]);
      } else {
        log_message('error', 'Error al cambiar estado del cliente - ID: ' . $customer_id);
        $db_error = $this->db->error();
        $error_msg = 'Error al cambiar el estado del cliente. ';
        
        if (!empty($db_error['message'])) {
          $error_msg .= 'Error de BD: ' . $db_error['message'];
        } else {
          $error_msg .= 'Verifica los logs del servidor para más detalles.';
        }
        
        echo json_encode(['success' => false, 'error' => $error_msg]);
      }
      
    } catch (Exception $e) {
      ob_end_clean();
      log_message('error', 'Excepción en ajax_toggle_customer_status: ' . $e->getMessage());
      echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor: ' . $e->getMessage()
      ]);
    }
  }

}

/* End of file Customers.php */
/* Location: ./application/controllers/admin/Customers.php */