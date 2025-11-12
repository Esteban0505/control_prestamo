<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Loans extends MY_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('loans_m');
    $this->load->model('customers_m');
    $this->load->library('form_validation');
    $this->load->library('amortization');
    $this->load->library('paymentcalculator');
    $this->load->helper('currency');
    $this->load->helper('format');
    $this->load->helper('permission');
  }

  public function index()
  {
    log_message('debug', 'Cargando lista de préstamos');

    // Validar y sanitizar parámetros de entrada
    $page = $this->input->get('page');
    $per_page = $this->input->get('per_page');
    $search = $this->input->get('search');
    $status_filter = $this->input->get('status');

    // Validaciones de seguridad
    $page = is_numeric($page) && $page > 0 ? (int)$page : 1;
    $per_page = is_numeric($per_page) && in_array($per_page, [25, 50, 100]) ? (int)$per_page : 25;
    $search = is_string($search) ? trim(strip_tags($search)) : null;
    $status_filter = in_array($status_filter, ['0', '1', '']) ? $status_filter : null;

    // Limitar longitud de búsqueda para prevenir ataques
    if ($search && strlen($search) > 100) {
      $search = substr($search, 0, 100);
    }

    // Obtener datos paginados
    $result = $this->loans_m->get_loans($page, $per_page, $search, $status_filter);

    $data['loans'] = $result['loans'];
    $data['total_records'] = $result['total_records'];
    $data['total_pages'] = $result['total_pages'];
    $data['current_page'] = $result['current_page'];
    $data['per_page'] = $result['per_page'];
    $data['search'] = $search;
    $data['status_filter'] = $status_filter;

    log_message('debug', 'Loans in controller: ' . json_encode($data['loans']));
    $data['subview'] = 'admin/loans/index';
    $this->load->view('admin/_main_layout', $data);
    log_message('debug', 'Vista de lista de préstamos cargada');
  }

  public function edit()
  {
    log_message('debug', 'Iniciando método edit() para préstamo');
    $loan_id = $this->input->get('id');
    $is_edit = !empty($loan_id);
    $is_ajax = $this->input->is_ajax_request() || !is_null($this->input->post('credit_amount'));
    if ($is_ajax) {
      header('Content-Type: application/json');
    }
    $data['coins'] = $this->loans_m->get_coins();
    $data['users'] = $this->customers_m->get_active_users();

    // Asegurar consistencia: actualizar status de préstamos pagados y clientes sin préstamos activos
    $this->db->query("UPDATE loans l SET l.status = 0 WHERE l.status = 1 AND (SELECT SUM(COALESCE(li.balance, 0)) FROM loan_items li WHERE li.loan_id = l.id) = 0");
    $this->db->query("UPDATE customers c SET c.loan_status = 0 WHERE c.loan_status = 1 AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id AND l.status = 1)");
    error_log("CONSISTENCY_UPDATE_EXECUTED");

    // También asegurar que balances de cuotas pagadas sean 0
    $this->db->query("UPDATE loan_items SET balance = 0 WHERE status = 0 AND balance != 0");
    error_log("BALANCE_CONSISTENCY_UPDATE_EXECUTED");

    if ($is_edit) {
      $data['loan'] = $this->loans_m->get_loan($loan_id);
      $data['customer'] = $this->customers_m->get($data['loan']->customer_id);
      $data['current_limit'] = $this->loans_m->get_customer_limit($data['loan']->customer_id);
      $data['is_edit'] = true;
    } else {
      $data['is_edit'] = false;
    }

    $rules = $this->loans_m->loan_rules;
    $this->form_validation->set_rules($rules);

    // Agregar validaciones callback personalizadas
    $this->form_validation->set_rules('date', 'Fecha y hora de emisión', 'callback_validate_emission_datetime');
    $this->form_validation->set_rules('payment_start_date', 'Fecha de inicio de cobros', 'callback_validate_payment_start_date');
    $raw_post = $this->input->post();
    log_message('debug', 'Validation rules set for loan ' . ($is_edit ? 'update' : 'creation'));
    log_message('debug', 'POST data received: ' . json_encode($raw_post));

    // === DIAGNÓSTICO FECHAS - INICIO VALIDACIÓN ===
    log_message('debug', '=== DIAGNÓSTICO FECHAS - INICIO VALIDACIÓN ===');
    log_message('debug', 'Server timezone: ' . date_default_timezone_get());
    log_message('debug', 'Current server time: ' . date('Y-m-d H:i:s T'));
    log_message('debug', 'Current timestamp: ' . time());
    log_message('debug', 'POST date field: ' . ($this->input->post('date') ?? 'NOT SET'));
    log_message('debug', 'POST payment_start_date field: ' . ($this->input->post('payment_start_date') ?? 'NOT SET'));

    // Logging de préstamo activo antes de validar
    $customer_id = $this->input->post('customer_id') ?: ($data['loan']->customer_id ?? null);
    if ($customer_id) {
      // Verificar si tiene préstamo activo (solo status = 1)
      $this->db->select('l.status');
      $this->db->from('loans l');
      $this->db->where('l.customer_id', $customer_id);
      if ($is_edit) {
        $this->db->where('l.id !=', $loan_id);
      }
      $this->db->where('l.status', 1);
      $active_loan_query = $this->db->get();
      $active_loan = $active_loan_query->row();
      $has_active = $active_loan ? true : false;
      log_message('debug', 'Cliente ' . $customer_id . ' tiene préstamo activo: ' . ($has_active ? 'Sí' : 'No'));
      error_log("ACTIVE_LOAN_LOG: customer_id=$customer_id, has_active=" . ($has_active ? '1' : '0') . ", active_loan=" . json_encode($active_loan));
    }
    if (isset($raw_post['amortization_type'])) {
        log_message('debug', 'amortization_type received in controller: ' . $raw_post['amortization_type']);
    } else {
        log_message('debug', 'amortization_type not found in POST data');
    }
    if (isset($raw_post['credit_amount'])) {
        log_message('debug', 'Raw credit_amount from POST: ' . $raw_post['credit_amount']);
    } else {
        log_message('debug', 'credit_amount not found in POST');
    }

    log_message('debug', 'Form validation result: ' . ($this->form_validation->run() ? 'PASSED' : 'FAILED'));
    log_message('debug', '=== DIAGNÓSTICO FECHAS - FIN VALIDACIÓN ===');
    if ($this->form_validation->run() == TRUE) {
      log_message('debug', 'Form validation PASSED, proceeding with loan creation/update');

      // Validación adicional: impedir si cliente tiene cuotas pendientes
      $customer_id = $this->input->post('customer_id');
      log_message('debug', 'Checking pending installments for customer_id: ' . $customer_id);

      // Verificar si cliente está en blacklist
      $is_blacklisted = $this->customers_m->check_blacklist($customer_id);
      if ($is_blacklisted) {
        log_message('debug', 'Validation FAILED: Customer is blacklisted');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'Cliente bloqueado por política de riesgo. Contacte al administrador.']);
          return;
        } else {
          $this->session->set_flashdata('error', 'Cliente bloqueado por política de riesgo. Contacte al administrador.');
          redirect('admin/loans/edit');
        }
      }

      // Verificar si el cliente está activo
      $is_active = $this->customers_m->is_customer_active($customer_id);
      if (!$is_active) {
        log_message('debug', 'Validation FAILED: Customer is inactive');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El cliente está desactivado y no puede realizar préstamos. Active el cliente primero.']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El cliente está desactivado y no puede realizar préstamos. Active el cliente primero.');
          redirect('admin/loans/edit');
        }
      }

      // CORRECCIÓN: Verificar si el cliente tiene cuotas pendientes (balance > 0) en lugar de solo préstamos activos
      $this->db->select('SUM(COALESCE(li.balance, 0)) as total_pending_balance, COUNT(*) as pending_installments');
      $this->db->from('loans l');
      $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
      $this->db->where('l.customer_id', $customer_id);
      if ($is_edit) {
        $this->db->where('l.id !=', $loan_id);
      }
      $this->db->where('l.status', 1); // Solo préstamos activos
      $this->db->where('li.status !=', 0); // Solo cuotas no completamente pagadas
      $this->db->where('li.extra_payment !=', 3); // Excluir condonadas
      $pending_check = $this->db->get()->row();

      $has_pending_installments = $pending_check && ($pending_check->total_pending_balance > 0.01 || $pending_check->pending_installments > 0);
      log_message('debug', 'Pending installments check result: ' . json_encode($pending_check));
      error_log("PENDING_INSTALLMENTS_VALIDATION: customer_id=$customer_id, has_pending=" . ($has_pending_installments ? '1' : '0'));
      if ($has_pending_installments) {
        log_message('debug', 'Validation FAILED: Customer has pending installments');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El cliente tiene cuotas pendientes de pago. Complete el pago de las cuotas actuales antes de crear un nuevo préstamo.']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El cliente tiene cuotas pendientes de pago. Complete el pago de las cuotas actuales antes de crear un nuevo préstamo.');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Active loan validation PASSED');

      // Validación de límites de crédito solo cuando se intenta guardar (no en AJAX)
      if (!$is_ajax) {
        log_message('debug', 'Validating loan limits for non-AJAX request');
        $credit_amount = $this->input->post('credit_amount');
        if (!$this->validate_loan_limit($credit_amount)) {
          log_message('debug', 'Loan limit validation FAILED');
          $this->session->set_flashdata('error', 'Límite de crédito excedido o cliente con préstamo activo');
          redirect('admin/loans/edit');
          return;
        }
        log_message('debug', 'Loan limit validation PASSED');
      }

      // Obtener datos del formulario y convertir formato colombiano a decimal
      $principal_input = $raw_post['credit_amount'] ?? '';
      log_message('debug', 'credit_amount recibido en edit(): ' . $principal_input);
      log_message('debug', 'all POST data: ' . json_encode($raw_post));
      $interest_input = $raw_post['interest_amount'] ?? '';
      $num_months = (int) $raw_post['num_months'];
      $payment_frequency = $raw_post['payment_m'];
      $amortization_type = $raw_post['amortization_type'] ?: 'francesa';
      error_log("Amortization recibido en edit: " . $raw_post['amortization_type']);
      // Validar y corregir amortization_type si es inválido
      if (!in_array($amortization_type, ['francesa', 'estaunidense', 'mixta'])) {
          log_message('debug', 'amortization_type inválido recibido: ' . $amortization_type . ', usando default francesa');
          $amortization_type = 'francesa';
      }
      log_message('debug', 'Valor de amortization_type validado: ' . $amortization_type);

      // Validación adicional removida: ahora se permiten todos los tipos de amortización con cualquier frecuencia
      log_message('debug', 'Amortization type validation PASSED');
      $start_date = $raw_post['date'];
      $payment_start_date = $raw_post['payment_start_date'] ?? null;

      // Si se proporciona fecha completa de inicio de cobros, convertir formato dd/mm/yyyy a yyyy-mm-dd y extraer el día
      if ($payment_start_date) {
        // Convertir de formato dd/mm/yyyy a yyyy-mm-dd para strtotime
        $date_parts = explode('/', $payment_start_date);
        if (count($date_parts) === 3) {
          $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
          $payment_start_day = (int) date('j', strtotime($formatted_date));
        } else {
          $payment_start_day = (int) date('j', strtotime($payment_start_date));
        }
      } else {
        $payment_start_day = 1; // Valor por defecto
      }
      $assigned_user_id = $raw_post['assigned_user_id'] ?? null;
      $tasa_type = $raw_post['tasa_tipo'];

      // Log adicional para verificar parsing
      log_message('debug', 'Antes de parsear principal: ' . $principal_input);
      log_message('debug', 'Antes de parsear interest: ' . $interest_input);

      // Convertir y validar formato colombiano
      log_message('debug', 'Parseando monto principal: ' . $principal_input);
      $principal = $this->parse_currency_input($principal_input, true);
      log_message('debug', 'Monto principal parseado: ' . $principal . ' (tipo: ' . gettype($principal) . ')');
      if ($principal === false) {
        log_message('error', 'Formato inválido para monto principal: ' . $principal_input);
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El formato del monto del préstamo es inválido. Use formato: 1.000.000']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El formato del monto del préstamo es inválido. Use formato: 1.000.000');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Monto principal parseado: ' . $principal);

      log_message('debug', 'Parseando tasa de interés: ' . $interest_input);
      $interest_rate = $this->parse_currency_input($interest_input);
      if ($interest_rate === false) {
        log_message('error', 'Formato inválido para tasa de interés: ' . $interest_input);
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El formato de la tasa de interés es inválido. Use formato: 15,50']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El formato de la tasa de interés es inválido. Use formato: 15,50');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Tasa de interés parseada: ' . $interest_rate);

      // Log adicional para verificar valores parseados
      log_message('debug', 'Valores después de parsing: principal=' . $principal . ', interest_rate=' . $interest_rate);

      // Validaciones adicionales
      log_message('debug', 'Validating principal amount: ' . $principal);
      if ($principal <= 0) {
        log_message('debug', 'Validation FAILED: Principal <= 0');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El monto del préstamo debe ser mayor a 0']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El monto del préstamo debe ser mayor a 0');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Principal validation PASSED');

      log_message('debug', 'Validating interest rate: ' . $interest_rate);
      if ($interest_rate < 0) {
        log_message('debug', 'Validation FAILED: Interest rate < 0');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'La tasa de interés no puede ser negativa']);
          return;
        } else {
          $this->session->set_flashdata('error', 'La tasa de interés no puede ser negativa');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Interest rate validation PASSED');

      log_message('debug', 'Validating num_months: ' . $num_months);
      if ($num_months <= 0 || $num_months > 120) {
        log_message('debug', 'Validation FAILED: num_months out of range');
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El plazo en meses debe estar entre 1 y 120']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El plazo en meses debe estar entre 1 y 120');
          redirect('admin/loans/edit');
        }
      }
      log_message('debug', 'Num months validation PASSED');

      log_message('debug', 'Tasa: ' . $interest_rate . ', tipo: ' . $tasa_type . ', iniciando cálculo de amortización');

      // Calcular días por período
      switch ($payment_frequency) {
        case 'diario': $days_per_period = 1; break;
        case 'semanal': $days_per_period = 7; break;
        case 'quincenal': $days_per_period = 15; break;
        case 'mensual': $days_per_period = 30; break; // 30 días fijos
        default: $days_per_period = 30;
      }

      // Calcular periods_per_year propuesto
      switch ($payment_frequency) {
        case 'diario': $periods_per_year = 365; break;
        case 'semanal': $periods_per_year = 52; break;
        case 'quincenal': $periods_per_year = 24; break; // 365/15 ≈ 24.33, usar 24 fijo
        case 'mensual': $periods_per_year = 12; break;
        default: $periods_per_year = 12;
      }
      // Calcular número de cuotas basado en num_months
      $periods = $num_months * ($periods_per_year / 12);
      log_message('debug', 'Controlador - payment_frequency=' . $payment_frequency . ', days_per_period=' . $days_per_period . ', periods_per_year=' . $periods_per_year . ', num_months=' . $num_months . ', periods=' . $periods);
      log_message('debug', 'Datos del formulario: principal_input=' . $principal_input . ', interest_input=' . $interest_input . ', periods=' . $periods . ', frequency=' . $payment_frequency . ', type=' . $amortization_type . ', start_date=' . $start_date);

      // Calcular tasa periódica
      $period_rate = $this->loans_m->get_period_rate($interest_rate, $periods_per_year, $tasa_type);
      log_message('debug', 'Tasa periódica calculada: ' . $period_rate);

      // Validar fecha y hora de emisión (ya validada por form_validation)
      log_message('debug', 'Emission date and time validation PASSED (validated by form_validation)');
      log_message('debug', 'start_date value: ' . $start_date);

      log_message('debug', 'Attempting to calculate amortization table with params: principal=' . $principal . ', period_rate=' . $period_rate . ', periods=' . $periods . ', frequency=' . $payment_frequency . ', type=' . $amortization_type . ', tasa_type=' . $tasa_type);
      try {
        // Usar payment_start_date directamente (ya no necesitamos convertir a día)
        $payment_start_date = $raw_post['payment_start_date'] ?? null;

        // Calcular tabla de amortización usando la librería
        $amortization_table = $this->amortization->calculate_amortization_table(
          $principal,
          $period_rate,
          $periods,
          $payment_frequency,
          $start_date,
          $amortization_type,
          $tasa_type,
          $payment_start_date
        );

        // Verificar que se generó la tabla correctamente
        if (empty($amortization_table)) {
          log_message('debug', 'Amortization table calculation FAILED: empty result');
          throw new Exception('No se pudo generar la tabla de amortización');
        }
        log_message('debug', 'Tabla de amortización calculada con ' . count($amortization_table) . ' pagos');

        // Convertir tabla de amortización al formato esperado por la base de datos
        $items = [];
        foreach ($amortization_table as $payment) {
          $items[] = [
            'date' => $payment['payment_date'],
            'num_quota' => $payment['period'],
            'fee_amount' => $payment['payment'],
            'interest_amount' => $payment['interest'],
            'capital_amount' => $payment['principal'],
            'balance' => $payment['balance']
          ];
        }

        // Obtener el monto de la cuota (para el método francés será constante)
        $fee_amount = $amortization_table[0]['payment'];

        // Calcular total_amount para validación
        $total_amount = $fee_amount * $periods;
        log_message('debug', 'Calculated total_amount: ' . $total_amount . ', credit_amount: ' . $principal . ', interest_total: ' . ($total_amount - $principal));

        // Datos del préstamo
        $loan_data = $this->loans_m->array_from_post([
          'customer_id',
          'credit_amount',
          'interest_amount',
          'payment_m',
          'coin_id',
          'date',
          'amortization_type',
          'tipo_cliente',
          'payment_start_day'
        ]);
        $loan_data['amortization_type'] = $amortization_type;

        // Establecer zona horaria a Bogotá (UTC-5) para la fecha de emisión
        date_default_timezone_set('America/Bogota');

        // Para nuevos préstamos, usar la fecha/hora actual en zona horaria colombiana
        if (!$is_edit) {
          $loan_data['date'] = date('Y-m-d H:i:s');
          log_message('debug', 'Nueva fecha de emisión para préstamo: ' . $loan_data['date']);
        } elseif (empty($loan_data['date'])) {
          // Si se está editando pero no se envió fecha, mantener la existente
          $existing_loan = $this->loans_m->get_loan($loan_id);
          $loan_data['date'] = $existing_loan->date;
          log_message('debug', 'Manteniendo fecha existente para edición: ' . $loan_data['date']);
        } else {
          // Si se envió una fecha desde el formulario, usarla tal cual (datetime-local ya maneja zona horaria)
          log_message('debug', 'Fecha enviada desde formulario: ' . $loan_data['date']);
        }
        // Guardar el número de cuotas calculado en num_fee
        $loan_data['num_fee'] = $periods;
        
        // Asegurar que se guarden valores numéricos ya parseados (sin formateo ni padding)
        $loan_data['credit_amount'] = $principal;
        $loan_data['interest_amount'] = $interest_rate;
        log_message('debug', 'Saving credit_amount: ' . $principal . ', interest_amount: ' . $interest_rate);
        
        // Agregar el monto de cuota calculado y auditoría
        $loan_data['fee_amount'] = $fee_amount;
        $loan_data['created_by'] = get_user_id(); // Registrar quién creó el préstamo
        $loan_data['assigned_user_id'] = $assigned_user_id;

        // Iniciar transacción
        log_message('debug', 'Iniciando transacción para guardar préstamo');
        $this->db->trans_start();

        $save_success = false;
        if ($is_edit) {
          $save_success = $this->loans_m->update_loan($loan_id, $loan_data, $items);
        } else {
          $save_success = $this->loans_m->add_loan($loan_data, $items);
        }

        if ($save_success) {
          $this->db->trans_complete();
          if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Transacción fallida al ' . ($is_edit ? 'actualizar' : 'crear') . ' préstamo');
            if ($is_ajax) {
              echo json_encode(['success' => false, 'error' => 'Error al ' . ($is_edit ? 'actualizar' : 'crear') . ' el préstamo. Transacción fallida.']);
              return;
            } else {
              $this->session->set_flashdata('error', 'Error al ' . ($is_edit ? 'actualizar' : 'crear') . ' el préstamo. Transacción fallida.');
            }
          } else {
            log_message('info', 'Préstamo ' . ($is_edit ? 'actualizado' : 'creado') . ' exitosamente con ID: ' . ($is_edit ? $loan_id : $this->db->insert_id()));
            if ($is_ajax) {
              echo json_encode(['success' => true, 'message' => 'Préstamo ' . ($is_edit ? 'actualizado' : 'agregado') . ' correctamente']);
              return;
            } else {
              $this->session->set_flashdata('msg', 'Préstamo ' . ($is_edit ? 'actualizado' : 'agregado') . ' correctamente');
              // Actualizar gráficas en dashboard si es modal
              echo "<script>if (window.parent && window.parent.location.href.includes('admin') && typeof window.parent.updateCharts === 'function') { window.parent.updateCharts(); }</script>";
            }
          }
        } else {
          log_message('error', 'Error al guardar préstamo en base de datos');
          $this->db->trans_rollback();
          if ($is_ajax) {
            echo json_encode(['success' => false, 'error' => 'Error al ' . ($is_edit ? 'actualizar' : 'crear') . ' el préstamo']);
            return;
          } else {
            $this->session->set_flashdata('error', 'Error al ' . ($is_edit ? 'actualizar' : 'crear') . ' el préstamo');
          }
        }

      } catch (Exception $e) {
        log_message('error', 'Error en cálculo de amortización: ' . $e->getMessage());
        log_message('debug', 'Exception caught during amortization calculation');
        $this->db->trans_rollback();
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'Error en el cálculo: ' . $e->getMessage()]);
          return;
        } else {
          $this->session->set_flashdata('error', 'Error en el cálculo: ' . $e->getMessage());
        }
      }

      redirect('admin/loans');
    }

    if ($is_ajax) {
      echo json_encode(['success' => false, 'error' => strip_tags(validation_errors())]);
      return;
    } else {
      log_message('debug', 'Form validation FAILED, showing validation errors');
      $validation_errors = validation_errors();
      log_message('debug', 'Validation errors: ' . $validation_errors);
      log_message('debug', '=== DIAGNÓSTICO FECHAS - FIN VALIDACIÓN ===');
      $data['subview'] = 'admin/loans/edit';
      $this->load->view('admin/_main_layout', $data);
    }
  }

  function ajax_searchCst()
  {
    $dni = $this->input->post('dni');
    $suggest = $this->input->post('suggest') == '1';
    log_message('debug', 'Buscando cliente por DNI: ' . $dni . ', suggest: ' . ($suggest ? 'true' : 'false'));
    $cst = $this->loans_m->get_searchCst($dni, $suggest);
    log_message('debug', 'Resultado de búsqueda: ' . json_encode($cst));
    echo json_encode($cst);
  }

  /**
   * Validador personalizado para formato de moneda colombiana
   */
  public function validate_currency_format($str)
  {
    if (empty($str)) {
      return TRUE; // Dejamos que 'required' maneje esto
    }

    return validate_colombian_currency($str);
  }

  /**
   * Validador personalizado para verificar que la fecha no sea futura
   */
  public function check_date_not_future($date)
  {
    date_default_timezone_set('America/Bogota');
    if (strtotime($date) > strtotime(date('Y-m-d'))) {
      $this->form_validation->set_message('check_date_not_future', 'La fecha de emisión no puede ser futura.');
      return false;
    }
    return true;
  }

  /**
   * Valida fecha y hora de emisión
   */
  public function validate_emission_datetime($datetime) {
    log_message('debug', 'validate_emission_datetime called with: ' . $datetime);

    if (empty($datetime)) {
      $this->form_validation->set_message('validate_emission_datetime', 'La fecha y hora de emisión es requerida');
      return false;
    }

    // Verificar formato datetime-local (Y-m-dTH:i:s o Y-m-dTH:i)
    $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/';
    if (!preg_match($pattern, $datetime)) {
      log_message('debug', 'validate_emission_datetime: formato inválido - ' . $datetime);
      $this->form_validation->set_message('validate_emission_datetime', 'Formato de fecha y hora inválido. Use formato: YYYY-MM-DDTHH:MM:SS');
      return false;
    }

    // Asegurar zona horaria correcta
    date_default_timezone_set('America/Bogota');

    // Convertir a timestamp
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
      log_message('debug', 'validate_emission_datetime: strtotime falló para - ' . $datetime);
      $this->form_validation->set_message('validate_emission_datetime', 'Fecha y hora inválida');
      return false;
    }

    $current_time = time();

    // Log para diagnóstico
    log_message('debug', 'VALIDATE_EMISSION_DATETIME: input=' . $datetime . ', timestamp=' . $timestamp . ', current=' . $current_time . ', diff=' . ($current_time - $timestamp));

    // Para fecha de emisión: permitir un rango más amplio (±4 horas para flexibilidad)
    // Esto permite flexibilidad para correcciones administrativas
    $four_hours_ago = $current_time - 14400; // 4 horas atrás
    $four_hours_future = $current_time + 14400; // 4 horas adelante

    if ($timestamp < $four_hours_ago) {
      log_message('debug', 'validate_emission_datetime: fecha demasiado antigua - emission_time=' . date('Y-m-d H:i:s', $timestamp) . ' < four_hours_ago=' . date('Y-m-d H:i:s', $four_hours_ago));
      $this->form_validation->set_message('validate_emission_datetime', 'La fecha y hora de emisión no puede ser anterior a hace 4 horas');
      return false;
    }

    if ($timestamp > $four_hours_future) {
      log_message('debug', 'validate_emission_datetime: fecha futura - emission_time=' . date('Y-m-d H:i:s', $timestamp) . ' > four_hours_future=' . date('Y-m-d H:i:s', $four_hours_future));
      $this->form_validation->set_message('validate_emission_datetime', 'La fecha y hora de emisión no puede ser posterior a dentro de 4 horas');
      return false;
    }

    log_message('debug', 'validate_emission_datetime: validación PASSED');
    return true;
  }

  /**
   * Valida fecha de inicio de cobros
   */
  public function validate_payment_start_date($date) {
    log_message('debug', 'validate_payment_start_date called with: ' . $date);

    if (empty($date)) {
      $this->form_validation->set_message('validate_payment_start_date', 'La fecha de inicio de cobros es requerida');
      return false;
    }

    // Verificar formato dd/mm/yyyy
    $pattern = '/^\d{2}\/\d{2}\/\d{4}$/';
    if (!preg_match($pattern, $date)) {
      log_message('debug', 'validate_payment_start_date: formato inválido - ' . $date);
      $this->form_validation->set_message('validate_payment_start_date', 'Formato de fecha inválido. Use formato: DD/MM/YYYY');
      return false;
    }

    // Convertir formato dd/mm/yyyy a yyyy-mm-dd para validación
    $parts = explode('/', $date);
    if (count($parts) !== 3) {
      log_message('debug', 'validate_payment_start_date: partes insuficientes - ' . $date);
      $this->form_validation->set_message('validate_payment_start_date', 'Fecha inválida');
      return false;
    }

    $day = (int) $parts[0];
    $month = (int) $parts[1];
    $year = (int) $parts[2];

    log_message('debug', 'validate_payment_start_date: parsed day=' . $day . ', month=' . $month . ', year=' . $year);

    // Validar rango de valores
    if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 2020 || $year > 2030) {
      log_message('debug', 'validate_payment_start_date: fuera de rango - day=' . $day . ', month=' . $month . ', year=' . $year);
      $this->form_validation->set_message('validate_payment_start_date', 'Fecha fuera de rango válido');
      return false;
    }

    // Validar fecha real
    if (!checkdate($month, $day, $year)) {
      log_message('debug', 'validate_payment_start_date: fecha inválida por checkdate - ' . $date);
      $this->form_validation->set_message('validate_payment_start_date', 'Fecha inválida');
      return false;
    }

    // Convertir a timestamp para comparación
    $formatted_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $payment_timestamp = strtotime($formatted_date);

    if ($payment_timestamp === false) {
      log_message('debug', 'validate_payment_start_date: strtotime falló para formatted_date - ' . $formatted_date);
      $this->form_validation->set_message('validate_payment_start_date', 'Fecha inválida');
      return false;
    }

    log_message('debug', 'validate_payment_start_date: payment_timestamp=' . date('Y-m-d H:i:s', $payment_timestamp));

    // Comparar con fecha de emisión (solo la fecha, no la hora)
    $emission_datetime = $this->input->post('date');
    log_message('debug', 'validate_payment_start_date: emission_datetime from POST - ' . $emission_datetime);

    if (!empty($emission_datetime)) {
      // Extraer solo la fecha de emisión (ignorar hora)
      $emission_date_only = date('Y-m-d', strtotime($emission_datetime));
      $emission_date_timestamp = strtotime($emission_date_only);

      log_message('debug', 'validate_payment_start_date: emission_date_only=' . $emission_date_only . ', emission_date_timestamp=' . date('Y-m-d H:i:s', $emission_date_timestamp));

      // Permitir fecha de inicio de cobros igual o posterior a la fecha de emisión
      if ($emission_date_timestamp !== false && $payment_timestamp < $emission_date_timestamp) {
        log_message('debug', 'validate_payment_start_date: fecha de cobros anterior a emisión - payment=' . date('Y-m-d', $payment_timestamp) . ' < emission=' . date('Y-m-d', $emission_date_timestamp));
        $this->form_validation->set_message('validate_payment_start_date', 'La fecha de inicio de cobros no puede ser anterior a la fecha de emisión');
        return false;
      }
    }

    // Validar que no sea demasiado futura (máximo 2 años)
    date_default_timezone_set('America/Bogota');
    $max_future = strtotime('+2 years');
    log_message('debug', 'validate_payment_start_date: max_future=' . date('Y-m-d', $max_future));

    if ($payment_timestamp > $max_future) {
      log_message('debug', 'validate_payment_start_date: fecha demasiado futura - payment=' . date('Y-m-d', $payment_timestamp) . ' > max_future=' . date('Y-m-d', $max_future));
      $this->form_validation->set_message('validate_payment_start_date', 'La fecha de inicio de cobros no puede ser posterior a 2 años');
      return false;
    }

    log_message('debug', 'validate_payment_start_date: validación PASSED');
    return true;
  }

  /**
   * Convierte input de moneda colombiana a decimal o entero
   */
  private function parse_currency_input($input, $allow_decimals = true)
  {
    log_message('debug', 'parse_currency_input called with input: ' . $input . ', allow_decimals: ' . ($allow_decimals ? 'true' : 'false'));
    if (empty($input)) {
      log_message('debug', 'parse_currency_input: input empty, returning ' . ($allow_decimals ? '0.00' : '0'));
      return $allow_decimals ? 0.00 : 0;
    }

    // Remover espacios y símbolos
    $input = trim($input);
    $input = str_replace('$', '', $input);
    $input = str_replace(' ', '', $input);
    log_message('debug', 'parse_currency_input: input after clean: ' . $input);

    // Si es un número simple sin formato, devolverlo
    if (is_numeric($input)) {
      $result = $allow_decimals ? (float) $input : (int) $input;
      log_message('debug', 'parse_currency_input: is_numeric true, returning ' . $result);
      return $result;
    }

    // Detectar si , es usado como separador de miles o decimal
    $has_comma = strpos($input, ',') !== false;
    $has_dot = strpos($input, '.') !== false;
    log_message('debug', 'parse_currency_input: has_comma: ' . ($has_comma ? 'true' : 'false') . ', has_dot: ' . ($has_dot ? 'true' : 'false'));

    if ($has_comma && !$has_dot) {
      // Asumir , es separador de miles
      $input = str_replace(',', '.', $input);
      log_message('debug', 'parse_currency_input: replaced , with . : ' . $input);
    } elseif ($has_comma && $has_dot) {
      // Si , está después del último . , asumir , es decimal
      $last_dot = strrpos($input, '.');
      $last_comma = strrpos($input, ',');
      if ($last_comma <= $last_dot) {
        // , es separador de miles, reemplazar con .
        $input = str_replace(',', '.', $input);
        log_message('debug', 'parse_currency_input: , is thousands sep, replaced with . : ' . $input);
      }
      // Si , es después, dejar como está
      log_message('debug', 'parse_currency_input: , is decimal, left as is: ' . $input);
    }
    // Si solo . , asumir es separador de miles

    // Remover separadores de miles
    $input = preg_replace('/\./', '', $input);
    log_message('debug', 'parse_currency_input: removed thousands sep: ' . $input);
    // Convertir , a .
    $input = str_replace(',', '.', $input);
    log_message('debug', 'parse_currency_input: converted , to . : ' . $input);

    $result = $allow_decimals ? (float) $input : (int) $input;
    log_message('debug', 'parse_currency_input: final result: ' . $result);
    return $result;
  }

  /**
   * Parsea input de COP (Colombian Peso) a entero
   */
  public function parse_cop_input($value) {
    if (is_null($value)) return 0;
    // Quitar símbolos de moneda, espacios y letras
    $clean = preg_replace('/[^0-9.,-]/', '', $value);

    // Normalizar separadores (deja solo un punto decimal)
    if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
      // Si hay coma y punto: quitar separador de miles
      if (strrpos($clean, ',') > strrpos($clean, '.')) {
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
      } else {
        $clean = str_replace(',', '', $clean);
      }
    } else {
      $clean = str_replace(',', '', $clean);
    }

    return floatval($clean);
  }


  function view($id)
  {
    log_message('debug', 'Cargando vista de préstamo ID: ' . $id);

    // Validar que se proporcione un ID válido
    if (!$id || !is_numeric($id)) {
      log_message('error', 'ID de préstamo no válido o no proporcionado: ' . $id);
      $this->session->set_flashdata('error', 'ID de préstamo no válido o no proporcionado.');
      redirect('admin/loans');
      return;
    }

    $data['loan'] = $this->loans_m->get_loan($id);

    // Verificar que el préstamo existe
    if (!$data['loan']) {
      log_message('error', 'Préstamo no encontrado con ID: ' . $id);
      $this->session->set_flashdata('error', 'Préstamo no encontrado.');
      redirect('admin/loans');
      return;
    }

    $data['items'] = $this->loans_m->get_loanItems($id);

    // Usar el layout principal para que se apliquen los estilos CSS
    $data['subview'] = 'admin/loans/view';
    $this->load->view('admin/_main_layout', $data);
    log_message('debug', 'Vista de préstamo cargada con layout principal');
  }

  public function ajax_calculate_amortization() {
    header('Content-Type: application/json');
    log_message('debug', 'Iniciando ajax_calculate_amortization con parámetros: ' . json_encode($this->input->post()));

    $tasa_tipo_input = $this->input->post('tasa_tipo');
    log_message('debug', 'tasa_tipo recibido: ' . $tasa_tipo_input);

    // Validaciones adicionales
    $periods = intval($this->input->post('num_months'));
    $principal_input = $this->input->post('credit_amount');
    $interest_rate_input = $this->input->post('interest_amount');
    $start_date = $this->input->post('date');
    $payment_day = date('j', strtotime($start_date));
    error_log("Fecha de inicio recibida: " . $start_date);

    if (!is_numeric($periods) || $periods <= 0) {
      log_message('error', 'Número de períodos inválido: ' . $periods);
      echo json_encode(['success' => false, 'error' => 'El número de períodos debe ser mayor a 0']);
      return;
    }

    $principal = $this->parse_cop_input($principal_input);
    if ($principal <= 0) {
      log_message('error', 'Principal inválido: ' . $principal);
      echo json_encode(['success' => false, 'error' => 'El principal debe ser mayor a 0']);
      return;
    }

    $interest_rate = floatval($interest_rate_input);
    if ($interest_rate < 0) {
      log_message('error', 'Tasa de interés inválida: ' . $interest_rate);
      echo json_encode(['success' => false, 'error' => 'La tasa de interés no puede ser negativa']);
      return;
    }

    if (empty($start_date) || !strtotime($start_date)) {
      log_message('error', 'Fecha de inicio inválida: ' . $start_date);
      echo json_encode(['success' => false, 'error' => 'La fecha de inicio debe ser una fecha válida']);
      return;
    }

    // Validaciones
    // Validaciones
    $monto_input = $this->input->post('credit_amount');
    if (empty($monto_input)) {
      log_message('error', 'Monto es requerido');
      echo json_encode(['success' => false, 'error' => 'Monto es requerido']);
      return;
    }
    $monto = $this->parse_cop_input($monto_input);
    if ($monto <= 0) {
      log_message('error', 'Monto debe ser mayor a 0: ' . $monto);
      echo json_encode(['success' => false, 'error' => 'Monto debe ser mayor a 0']);
      return;
    }
    if ($monto > 1000000000) { // Validación adicional: monto no mayor a 1 billón
      log_message('error', 'Monto demasiado grande: ' . $monto);
      echo json_encode(['success' => false, 'error' => 'Monto no puede ser mayor a 1.000.000.000']);
      return;
    }

    $interes = floatval($this->input->post('interest_amount')); // porcentaje anual
    if ($interes < 0) {
      log_message('error', 'Interés no puede ser negativo: ' . $interes);
      echo json_encode(['success' => false, 'error' => 'Interés no puede ser negativo']);
      return;
    }
    if ($interes > 100) { // Validación adicional: interés no mayor a 100%
      log_message('error', 'Interés no puede ser mayor a 100%: ' . $interes);
      echo json_encode(['success' => false, 'error' => 'Interés no puede ser mayor a 100%']);
      return;
    }

    $num_months_ajax = intval($this->input->post('num_months'));
    if ($num_months_ajax <= 0 || $num_months_ajax > 120) {
      log_message('error', 'Plazo en meses debe estar entre 1 y 120: ' . $num_months_ajax);
      echo json_encode(['success' => false, 'error' => 'Plazo en meses debe estar entre 1 y 120']);
      return;
    }

    $rate_type = strtolower($this->input->post('tasa_tipo')) ?: 'tna'; // Usar el valor enviado o default TNA
    log_message('debug', 'rate_type en ajax_calculate_amortization: ' . $rate_type);

    $forma_pago = strtolower($this->input->post('payment_m')); // mensual, quincenal...
    if (!in_array($forma_pago, ['mensual', 'quincenal', 'semanal', 'diario'])) {
      log_message('error', 'Forma de pago inválida: ' . $forma_pago);
      echo json_encode(['success' => false, 'error' => 'Forma de pago inválida']);
      return;
    }

    // Obtener tipo de amortización directamente como método de la librería
    $method = $this->input->post('amortization_type');
    error_log("Amortization recibido en ajax: " . $this->input->post('amortization_type'));
    error_log("Amortization type en controlador: " . $method);
    // Validar y corregir method si es inválido
    if (!in_array($method, ['francesa', 'estaunidense', 'mixta'])) {
        log_message('debug', 'method inválido recibido: ' . $method . ', usando default francesa');
        $method = 'francesa';
    }
    log_message('debug', 'Valor de method validado: ' . $method);

    if (!in_array($method, ['francesa', 'estaunidense', 'mixta'])) {
        log_message('error', 'Tipo de amortización inválido después de validación: ' . $method);
        echo json_encode(['success' => false, 'error' => 'Debes seleccionar un tipo de amortización válido.']);
        return;
    }

    // Validación adicional: fecha de inicio
    $start_date = $this->input->post('date');
    if (empty($start_date) || !strtotime($start_date)) {
      log_message('error', 'Fecha de inicio inválida: ' . $start_date);
      echo json_encode(['success' => false, 'error' => 'Fecha de inicio es requerida y debe ser válida']);
      return;
    }

    // Calcular días por período
    switch ($forma_pago) {
      case 'diario': $days_per_period = 1; break;
      case 'semanal': $days_per_period = 7; break;
      case 'quincenal': $days_per_period = 15; break;
      case 'mensual': $days_per_period = 30; break; // 30 días fijos
      default: $days_per_period = 30;
    }

    // Calcular periods_per_year propuesto
    switch ($forma_pago) {
      case 'diario': $periods_per_year_ajax = 365; break;
      case 'semanal': $periods_per_year_ajax = 52; break;
      case 'quincenal': $periods_per_year_ajax = 24; break; // 365/15 ≈ 24.33, usar 24 fijo
      case 'mensual': $periods_per_year_ajax = 12; break;
      default: $periods_per_year_ajax = 12;
    }
    // Calcular número de cuotas basado en num_months
    $nro_cuotas = $num_months_ajax * ($periods_per_year_ajax / 12);
    log_message('debug', 'Ajax - forma_pago=' . $forma_pago . ', days_per_period=' . $days_per_period . ', periods_per_year=' . $periods_per_year_ajax . ', num_months=' . $num_months_ajax . ', nro_cuotas=' . $nro_cuotas);

    // Calcular tasa periódica
    $period_rate = $this->loans_m->get_period_rate($interes, $periods_per_year_ajax, $rate_type);
    log_message('debug', 'Tasa periódica calculada: ' . $period_rate);

    try {
       // Obtener payment_start_date directamente (ya no necesitamos convertir a día)
       $payment_start_date_ajax = $this->input->post('payment_start_date');

       // Calcular tabla de amortización usando la librería
       $result = $this->amortization->ajax_calculate_amortization(
         $monto,
         $period_rate,
         $nro_cuotas,
         $forma_pago,
         $start_date,
         $method,
         $rate_type,
         $payment_start_date_ajax
       );

      if ($result['success']) {
        log_message('info', 'Cálculo de amortización completado exitosamente');
        // Mapear tabla al formato esperado por el frontend
        $tabla = [];
        foreach ($result['amortization_table'] as $row) {
          $tabla[] = [
            'periodo' => $row['period'],
            'fecha' => $row['payment_date'],
            'cuota' => $row['payment'],
            'capital' => $row['principal'],
            'interes' => $row['interest'],
            'saldo' => $row['balance']
          ];
        }

        echo json_encode([
          'success' => true,
          'data' => [
            'cuota' => $result['amortization_table'][0]['payment'] ?? 0,
            'totalCuotas' => $result['summary']['total_payments'] ?? 0,
            'totalInteres' => $result['summary']['total_interest'] ?? 0,
            'totalCapital' => $result['summary']['total_principal'] ?? 0,
            'nCuotas' => count($result['amortization_table']),
            'tasa_aplicada' => 'Calculada',
            'tabla' => $tabla
          ]
        ]);
      } else {
        log_message('error', 'Error en cálculo AJAX: ' . $result['error']);
        echo json_encode(['success' => false, 'error' => $result['error']]);
      }

    } catch (Exception $e) {
      log_message('error', 'Error en cálculo de amortización: ' . $e->getMessage());
      echo json_encode(['success' => false, 'error' => 'Error en el cálculo: ' . $e->getMessage()]);
    }
  }
  /**
   * Valida el límite del monto del préstamo según tipo de cliente y historial
   */
  public function validate_loan_limit($amount) {
    log_message('debug', 'Monto recibido (raw): ' . $amount);
    $customer_id = $this->input->post('customer_id');
    error_log("customer_id: " . $customer_id);
    log_message('debug', 'validate_loan_limit called with customer_id: ' . $customer_id);

    if (!$customer_id) {
      log_message('debug', 'customer_id is null or empty');
      $this->form_validation->set_message('validate_loan_limit', 'Debe seleccionar un cliente');
      return FALSE;
    }

    // Definir si es edición y obtener loan_id
    $loan_id = $this->input->get('id');
    $is_edit = !empty($loan_id);

    // Verificar si tiene préstamo activo para logging
    $this->db->select('l.id');
    $this->db->from('loans l');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->where('l.status', 1);
    $active_loan = $this->db->get()->row();

    // Obtener tipo_cliente desde DB
    $customer = $this->db->select('tipo_cliente')->where('id', $customer_id)->get('customers')->row();
    log_message('debug', 'Customer query result: ' . json_encode($customer));
    $tipo_cliente = $customer ? trim(strtolower($customer->tipo_cliente)) : 'normal';
    error_log("tipo_cliente: " . $tipo_cliente);
    log_message('debug', 'Tipo cliente: ' . $tipo_cliente);

    // CORRECCIÓN: Verificar si el cliente tiene cuotas pendientes (balance > 0) en préstamos activos
    // En lugar de solo verificar si tiene un préstamo con status = 1
    $this->db->select('SUM(COALESCE(li.balance, 0)) as total_pending_balance, COUNT(*) as pending_installments');
    $this->db->from('loans l');
    $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->where('l.status', 1); // Solo préstamos activos
    $this->db->where('li.status !=', 0); // Solo cuotas no completamente pagadas
    $this->db->where('li.extra_payment !=', 3); // Excluir condonadas
    $pending_check = $this->db->get()->row();

    $has_pending_installments = $pending_check && ($pending_check->total_pending_balance > 0.01 || $pending_check->pending_installments > 0);
    if ($has_pending_installments) {
        log_message('debug', 'VALIDATION FAILED: Customer has pending installments');
        $this->form_validation->set_message('validate_loan_limit', 'El cliente tiene cuotas pendientes de pago. No se permite un nuevo préstamo hasta completar el pago de las cuotas actuales.');
        return FALSE;
    }
    error_log("pending_installments: " . ($has_pending_installments ? 'true' : 'false'));
    log_message('debug', 'Pending installments check result: ' . json_encode($pending_check));
    log_message('debug', 'VALIDATE_LOAN_LIMIT: pending_installments check - customer_id=' . $customer_id . ', has_pending=' . ($has_pending_installments ? '1' : '0'));
    $estado = $has_pending_installments ? 'Pendiente' : 'Sin pendientes';
    $balance = $pending_check ? $pending_check->total_pending_balance : 0;

    // Si especial, sin límite
    if ($tipo_cliente == 'especial') {
      log_message('debug', 'Cliente especial, sin límite - VALIDATION PASSED');
      return TRUE;
    }

    // Contar préstamos completados (balance = 0)
    $this->db->select('COUNT(*) as completed_count');
    $this->db->from('loans l');
    $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->group_by('l.id');
    $this->db->having('SUM(COALESCE(li.balance, 0)) = 0');
    $completed_query = $this->db->get();
    log_message('debug', 'Completed loans query result: ' . json_encode($completed_query->result()));
    $completed_count = $completed_query->num_rows(); // Número de préstamos completados
    error_log("completed_count: " . $completed_count);
    log_message('debug', 'completed_count: ' . $completed_count);
    log_message('debug', 'VALIDATE_LOAN_LIMIT: completed_count calculation - customer_id=' . $customer_id . ', completed_count=' . $completed_count);

    // Calcular max_limit
    if ($completed_count >= 2) {
      $max_limit = 5000000;
    } elseif ($completed_count == 1) {
      $max_limit = 1200000;
    } else {
      $max_limit = 500000;
    }
    error_log("max_limit: " . $max_limit);
    log_message('debug', 'max_limit calculated: ' . $max_limit);

    // Convertir el monto al formato numérico
    $parsed_amount = $this->parse_colombian_currency($amount);
    error_log("VALIDATE_LOAN_LIMIT: customer_id=$customer_id, tipo_cliente_db=$tipo_cliente, raw_amount=\"$amount\", parsed_amount=$parsed_amount, completed_count=$completed_count, max_limit=$max_limit, has_active_loan=" . ($active_loan ? '1' : '0'));
    error_log("parsed_amount: " . $parsed_amount);
    log_message('debug', 'cliente_id: ' . $customer_id . ', estado: ' . $estado . ', balance: ' . $balance . ', tipo_cliente: ' . $tipo_cliente . ', completed_count: ' . $completed_count . ', max_limit: ' . $max_limit);
    error_log("VALIDATE_LOAN_LIMIT: tipo_cliente=$tipo_cliente, completed_count=$completed_count, credit_amount=$parsed_amount, max_limit=$max_limit, status=$estado");
    log_message('debug', 'Monto parseado: ' . $parsed_amount);
    error_log("VALIDATE_LOAN_LIMIT_CHECK: parsed_amount=$parsed_amount, max_limit=$max_limit, exceeds=" . ($parsed_amount > $max_limit ? 'yes' : 'no'));
    log_message('error', 'DEBUG_LIMIT: ' . json_encode(['tipo_cliente' => $tipo_cliente, 'completed_count' => $completed_count, 'max_limit' => $max_limit, 'parsed_amount' => $parsed_amount, 'has_active_loan' => $active_loan ? 1 : 0]));
    if ($parsed_amount === false || $parsed_amount > $max_limit) {
      log_message('debug', 'VALIDATION FAILED: parsed_amount=' . $parsed_amount . ' > max_limit=' . $max_limit . ' or parsed_amount is false');
      $this->form_validation->set_message('validate_loan_limit',
        'Límite excedido. Monto solicitado: $' . number_format($parsed_amount, 0, ',', '.') . ', Límite: $' . number_format($max_limit, 0, ',', '.') . ', Tipo: ' . $tipo_cliente . ', Completados: ' . $completed_count);
      error_log("VALIDATE_LOAN_LIMIT_CHECK: allowed=false, reason=\"exceeds_limit\"");
      return FALSE;
    }

    log_message('debug', 'VALIDATION PASSED: parsed_amount=' . $parsed_amount . ' <= max_limit=' . $max_limit);
    error_log("VALIDATE_LOAN_LIMIT_CHECK: allowed=true, reason=\"" . ($active_loan ? 'active_loan' : 'ok') . "\"");
    return TRUE;
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

     // Detectar formato mixto para logs adicionales
     $has_comma = strpos($value, ',') !== false;
     $has_dot = strpos($value, '.') !== false;
     if ($has_comma && $has_dot) {
       $last_comma_pos = strrpos($value, ',');
       $last_dot_pos = strrpos($value, '.');
       if ($last_comma_pos > $last_dot_pos) {
         log_message('debug', 'parse_colombian_currency: formato mixto detectado, coma como decimal (última coma después de último punto)');
       } else {
         log_message('debug', 'parse_colombian_currency: formato mixto detectado, posible error en separadores');
       }
     }

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

  public function ajax_get_credit_limit() {
    header('Content-Type: application/json');
    $customer_id = $this->input->post('customer_id');

    if (!$customer_id) {
      echo json_encode(['error' => 'Customer ID is required']);
      exit;
    }

    try {
      // Asegurar consistencia: actualizar status de préstamos pagados
      $this->db->query("UPDATE loans l SET l.status = 0 WHERE l.status = 1 AND (SELECT SUM(COALESCE(li.balance, 0)) FROM loan_items li WHERE li.loan_id = l.id) = 0");

      $this->db->select('l.status');
      $this->db->from('loans l');
      $this->db->where('l.customer_id', $customer_id);
      $this->db->where('l.status', 1);
      $active_loan = $this->db->get()->row();
      $has_active_loan = $active_loan ? true : false;

      $tipo_cliente = 'normal';
      $completed_count = 0;
      $limit = 0;

      if ($has_active_loan) {
          $limit = 0;
      } else {
          $customer = $this->db->select('tipo_cliente')->where('id', $customer_id)->get('customers')->row();
          $tipo_cliente = $customer ? trim(strtolower($customer->tipo_cliente)) : 'normal';
          $this->db->select('COUNT(*) as completed_count');
          $this->db->from('loans l');
          $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
          $this->db->where('l.customer_id', $customer_id);
          $this->db->group_by('l.id');
          $this->db->having('SUM(COALESCE(li.balance, 0)) = 0');
          $completed_query = $this->db->get();
          $completed_count = $completed_query->num_rows();
          $limit = $this->loans_m->get_customer_limit($customer_id);
      }
      error_log("AJAX_GET_CREDIT_LIMIT: customer_id=$customer_id -> tipo=$tipo_cliente limit=$limit completed_count=$completed_count");
      echo json_encode(['limit' => $limit]);
    } catch (Exception $e) {
      error_log("AJAX_GET_CREDIT_LIMIT ERROR: " . $e->getMessage());
      echo json_encode(['error' => 'Internal server error']);
    }
    exit;
  }

  public function ajax_get_current_time() {
   header('Content-Type: application/json');
   date_default_timezone_set('America/Bogota');

   $current_time = date('d/m/Y H:i:s'); // Con segundos para tiempo real
   $timestamp = time();
   $timezone = 'America/Bogota (UTC-5)';

   error_log("ajax_get_current_time called - Time: $current_time, Timestamp: $timestamp");

   echo json_encode([
     'current_time' => $current_time,
     'timestamp' => $timestamp,
     'timezone' => $timezone
   ]);
   exit;
 }

 /**
  * Obtiene cuotas de un préstamo para pago personalizado
  */
 public function ajax_get_loan_quotas() {
   header('Content-Type: application/json');

   try {
     $loan_id = $this->input->post('loan_id');

     if (!$loan_id || !is_numeric($loan_id)) {
       throw new Exception('ID de préstamo inválido');
     }

     // Verificar que el préstamo existe y está activo
     $loan = $this->loans_m->get_loan($loan_id);
     if (!$loan || $loan->status != 1) {
       throw new Exception('Préstamo no encontrado o inactivo');
     }

     // Obtener cuotas del préstamo
     $quotas = $this->loans_m->get_loanItems($loan_id);

     // Filtrar y formatear cuotas
     $formatted_quotas = [];
     foreach ($quotas as $quota) {
       // Solo incluir cuotas que no estén completamente pagadas (status != 0)
       if ($quota->status != 0) {
         $formatted_quotas[] = [
           'id' => $quota->id,
           'loan_id' => $quota->loan_id,
           'num_quota' => $quota->num_quota,
           'date' => $quota->date,
           'fee_amount' => $quota->fee_amount,
           'interest_amount' => $quota->interest_amount,
           'capital_amount' => $quota->capital_amount,
           'balance' => $quota->balance,
           'status' => $quota->status
         ];
       }
     }

     echo json_encode([
       'success' => true,
       'data' => $formatted_quotas
     ]);

   } catch (Exception $e) {
     echo json_encode([
       'success' => false,
       'error' => $e->getMessage()
     ]);
   }

   exit;
 }

 /**
  * Procesa pago personalizado aplicando monto secuencialmente a cuotas seleccionadas
  */
 public function ajax_custom_payment() {
   header('Content-Type: application/json');

   try {
     // Validar método POST
     if (!$this->input->is_ajax_request() || $this->input->method() !== 'post') {
       throw new Exception('Método no permitido');
     }

     // Obtener y validar parámetros
     $loan_item_ids = $this->input->post('loan_item_ids');
     $custom_amount = floatval($this->input->post('custom_amount'));
     $payment_description = trim($this->input->post('payment_description', true));

     // Validaciones básicas
     if (empty($loan_item_ids) || !is_array($loan_item_ids)) {
       throw new Exception('Debe seleccionar al menos una cuota');
     }

     if ($custom_amount <= 0) {
       throw new Exception('El monto debe ser un número positivo mayor a 0');
     }

     if (strlen($payment_description) > 255) {
       throw new Exception('La descripción no puede exceder 255 caracteres');
     }

     $loan_id = $this->input->post('loan_id');
     if (!$loan_id || !is_numeric($loan_id)) {
       throw new Exception('ID de préstamo inválido');
     }

     // Usar la nueva librería PaymentCalculator para procesamiento preciso
     $selected_installments = [];
     foreach ($loan_item_ids as $item_id) {
       $selected_installments[] = ['id' => $item_id];
     }

     $result = $this->paymentcalculator->process_custom_payment(
       $loan_id,
       $selected_installments,
       $custom_amount,
       $payment_description
     );

     // Generar audit trail
     $this->paymentcalculator->generate_audit_trail($loan_id, [
       'total_payment_amount' => $custom_amount,
       'payment_breakdown' => $result['payment_breakdown'],
       'redistribution_log' => $result['redistribution_log']
     ]);

     if ($result['success']) {
       echo json_encode([
         'success' => true,
         'message' => $result['message'],
         'data' => [
           'total_processed' => $result['total_processed'],
           'remaining_amount' => $result['remaining_amount'],
           'breakdown' => $result['payment_breakdown'],
           'redistribution_log' => $result['redistribution_log']
         ]
       ]);
     } else {
       echo json_encode([
         'success' => false,
         'error' => $result['message']
       ]);
     }

   } catch (Exception $e) {
     log_message('error', 'Error en ajax_custom_payment: ' . $e->getMessage());

     echo json_encode([
       'success' => false,
       'error' => $e->getMessage()
     ]);
   }

   exit;
 }

  /**
   * Genera y descarga PDF de la tabla de amortización
   */
  public function generate_amortization_pdf() {
    try {
      // Verificar que se tenga la información necesaria
      $credit_amount = $this->input->post('credit_amount');
      $interest_amount = $this->input->post('interest_amount');
      $num_months = $this->input->post('num_months');
      $payment_m = $this->input->post('payment_m');
      $amortization_type = $this->input->post('amortization_type');
      $date = $this->input->post('date');
      $tasa_tipo = $this->input->post('tasa_tipo') ?: 'TNA';

      if (!$credit_amount || !$interest_amount || !$num_months || !$payment_m || !$amortization_type || !$date) {
        throw new Exception('Faltan parámetros requeridos para generar el PDF');
      }

      // Calcular la amortización usando la misma lógica que ajax_calculate_amortization
      $principal = $this->parse_cop_input($credit_amount);
      $interest_rate = floatval($interest_amount);
      $periods = intval($num_months);
      $payment_frequency = $payment_m;
      $method = $amortization_type;
      $start_date = $date;
      $payment_day = date('j', strtotime($start_date));

      // Calcular días por período
      switch ($payment_frequency) {
        case 'diario': $days_per_period = 1; break;
        case 'semanal': $days_per_period = 7; break;
        case 'quincenal': $days_per_period = 15; break;
        case 'mensual': $days_per_period = 30; break;
        default: $days_per_period = 30;
      }

      // Calcular periods_per_year
      switch ($payment_frequency) {
        case 'diario': $periods_per_year = 365; break;
        case 'semanal': $periods_per_year = 52; break;
        case 'quincenal': $periods_per_year = 24; break;
        case 'mensual': $periods_per_year = 12; break;
        default: $periods_per_year = 12;
      }

      // Calcular número de cuotas basado en num_months
      $nro_cuotas = $periods * ($periods_per_year / 12);

      // Calcular tasa periódica
      $period_rate = $this->loans_m->get_period_rate($interest_rate, $periods_per_year, $tasa_tipo);

      // Calcular tabla de amortización
      $amortization_table = $this->amortization->calculate_amortization_table(
        $principal,
        $period_rate,
        $nro_cuotas,
        $payment_frequency,
        $start_date,
        $method,
        $payment_day
      );

      if (empty($amortization_table)) {
        throw new Exception('No se pudo generar la tabla de amortización');
      }

      // Calcular resumen
      $summary = $this->amortization->calculate_loan_summary($amortization_table);

      // Generar PDF usando formato APA
      require_once APPPATH . 'third_party/fpdf183/pdf_apa.php';

      // Obtener nombre del usuario logueado
      $current_user = $this->user_m->get_current_user();
      $user_name = $current_user ? $current_user->first_name . ' ' . $current_user->last_name : 'Usuario desconocido';

      // Crear instancia PDF con formato APA
      $pdf = new PDF_APA('P', 'mm', 'A4');
      $pdf->setTitle('Tabla de Amortización - Sistema de Préstamos');
      $pdf->setAuthor($user_name);

      // Agregar referencias APA si es necesario
      $pdf->addReference('Sistema de Gestión de Préstamos. (2024). Tabla de amortización generada automáticamente.');
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
      $pdf->createSection('Información del Préstamo', 1);

      // Información del préstamo
      $loan_info = [
          ['Monto del Préstamo:', $pdf->formatCurrency($principal)],
          ['Tasa de Interés:', number_format($interest_rate, 2, ',', '.') . '% ' . strtoupper($tasa_tipo)],
          ['Plazo:', $periods . ' meses (' . $nro_cuotas . ' cuotas)'],
          ['Forma de Pago:', ucfirst($payment_frequency)],
          ['Tipo de Amortización:', ucfirst($method)],
          ['Fecha de Inicio:', $pdf->formatDate($start_date)],
          ['Fecha de Pago:', $pdf->formatDate($start_date)]
      ];

      foreach ($loan_info as $info) {
          $pdf->Cell(60, 8, utf8_decode($info[0]), 0, 0);
          $pdf->Cell(0, 8, utf8_decode($info[1]), 0, 1);
      }

      $pdf->Ln(5);

      // Resumen financiero
      $pdf->createSection('Resumen Financiero', 1);

      $financial_summary = [
          ['Valor por Cuota:', $pdf->formatCurrency($amortization_table[0]['payment'])],
          ['Total de Intereses:', $pdf->formatCurrency($summary['total_interest'])],
          ['Total a Pagar:', $pdf->formatCurrency($summary['total_payments'])]
      ];

      foreach ($financial_summary as $summary_item) {
          $pdf->Cell(60, 8, utf8_decode($summary_item[0]), 0, 0);
          $pdf->Cell(0, 8, utf8_decode($summary_item[1]), 0, 1);
      }

      $pdf->Ln(5);

      // Tabla de amortización con formato APA
      $pdf->createSection('Tabla de Amortización Detallada', 1);

      // Preparar datos de tabla
      $headers = ['Período', 'Fecha', 'Cuota', 'Capital', 'Interés', 'Saldo'];
      $table_data = [];

      foreach ($amortization_table as $row) {
          $table_data[] = [
              $row['period'],
              $pdf->formatDate($row['payment_date']),
              $pdf->formatCurrency($row['payment']),
              $pdf->formatCurrency($row['principal']),
              $pdf->formatCurrency($row['interest']),
              $pdf->formatCurrency($row['balance'])
          ];
      }

      // Definir anchos de columna
      $widths = [20, 25, 30, 30, 30, 30];

      // Crear tabla con formato APA
      $pdf->createTable($headers, $table_data, $widths);

      // Agregar sección de referencias si hay referencias
      $pdf->createReferencesPage();

      // Generar y enviar el PDF
      $pdf_content = $pdf->Output('', 'S');

      // Configurar headers para descarga
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="tabla_amortizacion_' . date('Y-m-d_H-i-s') . '.pdf"');
      header('Content-Length: ' . strlen($pdf_content));
      header('Cache-Control: private, max-age=0, must-revalidate');
      header('Pragma: public');

      echo $pdf_content;
      exit;

    } catch (Exception $e) {
      log_message('error', 'Error generando PDF de amortización: ' . $e->getMessage());
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'Error al generar el PDF: ' . $e->getMessage()]);
      exit;
    }
  }

}
  
  /* End of file Loans.php */
  /* Location: ./application/controllers/admin/Loans.php */
