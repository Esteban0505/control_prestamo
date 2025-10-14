<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('config_m');
    $this->load->model('reports_m');
    $this->load->library('form_validation');
    $this->load->driver('cache', array('adapter' => 'file'));
  }

  public function index()
  {
    log_message('debug', 'Dashboard index: Iniciando carga de datos');

    // Obtener fechas del filtro
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    log_message('debug', 'Dashboard index: Fechas recibidas - start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    // Validar fechas si están presentes
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    $data['start_date'] = $start_date;
    $data['end_date'] = $end_date;

    // Obtener datos con caché y manejo de errores
    $data = array_merge($data, $this->_get_dashboard_data($validated_dates));

    log_message('debug', 'Dashboard index: Finalizando carga, enviando datos a vista');
    $data['subview'] = 'admin/index';
    $this->load->view('admin/_main_layout', $data);
    log_message('debug', 'Dashboard index: Vista cargada exitosamente');
  }

  public function ajax_get_chart_data()
  {
    log_message('debug', 'Dashboard ajax_get_chart_data: Iniciando obtención de datos para gráficas AJAX');

    // Obtener fechas del filtro
    $start_date = $this->input->get('start_date');
    $end_date = $this->input->get('end_date');

    log_message('debug', 'Dashboard ajax_get_chart_data: Fechas recibidas - start_date: ' . ($start_date ?: 'null') . ', end_date: ' . ($end_date ?: 'null'));

    // Validar fechas si están presentes
    $validated_dates = $this->_validate_date_filters($start_date, $end_date);

    // Limpiar caché para obtener datos frescos
    $cache_key = 'dashboard_data_' . md5(serialize($validated_dates));
    $this->cache->delete($cache_key);
    log_message('debug', 'Dashboard ajax_get_chart_data: Caché limpiado para fechas: ' . ($start_date ?: 'null') . ' - ' . ($end_date ?: 'null'));

    // Obtener datos de gráficas sin caché
    $chart_data = $this->_get_chart_data($validated_dates);

    log_message('debug', 'Dashboard ajax_get_chart_data: Datos obtenidos - countLC: ' . strlen($chart_data['countLC']) . ' chars, loanAmounts: ' . count(json_decode($chart_data['loanAmountsByMonthJson'], true)) . ' items');

    log_message('debug', 'Dashboard ajax_get_chart_data: Enviando respuesta JSON con ' . count($chart_data) . ' elementos');
    echo json_encode($chart_data);
    log_message('debug', 'Dashboard ajax_get_chart_data: Respuesta enviada exitosamente');
  }

  /**
   * Obtiene la hora exacta del servidor en zona horaria colombiana
   */
  public function get_server_time()
  {
    // Configurar zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    $response = array(
      'success' => true,
      'time' => date('h:i:s A'),
      'timezone' => 'COT (UTC-5)',
      'timestamp' => time()
    );

    header('Content-Type: application/json');
    echo json_encode($response);
  }

  /**
   * Valida los filtros de fecha
   */
  private function _validate_date_filters($start_date, $end_date)
  {
    if ($start_date && $end_date) {
      // Validación simple de formato yyyy-mm-dd
      $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';

      if (preg_match($date_pattern, $start_date) && preg_match($date_pattern, $end_date)) {
        // Validar que sean fechas reales
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        if ($start_timestamp !== false && $end_timestamp !== false && $start_timestamp <= $end_timestamp) {
          log_message('debug', 'Dashboard _validate_date_filters: Fechas validadas correctamente');
          return ['start' => $start_date, 'end' => $end_date];
        } else {
          log_message('error', 'Dashboard _validate_date_filters: Fechas inválidas - start: ' . $start_date . ', end: ' . $end_date);
        }
      } else {
        log_message('error', 'Dashboard _validate_date_filters: Formato de fecha inválido - start: ' . $start_date . ', end: ' . $end_date);
      }
    } else {
      log_message('debug', 'Dashboard _validate_date_filters: No se proporcionaron fechas, usando datos sin filtro');
    }
    return null;
  }

  /**
   * Obtiene todos los datos del dashboard con caché
   */
  private function _get_dashboard_data($validated_dates)
  {
    $cache_key = 'dashboard_data_' . md5(serialize($validated_dates));
    $data = $this->cache->get($cache_key);

    if (!$data) {
      log_message('debug', 'Dashboard: Datos no en caché, obteniendo de BD');

      // Contadores con manejo de errores
      $data['qCts'] = $this->_get_count_with_fallback('get_countCts', 'config_m');
      $data['qLoans'] = $this->_get_count_with_fallback('get_countLoans', 'config_m');
      $data['qPaids'] = $this->_get_count_with_fallback('get_countPaids', 'config_m');

      // Nuevas métricas adicionales
      $data['totalPortfolio'] = $this->reports_m->get_total_active_portfolio();
      $data['delinquencyRate'] = $this->reports_m->get_delinquency_rate();
      $data['avgLoanPerCustomer'] = $this->reports_m->get_average_loan_per_customer();

      // Datos de gráficas
      $chart_data = $this->_get_chart_data($validated_dates);
      $data = array_merge($data, $chart_data);

      // Cache por 5 minutos
      $this->cache->save($cache_key, $data, 300);
      log_message('debug', 'Dashboard: Datos guardados en caché');
    } else {
      log_message('debug', 'Dashboard: Datos obtenidos desde caché');
    }

    return $data;
  }

  /**
   * Obtiene datos de gráficas
   */
  private function _get_chart_data($validated_dates)
  {
    $monthLabels = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

    // Gráfica de pastel
    $currency_loans = $this->reports_m->get_currency_loans(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $data_lc = [];
    if (!empty($currency_loans)) {
      foreach($currency_loans as $row) {
        $data_lc['label'][] = $row->name ?? 'Sin nombre';
        $data_lc['data'][] = (float) ($row->total_amount ?? 0);
      }
    }
    $data['countLC'] = json_encode($data_lc);

    // Monto total prestado por mes
    $loan_amounts = $this->reports_m->get_loan_amounts_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $loanAmountsByMonthData = array();
    if (!empty($loan_amounts)) {
      foreach($loan_amounts as $row) {
        $monthName = $monthLabels[($row->month ?? 1) - 1] ?? 'Mes desconocido';
        $loanAmountsByMonthData[] = array(
          "label" => $monthName . " " . ($row->year ?? date('Y')),
          "data" => (float) ($row->total_amount ?? 0)
        );
      }
    }
    $data['loanAmountsByMonthJson'] = json_encode($loanAmountsByMonthData);

    // Pagos recibidos por mes
    $received_payments = $this->reports_m->get_received_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $receivedPaymentsData = array();
    if (!empty($received_payments)) {
      foreach($received_payments as $row) {
        $monthName = $monthLabels[($row->month ?? 1) - 1] ?? 'Mes desconocido';
        $receivedPaymentsData[] = array(
          "label" => $monthName . " " . ($row->year ?? date('Y')),
          "data" => (float) ($row->total_payments ?? 0)
        );
      }
    }
    $data['receivedPaymentsDataJson'] = json_encode($receivedPaymentsData);

    // Pagos esperados por mes
    $expected_payments = $this->reports_m->get_expected_payments_by_month(
      $validated_dates ? $validated_dates['start'] : null,
      $validated_dates ? $validated_dates['end'] : null
    );

    $expectedPaymentsData = array();
    if (!empty($expected_payments)) {
      foreach($expected_payments as $row) {
        $monthName = $monthLabels[($row->month ?? 1) - 1] ?? 'Mes desconocido';
        $expectedPaymentsData[] = array(
          "label" => $monthName . " " . ($row->year ?? date('Y')),
          "data" => (float) ($row->total_expected ?? 0)
        );
      }
    }
    $data['expectedPaymentsDataJson'] = json_encode($expectedPaymentsData);

    return $data;

    return $data;
  }

  /**
   * Obtiene contador con fallback en caso de error
   */
  private function _get_count_with_fallback($method, $model)
  {
    try {
      return $this->$model->$method();
    } catch (Exception $e) {
      log_message('error', 'Dashboard: Error obteniendo ' . $method . ' - ' . $e->getMessage());
      return (object) ['cantidad' => 0];
    }
  }

}

/* End of file Dashboard.php */
/* Location: ./application/controllers/admin/Dashboard.php */