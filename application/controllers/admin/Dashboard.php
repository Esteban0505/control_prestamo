<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('config_m');
    $this->load->model('reports_m');
    $this->load->library('session');
    $this->load->library('form_validation');
    $this->session->userdata('loggedin') == TRUE || redirect('user/login');
  }

  public function index()
  {
    log_message('debug', 'Dashboard index: Iniciando carga de datos');

    // Obtener fechas del filtro
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    // Validar fechas si están presentes
    $validated_dates = null;
    if ($start_date && $end_date) {
      $this->form_validation->set_data(['start_date' => $start_date, 'end_date' => $end_date]);
      $this->form_validation->set_rules('start_date', 'Fecha de inicio', 'required|valid_date');
      $this->form_validation->set_rules('end_date', 'Fecha de fin', 'required|valid_date');

      if ($this->form_validation->run() == TRUE && strtotime($start_date) <= strtotime($end_date)) {
        $validated_dates = ['start' => $start_date, 'end' => $end_date];
      } else {
        log_message('error', 'Dashboard: Fechas inválidas - start: ' . $start_date . ', end: ' . $end_date);
      }
    }

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;

    // Contadores existentes
    $data['qCts'] = $this->config_m->get_countCts();
    $data['qLoans'] = $this->config_m->get_countLoans();
    $data['qPaids'] = $this->config_m->get_countPaids();

    // Gráfica de pastel - usar método del modelo con filtro
    $currency_loans = $this->reports_m->get_currency_loans(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $data_lc = [];
    foreach($currency_loans as $row) {
      $data_lc['label'][] = $row->name;
      $data_lc['data'][] = (float) $row->total_amount;
    }
    $data['countLC'] = json_encode($data_lc);

    // Monto total prestado por mes
    $loan_amounts = $this->reports_m->get_loan_amounts_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $monthLabels = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $loanAmountsByMonthData = array();
    foreach($loan_amounts as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $loanAmountsByMonthData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_amount);
    }
    $data['loanAmountsByMonthJson'] = json_encode($loanAmountsByMonthData);

    // Pagos recibidos por mes
    $received_payments = $this->reports_m->get_received_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $receivedPaymentsData = array();
    foreach($received_payments as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $receivedPaymentsData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_payments);
    }
    $data['receivedPaymentsDataJson'] = json_encode($receivedPaymentsData);

    // Pagos esperados por mes
    $expected_payments = $this->reports_m->get_expected_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $expectedPaymentsData = array();
    foreach($expected_payments as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $expectedPaymentsData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_expected);
    }
    $data['expectedPaymentsDataJson'] = json_encode($expectedPaymentsData);

    $data['subview'] = 'admin/index';
    $this->load->view('admin/_main_layout', $data);
  }

  public function ajax_get_chart_data()
  {
    // Obtener fechas del filtro
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    // Validar fechas si están presentes
    $validated_dates = null;
    if ($start_date && $end_date) {
      $this->form_validation->set_data(['start_date' => $start_date, 'end_date' => $end_date]);
      $this->form_validation->set_rules('start_date', 'Fecha de inicio', 'required|valid_date');
      $this->form_validation->set_rules('end_date', 'Fecha de fin', 'required|valid_date');

      if ($this->form_validation->run() == TRUE && strtotime($start_date) <= strtotime($end_date)) {
        $validated_dates = ['start' => $start_date, 'end' => $end_date];
      } else {
        log_message('error', 'Dashboard ajax_get_chart_data: Fechas inválidas - start: ' . $start_date . ', end: ' . $end_date);
      }
    }

    // Gráfica de pastel - usar método del modelo con filtro
    $currency_loans = $this->reports_m->get_currency_loans(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $data_lc = [];
    foreach($currency_loans as $row) {
      $data_lc['label'][] = $row->name;
      $data_lc['data'][] = (float) $row->total_amount;
    }

    // Monto total prestado por mes
    $loan_amounts = $this->reports_m->get_loan_amounts_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $monthLabels = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $loanAmountsByMonthData = array();
    foreach($loan_amounts as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $loanAmountsByMonthData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_amount);
    }

    // Pagos recibidos por mes
    $received_payments = $this->reports_m->get_received_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $receivedPaymentsData = array();
    foreach($received_payments as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $receivedPaymentsData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_payments);
    }

    // Pagos esperados por mes
    $expected_payments = $this->reports_m->get_expected_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $expectedPaymentsData = array();
    foreach($expected_payments as $row) {
      $monthName = $monthLabels[$row->month - 1];
      $expectedPaymentsData[] = array("label" => $monthName . " " . $row->year, "data" => (float)$row->total_expected);
    }

    $response = array(
      'countLC' => $data_lc,
      'loanAmountsByMonthJson' => $loanAmountsByMonthData,
      'receivedPaymentsDataJson' => $receivedPaymentsData,
      'expectedPaymentsDataJson' => $expectedPaymentsData
    );

    echo json_encode($response);
  }

}

/* End of file Dashboard.php */
/* Location: ./application/controllers/admin/Dashboard.php */