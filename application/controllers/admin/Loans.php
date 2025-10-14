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
    $this->load->helper('currency');
    $this->load->helper('format');
    $this->load->helper('permission');
  }

  public function index()
  {
    log_message('debug', 'Cargando lista de préstamos');
    $data['loans'] = $this->loans_m->get_loans();
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
    $raw_post = $this->input->post();
    log_message('debug', 'Validation rules set for loan ' . ($is_edit ? 'update' : 'creation'));
    log_message('debug', 'POST data received: ' . json_encode($raw_post));

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
      $active_loan = $this->db->get()->row();
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

    if ($this->form_validation->run() == TRUE) {

      // Validación adicional: impedir si cliente tiene préstamo activo
      $customer_id = $this->input->post('customer_id');
      $this->db->select('l.status');
      $this->db->from('loans l');
      $this->db->where('l.customer_id', $customer_id);
      if ($is_edit) {
        $this->db->where('l.id !=', $loan_id);
      }
      $this->db->where('l.status', 1);
      $active_loan = $this->db->get()->row();
      error_log("ACTIVE_LOAN_VALIDATION: customer_id=$customer_id, active_loan=" . json_encode($active_loan));
      if ($active_loan) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'Persona con préstamo pendiente']);
          return;
        } else {
          $this->session->set_flashdata('error', 'Persona con préstamo pendiente');
          redirect('admin/loans/edit');
        }
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
      $start_date = $raw_post['date'];
      $payment_day = date('j', strtotime($start_date));
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
      if ($principal <= 0) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El monto del préstamo debe ser mayor a 0']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El monto del préstamo debe ser mayor a 0');
          redirect('admin/loans/edit');
        }
      }

      if ($interest_rate < 0) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'La tasa de interés no puede ser negativa']);
          return;
        } else {
          $this->session->set_flashdata('error', 'La tasa de interés no puede ser negativa');
          redirect('admin/loans/edit');
        }
      }

      if ($num_months <= 0 || $num_months > 120) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El plazo en meses debe estar entre 1 y 120']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El plazo en meses debe estar entre 1 y 120');
          redirect('admin/loans/edit');
        }
      }

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

      // Validar fecha de inicio
      if (empty($start_date) || !strtotime($start_date)) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'La fecha de inicio es requerida y debe ser válida']);
          return;
        } else {
          $this->session->set_flashdata('error', 'La fecha de inicio es requerida y debe ser válida');
          redirect('admin/loans/edit');
        }
      }

      try {
        // Calcular tabla de amortización usando la librería
        $amortization_table = $this->amortization->calculate_amortization_table(
          $principal,
          $period_rate,
          $periods,
          $payment_frequency,
          $start_date,
          $amortization_type,
          $payment_day
        );

        // Verificar que se generó la tabla correctamente
        if (empty($amortization_table)) {
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
          'tipo_cliente'
        ]);
        $loan_data['amortization_type'] = $amortization_type;
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
      $data['subview'] = 'admin/loans/edit';
      $this->load->view('admin/_main_layout', $data);
    }
  }

  function ajax_searchCst()
  {
    $dni = $this->input->post('dni');
    log_message('debug', 'Buscando cliente por DNI: ' . $dni);
    $cst = $this->loans_m->get_searchCst($dni);
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
    $data['loan'] = $this->loans_m->get_loan($id);
    $data['items'] = $this->loans_m->get_loanItems($id);

    $this->load->view('admin/loans/view', $data);
    log_message('debug', 'Vista de préstamo cargada');
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
      // Calcular tabla de amortización usando la librería
      $amortization_table = $this->amortization->calculate_amortization_table(
        $monto,
        $period_rate,
        $nro_cuotas,
        $forma_pago,
        $start_date,
        $method,
        $payment_day
      );

      // Verificar que se generó la tabla correctamente
      if (empty($amortization_table)) {
        throw new Exception('No se pudo generar la tabla de amortización');
      }
      log_message('debug', 'Tabla de amortización calculada con ' . count($amortization_table) . ' pagos');

      // Calcular resumen
      $summary = $this->amortization->calculate_loan_summary($amortization_table);

      // Calcular totales
      $cuota = $amortization_table[0]['payment'];
      $totalCuotas = $summary['total_payments'];
      $totalInteres = $summary['total_interest'];
      $totalCapital = $summary['total_principal'];
      $nCuotas = count($amortization_table);

      // Mapear tabla
      $tabla = [];
      foreach ($amortization_table as $row) {
        $tabla[] = [
          'periodo' => $row['period'],
          'fecha' => $row['payment_date'],
          'cuota' => $row['payment'],
          'capital' => $row['principal'],
          'interes' => $row['interest'],
          'saldo' => $row['balance']
        ];
      }

      // Preparar data
      $data = [
        'cuota' => $cuota,
        'totalCuotas' => $totalCuotas,
        'totalInteres' => $totalInteres,
        'totalCapital' => $totalCapital,
        'nCuotas' => $nCuotas,
        'tasa_aplicada' => number_format($period_rate * 100, 4) . '%',
        'tabla' => $tabla
      ];

      log_message('info', 'Cálculo de amortización completado exitosamente');
      echo json_encode([
        'success' => true,
        'data' => $data
      ]);

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

    // Obtener tipo_cliente desde DB
    $customer = $this->db->select('tipo_cliente')->where('id', $customer_id)->get('customers')->row();
    log_message('debug', 'Customer query result: ' . json_encode($customer));
    $tipo_cliente = $customer ? trim(strtolower($customer->tipo_cliente)) : 'normal';
    error_log("tipo_cliente: " . $tipo_cliente);
    log_message('debug', 'Tipo cliente: ' . $tipo_cliente);

    // Obtener estado y balance del préstamo activo
    $this->db->select('l.status, SUM(COALESCE(li.balance, 0)) as total_balance');
    $this->db->from('loans l');
    $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->group_by('l.id');
    $this->db->having('SUM(COALESCE(li.balance, 0)) > 0');
    $active_loan = $this->db->get()->row();
    $has_active_loan = $active_loan ? true : false;
    if ($has_active_loan) {
        $this->form_validation->set_message('validate_loan_limit', 'El cliente tiene un préstamo activo. No se permite un nuevo préstamo.');
        return FALSE;
    }
    error_log("active_loan: " . ($active_loan ? 'true' : 'false'));
    log_message('debug', 'Active loan query result: ' . json_encode($active_loan));
    log_message('debug', 'VALIDATE_LOAN_LIMIT: has_active_loan check - customer_id=' . $customer_id . ', active_loan=' . json_encode($active_loan));
    $estado = $active_loan ? $active_loan->status : 'No activo';
    $balance = $active_loan ? $active_loan->total_balance : 0;

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
    log_message('debug', 'parse_colombian_currency called with value: ' . $value);
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

    // Asegurar consistencia: actualizar status de préstamos pagados
    $this->db->query("UPDATE loans l SET l.status = 0 WHERE l.status = 1 AND (SELECT SUM(COALESCE(li.balance, 0)) FROM loan_items li WHERE li.loan_id = l.id) = 0");

    $this->db->select('l.status');
    $this->db->from('loans l');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->where('l.status', 1);
    $active_loan = $this->db->get()->row();
    $has_active_loan = $active_loan ? true : false;
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

      // Generar PDF usando FPDF
      require_once APPPATH . 'third_party/fpdf183/fpdf.php';

      $pdf = new FPDF('P', 'mm', 'A4');
      $pdf->AddPage();

      // Configurar colores del modal (azul principal)
      $primaryColor = [0, 123, 255]; // Bootstrap primary blue
      $secondaryColor = [108, 117, 125]; // Bootstrap secondary gray
      $lightColor = [248, 249, 250]; // Light background
      $darkColor = [33, 37, 41]; // Dark text

      // Configurar fuente y colores
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);

      // Título con color
      $pdf->Cell(0, 10, 'TABLA DE AMORTIZACION', 0, 1, 'C');
      $pdf->Ln(5);

      // Información del préstamo con colores
      $pdf->SetFont('Arial', 'B', 12);
      $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
      $pdf->Cell(0, 8, 'INFORMACION DEL PRESTAMO', 0, 1, 'L');
      $pdf->Ln(2);

      $pdf->SetFont('Arial', '', 10);
      $pdf->SetTextColor($darkColor[0], $darkColor[1], $darkColor[2]);
      $pdf->Cell(50, 6, 'Monto del Prestamo:', 0, 0);
      $pdf->Cell(0, 6, '$ ' . number_format($principal, 0, ',', '.'), 0, 1);

      $pdf->Cell(50, 6, 'Tasa de Interes:', 0, 0);
      $pdf->Cell(0, 6, number_format($interest_rate, 2, ',', '.') . '% ' . strtoupper($tasa_tipo), 0, 1);

      $pdf->Cell(50, 6, 'Plazo:', 0, 0);
      $pdf->Cell(0, 6, $periods . ' meses (' . $nro_cuotas . ' cuotas)', 0, 1);

      $pdf->Cell(50, 6, 'Forma de Pago:', 0, 0);
      $pdf->Cell(0, 6, ucfirst($payment_frequency), 0, 1);

      $pdf->Cell(50, 6, 'Tipo de Amortizacion:', 0, 0);
      $pdf->Cell(0, 6, ucfirst($method), 0, 1);

      $pdf->Cell(50, 6, 'Fecha de Inicio:', 0, 0);
      $pdf->Cell(0, 6, date('d/m/Y', strtotime($start_date)), 0, 1);

      $pdf->Cell(50, 6, 'Fecha de Pago:', 0, 0);
      $pdf->Cell(0, 6, date('d/m/Y', strtotime($start_date)), 0, 1);

      $pdf->Ln(5);

      // Resumen financiero
      $pdf->SetFont('Arial', 'B', 12);
      $pdf->Cell(0, 8, 'RESUMEN FINANCIERO', 0, 1, 'L');
      $pdf->Ln(2);

      $pdf->SetFont('Arial', '', 10);
      $pdf->Cell(50, 6, 'Valor por Cuota:', 0, 0);
      $pdf->Cell(0, 6, '$ ' . number_format($amortization_table[0]['payment'], 0, ',', '.'), 0, 1);

      $pdf->Cell(50, 6, 'Total de Intereses:', 0, 0);
      $pdf->Cell(0, 6, '$ ' . number_format($summary['total_interest'], 0, ',', '.'), 0, 1);

      $pdf->Cell(50, 6, 'Total a Pagar:', 0, 0);
      $pdf->Cell(0, 6, '$ ' . number_format($summary['total_payments'], 0, ',', '.'), 0, 1);

      $pdf->Ln(5);

      // Tabla de amortización con colores
      $pdf->SetFont('Arial', 'B', 10);
      $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
      $pdf->Cell(0, 8, 'TABLA DE AMORTIZACION DETALLADA', 0, 1, 'L');
      $pdf->Ln(2);

      // Encabezados de tabla con colores del modal
      $pdf->SetFont('Arial', 'B', 8);
      $pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]); // Azul principal
      $pdf->SetTextColor(255, 255, 255); // Texto blanco

      $pdf->Cell(15, 8, 'Periodo', 1, 0, 'C', true);
      $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
      $pdf->Cell(25, 8, 'Cuota', 1, 0, 'C', true);
      $pdf->Cell(25, 8, 'Capital', 1, 0, 'C', true);
      $pdf->Cell(25, 8, 'Interés', 1, 0, 'C', true);
      $pdf->Cell(25, 8, 'Saldo', 1, 1, 'C', true);

      // Datos de la tabla con colores alternos
      $pdf->SetFont('Arial', '', 7);
      $pdf->SetTextColor($darkColor[0], $darkColor[1], $darkColor[2]);
      $fill = false;
      foreach ($amortization_table as $row) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255); // Alternar colores de fila
        $pdf->Cell(15, 6, $row['period'], 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($row['payment_date'])), 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, number_format($row['payment'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell(25, 6, number_format($row['principal'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell(25, 6, number_format($row['interest'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell(25, 6, number_format($row['balance'], 0, ',', '.'), 1, 1, 'R', $fill);
        $fill = !$fill;

        // Nueva página si es necesario
        if ($pdf->GetY() > 250) {
          $pdf->AddPage();
          // Reimprimir encabezados con colores
          $pdf->SetFont('Arial', 'B', 8);
          $pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
          $pdf->SetTextColor(255, 255, 255);
          $pdf->Cell(15, 8, 'Periodo', 1, 0, 'C', true);
          $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
          $pdf->Cell(25, 8, 'Cuota', 1, 0, 'C', true);
          $pdf->Cell(25, 8, 'Capital', 1, 0, 'C', true);
          $pdf->Cell(25, 8, 'Interés', 1, 0, 'C', true);
          $pdf->Cell(25, 8, 'Saldo', 1, 1, 'C', true);
          $pdf->SetFont('Arial', '', 7);
          $pdf->SetTextColor($darkColor[0], $darkColor[1], $darkColor[2]);
          $fill = false;
        }
      }

      // Pie de página con colores
      $pdf->Ln(5);
      $pdf->SetFont('Arial', 'I', 8);
      $pdf->SetTextColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
      $pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
      $pdf->Cell(0, 5, 'Sistema de Prestamos - Tabla de Amortizacion', 0, 1, 'C');

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
