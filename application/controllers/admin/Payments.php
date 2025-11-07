<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('payments_m');
    $this->load->model('loans_m');
    $this->load->library('form_validation');
    $this->load->library('session');
    $this->load->helper('format');
    $this->load->helper('permission');
    $this->session->userdata('loggedin') == TRUE || redirect('user/login');
  }

  public function index()
  {
    $data['payments'] = $this->payments_m->get_payments();
    $data['subview'] = 'admin/payments/index';

    $this->load->view('admin/_main_layout', $data);
  }

  public function edit()
  {
    $data['subview'] = 'admin/payments/edit';
    $this->load->view('admin/_main_layout', $data);
  }

  function ajax_searchCst()
  {
    $dni = $this->input->post('dni');
    $suggest = $this->input->post('suggest');
    $customers = $this->payments_m->get_searchCst($dni, $suggest);

    if ($this->input->post('suggest') == '1') {
      echo json_encode(['cst' => $customers, 'quotas' => []]);
    } else {
      $quota_data = $this->payments_m->get_quotasCst((int)$customers->loan_id);
      $search_data = ['cst' => $customers, 'quotas' => $quota_data];
      echo json_encode($search_data);
    }
  }

  function ajax_get_quotas()
  {
    $loan_id = $this->input->post('loan_id');
    $quotas = $this->payments_m->get_quotasCst($loan_id);

    echo json_encode(['quotas' => $quotas]);
  }

  function ticket()
  {
    // print_r($_POST);
    // print_r($this->input->post('quota_id'));
    $data['name_cst'] = $this->input->post('name_cst');
    $data['coin'] = $this->input->post('coin');
    $data['loan_id'] = $this->input->post('loan_id');

    if ($this->input->post('quota_id') && is_array($this->input->post('quota_id'))) {
      foreach ($this->input->post('quota_id') as $q) {
        $this->payments_m->update_quota(['status' => 0], $q);
      }
    }

    if (!$this->payments_m->check_cstLoan($this->input->post('loan_id'))) {
      $this->payments_m->update_cstLoan($this->input->post('loan_id'), $this->input->post('customer_id'));
    }

    $data['quotasPaid'] = $this->payments_m->get_quotasPaid($this->input->post('quota_id'));

    $this->load->view('admin/payments/ticket', $data);
  }

  /**
   * Nuevo endpoint para pago manual con monto y descripción
   */
  public function manual_pay()
  {
    if (!can_edit(get_user_role(), 'payments')) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'No tiene permisos']));
      return;
    }

    $loan_item_id = $this->input->post('loan_item_id');
    $amount_input = $this->input->post('amount');
    $description = $this->input->post('description');
    $payment_type = $this->input->post('tipo_pago');

    // Validar datos requeridos
    if (empty($loan_item_id) || empty($amount_input) || empty($payment_type)) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'Faltan datos requeridos']));
      return;
    }

    // Validar tipo de pago
    $valid_types = ['interest', 'capital', 'full'];
    if (!in_array($payment_type, $valid_types)) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'Tipo de pago inválido']));
      return;
    }

    // Parsear moneda (acepta 1.000,00 o 1000.00)
    $amount = parse_money_co($amount_input);
    if ($amount === false) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'Monto inválido']));
      return;
    }

    // Obtener información de la cuota
    $loan_item = $this->payments_m->get_loan_item($loan_item_id);
    if (!$loan_item) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'Cuota no encontrada']));
      return;
    }

    if ((int)$loan_item->status === 0) {
      $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'La cuota ya está pagada']));
      return;
    }

    // Validar monto según tipo de pago
    $interest_pending = $loan_item->interest_amount - ($loan_item->interest_paid ?? 0);
    $capital_pending = $loan_item->capital_amount - ($loan_item->capital_paid ?? 0);

    switch ($payment_type) {
      case 'interest':
        if ($amount > $interest_pending) {
          $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'El monto excede los intereses pendientes']));
          return;
        }
        break;

      case 'capital':
        if ($amount > $capital_pending) {
          $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'El monto excede el capital pendiente']));
          return;
        }
        break;

      case 'full':
        $total_pending = $interest_pending + $capital_pending;
        if ($amount < $total_pending) {
          $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'error' => 'El monto es menor al total pendiente de la cuota']));
          return;
        }
        break;
    }

    $result = $this->payments_m->process_manual_payment($loan_item_id, $amount, $description, $payment_type, get_user_id());
    $this->output->set_content_type('application/json')->set_output(json_encode($result));
  }

  /**
   * Procesa un pago (parcial, intereses, capital o total)
   */
  public function pay()
  {
    // Verificar permisos
    if (!can_edit(get_user_role(), 'payments')) {
      $response = [
        'success' => false,
        'error' => 'No tiene permisos para realizar cobranzas'
      ];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    $loan_id = $this->input->post('loan_id');
    $loan_item_id = $this->input->post('loan_item_id');
    $amount_input = $this->input->post('amount');
    $payment_type = $this->input->post('payment_type'); // 'interest', 'capital', 'both', 'full'
    $method = $this->input->post('method');
    $notes = $this->input->post('notes');

    // Validar datos requeridos
    if (empty($loan_id) || empty($amount_input) || empty($payment_type)) {
      $response = [
        'success' => false,
        'error' => 'Faltan datos requeridos'
      ];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    // Convertir y validar monto
    $amount_result = sanitize_money_input($amount_input);
    if (!$amount_result['valid']) {
      $response = [
        'success' => false,
        'error' => 'Formato de monto inválido. Use formato: 1.000.000,50'
      ];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    $amount = $amount_result['value'];

    if ($amount <= 0) {
      $response = [
        'success' => false,
        'error' => 'El monto debe ser mayor a 0'
      ];
      $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
      return;
    }

    try {
      // Obtener información del préstamo y cuota
      $loan = $this->loans_m->get_loan($loan_id);
      if (!$loan) {
        throw new Exception('Préstamo no encontrado');
      }

      $loan_item = null;
      if ($loan_item_id) {
        $loan_item = $this->payments_m->get_loan_item($loan_item_id);
        if (!$loan_item) {
          throw new Exception('Cuota no encontrada');
        }
      }

      // Procesar el pago según el tipo
      $result = $this->payments_m->process_payment([
        'loan_id' => $loan_id,
        'loan_item_id' => $loan_item_id,
        'amount' => $amount,
        'payment_type' => $payment_type,
        'method' => $method,
        'notes' => $notes,
        'payment_user_id' => get_user_id(),
        'loan' => $loan,
        'loan_item' => $loan_item
      ]);

      if ($result['success']) {
        $response = [
          'success' => true,
          'message' => 'Pago registrado correctamente',
          'data' => $result['data']
        ];
        // Actualizar gráficas en dashboard si es modal
        echo "<script>if (window.parent && window.parent.location.href.includes('admin') && typeof window.parent.updateCharts === 'function') { window.parent.updateCharts(); }</script>";
      } else {
        $response = [
          'success' => false,
          'error' => $result['error']
        ];
      }

    } catch (Exception $e) {
      $response = [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }

    $this->output
      ->set_content_type('application/json')
      ->set_output(json_encode($response));
  }

}

/* End of file Payments.php */
/* Location: ./application/controllers/admin/Payments.php */