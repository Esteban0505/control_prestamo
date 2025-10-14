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
    $data['subview'] = 'admin/loans/index';
    $this->load->view('admin/_main_layout', $data);
    log_message('debug', 'Vista de lista de préstamos cargada');
  }

  public function edit()
  {
    log_message('debug', 'Iniciando método edit() para préstamo');
    $is_ajax = $this->input->is_ajax_request() || !is_null($this->input->post('credit_amount'));
    if ($is_ajax) {
      header('Content-Type: application/json');
    }
    $data['coins'] = $this->loans_m->get_coins();
    $data['users'] = $this->customers_m->get_active_users();

    $rules = $this->loans_m->loan_rules;
    $this->form_validation->set_rules($rules);
    log_message('debug', 'Validation rules set for loan creation');
    log_message('debug', 'POST data received: ' . json_encode($this->input->post()));

    if ($this->form_validation->run() == TRUE) {

      // Obtener datos del formulario y convertir formato colombiano a decimal
      $principal_input = $this->input->post('credit_amount');
      $interest_input = $this->input->post('interest_amount');
      $periods = $this->input->post('num_fee');
      $payment_frequency = $this->input->post('payment_m');
      $amortization_type = $this->input->post('amortization_type');
      $start_date = $this->input->post('date');
      $assigned_user_id = $this->input->post('assigned_user_id');
      $tasa_type = $this->input->post('tasa_tipo');
      log_message('debug', 'Datos del formulario: principal_input=' . $principal_input . ', interest_input=' . $interest_input . ', periods=' . $periods . ', frequency=' . $payment_frequency . ', type=' . $amortization_type);

      // Convertir y validar formato colombiano
      log_message('debug', 'Parseando monto principal: ' . $principal_input);
      $principal = $this->parse_currency_input($principal_input, false);
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

      // Validaciones adicionales
      if (!is_int($principal) || $principal <= 0) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El monto del préstamo debe ser un entero mayor a 0']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El monto del préstamo debe ser un entero mayor a 0');
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

      if ($periods <= 0 || $periods > 120) {
        if ($is_ajax) {
          echo json_encode(['success' => false, 'error' => 'El número de cuotas debe estar entre 1 y 120']);
          return;
        } else {
          $this->session->set_flashdata('error', 'El número de cuotas debe estar entre 1 y 120');
          redirect('admin/loans/edit');
        }
      }

      log_message('debug', 'Tasa: ' . $interest_rate . ', tipo: ' . $tasa_type . ', iniciando cálculo de amortización');

      // Calcular períodos por año
      switch ($payment_frequency) {
        case 'mensual': $periods_per_year = 12; break;
        case 'quincenal': $periods_per_year = 24; break;
        case 'semanal': $periods_per_year = 52; break;
        case 'diario': $periods_per_year = 365; break;
        default: $periods_per_year = 12;
      }

      // Calcular tasa periódica
      $period_rate = $this->loans_m->get_period_rate($interest_rate, $periods_per_year, $tasa_type);
      log_message('debug', 'Tasa periódica calculada: ' . $period_rate);

      // Validar fecha de inicio
      if (empty($start_date) || !DateTime::createFromFormat('Y-m-d', $start_date)) {
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
          $amortization_type,
          $start_date
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

        // Datos del préstamo
        $loan_data = $this->loans_m->array_from_post([
          'customer_id',
          'credit_amount', 
          'interest_amount', 
          'num_fee', 
          'payment_m', 
          'coin_id', 
          'date',
          'amortization_type'
        ]);
        
        // Asegurar que se guarden valores numéricos ya parseados (sin formateo ni padding)
        $loan_data['credit_amount'] = $principal;
        $loan_data['interest_amount'] = $interest_rate;
        
        // Agregar el monto de cuota calculado y auditoría
        $loan_data['fee_amount'] = $fee_amount;
        $loan_data['created_by'] = get_user_id(); // Registrar quién creó el préstamo
        $loan_data['assigned_user_id'] = $assigned_user_id;

        // Iniciar transacción
        log_message('debug', 'Iniciando transacción para guardar préstamo');
        $this->db->trans_start();

        if ($this->loans_m->add_loan($loan_data, $items)) {
          $this->db->trans_complete();
          if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Transacción fallida al crear préstamo');
            if ($is_ajax) {
              echo json_encode(['success' => false, 'error' => 'Error al crear el préstamo. Transacción fallida.']);
              return;
            } else {
              $this->session->set_flashdata('error', 'Error al crear el préstamo. Transacción fallida.');
            }
          } else {
            log_message('info', 'Préstamo creado exitosamente con ID: ' . $this->db->insert_id());
            if ($is_ajax) {
              echo json_encode(['success' => true, 'message' => 'Préstamo agregado correctamente']);
              return;
            } else {
              $this->session->set_flashdata('msg', 'Préstamo agregado correctamente');
              // Actualizar gráficas en dashboard si es modal
              echo "<script>if (window.parent && window.parent.location.href.includes('admin') && typeof window.parent.updateCharts === 'function') { window.parent.updateCharts(); }</script>";
            }
          }
        } else {
          log_message('error', 'Error al guardar préstamo en base de datos');
          $this->db->trans_rollback();
          if ($is_ajax) {
            echo json_encode(['success' => false, 'error' => 'Error al crear el préstamo']);
            return;
          } else {
            $this->session->set_flashdata('error', 'Error al crear el préstamo');
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

    return validate_money_co($str);
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
    if (empty($input)) {
      return $allow_decimals ? 0.00 : 0;
    }

    // Remover espacios y símbolos
    $input = trim($input);
    $input = str_replace('$', '', $input);
    $input = str_replace(' ', '', $input);

    // Si es un número simple sin formato, devolverlo
    if (is_numeric($input)) {
      return $allow_decimals ? (float) $input : (int) $input;
    }

    // Detectar si , es usado como separador de miles o decimal
    $has_comma = strpos($input, ',') !== false;
    $has_dot = strpos($input, '.') !== false;

    if ($has_comma && !$has_dot) {
      // Asumir , es separador de miles
      $input = str_replace(',', '.', $input);
    } elseif ($has_comma && $has_dot) {
      // Si , está después del último . , asumir , es decimal
      $last_dot = strrpos($input, '.');
      $last_comma = strrpos($input, ',');
      if ($last_comma <= $last_dot) {
        // , es separador de miles, reemplazar con .
        $input = str_replace(',', '.', $input);
      }
      // Si , es después, dejar como está
    }
    // Si solo . , asumir es separador de miles

    // Remover separadores de miles
    $input = preg_replace('/\./', '', $input);
    // Convertir , a .
    $input = str_replace(',', '.', $input);

    return $allow_decimals ? (float) $input : (int) $input;
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

    // Validaciones adicionales
    $periods = $this->input->post('num_fee');
    $principal_input = $this->input->post('credit_amount');
    $interest_rate_input = $this->input->post('interest_amount');
    $start_date = $this->input->post('date');

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

    $nro_cuotas = intval($this->input->post('num_fee'));
    if ($nro_cuotas <= 0 || $nro_cuotas > 120) {
      log_message('error', 'Número de cuotas debe estar entre 1 y 120: ' . $nro_cuotas);
      echo json_encode(['success' => false, 'error' => 'Número de cuotas debe estar entre 1 y 120']);
      return;
    }

    $rate_type = 'tna'; // Default to TNA

    $forma_pago = strtolower($this->input->post('payment_m')); // mensual, quincenal...
    if (!in_array($forma_pago, ['mensual', 'quincenal', 'semanal', 'diario'])) {
      log_message('error', 'Forma de pago inválida: ' . $forma_pago);
      echo json_encode(['success' => false, 'error' => 'Forma de pago inválida']);
      return;
    }

    // Obtener tipo de amortización directamente como método de la librería
    $method = $this->input->post('amortization_type');
    if (!in_array($method, ['francesa', 'estadounidense', 'mixta'])) {
        log_message('error', 'Tipo de amortización inválido: ' . $method);
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

    // Calcular períodos por año
    switch ($forma_pago) {
      case 'mensual': $periods_per_year = 12; break;
      case 'quincenal': $periods_per_year = 24; break;
      case 'semanal': $periods_per_year = 52; break;
      case 'diario': $periods_per_year = 365; break;
      default: $periods_per_year = 12;
    }

    // Calcular tasa periódica
    $period_rate = $this->loans_m->get_period_rate($interes, $periods_per_year, $rate_type);
    log_message('debug', 'Tasa periódica calculada: ' . $period_rate);

    try {
      // Calcular tabla de amortización usando la librería
      $amortization_table = $this->amortization->calculate_amortization_table(
        $monto,
        $period_rate,
        $nro_cuotas,
        $forma_pago,
        $start_date,
        $method
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

}

/* End of file Loans.php */
/* Location: ./application/controllers/admin/Loans.php */
