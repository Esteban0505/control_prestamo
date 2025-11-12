<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('payments_m');
    $this->load->model('user_m');
    $this->load->model('loans_m');
    $this->load->model('customers_m');
    $this->load->model('coins_m');
    $this->load->library('form_validation');
    $this->load->library('session');
    $this->load->driver('cache');
    $this->load->helper('error_handler');
    $this->load->library('PaymentTypeValidator'); // Cargar librería de validación de tipos de pago
    $this->load->library('PaymentCalculator'); // Cargar librería de cálculo de pagos
    $this->load->library('PaymentValidator'); // Cargar librería de validación de pagos
    $this->session->userdata('loggedin') == TRUE || redirect('user/login');
  }

  public function index()
  {
    // Obtener parámetros de paginación y filtros
    $page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
    $per_page = $this->input->get('per_page') ? (int)$this->input->get('per_page') : 25;
    $search = $this->input->get('search');
    $date_from = $this->input->get('date_from');
    $date_to = $this->input->get('date_to');

    // Validar parámetros
    if ($page < 1) $page = 1;
    if ($per_page < 1 || $per_page > 100) $per_page = 25;

    // Calcular offset
    $offset = ($page - 1) * $per_page;

    // Obtener datos paginados con filtros
    $result = $this->payments_m->get_payments_paginated($per_page, $offset, $search, $date_from, $date_to);

    $data['payments'] = $result['payments'];
    $data['total_records'] = $result['total'];
    $data['current_page'] = $page;
    $data['per_page'] = $per_page;
    $data['total_pages'] = ceil($result['total'] / $per_page);
    $data['search'] = $search;
    $data['date_from'] = $date_from;
    $data['date_to'] = $date_to;

    // Obtener estadísticas optimizadas con caché
    $cache_key = 'payments_stats_' . date('Y-m-d-H');
    $data['stats'] = $this->get_cached_stats($cache_key);

    $data['subview'] = 'admin/payments/index';

    $this->load->view('admin/_main_layout', $data);
  }

  /**
   * Obtiene estadísticas con caché para mejorar rendimiento
   */
  private function get_cached_stats($cache_key)
  {
    // Verificar si existe caché
    $cached_stats = $this->cache->get($cache_key);
    if ($cached_stats !== FALSE) {
      return $cached_stats;
    }

    // Obtener estadísticas desde el modelo
    $stats = $this->payments_m->get_payments_stats();

    // Guardar en caché por 1 hora
    $this->cache->save($cache_key, $stats, 3600);

    return $stats;
  }

  public function edit()
  {
    $data['users'] = $this->user_m->get_active_users();
    $data['subview'] = 'admin/payments/edit';
    $this->load->view('admin/_main_layout', $data);
  }

  function ajax_searchCst()
  {
    log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== INICIANDO ajax_searchCst ==========');
    $dni = $this->input->post('dni');
    $suggest = $this->input->post('suggest') == '1';
    log_message('debug', 'PAYMENTS_DIAGNOSIS: DNI recibido: ' . $dni . ', suggest: ' . ($suggest ? 'true' : 'false'));
    log_message('debug', 'PAYMENTS_DIAGNOSIS: POST completo: ' . json_encode($this->input->post()));

   try {
     // Validar DNI
     if (empty($dni)) {
       throw_error('VALIDATION_001', ['DNI']);
     }

     $cst = $this->payments_m->get_searchCst($dni, $suggest);
     log_message('debug', 'PAYMENTS_DIAGNOSIS: Cliente encontrado: ' . json_encode($cst));
     log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN ajax_searchCst ==========');

     // Obtener usuario actual para fallback
     $current_user = $this->session->userdata('user_id');
     log_message('debug', 'Usuario actual: ' . $current_user);

     // Si assigned_user_id es null, usar usuario actual
     if ($cst) {
       if ($suggest && is_array($cst)) {
         foreach ($cst as $item) {
           if ($item->assigned_user_id == null) {
             $item->assigned_user_id = $current_user;
             log_message('debug', 'Asignando usuario actual como assigned_user_id en suggest: ' . $current_user);
           }
         }
       } elseif (!$suggest && $cst->assigned_user_id == null) {
         $cst->assigned_user_id = $current_user;
         log_message('debug', 'Asignando usuario actual como assigned_user_id: ' . $current_user);
       }
     }

     if ($suggest) {
       $search_data = ['cst' => $cst, 'current_user' => $current_user];
     } else {
       $quota_data = '';
       if ($cst != null) {
         $quota_data = $this->payments_m->get_quotasCst($cst->loan_id);
         log_message('debug', 'Cuotas encontradas: ' . json_encode($quota_data));

         // Filtrar cuotas condonadas (extra_payment = 3) para que no aparezcan en búsquedas de cuotas pendientes
         $quota_data = array_filter($quota_data, function($quota) {
           return !isset($quota['extra_payment']) || $quota['extra_payment'] != 3;
         });
         $quota_data = array_values($quota_data); // Reindexar array
         log_message('debug', 'Cuotas filtradas (extra_payment != 3): ' . json_encode($quota_data));
       }
       $search_data = ['cst' => $cst, 'quotas' => $quota_data, 'current_user' => $current_user];
     }

     log_message('debug', 'Datos de respuesta: ' . json_encode($search_data));

     echo json_encode(ajax_success($search_data));
   } catch (Exception $e) {
     log_message('error', 'Error en ajax_searchCst: ' . $e->getMessage());
     echo json_encode(ajax_error('SYSTEM_001', [], ['details' => $e->getMessage()]));
   }
 }

  function ajax_get_quotas()
   {
    $loan_id = $this->input->post('loan_id');
    log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== INICIANDO ajax_get_quotas ==========');
    log_message('debug', 'PAYMENTS_DIAGNOSIS: Obteniendo cuotas para loan_id: ' . $loan_id);

    try {
      // Validar loan_id
      if (empty($loan_id) || !is_numeric($loan_id)) {
        throw_error('VALIDATION_001', ['ID del préstamo']);
      }

      $quotas = $this->payments_m->get_quotasCst($loan_id);
      log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuotas crudas obtenidas de BD: ' . json_encode($quotas));

      // DIAGNÓSTICO ESPECÍFICO PARA LOAN #141 Y CUOTA #4
      if ($loan_id == 141) {
        log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO ESPECÍFICO LOAN 141 ==========');
        foreach ($quotas as $quota) {
          $num_quota = $quota['num_quota'] ?? 'N/A';
          $balance = $quota['balance'] ?? 'N/A';
          $status = $quota['status'] ?? 'N/A';
          $id = $quota['id'] ?? 'N/A';
          log_message('debug', 'PAYMENTS_DIAGNOSIS: LOAN 141 - Cuota ID ' . $id . ' #' . $num_quota . ' - balance: ' . $balance . ', status: ' . $status);

          if ($num_quota == 4) {
            log_message('debug', 'PAYMENTS_DIAGNOSIS: LOAN 141 - CUOTA #4 ENCONTRADA - ID: ' . $id . ', balance: ' . $balance . ', status: ' . $status);
          }
        }
        log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO ESPECÍFICO LOAN 141 ==========');
      }

      // DIAGNÓSTICO: Log detallado de fechas antes del filtrado
      foreach ($quotas as $index => $quota) {
        log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuota #' . $index . ' - ID: ' . ($quota['id'] ?? 'N/A') . ', date: ' . ($quota['date'] ?? 'N/A') . ', num_quota: ' . ($quota['num_quota'] ?? 'N/A'));
      }

      // CORRECCIÓN: Asegurar que se incluyan cuotas pendientes, parciales y no completas
      // Filtrar cuotas condonadas (extra_payment = 3) y pagadas completamente (status = 0)
      $quotas_filtradas = array_filter($quotas, function($quota) {
        // Incluir cuotas con status=1 (pendientes), status=3 (parciales) y status=4 (no completas)
        $is_pending = isset($quota['status']) && in_array($quota['status'], [1, 3, 4]);
        $is_not_condoned = !isset($quota['extra_payment']) || $quota['extra_payment'] != 3;
        // Excluir cuotas completamente pagadas (status = 0)
        $is_not_paid = !isset($quota['status']) || $quota['status'] != 0;

        // CORRECCIÓN ADICIONAL: Incluir cuotas con montos pendientes totales > 0 (para casos donde balance=0 pero hay intereses pendientes)
        $has_pending_amounts = false;
        if (isset($quota['interest_amount']) && isset($quota['capital_amount'])) {
          $interest_pending = $quota['interest_amount'] - ($quota['interest_paid'] ?? 0);
          $capital_pending = $quota['capital_amount'] - ($quota['capital_paid'] ?? 0);
          $has_pending_amounts = ($interest_pending + $capital_pending) > 0;
        }

        $include = ($is_pending && $is_not_condoned && $is_not_paid) || ($has_pending_amounts && $is_not_condoned && $is_not_paid);

        // DIAGNÓSTICO DETALLADO: Log para cada cuota procesada
        $interest_pending_log = isset($quota['interest_amount']) ? $quota['interest_amount'] - ($quota['interest_paid'] ?? 0) : 'N/A';
        $capital_pending_log = isset($quota['capital_amount']) ? $quota['capital_amount'] - ($quota['capital_paid'] ?? 0) : 'N/A';
        $total_pending_log = is_numeric($interest_pending_log) && is_numeric($capital_pending_log) ? $interest_pending_log + $capital_pending_log : 'N/A';
        log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuota ID ' . ($quota['id'] ?? 'N/A') . ' - status: ' . ($quota['status'] ?? 'N/A') . ', balance: ' . ($quota['balance'] ?? 'N/A') . ', interest_pending: ' . $interest_pending_log . ', capital_pending: ' . $capital_pending_log . ', total_pending: ' . $total_pending_log . ', is_pending: ' . ($is_pending ? 'SÍ' : 'NO') . ', has_pending_amounts: ' . ($has_pending_amounts ? 'SÍ' : 'NO') . ', include: ' . ($include ? 'SÍ' : 'NO'));

        // Log específico para cuota #4 del loan #141
        if (isset($quota['num_quota']) && $quota['num_quota'] == 4 && isset($quota['loan_id']) && $quota['loan_id'] == 141) {
          log_message('debug', 'PAYMENTS_DIAGNOSIS: LOAN 141 CUOTA #4 - is_pending: ' . ($is_pending ? 'SÍ' : 'NO') . ', is_not_condoned: ' . ($is_not_condoned ? 'SÍ' : 'NO') . ', has_pending_amounts: ' . ($has_pending_amounts ? 'SÍ' : 'NO') . ', INCLUDE: ' . ($include ? 'SÍ' : 'NO'));
        }

        return $include;
      });

      log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuotas después del filtrado: ' . json_encode($quotas_filtradas));

      // DIAGNÓSTICO ESPECÍFICO PARA LOAN #141 DESPUÉS DEL FILTRADO
      if ($loan_id == 141) {
        log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO LOAN 141 DESPUÉS FILTRADO ==========');
        $cuota_4_encontrada = false;
        foreach ($quotas_filtradas as $quota) {
          $num_quota = $quota['num_quota'] ?? 'N/A';
          $balance = $quota['balance'] ?? 'N/A';
          $status = $quota['status'] ?? 'N/A';
          $id = $quota['id'] ?? 'N/A';
          log_message('debug', 'PAYMENTS_DIAGNOSIS: LOAN 141 FILTRADA - Cuota ID ' . $id . ' #' . $num_quota . ' - balance: ' . $balance . ', status: ' . $status);

          if ($num_quota == 4) {
            $cuota_4_encontrada = true;
            log_message('debug', 'PAYMENTS_DIAGNOSIS: LOAN 141 - CUOTA #4 INCLUIDA EN RESULTADO FINAL - ID: ' . $id . ', balance: ' . $balance . ', status: ' . $status);
          }
        }
        if (!$cuota_4_encontrada) {
          log_message('error', 'PAYMENTS_DIAGNOSIS: LOAN 141 - CUOTA #4 NO ENCONTRADA EN RESULTADO FINAL - REVISAR FILTROS');
        }
        log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO LOAN 141 DESPUÉS FILTRADO ==========');
      }

      // DIAGNÓSTICO: Log detallado de fechas después del filtrado
      foreach ($quotas_filtradas as $index => $quota) {
        log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuota filtrada #' . $index . ' - ID: ' . ($quota['id'] ?? 'N/A') . ', date: ' . ($quota['date'] ?? 'N/A') . ', num_quota: ' . ($quota['num_quota'] ?? 'N/A'));
        // Verificar formato de fecha
        if (isset($quota['date'])) {
          $date_obj = date_create($quota['date']);
          log_message('debug', 'PAYMENTS_DIAGNOSIS: Fecha parseada - Original: ' . $quota['date'] . ', Formato Y-m-d: ' . ($date_obj ? date_format($date_obj, 'Y-m-d') : 'INVALID'));
        }
      }

      $response_data = ['quotas' => array_values($quotas_filtradas)];
      log_message('debug', 'PAYMENTS_DIAGNOSIS: Datos finales enviados al frontend: ' . json_encode($response_data));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN ajax_get_quotas ==========');

      echo json_encode(ajax_success($response_data));
    } catch (Exception $e) {
      log_message('error', 'PAYMENTS_DIAGNOSIS: Error en ajax_get_quotas: ' . $e->getMessage());
      echo json_encode(ajax_error('LOAN_001', [], ['details' => $e->getMessage()]));
    }
  }

 /**
  * Obtiene el saldo total pendiente de un préstamo para pago total
  */
 function get_total_pending_balance()
 {
   $loan_id = $this->input->post('loan_id');
   log_message('debug', 'Obteniendo saldo total pendiente para loan_id: ' . $loan_id);

   try {
     // Validar loan_id
     if (empty($loan_id) || !is_numeric($loan_id)) {
       throw_error('VALIDATION_001', ['ID del préstamo']);
     }

     // Calcular el saldo total pendiente del préstamo
     $this->db->select('SUM(COALESCE(balance, 0)) as total_balance, SUM(COALESCE(interest_amount - COALESCE(interest_paid, 0), 0)) as total_interest_pending, SUM(COALESCE(capital_amount - COALESCE(capital_paid, 0), 0)) as total_capital_pending');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->where('status', 1); // Solo cuotas pendientes
     $balance_result = $this->db->get()->row();

     $total_balance = $balance_result->total_balance ?? 0;
     $total_interest_pending = $balance_result->total_interest_pending ?? 0;
     $total_capital_pending = $balance_result->total_capital_pending ?? 0;

     log_message('debug', 'Saldo total pendiente calculado - balance: ' . $total_balance . ', interest: ' . $total_interest_pending . ', capital: ' . $total_capital_pending);

     echo json_encode(ajax_success([
       'total_balance' => $total_balance,
       'total_interest_pending' => $total_interest_pending,
       'total_capital_pending' => $total_capital_pending
     ]));
   } catch (Exception $e) {
     log_message('error', 'Error en get_total_pending_balance: ' . $e->getMessage());
     echo json_encode(ajax_error('LOAN_001', [], ['details' => $e->getMessage()]));
   }
 }

 /**
  * Método para procesar pagos personalizados con monto aplicado secuencialmente a cuotas seleccionadas
  * CORREGIDO: Maneja pagos parciales correctamente, distribuyendo saldos restantes en cuotas futuras
  */
 function custom_payment()
 {
     log_message('debug', 'CUSTOM_PAYMENT: ========== INICIANDO PAGO PERSONALIZADO SECUENCIAL ==========');
     log_message('debug', 'CUSTOM_PAYMENT: POST data: ' . json_encode($this->input->post()));

     // DIAGNÓSTICO: Agregar logs específicos para el problema reportado
     $custom_amount = $this->input->post('custom_amount');
     $loan_item_ids = $this->input->post('loan_item_ids');
     $custom_payment_type = $this->input->post('custom_payment_type'); // Nuevo: tipo de pago personalizado (partial/incomplete)
     log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: custom_amount recibido: ' . $custom_amount);
     log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: loan_item_ids recibido: ' . json_encode($loan_item_ids));
     log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: custom_payment_type recibido: ' . $custom_payment_type);
     log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Cantidad de cuotas seleccionadas: ' . (is_array($loan_item_ids) ? count($loan_item_ids) : 'NO ES ARRAY'));

     // DIAGNÓSTICO ADICIONAL: Verificar si custom_payment_type está vacío o nulo
     if (empty($custom_payment_type)) {
         log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: ERROR CRÍTICO - custom_payment_type está VACÍO o NULO! Verificar formulario frontend.');
     } else {
         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: custom_payment_type válido: ' . $custom_payment_type);
     }

     // DIAGNÓSTICO CRÍTICO: Verificar si el monto personalizado es $5,000 (caso reportado)
     if ($custom_amount == 5000) {
         log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: DETECTADO CASO REPORTADO - Monto personalizado: $5,000');
         log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: ALERTA: Sistema aplicará solo $992.87 en lugar de $5,000 completos');
         log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: CAUSA IDENTIFICADA: Límite o cálculo incorrecto en procesamiento');
     }

     try {
         // CORRECCIÓN: Garantizar atomicidad de transacciones
         $this->db->trans_begin();

         // Recibir y validar parámetros
         $loan_item_ids = $this->input->post('loan_item_ids');
         $custom_amount = $this->input->post('custom_amount');
         $custom_payment_type = $this->input->post('custom_payment_type') ?? 'partial'; // Default: partial
         $user_id = $this->input->post('user_id');
         $customer_id = $this->input->post('customer_id');
         $payment_description = $this->input->post('payment_description') ?? 'Pago personalizado - Monto aplicado secuencialmente a cuotas seleccionadas';

         // Validar tipo de pago personalizado
         $valid_custom_payment_types = ['partial', 'incomplete', 'pago_personalizado'];
         if (!in_array($custom_payment_type, $valid_custom_payment_types)) {
             log_message('error', 'TIPO_PAGO_PERSONALIZADO_INVALIDO: Recibido "' . $custom_payment_type . '", tipos válidos: ' . json_encode($valid_custom_payment_types));
             throw_error('PAYMENT_015'); // Tipo de pago personalizado inválido
         }

         // Validaciones iniciales
         if (empty($loan_item_ids) || !is_array($loan_item_ids)) {
             throw_error('PAYMENT_007'); // Al menos una cuota seleccionada
         }

         if (empty($custom_amount) || !is_numeric($custom_amount) || $custom_amount <= 0) {
             throw_error('PAYMENT_010'); // Monto inválido
         }

         if (empty($user_id) || !is_numeric($user_id)) {
             throw_error('VALIDATION_001', ['Usuario']);
         }

         // Obtener información del préstamo desde la primera cuota
         $first_quota = $this->payments_m->get_loan_item($loan_item_ids[0]);
         if (!$first_quota) {
             throw_error('LOAN_001'); // Cuota no encontrada
         }

         $loan_id = $first_quota->loan_id;

         // Validar que el préstamo esté activo
         $loan = $this->loans_m->get_loan($loan_id);
         if (!$loan || $loan->status != 1) {
             throw_error('LOAN_002'); // Préstamo no activo
         }

         // Verificar si hay cuotas pendientes (status = 1) en lugar de balance
         $has_pending_quotas = $this->db->select('COUNT(*) as count')
                                        ->from('loan_items')
                                        ->where('loan_id', $loan_id)
                                        ->where('status', 1)
                                        ->get()->row()->count ?? 0;

         if ($has_pending_quotas == 0) {
             throw_error('PAYMENT_012'); // No hay cuotas pendientes
         }

         log_message('debug', 'CUSTOM_PAYMENT: Validaciones pasadas - loan_id: ' . $loan_id . ', monto: ' . $custom_amount . ', tipo: ' . $custom_payment_type . ', cuotas: ' . count($loan_item_ids));

         // DEBUG: Agregar logs detallados para diagnosticar el problema de actualización de estados
         log_message('debug', 'CUSTOM_PAYMENT: ========== DIAGNÓSTICO ANTES DEL PROCESAMIENTO ==========');
         log_message('debug', 'CUSTOM_PAYMENT: loan_item_ids: ' . json_encode($loan_item_ids));
         log_message('debug', 'CUSTOM_PAYMENT: custom_amount: ' . $custom_amount . ', custom_payment_type: ' . $custom_payment_type . ', user_id: ' . $user_id . ', loan_id: ' . $loan_id);
         foreach ($loan_item_ids as $quota_id) {
             $quota_info = $this->payments_m->get_loan_item($quota_id);
             log_message('debug', 'CUSTOM_PAYMENT: Cuota ID ' . $quota_id . ' - status: ' . ($quota_info ? $quota_info->status : 'NO ENCONTRADA') . ', balance: ' . ($quota_info ? $quota_info->balance : 'N/A') . ', interest_paid: ' . ($quota_info ? $quota_info->interest_paid : 'N/A') . ', capital_paid: ' . ($quota_info ? $quota_info->capital_paid : 'N/A') . ', fee_amount: ' . ($quota_info ? $quota_info->fee_amount : 'N/A'));
         }

         // Procesar pago personalizado según el tipo
         if ($custom_payment_type === 'partial') {
             // PAGO PARCIAL: Distribuir restante a futuras cuotas, marcar cuotas como status=3
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== PROCESANDO PAGO PARCIAL ========== - loan_id: ' . $loan_id . ', custom_amount: ' . $custom_amount);
             $result = $this->process_custom_payment_partial($loan_id, $loan_item_ids, $custom_amount, $user_id, $payment_description);
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: RESULTADO PAGO PARCIAL - is_partial: ' . ($result['is_partial'] ?? 'NO DEFINIDO') . ', remaining_balance_distributed: ' . ($result['remaining_balance_distributed'] ?? 0));
         } elseif ($custom_payment_type === 'incomplete') {
             // PAGO NO COMPLETO: Mantener saldo pendiente en cuota actual, marcar como status=4
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== PROCESANDO PAGO NO COMPLETO ========== - loan_id: ' . $loan_id . ', custom_amount: ' . $custom_amount);
             $result = $this->process_custom_payment_incomplete($loan_id, $loan_item_ids, $custom_amount, $user_id, $payment_description);
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: RESULTADO PAGO NO COMPLETO - status=4 aplicado, balance pendiente: ' . ($result['payment_breakdown'][0]['balance'] ?? 'NO DEFINIDO'));
         } else {
             // Para otros tipos, usar partial por defecto
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== PROCESANDO TIPO DESCONOCIDO (' . $custom_payment_type . ') COMO PARCIAL ========== - loan_id: ' . $loan_id . ', custom_amount: ' . $custom_amount);
             $result = $this->process_custom_payment_partial($loan_id, $loan_item_ids, $custom_amount, $user_id, $payment_description);
             log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: RESULTADO TIPO DESCONOCIDO - is_partial: ' . ($result['is_partial'] ?? 'NO DEFINIDO'));
         }

         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== RESULTADO DEL PROCESAMIENTO ==========');
         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: result success: ' . ($result['success'] ? 'TRUE' : 'FALSE'));
         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: result is_partial: ' . ($result['is_partial'] ?? 'NO DEFINIDO'));
         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: result message: ' . ($result['message'] ?? 'NO MESSAGE'));
         log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: result payment_breakdown count: ' . (isset($result['payment_breakdown']) ? count($result['payment_breakdown']) : 'NO BREAKDOWN'));

         if (!$result['success']) {
             throw new Exception($result['message']);
         }

         // DEBUG: Agregar logs después del procesamiento para verificar cambios
         log_message('debug', 'CUSTOM_PAYMENT: ========== DIAGNÓSTICO DESPUÉS DEL PROCESAMIENTO ========== - custom_payment_type: ' . $custom_payment_type);
         log_message('debug', 'CUSTOM_PAYMENT: Resultado del procesamiento: ' . json_encode($result));
         foreach ($loan_item_ids as $quota_id) {
             $quota_info = $this->payments_m->get_loan_item($quota_id);
             $status_after = $quota_info ? $quota_info->status : 'NO ENCONTRADA';
             log_message('debug', 'CUSTOM_PAYMENT: Cuota ID ' . $quota_id . ' DESPUÉS - status: ' . $status_after . ', balance: ' . ($quota_info ? $quota_info->balance : 'N/A') . ', interest_paid: ' . ($quota_info ? $quota_info->interest_paid : 'N/A') . ', capital_paid: ' . ($quota_info ? $quota_info->capital_paid : 'N/A') . ', fee_amount: ' . ($quota_info ? $quota_info->fee_amount : 'N/A'));

             // DIAGNÓSTICO CRÍTICO: Verificar si el status cambió correctamente
             if ($custom_payment_type === 'partial' && $status_after != 3) {
                 log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: ERROR CRÍTICO - Pago parcial pero status NO es 3! Status actual: ' . $status_after . ' - Cuota ID: ' . $quota_id);
             } elseif ($custom_payment_type === 'incomplete' && $status_after != 4) {
                 log_message('error', 'CUSTOM_PAYMENT_DIAGNOSIS: ERROR CRÍTICO - Pago incompleto pero status NO es 4! Status actual: ' . $status_after . ' - Cuota ID: ' . $quota_id);
             }
         }

         // Validar cálculo de pagos
         $validation = $this->paymentvalidator->validate_payment_calculation($result['payment_breakdown']);
         if (!$validation['is_valid']) {
             throw new Exception('Error de validación en cálculo de pagos: ' . implode(', ', $validation['errors']));
         }

         // Registrar el pago en la tabla payments
         $payment_data = [
             'loan_id' => $loan_id,
             'loan_item_id' => $loan_item_ids[0], // Primera cuota como referencia
             'amount' => $custom_amount,
             'tipo_pago' => 'custom',
             'monto_pagado' => $custom_amount,
             'interest_paid' => array_sum(array_column($result['payment_breakdown'], 'interest_paid')),
             'capital_paid' => array_sum(array_column($result['payment_breakdown'], 'capital_paid')),
             'payment_date' => date('Y-m-d H:i:s'),
             'payment_user_id' => $user_id,
             'method' => 'efectivo',
             'notes' => $payment_description . ' - Tipo: ' . strtoupper($custom_payment_type) . ' - Desglose: ' . json_encode($result['payment_breakdown'])
         ];

         // Agregar campos específicos para pagos personalizados
         if ($this->db->field_exists('custom_amount', 'payments')) {
             $payment_data['custom_amount'] = $custom_amount;
         }
         if ($this->db->field_exists('custom_payment_type', 'payments')) {
             $payment_data['custom_payment_type'] = $custom_payment_type;
         }

         $this->db->insert('payments', $payment_data);
         $payment_id = $this->db->insert_id();

         // Generar audit trail después del pago exitoso
         $this->paymentcalculator->generate_audit_trail($loan_id, $result);

         // Verificar si el préstamo debe cerrarse
         $this->check_and_close_loan($loan_id, $customer_id);

         // CORRECCIÓN: Garantizar atomicidad de transacciones
         if ($this->db->trans_status() === FALSE) {
             $this->db->trans_rollback();
             throw new Exception('Transacción fallida - datos inconsistentes');
         } else {
             $this->db->trans_commit();
         }

         log_message('info', 'CUSTOM_PAYMENT: Pago personalizado completado exitosamente - payment_id: ' . $payment_id . ', loan_id: ' . $loan_id . ', tipo: ' . strtoupper($custom_payment_type));

         // DEBUG: Log detallado del resultado final
         log_message('debug', 'CUSTOM_PAYMENT: ========== RESULTADO FINAL ==========');
         log_message('debug', 'CUSTOM_PAYMENT: custom_payment_type: ' . $custom_payment_type);
         log_message('debug', 'CUSTOM_PAYMENT: is_partial: ' . ($result['is_partial'] ? 'TRUE' : 'FALSE'));
         log_message('debug', 'CUSTOM_PAYMENT: is_last_installment: ' . ($result['is_last_installment'] ?? 'FALSE'));
         log_message('debug', 'CUSTOM_PAYMENT: additional_quota_generated: ' . ($result['additional_quota_generated'] ?? 'FALSE'));
         log_message('debug', 'CUSTOM_PAYMENT: remaining_balance_distributed: ' . ($result['remaining_balance_distributed'] ?? 0));
         log_message('debug', 'CUSTOM_PAYMENT: payment_breakdown count: ' . count($result['payment_breakdown'] ?? []));

         // Preparar mensaje de respuesta según el tipo de pago
         $message_suffix = '';
         if ($custom_payment_type === 'partial') {
             $message_suffix = ($result['additional_quota_generated'] ?? false) ? ' (Nueva cuota con mora generada)' : (($result['is_partial'] ?? false) ? ' (Pago parcial - saldo distribuido en cuotas futuras, cuotas marcadas como status=3)' : '');
         } elseif ($custom_payment_type === 'incomplete') {
             $message_suffix = ' (Pago no completo - saldo pendiente en cuota actual, cuota marcada como status=4)';
         }

         // Retornar respuesta exitosa
         echo json_encode(ajax_success([
             'payment_id' => $payment_id,
             'total_amount' => $custom_amount,
             'total_interest_paid' => array_sum(array_column($result['payment_breakdown'], 'interest_paid')),
             'total_capital_paid' => array_sum(array_column($result['payment_breakdown'], 'capital_paid')),
             'custom_payment_type' => $custom_payment_type,
             'is_partial' => $result['is_partial'] ?? false,
             'is_last_installment' => $result['is_last_installment'] ?? false,
             'additional_quota_generated' => $result['additional_quota_generated'] ?? false,
             'remaining_balance_distributed' => $result['remaining_balance_distributed'] ?? 0,
             'breakdown' => $result['payment_breakdown'],
             'message' => 'Pago personalizado procesado exitosamente' . $message_suffix
         ]));

     } catch (Exception $e) {
         // Revertir transacción en caso de error
         $this->db->trans_rollback();
         log_message('error', 'CUSTOM_PAYMENT: Error procesando pago personalizado - ' . $e->getMessage());
         echo json_encode(ajax_error('SYSTEM_001', [], ['details' => $e->getMessage()]));
     }
 }


 /**
  * Método principal para procesar tickets de pago
  */
 function ticket()
  {
      log_message('debug', 'TICKET: ========== INICIO PROCESAMIENTO TICKET ==========');

      // LOGS DIAGNÓSTICOS DETALLADOS: Verificar datos que llegan al método ticket()
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO DATOS ENTRADA ==========');
      log_message('debug', 'PAYMENTS_DIAGNOSIS: POST completo recibido: ' . json_encode($this->input->post()));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: tipo_pago recibido: ' . ($this->input->post('tipo_pago') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: quota_id recibido: ' . json_encode($this->input->post('quota_id')));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: loan_id recibido: ' . ($this->input->post('loan_id') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: user_id recibido: ' . ($this->input->post('user_id') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: custom_amount recibido: ' . ($this->input->post('custom_amount') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: custom_payment_type recibido: ' . ($this->input->post('custom_payment_type') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: customer_id recibido: ' . ($this->input->post('customer_id') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: amount recibido: ' . ($this->input->post('amount') ?? 'NO RECIBIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO DATOS ENTRADA ==========');

      // Preparar y validar datos iniciales
      $payment_data = $this->prepare_and_validate_payment_data();

      // CORRECCIÓN: Verificar y corregir saldos negativos antes del procesamiento
      $loan_id = $payment_data['loan_id'];
      $this->check_and_fix_negative_balances($loan_id, $payment_data['user_id']);

      // Filtrar cuotas pendientes
      $pending_quota_ids = $this->filter_pending_quotas($payment_data['quota_ids'], $payment_data['loan_id'], $payment_data['user_id']);

      // Validación adicional: En pagos personalizados, asegurar que las cuotas seleccionadas tengan montos pendientes reales
      if ($payment_data['tipo_pago'] === 'custom' && !empty($pending_quota_ids)) {
          $valid_quota_ids = [];
          foreach ($pending_quota_ids as $qid) {
              $qi = $this->payments_m->get_loan_item($qid);
              if ($qi) {
                  $interest_pending = max(0, ($qi->interest_amount ?? 0) - ($qi->interest_paid ?? 0));
                  $capital_pending = max(0, ($qi->capital_amount ?? 0) - ($qi->capital_paid ?? 0));
                  $total_pending = $interest_pending + $capital_pending;
                  if ($total_pending > 0) {
                      $valid_quota_ids[] = $qid;
                  } else {
                      log_message('debug', 'TICKET_VALIDATION: Cuota ' . $qid . ' descartada para custom payment por total_pending=0');
                  }
              }
          }
          if (empty($valid_quota_ids)) {
              log_message('error', 'TICKET_VALIDATION: Ninguna cuota con pendiente > 0 para tipo_pago=custom. Abortar.');
              show_error('No hay montos pendientes en las cuotas seleccionadas para aplicar pago personalizado.', 400, 'Validación de Pago');
              return;
          }
          // Reemplazar por las válidas
          $pending_quota_ids = $valid_quota_ids;
      }

      // LOGS DIAGNÓSTICOS: Verificar processed_quotas antes de preparar ticket
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO PROCESSED_QUOTAS ==========');
      log_message('debug', 'PAYMENTS_DIAGNOSIS: pending_quota_ids después de filtrado: ' . json_encode($pending_quota_ids));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: Cantidad de pending_quota_ids: ' . count($pending_quota_ids));

      // Procesar pagos según tipo
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== PROCESANDO PAGOS ========== - tipo_pago: ' . $payment_data['tipo_pago']);
      $processed_quotas = $this->process_payments_by_type($payment_data, $pending_quota_ids);
      log_message('debug', 'PAYMENTS_DIAGNOSIS: processed_quotas retornado: ' . json_encode($processed_quotas));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: Cantidad de processed_quotas: ' . count($processed_quotas));

      // DIAGNÓSTICO: Verificar si processed_quotas está vacío
      if (empty($processed_quotas)) {
          log_message('error', 'PAYMENTS_DIAGNOSIS: ERROR CRÍTICO - processed_quotas está VACÍO después del procesamiento - tipo_pago: ' . $payment_data['tipo_pago'] . ', pending_quota_ids: ' . json_encode($pending_quota_ids));
          // CORRECCIÓN: Si processed_quotas está vacío, intentar procesar con lógica alternativa
          if (!empty($pending_quota_ids)) {
              log_message('debug', 'PAYMENTS_DIAGNOSIS: Intentando procesar cuotas pendientes manualmente');
              $processed_quotas = $this->process_fallback_payment($payment_data, $pending_quota_ids);
          }

          // Si aún está vacío después del fallback, lanzar excepción crítica
          if (empty($processed_quotas)) {
              throw new Exception('No se procesaron cuotas para generar el ticket - tipo_pago: ' . $payment_data['tipo_pago'] . ', cuotas pendientes: ' . count($pending_quota_ids));
          }
      } else {
          log_message('debug', 'PAYMENTS_DIAGNOSIS: processed_quotas contiene ' . count($processed_quotas) . ' elementos');
      }
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO PROCESSED_QUOTAS ==========');

      // Preparar datos para el ticket
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== PREPARANDO DATOS PARA TICKET ========== - processed_quotas count: ' . count($processed_quotas));
      $data = $this->prepare_ticket_data($payment_data, $processed_quotas);

      // LOGS DIAGNÓSTICOS: Verificar quotasPaid después de prepare_ticket_data
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO QUOTASPAD DESPUÉS DE PREPARE ========== - tipo_pago: ' . ($tipo_pago ?? 'NO DEFINIDO'));
      log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] existe: ' . (isset($data['quotasPaid']) ? 'SÍ' : 'NO'));
      if (isset($data['quotasPaid'])) {
          log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] count: ' . count($data['quotasPaid']));
          log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] contenido: ' . json_encode($data['quotasPaid']));

          // DIAGNÓSTICO: Verificar si quotasPaid está vacío
          if (empty($data['quotasPaid'])) {
              log_message('error', 'PAYMENTS_DIAGNOSIS: ERROR CRÍTICO - data[quotasPaid] está VACÍO después de prepare_ticket_data - processed_quotas: ' . json_encode($processed_quotas));
          } else {
              log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] contiene ' . count($data['quotasPaid']) . ' elementos');
          }

          // DIAGNÓSTICO ADICIONAL: Verificar montos en quotasPaid
          $total_interest_paid = array_sum(array_column($data['quotasPaid'], 'interest_paid'));
          $total_capital_paid = array_sum(array_column($data['quotasPaid'], 'capital_paid'));
          log_message('debug', 'PAYMENTS_DIAGNOSIS: TOTALES EN QUOTASPAD - interest_paid: ' . $total_interest_paid . ', capital_paid: ' . $total_capital_paid . ', suma: ' . ($total_interest_paid + $total_capital_paid));
      } else {
          log_message('error', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] NO EXISTE después de prepare_ticket_data');
      }
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO QUOTASPAD DESPUÉS DE PREPARE ==========');

      // Actualizar cuotas después del pago
      $this->update_quotas_after_payment($payment_data, $pending_quota_ids);
      log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] count: ' . (isset($data['quotasPaid']) ? count($data['quotasPaid']) : 'NO EXISTE'));

      // CORRECCIÓN: Para pagos personalizados, llamar a handle_remaining_balance si es necesario
      if ($payment_data['tipo_pago'] === 'custom' && !empty($processed_quotas) && isset($processed_quotas['is_partial']) && $processed_quotas['is_partial']) {
          log_message('debug', 'PAYMENTS_DIAGNOSIS: CORRECCIÓN - Pago personalizado parcial detectado, llamando a handle_remaining_balance');
          $this->handle_remaining_balance($payment_data['loan_id'], $processed_quotas['remaining_balance_distributed'] ?? 0, $payment_data['user_id'], true, $pending_quota_ids);
      }

      // Calcular total_amount
      $data['total_amount'] = $this->calculate_ticket_total_amount($payment_data, $processed_quotas, $data);

      // CORRECCIÓN: Para pagos personalizados, asegurar que los totales de intereses y capital se calculen correctamente
      if ($payment_data['tipo_pago'] === 'custom' && !empty($data['quotasPaid'])) {
          $total_interest_paid = array_sum(array_column($data['quotasPaid'], 'interest_paid'));
          $total_capital_paid = array_sum(array_column($data['quotasPaid'], 'capital_paid'));
          log_message('debug', 'PAYMENTS_DIAGNOSIS: CORRECCIÓN - Totales calculados para custom payment - interest_paid: ' . $total_interest_paid . ', capital_paid: ' . $total_capital_paid);

          // Actualizar total_amount si es necesario
          $calculated_total = $total_interest_paid + $total_capital_paid;
          if ($calculated_total > 0 && $calculated_total != $data['total_amount']) {
              log_message('debug', 'PAYMENTS_DIAGNOSIS: CORRECCIÓN - total_amount ANTES: ' . $data['total_amount'] . ', calculado: ' . $calculated_total);
              $data['total_amount'] = $calculated_total;
              log_message('debug', 'PAYMENTS_DIAGNOSIS: CORRECCIÓN - total_amount actualizado a: ' . $calculated_total);
          }
      }

      // LOGS DIAGNÓSTICOS ADICIONALES: Verificar estado de cuotas después del procesamiento
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO ESTADO FINAL DE CUOTAS ==========');
      if (!empty($pending_quota_ids)) {
          foreach ($pending_quota_ids as $quota_id) {
              $final_quota = $this->payments_m->get_loan_item($quota_id);
              if ($final_quota) {
                  log_message('debug', 'PAYMENTS_DIAGNOSIS: Cuota ID ' . $quota_id . ' - status: ' . $final_quota->status . ', balance: ' . $final_quota->balance . ', interest_paid: ' . ($final_quota->interest_paid ?? 0) . ', capital_paid: ' . ($final_quota->capital_paid ?? 0));
              } else {
                  log_message('error', 'PAYMENTS_DIAGNOSIS: ERROR - Cuota ID ' . $quota_id . ' no encontrada después del procesamiento');
              }
          }
      }
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO ESTADO FINAL DE CUOTAS ==========');

      // LOGS DIAGNÓSTICOS: Verificar total_amount calculado
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== DIAGNÓSTICO TOTAL_AMOUNT ==========');
      log_message('debug', 'PAYMENTS_DIAGNOSIS: data[total_amount] calculado: ' . $data['total_amount']);
      log_message('debug', 'PAYMENTS_DIAGNOSIS: data[quotasPaid] count final: ' . (isset($data['quotasPaid']) ? count($data['quotasPaid']) : 'NO EXISTE'));
      if (isset($data['quotasPaid']) && !empty($data['quotasPaid'])) {
          $total_interest_paid = array_sum(array_column($data['quotasPaid'], 'interest_paid'));
          $total_capital_paid = array_sum(array_column($data['quotasPaid'], 'capital_paid'));
          log_message('debug', 'PAYMENTS_DIAGNOSIS: Suma total interest_paid en quotasPaid: ' . $total_interest_paid);
          log_message('debug', 'PAYMENTS_DIAGNOSIS: Suma total capital_paid en quotasPaid: ' . $total_capital_paid);
          log_message('debug', 'PAYMENTS_DIAGNOSIS: Suma total (interest + capital): ' . ($total_interest_paid + $total_capital_paid));

          // DIAGNÓSTICO CRÍTICO: Verificar si total_amount coincide con la suma de pagos
          $expected_total = $total_interest_paid + $total_capital_paid;
          if ($data['total_amount'] != $expected_total) {
              log_message('error', 'PAYMENTS_DIAGNOSIS: ERROR CRÍTICO - total_amount (' . $data['total_amount'] . ') NO COINCIDE con suma de pagos (' . $expected_total . ')');
          } else {
              log_message('debug', 'PAYMENTS_DIAGNOSIS: total_amount coincide correctamente con suma de pagos');
          }
      }
      log_message('debug', 'PAYMENTS_DIAGNOSIS: ========== FIN DIAGNÓSTICO TOTAL_AMOUNT ==========');

      // Verificar cierre de préstamo
      $this->check_and_close_loan($payment_data['loan_id'], $payment_data['customer_id']);

      // Cargar vista del ticket
      $this->load->view('admin/payments/ticket', $data);

      // Completar la transacción exitosamente
      $this->db->trans_complete();
      log_message('info', 'PAYMENTS_DIAGNOSIS: TRANSACCIÓN COMPLETADA EXITOSAMENTE - loan_id: ' . $payment_data['loan_id'] . ', user_id: ' . $payment_data['user_id'] . ', monto_total: ' . $data['total_amount']);
  }

  /**
   * Prepara y valida los datos iniciales del pago
   */
  private function prepare_and_validate_payment_data()
  {
      // Recibir datos POST
      $data['name_cst'] = $this->input->post('name_cst');
      $data['coin'] = $this->input->post('coin');
      $data['loan_id'] = $this->input->post('loan_id');
      $user_id = $this->input->post('user_id');
      $tipo_pago = $this->input->post('tipo_pago');
      $custom_amount = $this->input->post('custom_amount');
      $custom_payment_type = $this->input->post('custom_payment_type');
      $payment_description = $this->input->post('payment_description');
      $quota_ids = $this->input->post('quota_id');

      // DIAGNÓSTICO: Agregar logging detallado para investigar el error de "Tipo de aplicación inválido"
      log_message('debug', 'PAYMENT_VALIDATION: custom_payment_type recibido: ' . json_encode($custom_payment_type));
      log_message('debug', 'PAYMENT_VALIDATION: POST data completo: ' . json_encode($this->input->post()));

      // Asegurar que coin esté disponible
      if (empty($data['coin'])) {
          log_message('error', 'TICKET: Campo coin no recibido en POST data');
          throw_error('VALIDATION_001', ['Moneda']);
      }

      // Agregar datos nuevos a la data del ticket
      $data['tipo_pago'] = $tipo_pago;
      $data['custom_amount'] = $custom_amount;
      $data['custom_payment_type'] = $custom_payment_type;
      $data['payment_description'] = $payment_description;

      // Para pagos personalizados, asegurar que custom_payment_type tenga un valor válido por defecto
      if ($tipo_pago === 'custom' && empty($custom_payment_type)) {
          $custom_payment_type = 'cuota'; // Prioridad interés-capital por defecto
          $data['custom_payment_type'] = $custom_payment_type;
          log_message('debug', 'PAYMENT_VALIDATION: Aplicando prioridad automática interés-capital para pago personalizado (tipo vacío) - Monto: ' . $custom_amount);
      }

      log_message('debug', 'TICKET: Datos básicos - name_cst: ' . $data['name_cst'] . ', coin: ' . $data['coin'] . ', loan_id: ' . $data['loan_id'] . ', user_id: ' . $user_id . ', tipo_pago: ' . $tipo_pago . ', custom_amount: ' . $custom_amount . ', custom_payment_type: ' . $custom_payment_type . ', payment_description: ' . $payment_description);

      // Log específico para debugging del tipo_pago
      log_message('debug', 'TICKET: Tipo de pago recibido: ' . ($tipo_pago ?? 'NULO'));
      log_message('debug', 'TICKET: POST completo: ' . json_encode($this->input->post()));
      log_message('debug', 'TICKET: Campo tipo_pago específicamente: ' . (isset($_POST['tipo_pago']) ? $_POST['tipo_pago'] : 'NO EXISTE'));
      log_message('debug', 'TICKET: Campos del formulario: ' . json_encode(array_keys($this->input->post())));

      // Validación del campo tipo_pago usando el helper centralizado
      log_message('info', 'TICKET: ========== INICIO VALIDACIÓN ========== - loan_id: ' . $data['loan_id'] . ', user_id: ' . $user_id);

      // Preparar datos para validación centralizada
      $payment_data = [
          'tipo_pago' => $tipo_pago,
          'quota_ids' => $quota_ids,
          'custom_amount' => $custom_amount,
          'custom_payment_type' => $custom_payment_type,
          'loan_id' => $data['loan_id'],
          'user_id' => $user_id,
          'customer_id' => $this->input->post('customer_id'),
          'amount' => $this->input->post('amount'),
          'data' => $data
      ];

      // Agregar información adicional para validaciones específicas
      if ($tipo_pago === 'custom' && $custom_payment_type === 'liquidation' && !empty($quota_ids)) {
          $selected_quota = $this->payments_m->get_loan_item($quota_ids[0]);
          if ($selected_quota) {
              $payment_data['selected_quota_balance'] = $selected_quota->balance;
          }
      }

      // Agregar información de amortización mixta si aplica
      $loan = $this->loans_m->get_loan($data['loan_id']);
      if ($loan && $loan->amortization_type === 'mixta') {
          $payment_data['amortization_type'] = 'mixta';
          // Obtener números de cuota para validación
          $quota_numbers = [];
          if (!empty($quota_ids)) {
              foreach ($quota_ids as $qid) {
                  $quota = $this->payments_m->get_loan_item($qid);
                  if ($quota) {
                      $quota_numbers[] = $quota->num_quota;
                  }
              }
          }
          $payment_data['quota_numbers'] = $quota_numbers;
      }

      // Validaciones adicionales específicas por tipo de pago
      if ($tipo_pago === 'total') {
          if (count($quota_ids) < 1) {
              throw_error('PAYMENT_009'); // Al menos una cuota para pago total
          }
          if (count($quota_ids) > 1) {
              throw_error('PAYMENT_013'); // Solo una cuota para pago total
          }
      }

      if ($tipo_pago === 'early_total' || $tipo_pago === 'total_condonacion') {
          if (count($quota_ids) !== 1) {
              throw_error('PAYMENT_013'); // Solo una cuota para pago total anticipado
          }
      }

      // MEJORA: Validación adicional usando librería centralizada
      if (empty($tipo_pago)) {
          log_message('error', 'TIPO_PAGO_VACIO: Campo tipo_pago llegó vacío desde el formulario');
          throw_error('PAYMENT_002');
      }

      // Validar tipo_pago usando PaymentTypeValidator::isValidType
      if (!PaymentTypeValidator::isValidType($tipo_pago)) {
          log_message('error', 'TIPO_PAGO_INVALIDO: Tipo de pago no válido - ' . $tipo_pago . '. Tipos válidos: ' . json_encode(PaymentTypeValidator::getValidTypes()));
          throw_error('PAYMENT_002');
      }

      // Validar configuración completa usando librería centralizada
      $validation_errors = PaymentTypeValidator::validatePaymentConfig($tipo_pago, count($quota_ids), $custom_amount);
      if (!empty($validation_errors)) {
          foreach ($validation_errors as $error) {
              log_message('error', 'VALIDATION_ERROR: ' . $error);
          }
          throw_error('PAYMENT_002'); // Error genérico de validación
      }

      // CORRECCIÓN: Unificar cálculo de montos entre cliente y servidor para tipos 'total' y 'total_condonacion'
      // El cliente calcula solo la cuota seleccionada, pero el servidor suma todas las cuotas pendientes
      // Para mantener consistencia, ajustamos la lógica del servidor para que coincida con el cliente
      // cuando se trata de pago total de una sola cuota específica

      if ($tipo_pago === 'custom') {
          if (empty($custom_amount) || !is_numeric($custom_amount) || $custom_amount <= 0) {
              throw_error('PAYMENT_010');
          }

          // Validar que el monto personalizado no exceda el total de las cuotas seleccionadas
          $total_selected_amount = 0;
          if (!empty($quota_ids)) {
              foreach ($quota_ids as $quota_id) {
                  $quota = $this->payments_m->get_loan_item($quota_id);
                  if ($quota) {
                      $total_selected_amount += $quota->fee_amount;
                  }
              }
          }

          if ($custom_amount > $total_selected_amount) {
              throw_error('PAYMENT_011', [], ['max_amount' => number_format($total_selected_amount, 2, ',', '.')]);
          }

          // Para pagos personalizados, aplicar prioridad automática interés-capital si no se especifica tipo
          if (empty($custom_payment_type)) {
              $custom_payment_type = 'cuota'; // Prioridad interés-capital por defecto
              log_message('debug', 'PAYMENT_VALIDATION: Aplicando prioridad automática interés-capital para pago personalizado (tipo vacío) - Monto: ' . $custom_amount . ', Total cuotas: ' . $total_selected_amount);
          } else {
              log_message('debug', 'PAYMENT_VALIDATION: Usando tipo personalizado especificado: ' . $custom_payment_type . ' - Monto: ' . $custom_amount . ', Total cuotas: ' . $total_selected_amount);
          }

          // Validar que el tipo de pago personalizado sea válido
          $valid_custom_types = ['cuota', 'interes', 'capital', 'liquidation', 'pago_personalizado', 'partial', 'incomplete'];
          log_message('debug', 'PAYMENT_VALIDATION: Valores válidos: ' . json_encode($valid_custom_types));
          if (!in_array($custom_payment_type, $valid_custom_types)) {
              log_message('error', 'PAYMENT_VALIDATION: Tipo inválido - recibido: "' . $custom_payment_type . '", válidos: ' . json_encode($valid_custom_types));
              throw_error('PAYMENT_015');
          }

          // Validación adicional para liquidación anticipada
          if ($custom_payment_type === 'liquidation' && !empty($quota_ids)) {
              $selected_quota = $this->payments_m->get_loan_item($quota_ids[0]);
              if ($selected_quota && $custom_amount < $selected_quota->balance) {
                  throw_error('PAYMENT_016', [$selected_quota->balance]);
              }
          }

          // Validaciones para amortización mixta
          $loan = $this->loans_m->get_loan($data['loan_id']);
          if ($loan && $loan->amortization_type === 'mixta') {
              // Obtener números de cuota para validación
              $quota_numbers = [];
              if (!empty($quota_ids)) {
                  foreach ($quota_ids as $qid) {
                      $quota = $this->payments_m->get_loan_item($qid);
                      if ($quota) {
                          $quota_numbers[] = $quota->num_quota;
                      }
                  }
              }
              // Validar amortización mixta
              ErrorHandler::validate_mixed_amortization_payment([
                  'custom_payment_type' => $custom_payment_type,
                  'quota_numbers' => $quota_numbers
              ]);
          }
      }

      // Usar validación centralizada con logging mejorado
      try {
          ErrorHandler::validate_payment($payment_data);
          log_message('info', 'TICKET: VALIDACIÓN PASADA - Tipo de pago válido: ' . $tipo_pago . ' - loan_id: ' . $data['loan_id'] . ' - Descripción: ' . PaymentTypeValidator::getTypeDescription($tipo_pago));
      } catch (Exception $e) {
          log_message('error', 'TICKET: VALIDACIÓN FALLIDA - ' . $e->getMessage() . ' - loan_id: ' . $data['loan_id'] . ', user_id: ' . $user_id . ', tipo_pago: ' . $tipo_pago . ', tipos_válidos: ' . json_encode(PaymentTypeValidator::getValidTypes()));
          // Mostrar mensaje de error específico en lugar de lanzar excepción
          show_error($e->getMessage(), 500, 'Error de Validación');
          return; // Salir sin procesar
      }

      log_message('info', 'TICKET: VALIDACIÓN PASADA - Tipo de pago válido: ' . $tipo_pago . ' - loan_id: ' . $data['loan_id'] . ' - Descripción: ' . PaymentTypeValidator::getTypeDescription($tipo_pago));

      return $payment_data;
  }

  /**
   * Filtra las cuotas pendientes de pago
   */
  private function filter_pending_quotas($quota_ids, $loan_id, $user_id)
  {
      log_message('info', 'TICKET: ========== INICIANDO FILTRO DE CUOTAS PENDIENTES ========== - loan_id: ' . $loan_id . ', user_id: ' . $user_id);

      // Validar selección de cuotas usando el helper centralizado
      if (!isset($quota_ids) || empty($quota_ids) || !is_array($quota_ids)) {
          log_message('error', 'TICKET: ERROR CRÍTICO - No se seleccionaron cuotas para procesar');
          throw_error('PAYMENT_007');
      }

      // Validar que no se seleccionen más de 50 cuotas (límite razonable)
      if (count($quota_ids) > 50) {
          log_message('error', 'TICKET: Demasiadas cuotas seleccionadas: ' . count($quota_ids));
          throw_error('PAYMENT_008');
      }

      $pending_quota_ids = [];
      if (is_array($quota_ids) && !empty($quota_ids)) {
          log_message('debug', 'TICKET: Procesando ' . count($quota_ids) . ' cuotas del POST - loan_id: ' . $loan_id);
          foreach ($quota_ids as $q) {
              log_message('debug', 'TICKET: Verificando cuota ID ' . $q . ' - obteniendo info de BD - loan_id: ' . $loan_id);
              $quota_info = $this->payments_m->get_loan_item($q);
              log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Info obtenida: balance=' . ($quota_info ? $quota_info->balance : 'N/A') . ', status=' . ($quota_info ? $quota_info->status : 'N/A') . ' - loan_id: ' . $loan_id);
              // Incluir cuotas pendientes (status=1), parciales (status=3) y no completas (status=4)
              // Excluir cuotas pagadas completamente (status=0) y condonadas (extra_payment=3)
              if ($quota_info && in_array($quota_info->status, [1, 3, 4]) && (!isset($quota_info->extra_payment) || $quota_info->extra_payment != 3)) {
                  $pending_quota_ids[] = $q;
                  $status_text = $quota_info->status == 1 ? 'PENDIENTE' : ($quota_info->status == 3 ? 'PARCIAL' : 'NO COMPLETO');
                  log_message('info', 'TICKET: Cuota ID ' . $q . ' - Estado: ' . $status_text . ' (se procesará) - balance: ' . $quota_info->balance . ' - loan_id: ' . $loan_id);
              } else {
                  $status_text = $quota_info ? ($quota_info->status == 0 ? 'PAGADA' : 'OTRO') : 'NO ENCONTRADA';
                  log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Estado: ' . $status_text . ' (se ignora) - loan_id: ' . $loan_id);
              }
          }
      } else {
          log_message('error', 'TICKET: ERROR CRÍTICO - quota_ids no es array válido o está vacío - no se procesarán cuotas - loan_id: ' . $loan_id . ', user_id: ' . $user_id);
      }

      log_message('info', 'TICKET: Cuotas pendientes filtradas: ' . json_encode($pending_quota_ids) . ' - total: ' . count($pending_quota_ids) . ' - loan_id: ' . $loan_id);
      return $pending_quota_ids;
  }

  /**
   * Procesa pagos según el tipo_pago
   */
  private function process_payments_by_type($payment_data, $pending_quota_ids)
  {
      $tipo_pago = $payment_data['tipo_pago'];
      $custom_amount = $payment_data['custom_amount'];
      $custom_payment_type = $payment_data['custom_payment_type'];
      $user_id = $payment_data['user_id'];
      $loan_id = $payment_data['loan_id'];
      $amount = $payment_data['amount'];

      log_message('info', 'TICKET: ========== INICIANDO PROCESAMIENTO DE PAGOS ========== - tipo_pago: ' . $tipo_pago . ', loan_id: ' . $loan_id . ', user_id: ' . $user_id);

      $processed_quotas = [];

      switch ($tipo_pago) {
          case 'full':
              // Pago completo: procesar todas las cuotas seleccionadas normalmente
              log_message('info', 'TICKET: Procesando pago completo - cuotas: ' . count($payment_data['quota_ids']) . ', loan_id: ' . $loan_id);
              $processed_quotas = $this->process_full_payment($payment_data['quota_ids'], $user_id);
              log_message('info', 'TICKET: Pago completo procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'interest':
              // Solo interés: aplicar pago solo a intereses de las cuotas seleccionadas
              log_message('info', 'TICKET: Procesando pago solo interés - cuotas pendientes: ' . count($pending_quota_ids) . ', loan_id: ' . $loan_id . ', monto: ' . $amount);
              $processed_quotas = $this->process_interest_only_payment($pending_quota_ids, $user_id, $amount);
              log_message('info', 'TICKET: Pago solo interés procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'capital':
              // Pago a capital: aplicar pago solo a capital de las cuotas seleccionadas
              log_message('info', 'TICKET: Procesando pago a capital - cuotas pendientes: ' . count($pending_quota_ids) . ', loan_id: ' . $loan_id . ', monto: ' . $amount);
              $processed_quotas = $this->process_capital_only_payment($pending_quota_ids, $user_id, $amount);
              log_message('info', 'TICKET: Pago a capital procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'both':
              // Interés y capital: aplicar pago proporcionalmente a ambas partes
              log_message('info', 'TICKET: Procesando pago interés y capital - cuotas pendientes: ' . count($pending_quota_ids) . ', loan_id: ' . $loan_id . ', monto: ' . $amount);
              $processed_quotas = $this->process_interest_capital_payment($pending_quota_ids, $user_id, $amount);
              log_message('info', 'TICKET: Pago interés y capital procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'total':
              // PAGO TOTAL: Cancelar completamente una sola cuota específica
              log_message('info', 'TICKET: ========== PROCESANDO PAGO TOTAL ==========');
              log_message('info', 'TICKET: Cuotas pendientes para pago total: ' . json_encode($pending_quota_ids));
              $processed_quotas = $this->process_total_payment($pending_quota_ids, $user_id);
              log_message('info', 'TICKET: Pago total procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'custom':
              // Monto personalizado: aplicar monto con prioridad interés-capital o liquidación
              log_message('info', 'TICKET: ========== PROCESANDO PAGO PERSONALIZADO ==========');
              log_message('info', 'TICKET: Monto personalizado: ' . $custom_amount);
              log_message('info', 'TICKET: Tipo personalizado: ' . $custom_payment_type);
              log_message('info', 'TICKET: Cuotas pendientes: ' . count($pending_quota_ids));
              log_message('info', 'TICKET: Loan ID: ' . $loan_id);

              // DIAGNÓSTICO: Verificar si custom_payment_type está vacío y aplicar prioridad automática
              if (empty($custom_payment_type)) {
                  log_message('info', 'TICKET: custom_payment_type vacío, aplicando prioridad automática interés-capital');
                  $custom_payment_type = 'cuota'; // Prioridad interés-capital por defecto
              }

              // CORRECCIÓN CRÍTICA: Distinguir entre 'partial' e 'incomplete' y llamar al método correcto
              if ($custom_payment_type === 'incomplete') {
                  log_message('info', 'TICKET: Procesando pago NO COMPLETO (incomplete) - llamando a process_custom_payment_incomplete');
                  $processed_quotas = $this->process_custom_payment_incomplete($loan_id, $pending_quota_ids, $custom_amount, $user_id, $payment_description ?? 'Pago personalizado - No completo');
              } elseif ($custom_payment_type === 'partial') {
                  log_message('info', 'TICKET: Procesando pago PARCIAL - llamando a process_custom_payment_partial');
                  $processed_quotas = $this->process_custom_payment_partial($loan_id, $pending_quota_ids, $custom_amount, $user_id, $payment_description ?? 'Pago personalizado - Parcial');
              } else {
                  // Para otros tipos (cuota, interes, capital, liquidation, etc.), usar partial por defecto
                  log_message('info', 'TICKET: Tipo personalizado no reconocido (' . $custom_payment_type . '), usando process_custom_payment_partial por defecto');
                  $processed_quotas = $this->process_custom_payment_partial($loan_id, $pending_quota_ids, $custom_amount, $user_id, $payment_description ?? 'Pago personalizado secuencial');
              }

              log_message('info', 'TICKET: Pago personalizado procesado - tipo final: ' . $custom_payment_type . ', monto: ' . $custom_amount . ', cuotas procesadas: ' . count($processed_quotas));
              break;

          case 'early_total':
              // PAGO TOTAL ANTICIPADO CON CONDONACIÓN: Calcular capital pendiente + intereses vencidos, marcar cuota actual como pagada, condonar futuras
              log_message('info', 'TICKET: ========== PROCESANDO PAGO TOTAL ANTICIPADO CON CONDONACIÓN ==========');
              log_message('info', 'TICKET: Cuotas pendientes para pago anticipado: ' . json_encode($pending_quota_ids));
              $processed_quotas = $this->process_early_total_payment($pending_quota_ids, $user_id);
              log_message('info', 'TICKET: Pago total anticipado procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          case 'total_condonacion':
              // PAGO TOTAL ANTICIPADO CON CONDONACIÓN: Calcular capital pendiente + intereses vencidos, marcar cuota actual como pagada, condonar futuras
              log_message('info', 'TICKET: ========== PROCESANDO PAGO TOTAL ANTICIPADO CON CONDONACIÓN ==========');
              log_message('info', 'TICKET: TIPO_PAGO CONFIRMADO: total_condonacion - loan_id: ' . $loan_id . ', user_id: ' . $user_id);
              log_message('info', 'TICKET: Cuotas pendientes para pago anticipado: ' . json_encode($pending_quota_ids));
              log_message('info', 'TICKET: Iniciando llamada a process_early_total_payment...');
              $processed_quotas = $this->process_early_total_payment($pending_quota_ids, $user_id);
              log_message('info', 'TICKET: process_early_total_payment COMPLETADO - processed_quotas retornado: ' . json_encode($processed_quotas));
              log_message('info', 'TICKET: Pago total anticipado procesado - cuotas procesadas: ' . count($processed_quotas) . ' - loan_id: ' . $loan_id);
              break;

          default:
              log_message('error', 'TICKET: Tipo de pago no válido: ' . $tipo_pago);
              throw_error('PAYMENT_002');
      }

      log_message('debug', 'TICKET: Cuotas procesadas: ' . json_encode($processed_quotas));
      return $processed_quotas;
  }

  /**
   * Actualiza cuotas después del pago según el tipo
   */
  private function update_quotas_after_payment($payment_data, $pending_quota_ids)
  {
      $tipo_pago = $payment_data['tipo_pago'];
      $user_id = $payment_data['user_id'];
      $loan_id = $payment_data['loan_id'];
      $quota_ids = $payment_data['quota_ids'];

      log_message('info', 'TICKET: ========== ACTUALIZANDO CUOTAS DESPUÉS DEL PAGO ========== - tipo_pago: ' . $tipo_pago . ', loan_id: ' . $loan_id);

      // Para tipos de pago que requieren actualización específica
      switch ($tipo_pago) {
          case 'full':
              // Ya se actualizó en process_full_payment
              break;

          case 'interest':
              // Las cuotas se mantienen pendientes, solo se actualizó interest_paid
              break;

          case 'capital':
              // Ya se actualizó en process_capital_only_payment
              break;

          case 'both':
              // Ya se actualizó en process_interest_capital_payment
              break;

          case 'total':
              // Ya se actualizó en process_total_payment
              break;

          case 'custom':
              // Ya se actualizó en process_custom_payment
              break;
      }

      // Verificar si el préstamo debe cerrarse
      $this->check_and_close_loan($loan_id, $payment_data['customer_id']);
  }

  /**
   * Prepara los datos para el ticket usando SOLO processed_quotas sin consultas a BD
   */
  private function prepare_ticket_data($payment_data, $processed_quotas)
  {
      $tipo_pago = $payment_data['tipo_pago'];
      $custom_amount = $payment_data['custom_amount'];
      $custom_payment_type = $payment_data['custom_payment_type'];
      $user_id = $payment_data['user_id'];
      $loan_id = $payment_data['loan_id'];

      $data = $payment_data['data'];

      // LOG DIAGNÓSTICO DETALLADO: Tipo de pago y datos iniciales
      log_message('debug', 'TICKET: ========== INICIO PREPARE_TICKET_DATA ==========');
      log_message('debug', 'TICKET: tipo_pago recibido: ' . ($tipo_pago ?? 'NULO'));
      log_message('debug', 'TICKET: custom_amount: ' . ($custom_amount ?? 'NULO'));
      log_message('debug', 'TICKET: custom_payment_type: ' . ($custom_payment_type ?? 'NULO'));
      log_message('debug', 'TICKET: loan_id: ' . $loan_id);
      log_message('debug', 'TICKET: user_id: ' . $user_id);
      log_message('debug', 'TICKET: quota_ids originales: ' . json_encode($payment_data['quota_ids'] ?? []));
      log_message('debug', 'TICKET: processed_quotas recibido: ' . json_encode($processed_quotas));
      log_message('debug', 'TICKET: Cantidad processed_quotas: ' . count($processed_quotas));

      // Asignar processed_quotas a data para usar en la vista del ticket
      $data['processed_quotas'] = $processed_quotas;

      // Inicializar variables para la vista del ticket
      $data['show_payment_distribution'] = false;
      $data['is_liquidation'] = false;

      // Determinar si mostrar distribución de pagos
      if (!empty($processed_quotas) && isset($processed_quotas[0]['type'])) {
          if ($processed_quotas[0]['type'] === 'custom_priority') {
              $data['show_payment_distribution'] = true;
          }
      }

      // Determinar si es liquidación anticipada
      if (!empty($custom_payment_type) && $custom_payment_type === 'liquidation') {
          $data['is_liquidation'] = true;
      }

      // CORRECCIÓN CRÍTICA: Para pagos personalizados, asegurar que processed_quotas tenga la estructura correcta
      if ($tipo_pago === 'custom' && !empty($processed_quotas) && isset($processed_quotas['payment_breakdown'])) {
          log_message('debug', 'TICKET: CORRECCIÓN - processed_quotas viene de process_custom_payment_partial, usando payment_breakdown');
          $processed_quotas = $processed_quotas['payment_breakdown'];
          $data['processed_quotas'] = $processed_quotas;
      }

      log_message('debug', 'TICKET: ========== PROCESANDO DATOS PARA TICKET ==========');
      if (!$this->payments_m->check_cstLoan($loan_id)) {
          $this->payments_m->update_cstLoan($loan_id, $payment_data['customer_id']);
      }

      // CORRECCIÓN DEFINITIVA: Construir quotasPaid usando SOLO processed_quotas sin consultas a BD
      $data['quotasPaid'] = [];
      if (!empty($processed_quotas)) {
          log_message('debug', 'TICKET: Construyendo quotasPaid directamente desde processed_quotas (sin consultas BD)');
          log_message('debug', 'TICKET: processed_quotas contiene ' . count($processed_quotas) . ' elementos');

          foreach ($processed_quotas as $index => $processed) {
              log_message('debug', 'TICKET: Procesando processed_quota #' . $index . ': ' . json_encode($processed));

              // Obtener datos de cuota desde processed_quotas (ya contiene toda la información necesaria)
              $quota_id = isset($processed['quota_id']) ? $processed['quota_id'] : null;
              if (!$quota_id) {
                  log_message('error', 'TICKET: ERROR - processed item no tiene quota_id válido: ' . json_encode($processed));
                  continue;
              }

              // Fallback específico para pagos NO COMPLETOS: si vienen montos en 0 pero hay custom_amount, recalcular interés/capital pagado con datos de BD
              if ($tipo_pago === 'custom' && isset($processed['type']) && $processed['type'] === 'incomplete') {
                  $paid_interest_current = isset($processed['interest_paid']) ? floatval($processed['interest_paid']) : 0.0;
                  $paid_capital_current = isset($processed['capital_paid']) ? floatval($processed['capital_paid']) : 0.0;
                  $sum_paid_current = $paid_interest_current + $paid_capital_current;
                  if ($sum_paid_current == 0 && !empty($custom_amount)) {
                      $quota_info_fix = $this->payments_m->get_loan_item($quota_id);
                      if ($quota_info_fix) {
                          $interest_pending_fix = max(0, ($quota_info_fix->interest_amount ?? 0) - ($quota_info_fix->interest_paid ?? 0));
                          $capital_pending_fix = max(0, ($quota_info_fix->capital_amount ?? 0) - ($quota_info_fix->capital_paid ?? 0));
                          $amount_remaining_to_apply = floatval($custom_amount);
                          $apply_interest = min($amount_remaining_to_apply, $interest_pending_fix);
                          $amount_remaining_to_apply -= $apply_interest;
                          $apply_capital = min($amount_remaining_to_apply, $capital_pending_fix);
                          $new_total_paid = $apply_interest + $apply_capital;

                          // Actualizar processed con montos recalculados para el ticket
                          $processed_quotas[$index]['interest_paid'] = $apply_interest;
                          $processed_quotas[$index]['capital_paid'] = $apply_capital;
                          $processed_quotas[$index]['total_paid'] = $new_total_paid;
                          $processed_quotas[$index]['amount'] = $new_total_paid;

                          // Balance mostrado en ticket: saldo pendiente restante de la cuota actual
                          $remaining_after_payment = max(0, ($interest_pending_fix + $capital_pending_fix) - $new_total_paid);
                          $processed_quotas[$index]['balance'] = $remaining_after_payment;

                          // Reemplazar variable local processed para seguir usando abajo
                          $processed = $processed_quotas[$index];

                          log_message('debug', 'TICKET: FALLBACK INCOMPLETE aplicado - interest_paid: ' . $apply_interest . ', capital_paid: ' . $apply_capital . ', total_paid: ' . $new_total_paid . ', balance: ' . $remaining_after_payment);
                      }
                  }
              }

              // Usar directamente los valores de processed_quotas (que contienen los montos correctos del pago)
              $interest_paid = isset($processed['interest_paid']) ? $processed['interest_paid'] : 0;
              $capital_paid = isset($processed['capital_paid']) ? $processed['capital_paid'] : 0;
              $total_paid = isset($processed['total_paid']) ? $processed['total_paid'] : ($interest_paid + $capital_paid);

              // Obtener información de cuota desde processed_quotas (sin consultas BD)
              $num_quota = isset($processed['num_quota']) ? $processed['num_quota'] : 'N/A';
              $fee_amount = isset($processed['fee_amount']) ? $processed['fee_amount'] : 0;
              $interest_amount = isset($processed['interest_amount']) ? $processed['interest_amount'] : 0;
              $capital_amount = isset($processed['capital_amount']) ? $processed['capital_amount'] : 0;
              $balance = isset($processed['balance']) ? $processed['balance'] : 0;
              $loan_id_processed = isset($processed['loan_id']) ? $processed['loan_id'] : $loan_id;

              // Formatear fecha desde processed_quotas
              $formatted_date = 'Sin fecha';
              if (isset($processed['date']) && !empty($processed['date'])) {
                  if (is_string($processed['date'])) {
                      try {
                          $date_obj = date_create($processed['date']);
                          if ($date_obj) {
                              $formatted_date = date_format($date_obj, 'd/m/Y');
                          }
                      } catch (Exception $e) {
                          log_message('error', 'TICKET: Error formateando fecha desde processed_quotas: ' . $e->getMessage());
                      }
                  }
              }

              // Determinar status basado en processed_quotas y tipo de pago
              $status = 1; // Por defecto pendiente
              $extra_payment = 0; // Por defecto normal
              $payment_type = isset($processed['payment_type']) ? $processed['payment_type'] : 'paid';

              if ($tipo_pago === 'early_total' || $tipo_pago === 'total_condonacion') {
                  // Para condonaciones, las cuotas pueden tener diferentes estados
                  if ($payment_type === 'waived') {
                      $status = 0; // Condonada (cerrada)
                      $extra_payment = 3; // Marcar como condonada
                  } elseif ($payment_type === 'paid') {
                      $status = 0; // Pagada
                      $extra_payment = 0; // Pagada normalmente
                  }
              } elseif ($tipo_pago === 'custom') {
                  // DIAGNÓSTICO CRÍTICO: Agregar logs detallados para investigar el problema de status en pagos parciales
                  log_message('debug', 'TICKET_DIAGNOSIS: ========== DIAGNÓSTICO STATUS PAGO PERSONALIZADO ==========');
                  log_message('debug', 'TICKET_DIAGNOSIS: processed completo: ' . json_encode($processed));
                  log_message('debug', 'TICKET_DIAGNOSIS: isset(processed[payment_type]): ' . (isset($processed['payment_type']) ? 'SÍ' : 'NO'));
                  if (isset($processed['payment_type'])) {
                      log_message('debug', 'TICKET_DIAGNOSIS: processed[payment_type]: ' . $processed['payment_type']);
                  }
                  log_message('debug', 'TICKET_DIAGNOSIS: isset(processed[status_changed]): ' . (isset($processed['status_changed']) ? 'SÍ' : 'NO'));
                  if (isset($processed['status_changed'])) {
                      log_message('debug', 'TICKET_DIAGNOSIS: processed[status_changed]: ' . ($processed['status_changed'] ? 'TRUE' : 'FALSE'));
                  }

                  // Para pagos personalizados, determinar status basado en el tipo y si se completó el pago
                  if (isset($processed['payment_type'])) {
                      if ($processed['payment_type'] === 'incomplete') {
                          $status = 4; // Pago no completo
                          $status_text = 'Pago No Completo';
                          log_message('debug', 'TICKET_DIAGNOSIS: Status asignado = 4 (incomplete) por payment_type=incomplete');
                      } elseif ($processed['payment_type'] === 'partial') {
                          $status = 3; // Pago parcial
                          $status_text = 'Pago Parcial';
                          log_message('debug', 'TICKET_DIAGNOSIS: Status asignado = 3 (partial) por payment_type=partial');
                      } elseif (isset($processed['status_changed']) && $processed['status_changed']) {
                          $status = 0; // Pagada completamente
                          $status_text = 'Pagada';
                          log_message('debug', 'TICKET_DIAGNOSIS: Status asignado = 0 (pagada) por status_changed=true');
                      } else {
                          $status = 3; // Pago parcial
                          $status_text = 'Pago Parcial';
                          log_message('debug', 'TICKET_DIAGNOSIS: Status asignado = 3 (parcial) por defecto');
                      }
                  } else {
                      // Fallback para compatibilidad
                      $status = isset($processed['status_changed']) && $processed['status_changed'] ? 0 : 3;
                      $status_text = ($status == 0) ? 'Pagada' : 'Pago Parcial';
                      log_message('debug', 'TICKET_DIAGNOSIS: Status asignado por fallback: ' . $status . ' (' . $status_text . ')');
                  }
                  log_message('debug', 'TICKET_DIAGNOSIS: ========== FIN DIAGNÓSTICO ==========');
                  log_message('debug', 'TICKET: CORRECCIÓN - Status para pago personalizado: ' . $status . ' (' . $status_text . ') - payment_type: ' . ($processed['payment_type'] ?? 'N/A'));
              } elseif ($tipo_pago === 'interest' || $tipo_pago === 'capital' || $tipo_pago === 'both') {
                  // Para pagos parciales (solo interés, solo capital, o ambos), marcar como "Pago no completo" (status=3)
                  $status = 3; // Pago no completo
                  log_message('debug', 'TICKET: CORRECCIÓN - Status para pago parcial (' . $tipo_pago . '): ' . $status . ' (Pago no completo)');
              } else {
                  // Para otros tipos de pago, asumir pagada si hay montos aplicados
                  if ($total_paid > 0) {
                      $status = 0; // Pagada
                  }
              }

              $data['quotasPaid'][] = [
                  'id' => $quota_id,
                  'loan_id' => $loan_id_processed,
                  'date' => $formatted_date,
                  'num_quota' => $num_quota,
                  'fee_amount' => $fee_amount,
                  'interest_amount' => $interest_amount,
                  'capital_amount' => $capital_amount,
                  'balance' => $balance,
                  'status' => $status,
                  'interest_paid' => $interest_paid,
                  'capital_paid' => $capital_paid,
                  'pay_date' => date('Y-m-d H:i:s'),
                  'extra_payment' => $extra_payment,
                  'payment_type' => $payment_type
              ];

              log_message('debug', 'TICKET: Cuota agregada a quotasPaid desde processed_quotas - ID: ' . $quota_id . ', num_quota: ' . $num_quota . ', status: ' . $status . ', interest_paid: ' . $interest_paid . ', capital_paid: ' . $capital_paid . ', payment_type: ' . $payment_type);
          }

          log_message('debug', 'TICKET: quotasPaid construido con ' . count($data['quotasPaid']) . ' cuotas directamente desde processed_quotas');
      } else {
          log_message('debug', 'TICKET: processed_quotas vacío, quotasPaid permanecerá vacío');
      }

      // PARA PAGOS CON CONDONACIÓN: Usar processed_quotas directamente (ya incluye cuotas pagadas y condonadas)
      if ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total') {
           log_message('debug', 'TICKET: Procesando pago con condonación usando processed_quotas directamente - tipo_pago: ' . $tipo_pago);

           // processed_quotas ya contiene toda la información necesaria, no necesitamos consultas adicionales
           // La lógica anterior ya maneja esto en el bloque general de construcción de quotasPaid

           // Agregar información adicional para el ticket de condonación desde processed_quotas
           if (!empty($processed_quotas) && isset($processed_quotas[0])) {
               $data['waiver_info'] = [
                   'customer_payment' => $processed_quotas[0]['amount'] ?? 0,
                   'total_waived' => $processed_quotas[0]['waived_amount'] ?? 0,
                   'capital_waived' => $processed_quotas[0]['capital_waived'] ?? 0,
                   'interest_waived' => $processed_quotas[0]['interest_waived'] ?? 0,
                   'quotas_waived' => $processed_quotas[0]['quotas_waived'] ?? 0,
                   'selected_quota_num' => $processed_quotas[0]['selected_quota_num'] ?? 0
               ];
               log_message('debug', 'TICKET: Información de condonación agregada desde processed_quotas: ' . json_encode($data['waiver_info']));
           }
       }

      // LOG ADICIONAL: Comparar IDs solicitados vs IDs retornados
      $returned_ids = array_column($data['quotasPaid'], 'id');
      log_message('debug', 'TICKET: IDs solicitados (originales): ' . json_encode($payment_data['quota_ids']));
      log_message('debug', 'TICKET: IDs retornados desde processed_quotas: ' . json_encode($returned_ids));

      // LOG DIAGNÓSTICO DETALLADO: Verificar contenido de quotasPaid antes del cálculo
      log_message('debug', 'TICKET: ========== DIAGNÓSTICO FINAL DE QUOTASPAD ==========');
      log_message('debug', 'TICKET: DIAGNÓSTICO - quotasPaid es array: ' . (is_array($data['quotasPaid']) ? 'SÍ' : 'NO'));
      log_message('debug', 'TICKET: DIAGNÓSTICO - count(quotasPaid): ' . count($data['quotasPaid']));
      if (!empty($data['quotasPaid'])) {
          log_message('debug', 'TICKET: DIAGNÓSTICO - Primera cuota completa: ' . json_encode($data['quotasPaid'][0]));
          $fee_amounts = array_column($data['quotasPaid'], 'fee_amount');
          log_message('debug', 'TICKET: DIAGNÓSTICO - fee_amounts extraídos: ' . json_encode($fee_amounts));
          $total_fee_amounts = array_sum($fee_amounts);
          log_message('debug', 'TICKET: DIAGNÓSTICO - suma total de fee_amounts: ' . $total_fee_amounts);

          // Verificar si hay cuotas con status incorrecto
          $status_counts = array_count_values(array_column($data['quotasPaid'], 'status'));
          log_message('debug', 'TICKET: DIAGNÓSTICO - distribución de status en quotasPaid: ' . json_encode($status_counts));

          // Verificar balances
          $balances = array_column($data['quotasPaid'], 'balance');
          log_message('debug', 'TICKET: DIAGNÓSTICO - balances de cuotas: ' . json_encode($balances));

          // Verificar pagos realizados
          $interest_paids = array_column($data['quotasPaid'], 'interest_paid');
          $capital_paids = array_column($data['quotasPaid'], 'capital_paid');
          log_message('debug', 'TICKET: DIAGNÓSTICO - interest_paid por cuota: ' . json_encode($interest_paids));
          log_message('debug', 'TICKET: DIAGNÓSTICO - capital_paid por cuota: ' . json_encode($capital_paids));
      } else {
          log_message('error', 'TICKET: DIAGNÓSTICO - quotasPaid está VACÍO después del procesamiento');
      }

      log_message('debug', 'TICKET: ========== FIN PREPARE_TICKET_DATA ==========');

      // Obtener datos adicionales para el resumen del ticket (sin consultas relacionadas con cuotas)
      $customer_id = $payment_data['customer_id'];
      $customer = $this->customers_m->get($customer_id);
      $data['dni'] = $customer ? $customer->dni : '';

      $user = $this->user_m->get_user($user_id);
      $data['user_name'] = $user ? ($user->first_name . ' ' . $user->last_name) : '';

      $coin_id = $payment_data['data']['coin'];
      $coin = $this->coins_m->get($coin_id);
      $data['coin_name'] = $coin ? $coin->name : '';

      return $data;
  }

  /**
   * Calcula el total_amount para el ticket usando SOLO processed_quotas
   */
  private function calculate_ticket_total_amount($payment_data, $processed_quotas, $data)
  {
      $tipo_pago = $payment_data['tipo_pago'];
      $custom_amount = $payment_data['custom_amount'];
      $amount = $payment_data['amount'];

      log_message('debug', 'TICKET: ========== CÁLCULO DE TOTAL_AMOUNT ==========');

      // MANEJO ESPECÍFICO PARA PAGO TOTAL CON CONDONACIÓN Y PAGO TOTAL ANTICIPADO
      if ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total') {
          // Para total_condonacion y early_total, el monto total debe ser la suma de pago cliente + condonado
          if (!empty($processed_quotas) && isset($processed_quotas[0])) {
              $customer_payment = $processed_quotas[0]['amount'] ?? 0;
              $waived_amount = $processed_quotas[0]['waived_amount'] ?? 0;
              $total_amount = $customer_payment + $waived_amount;
              log_message('debug', 'TICKET: Total_amount para ' . $tipo_pago . ' calculado como suma de pago cliente + condonado: ' . $customer_payment . ' + ' . $waived_amount . ' = ' . $total_amount);
          } else {
              log_message('error', 'TICKET: ERROR - No hay processed_quotas para calcular total_amount en ' . $tipo_pago);
              $total_amount = 0;
          }
      }
      // CORRECCIÓN: Para pagos personalizados, usar el monto personalizado original
      elseif ($tipo_pago === 'custom' && !empty($custom_amount)) {
          $total_amount = $custom_amount;
          log_message('debug', 'TICKET: CORRECCIÓN - Pago personalizado usa custom_amount: ' . $custom_amount);
      }
      // Lógica unificada para otros tipos de pago: calcular total_amount basado en processed_quotas
      elseif (!empty($processed_quotas)) {
          // Suma de los montos realmente pagados desde processed_quotas
          $total_amount = array_sum(array_map(function($quota) {
              return ($quota['interest_paid'] ?? 0) + ($quota['capital_paid'] ?? 0);
          }, $processed_quotas));
          log_message('debug', 'TICKET: Total_amount calculado desde processed_quotas: ' . $total_amount . ' (interest_paid + capital_paid)');
      } else {
          // Último fallback: usar el monto del pago original
          $total_amount = $amount;
          log_message('debug', 'TICKET: Último fallback - usando monto del pago original: ' . $amount);
      }

      log_message('debug', 'TICKET: CÁLCULO - data[total_amount] final: ' . $total_amount);

      // CORRECCIÓN: Si total_amount es 0, usar el monto del pago original como fallback
      if ($total_amount == 0) {
          $total_amount = $amount;
          log_message('debug', 'TICKET: CORRECCIÓN - total_amount era 0, usando monto del pago original: ' . $amount);
      }

      return $total_amount;
  }

  /**
   * Verifica si el préstamo debe cerrarse
   * CORRECCIÓN: El préstamo se cierra solo si NO hay cuotas pendientes (status=1) NI parcialmente pagadas (status=3)
   */
  private function check_and_close_loan($loan_id, $customer_id)
  {
      // Contar cuotas que aún requieren pago: pendientes (status=1), parcialmente pagadas (status=3) o no completas (status=4)
      $unpaid_quotas = $this->db->where('loan_id', $loan_id)
                               ->where_in('status', [1, 3, 4]) // 1: pendiente, 3: parcial, 4: no completo
                               ->count_all_results('loan_items');

      log_message('debug', 'TICKET: Verificando cierre de préstamo - loan_id: ' . $loan_id . ', cuotas sin pagar completamente: ' . $unpaid_quotas . ', customer_id: ' . $customer_id);

      if ($unpaid_quotas == 0) {
          log_message('info', 'TICKET: Cerrando préstamo automáticamente - todas las cuotas completamente pagadas - loan_id: ' . $loan_id . ', customer_id: ' . $customer_id);
          $this->loans_m->update_loan($loan_id, ['status' => 0]);
          $this->customers_m->save(['loan_status' => 0], $customer_id);
          log_message('info', 'TICKET: Préstamo cerrado exitosamente - loan_id: ' . $loan_id . ', customer_id: ' . $customer_id);
      } else {
          log_message('debug', 'TICKET: Préstamo mantiene cuotas sin pagar completamente - loan_id: ' . $loan_id . ', cuotas restantes: ' . $unpaid_quotas);
      }
  }


   /**
    * Procesa pago completo de cuotas seleccionadas
    */
   private function process_full_payment($quota_ids, $user_id) {
     log_message('info', 'FULL_PAYMENT: Iniciando procesamiento de pago completo - cuotas: ' . count($quota_ids) . ', user_id: ' . $user_id);
     $processed = [];
     if (is_array($quota_ids) && !empty($quota_ids)) {
       foreach ($quota_ids as $quota_id) {
         $quota_info = $this->payments_m->get_loan_item($quota_id);
         if ($quota_info && $quota_info->status == 1) {
           log_message('debug', 'FULL_PAYMENT: Procesando cuota ID ' . $quota_id . ' - balance: ' . $quota_info->balance . ', fee_amount: ' . $quota_info->fee_amount);

           // Marcar cuota como pagada completamente
           $update_result = $this->payments_m->update_quota([
             'status' => 0,
             'paid_by' => $user_id,
             'pay_date' => date('Y-m-d H:i:s'),
             'capital_paid' => $quota_info->capital_amount,
             'interest_paid' => $quota_info->interest_amount,
             'balance' => 0
           ], $quota_id);

           if ($update_result) {
             log_message('info', 'FULL_PAYMENT: Cuota ID ' . $quota_id . ' pagada completamente - capital: ' . $quota_info->capital_amount . ', interés: ' . $quota_info->interest_amount);
           } else {
             log_message('error', 'FULL_PAYMENT: ERROR al actualizar cuota ID ' . $quota_id . ' - user_id: ' . $user_id);
           }

           // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
           $processed[] = [
             'quota_id' => $quota_id,
             'loan_id' => $quota_info->loan_id,
             'amount' => $quota_info->fee_amount,
             'type' => 'full',
             'interest_paid' => $quota_info->interest_amount,
             'capital_paid' => $quota_info->capital_amount,
             'total_paid' => $quota_info->fee_amount,
             'num_quota' => $quota_info->num_quota,
             'fee_amount' => $quota_info->fee_amount,
             'interest_amount' => $quota_info->interest_amount,
             'capital_amount' => $quota_info->capital_amount,
             'balance' => 0, // Después del pago completo
             'date' => $quota_info->date,
             'status' => 0, // Pagada
             'extra_payment' => 0,
             'payment_type' => 'paid'
           ];
         } else {
           log_message('debug', 'FULL_PAYMENT: Cuota ID ' . $quota_id . ' no válida o ya pagada - status: ' . ($quota_info ? $quota_info->status : 'N/A'));
         }
       }
     }
     log_message('info', 'FULL_PAYMENT: Procesamiento completado - cuotas procesadas: ' . count($processed));
     return $processed;
   }

   /**
    * Procesa pago solo de intereses con monto específico
    */
   private function process_interest_only_payment($quota_ids, $user_id, $payment_amount) {
     log_message('info', 'INTEREST_PAYMENT: Iniciando procesamiento de pago solo interés - cuotas: ' . count($quota_ids) . ', user_id: ' . $user_id . ', monto: ' . $payment_amount);
     $processed = [];
     $remaining_amount = $payment_amount;
     $is_partial = false;

     if (is_array($quota_ids) && !empty($quota_ids)) {
       foreach ($quota_ids as $quota_id) {
         if ($remaining_amount <= 0) break;

         $quota_info = $this->payments_m->get_loan_item($quota_id);
         if ($quota_info && $quota_info->status == 1) {
           $interest_pending = $quota_info->interest_amount - ($quota_info->interest_paid ?? 0);
           $capital_pending = $quota_info->capital_amount - ($quota_info->capital_paid ?? 0);

           log_message('debug', 'INTEREST_PAYMENT: Cuota ID ' . $quota_id . ' - interés pendiente: ' . $interest_pending . ', capital pendiente: ' . $capital_pending . ', monto restante: ' . $remaining_amount);

           $interest_to_pay = 0;
           $capital_to_pay = 0;

           // Primero aplicar a intereses pendientes
           if ($interest_pending > 0 && $remaining_amount > 0) {
             $interest_to_pay = min($remaining_amount, $interest_pending);
             $remaining_amount -= $interest_to_pay;
             log_message('debug', 'INTEREST_PAYMENT: Aplicado a interés: ' . $interest_to_pay . ', restante: ' . $remaining_amount);
           }

           // Si queda monto y no hay más intereses pendientes, aplicar a capital
           if ($remaining_amount > 0 && $capital_pending > 0) {
             $capital_to_pay = min($remaining_amount, $capital_pending);
             $remaining_amount -= $capital_to_pay;
             log_message('debug', 'INTEREST_PAYMENT: Aplicado a capital: ' . $capital_to_pay . ', restante: ' . $remaining_amount);
           }

           $total_paid_on_quota = $interest_to_pay + $capital_to_pay;

           if ($total_paid_on_quota > 0) {
             // Preparar datos de actualización
             $update_data = [
               'paid_by' => $user_id,
               'pay_date' => date('Y-m-d H:i:s')
             ];

             if ($interest_to_pay > 0) {
               $update_data['interest_paid'] = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
             }

             if ($capital_to_pay > 0) {
               $update_data['capital_paid'] = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
               $update_data['balance'] = $quota_info->balance - $capital_to_pay;
             }

             // Verificar si la cuota queda completamente pagada
             $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
             $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;

             if ($new_interest_paid >= $quota_info->interest_amount && $new_capital_paid >= $quota_info->capital_amount) {
               $update_data['status'] = 0; // Marcar como pagada
               $update_data['balance'] = 0; // Asegurar balance = 0
               log_message('debug', 'INTEREST_PAYMENT: Cuota ID ' . $quota_id . ' queda completamente pagada');
             }

             $update_result = $this->payments_m->update_quota($update_data, $quota_id);

             if ($update_result) {
               log_message('info', 'INTEREST_PAYMENT: Pago aplicado en cuota ID ' . $quota_id . ' - interés: ' . $interest_to_pay . ', capital: ' . $capital_to_pay);
             } else {
               log_message('error', 'INTEREST_PAYMENT: ERROR al actualizar cuota ID ' . $quota_id . ' - user_id: ' . $user_id);
             }

             // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
             $processed[] = [
               'quota_id' => $quota_id,
               'loan_id' => $quota_info->loan_id,
               'amount' => $total_paid_on_quota,
               'interest_paid' => $interest_to_pay,
               'capital_paid' => $capital_to_pay,
               'total_paid' => $total_paid_on_quota,
               'type' => 'interest',
               'num_quota' => $quota_info->num_quota,
               'fee_amount' => $quota_info->fee_amount,
               'interest_amount' => $quota_info->interest_amount,
               'capital_amount' => $quota_info->capital_amount,
               'balance' => $update_data['balance'] ?? $quota_info->balance,
               'date' => $quota_info->date,
               'status' => $update_data['status'] ?? $quota_info->status,
               'extra_payment' => $quota_info->extra_payment ?? 0,
               'payment_type' => 'paid'
             ];
           } else {
             log_message('debug', 'INTEREST_PAYMENT: Cuota ID ' . $quota_id . ' no tiene montos pendientes aplicables');
           }
         } else {
           log_message('debug', 'INTEREST_PAYMENT: Cuota ID ' . $quota_id . ' no válida o ya pagada');
         }
       }
     }

     // Determinar si el pago fue parcial
     if ($remaining_amount > 0) {
       $is_partial = true;
     }

     // Llamar al método auxiliar para manejar saldo restante
     $this->handle_remaining_balance($quota_info->loan_id ?? null, $remaining_amount, $user_id, $is_partial, $quota_ids);

     log_message('info', 'INTEREST_PAYMENT: Procesamiento completado - cuotas procesadas: ' . count($processed) . ', monto total aplicado: ' . ($payment_amount - $remaining_amount));
     return $processed;
   }

   /**
    * Procesa pago solo de capital con validación de monto y manejo de excedente a intereses
    */
   private function process_capital_only_payment($quota_ids, $user_id, $payment_amount) {
     log_message('info', 'CAPITAL_PAYMENT: Iniciando procesamiento de pago solo capital - cuotas: ' . count($quota_ids) . ', user_id: ' . $user_id . ', monto: ' . $payment_amount);
     $processed = [];
     $remaining_amount = $payment_amount;
     $is_partial = false;

     if (is_array($quota_ids) && !empty($quota_ids)) {
       foreach ($quota_ids as $quota_id) {
         if ($remaining_amount <= 0) break;

         $quota_info = $this->payments_m->get_loan_item($quota_id);
         if ($quota_info && $quota_info->status == 1) {
           $capital_pending = $quota_info->capital_amount - ($quota_info->capital_paid ?? 0);
           $interest_pending = $quota_info->interest_amount - ($quota_info->interest_paid ?? 0);

           log_message('debug', 'CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' - capital pendiente: ' . $capital_pending . ', interés pendiente: ' . $interest_pending . ', monto restante: ' . $remaining_amount);

           $capital_to_pay = 0;
           $interest_to_pay = 0;

           // Primero aplicar a capital pendiente
           if ($capital_pending > 0 && $remaining_amount > 0) {
             $capital_to_pay = min($remaining_amount, $capital_pending);
             $remaining_amount -= $capital_to_pay;
             log_message('debug', 'CAPITAL_PAYMENT: Aplicado a capital: ' . $capital_to_pay . ', restante: ' . $remaining_amount);
           }

           // Si queda monto y no hay más capital pendiente, aplicar a intereses pendientes
           if ($remaining_amount > 0 && $interest_pending > 0) {
             $interest_to_pay = min($remaining_amount, $interest_pending);
             $remaining_amount -= $interest_to_pay;
             log_message('debug', 'CAPITAL_PAYMENT: Aplicado a interés: ' . $interest_to_pay . ', restante: ' . $remaining_amount);
           }

           $total_paid_on_quota = $capital_to_pay + $interest_to_pay;

           if ($total_paid_on_quota > 0) {
             // Preparar datos de actualización
             $update_data = [
               'paid_by' => $user_id,
               'pay_date' => date('Y-m-d H:i:s')
             ];

             if ($capital_to_pay > 0) {
               $update_data['capital_paid'] = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
               $update_data['balance'] = $quota_info->balance - $capital_to_pay;
             }

             if ($interest_to_pay > 0) {
               $update_data['interest_paid'] = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
             }

             // Verificar si la cuota queda completamente pagada
             $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
             $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;

             if ($new_capital_paid >= $quota_info->capital_amount && $new_interest_paid >= $quota_info->interest_amount) {
               $update_data['status'] = 0; // Marcar como pagada
               $update_data['balance'] = 0; // Asegurar balance = 0
               log_message('debug', 'CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' queda completamente pagada');
             }

             $update_result = $this->payments_m->update_quota($update_data, $quota_id);

             if ($update_result) {
               log_message('info', 'CAPITAL_PAYMENT: Pago aplicado en cuota ID ' . $quota_id . ' - capital: ' . $capital_to_pay . ', interés: ' . $interest_to_pay);
             } else {
               log_message('error', 'CAPITAL_PAYMENT: ERROR al actualizar cuota ID ' . $quota_id . ' - user_id: ' . $user_id);
             }

             // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
             $processed[] = [
               'quota_id' => $quota_id,
               'loan_id' => $quota_info->loan_id,
               'amount' => $total_paid_on_quota,
               'capital_paid' => $capital_to_pay,
               'interest_paid' => $interest_to_pay,
               'total_paid' => $total_paid_on_quota,
               'type' => 'capital',
               'num_quota' => $quota_info->num_quota,
               'fee_amount' => $quota_info->fee_amount,
               'interest_amount' => $quota_info->interest_amount,
               'capital_amount' => $quota_info->capital_amount,
               'balance' => $update_data['balance'] ?? $quota_info->balance,
               'date' => $quota_info->date,
               'status' => $update_data['status'] ?? $quota_info->status,
               'extra_payment' => $quota_info->extra_payment ?? 0,
               'payment_type' => 'paid'
             ];
           } else {
             log_message('debug', 'CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' no tiene montos pendientes aplicables');
           }
         } else {
           log_message('debug', 'CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' no válida o ya pagada');
         }
       }
     }

     // Determinar si el pago fue parcial
     if ($remaining_amount > 0) {
       $is_partial = true;
     }

     // Llamar al método auxiliar para manejar saldo restante
     $this->handle_remaining_balance($quota_info->loan_id ?? null, $remaining_amount, $user_id, $is_partial, $quota_ids);

     log_message('info', 'CAPITAL_PAYMENT: Procesamiento completado - cuotas procesadas: ' . count($processed) . ', monto total aplicado: ' . ($payment_amount - $remaining_amount));
     return $processed;
   }

   /**
    * Procesa pago de interés y capital proporcionalmente
    */
   private function process_interest_capital_payment($quota_ids, $user_id, $payment_amount) {
     log_message('info', 'INTEREST_CAPITAL_PAYMENT: Iniciando procesamiento de pago interés y capital proporcional - cuotas: ' . count($quota_ids) . ', user_id: ' . $user_id . ', monto: ' . $payment_amount);
     $processed = [];
     $remaining_amount = $payment_amount;
     $is_partial = false;

     if (is_array($quota_ids) && !empty($quota_ids)) {
       foreach ($quota_ids as $quota_id) {
         if ($remaining_amount <= 0) break;

         $quota_info = $this->payments_m->get_loan_item($quota_id);
         if ($quota_info && $quota_info->status == 1) {
           $interest_pending = $quota_info->interest_amount - ($quota_info->interest_paid ?? 0);
           $capital_pending = $quota_info->capital_amount - ($quota_info->capital_paid ?? 0);
           $total_pending = $interest_pending + $capital_pending;

           log_message('debug', 'INTEREST_CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' - interés pendiente: ' . $interest_pending . ', capital pendiente: ' . $capital_pending . ', total pendiente: ' . $total_pending . ', monto restante: ' . $remaining_amount);

           $interest_to_pay = 0;
           $capital_to_pay = 0;

           // Si hay montos pendientes, distribuir proporcionalmente
           if ($total_pending > 0) {
             // Calcular proporciones
             $interest_ratio = $interest_pending / $total_pending;
             $capital_ratio = $capital_pending / $total_pending;

             // Aplicar pago proporcional
             $payment_for_quota = min($remaining_amount, $total_pending);
             $interest_to_pay = round($payment_for_quota * $interest_ratio, 2);
             $capital_to_pay = round($payment_for_quota * $capital_ratio, 2);

             // Ajustar por redondeo para evitar exceder el monto disponible
             if ($interest_to_pay + $capital_to_pay > $remaining_amount) {
               if ($interest_to_pay > $remaining_amount) {
                 $interest_to_pay = $remaining_amount;
                 $capital_to_pay = 0;
               } else {
                 $capital_to_pay = $remaining_amount - $interest_to_pay;
               }
             }

             $remaining_amount -= ($interest_to_pay + $capital_to_pay);
           }

           $total_paid_on_quota = $interest_to_pay + $capital_to_pay;

           if ($total_paid_on_quota > 0) {
             // Preparar datos de actualización
             $update_data = [
               'paid_by' => $user_id,
               'pay_date' => date('Y-m-d H:i:s')
             ];

             if ($interest_to_pay > 0) {
               $update_data['interest_paid'] = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
             }

             if ($capital_to_pay > 0) {
               $update_data['capital_paid'] = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
               $update_data['balance'] = $quota_info->balance - $capital_to_pay;
             }

             // Verificar si la cuota queda completamente pagada
             $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
             $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;

             if ($new_interest_paid >= $quota_info->interest_amount && $new_capital_paid >= $quota_info->capital_amount) {
               $update_data['status'] = 0; // Marcar como pagada
               $update_data['balance'] = 0; // Asegurar balance = 0
               log_message('debug', 'INTEREST_CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' queda completamente pagada');
             }

             $update_result = $this->payments_m->update_quota($update_data, $quota_id);

             if ($update_result) {
               log_message('info', 'INTEREST_CAPITAL_PAYMENT: Pago aplicado en cuota ID ' . $quota_id . ' - interés: ' . $interest_to_pay . ', capital: ' . $capital_to_pay);
             } else {
               log_message('error', 'INTEREST_CAPITAL_PAYMENT: ERROR al actualizar cuota ID ' . $quota_id . ' - user_id: ' . $user_id);
             }

             // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
             $processed[] = [
               'quota_id' => $quota_id,
               'loan_id' => $quota_info->loan_id,
               'amount' => $total_paid_on_quota,
               'interest_paid' => $interest_to_pay,
               'capital_paid' => $capital_to_pay,
               'total_paid' => $total_paid_on_quota,
               'type' => 'both',
               'num_quota' => $quota_info->num_quota,
               'fee_amount' => $quota_info->fee_amount,
               'interest_amount' => $quota_info->interest_amount,
               'capital_amount' => $quota_info->capital_amount,
               'balance' => $update_data['balance'] ?? $quota_info->balance,
               'date' => $quota_info->date,
               'status' => $update_data['status'] ?? $quota_info->status,
               'extra_payment' => $quota_info->extra_payment ?? 0,
               'payment_type' => 'paid'
             ];
           } else {
             log_message('debug', 'INTEREST_CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' no tiene montos pendientes aplicables');
           }
         } else {
           log_message('debug', 'INTEREST_CAPITAL_PAYMENT: Cuota ID ' . $quota_id . ' no válida o ya pagada');
         }
       }
     }

     // Determinar si el pago fue parcial
     if ($remaining_amount > 0) {
       $is_partial = true;
     }

     // Llamar al método auxiliar para manejar saldo restante
     $this->handle_remaining_balance($quota_info->loan_id ?? null, $remaining_amount, $user_id, $is_partial, $quota_ids);

     log_message('info', 'INTEREST_CAPITAL_PAYMENT: Procesamiento completado - cuotas procesadas: ' . count($processed) . ', monto total aplicado: ' . ($payment_amount - $remaining_amount));
     return $processed;
   }

   /**
    * Procesa PAGO TOTAL: Cancela completamente las cuotas desde la primera hasta la seleccionada
    */
   private function process_total_payment($quota_ids, $user_id) {
     $processed = [];
     log_message('info', 'TOTAL_PAYMENT: ========== INICIANDO PAGO TOTAL ========== - user_id: ' . $user_id);

     // Validar que se seleccionaron cuotas (solo una para pago total)
     if (empty($quota_ids) || !is_array($quota_ids) || count($quota_ids) !== 1) {
       log_message('error', 'TOTAL_PAYMENT: ERROR - Se debe seleccionar exactamente una cuota para pago total');
       return $processed;
     }

     // Obtener información de la cuota seleccionada
     $selected_quota_id = $quota_ids[0];
     $selected_quota = $this->payments_m->get_loan_item($selected_quota_id);
     if (!$selected_quota) {
       log_message('error', 'TOTAL_PAYMENT: ERROR - Cuota seleccionada no encontrada: ' . $selected_quota_id);
       return $processed;
     }

     $loan_id = $selected_quota->loan_id;
     $selected_num_quota = $selected_quota->num_quota;

     log_message('info', 'TOTAL_PAYMENT: Procesando pago total para préstamo ID: ' . $loan_id . ' - cuota seleccionada: #' . $selected_num_quota . ' - pagando cuotas del 1 al ' . $selected_num_quota);

     // Obtener cuotas pendientes desde la primera hasta la seleccionada (num_quota <= selected_num_quota)
     $this->db->select('*');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->where('status', 1); // Solo cuotas pendientes
     $this->db->where('num_quota <=', $selected_num_quota); // Solo hasta la cuota seleccionada
     $this->db->order_by('num_quota', 'ASC');
     $pending_quotas = $this->db->get()->result();

     log_message('info', 'TOTAL_PAYMENT: Cuotas pendientes encontradas hasta la seleccionada: ' . count($pending_quotas));
     log_message('debug', 'TOTAL_PAYMENT: IDs de cuotas pendientes: ' . json_encode(array_column($pending_quotas, 'id')));

     $total_payment_amount = 0;
     $total_interest_pending = 0;
     $total_capital_pending = 0;
     $processed_quota_ids = [];

     foreach ($pending_quotas as $quota) {
       log_message('debug', 'TOTAL_PAYMENT: Procesando cuota ID: ' . $quota->id . ' - num_quota: ' . $quota->num_quota);

       // Calcular saldo pendiente de esta cuota
       $interest_pending = $quota->interest_amount - ($quota->interest_paid ?? 0);
       $capital_pending = $quota->capital_amount - ($quota->capital_paid ?? 0);
       $quota_total = $interest_pending + $capital_pending;

       log_message('debug', 'TOTAL_PAYMENT: CÁLCULOS DE SALDO - Cuota #' . $quota->num_quota . ':');
       log_message('debug', 'TOTAL_PAYMENT:   - interest_amount: ' . $quota->interest_amount . ', interest_paid: ' . ($quota->interest_paid ?? 0) . ', interest_pending: ' . $interest_pending);
       log_message('debug', 'TOTAL_PAYMENT:   - capital_amount: ' . $quota->capital_amount . ', capital_paid: ' . ($quota->capital_paid ?? 0) . ', capital_pending: ' . $capital_pending);
       log_message('debug', 'TOTAL_PAYMENT:   - quota_total (interest + capital pending): ' . $quota_total);

       if ($quota_total <= 0) {
         log_message('debug', 'TOTAL_PAYMENT: Cuota ID ' . $quota->id . ' ya está completamente pagada - saltando');
         continue;
       }

       log_message('debug', 'TOTAL_PAYMENT: Cuota ID ' . $quota->id . ' VÁLIDA PARA PROCESAR - actualizando en BD');

       // Actualizar cuota como completamente pagada
       $update_data = [
         'status' => 0, // Pagada
         'capital_paid' => ($quota->capital_paid ?? 0) + $capital_pending, // Agregar capital pendiente
         'interest_paid' => ($quota->interest_paid ?? 0) + $interest_pending, // Agregar interés pendiente
         'balance' => 0, // Saldo a cero
         'paid_by' => $user_id,
         'pay_date' => date('Y-m-d H:i:s')
       ];

       log_message('debug', 'TOTAL_PAYMENT: Datos de actualización para cuota ID ' . $quota->id . ': ' . json_encode($update_data));

       $update_result = $this->payments_m->update_quota($update_data, $quota->id);

       if (!$update_result) {
         log_message('error', 'TOTAL_PAYMENT: ERROR - Falló actualización de cuota ID: ' . $quota->id);
         log_message('debug', 'TOTAL_PAYMENT: ACTUALIZACIÓN FALLIDA - cuota ID ' . $quota->id . ' NO PROCESADA');
         continue;
       }

       log_message('debug', 'TOTAL_PAYMENT: Cuota ID ' . $quota->id . ' actualizada exitosamente en BD');

       $processed_quota_ids[] = $quota->id;
       $total_payment_amount += $quota_total;
       $total_interest_pending += $interest_pending;
       $total_capital_pending += $capital_pending;

       // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
       $processed_item = [
         'quota_id' => $quota->id,
         'loan_id' => $quota->loan_id,
         'amount' => $quota_total,
         'type' => 'total',
         'interest_paid' => $interest_pending,
         'capital_paid' => $capital_pending,
         'total_paid' => $quota_total,
         'num_quota' => $quota->num_quota,
         'fee_amount' => $quota->fee_amount,
         'interest_amount' => $quota->interest_amount,
         'capital_amount' => $quota->capital_amount,
         'balance' => 0, // Después del pago total
         'date' => $quota->date,
         'status' => 0, // Pagada
         'extra_payment' => $quota->extra_payment ?? 0,
         'payment_type' => 'paid'
       ];

       $processed[] = $processed_item;

       log_message('info', 'TOTAL_PAYMENT: Cuota ID ' . $quota->id . ' (#' . $quota->num_quota . ') AGREGADA AL ARRAY PROCESSED - monto: ' . $quota_total);
       log_message('debug', 'TOTAL_PAYMENT: Item agregado a processed: ' . json_encode($processed_item));
     }

     log_message('debug', 'TOTAL_PAYMENT: Verificación final - processed array: ' . json_encode($processed));
     log_message('debug', 'TOTAL_PAYMENT: Total cuotas procesadas: ' . count($processed));
     log_message('debug', 'TOTAL_PAYMENT: Monto total pagado: ' . $total_payment_amount);
     log_message('debug', 'TOTAL_PAYMENT: Total interés pagado: ' . $total_interest_pending);
     log_message('debug', 'TOTAL_PAYMENT: Total capital pagado: ' . $total_capital_pending);

     if (empty($processed)) {
       log_message('error', 'TOTAL_PAYMENT: ERROR - No se pudo procesar ninguna cuota - ARRAY PROCESSED VACÍO');
       log_message('debug', 'TOTAL_PAYMENT: Causas posibles: todas las cuotas ya están pagadas o no hay cuotas pendientes');
       return $processed;
     }

     // Registrar el pago total en la tabla de pagos
     $payment_data = [
       'loan_id' => $loan_id,
       'loan_item_id' => $processed[0]['quota_id'], // ID de la primera cuota procesada
       'amount' => $total_payment_amount,
       'tipo_pago' => 'total',
       'monto_pagado' => $total_payment_amount,
       'interest_paid' => $total_interest_pending,
       'capital_paid' => $total_capital_pending,
       'payment_date' => date('Y-m-d H:i:s'),
       'payment_user_id' => $user_id,
       'method' => 'efectivo',
       'notes' => 'Pago total - Cancelación completa de todas las cuotas pendientes del préstamo'
     ];

     // Agregar campos custom_amount y custom_payment_type solo si existen en la tabla
     if ($this->db->field_exists('custom_amount', 'payments')) {
       $payment_data['custom_amount'] = null;
     }
     if ($this->db->field_exists('custom_payment_type', 'payments')) {
       $payment_data['custom_payment_type'] = null;
     }

     log_message('info', 'TOTAL_PAYMENT: Registrando pago total en BD - loan_id: ' . $loan_id . ', monto_total: ' . $total_payment_amount . ', user_id: ' . $user_id);
     log_message('debug', 'TOTAL_PAYMENT: Datos del pago a insertar: ' . json_encode($payment_data));

     $insert_result = $this->db->insert('payments', $payment_data);
     $payment_id = $this->db->insert_id();

     if (!$insert_result || !$payment_id) {
       log_message('error', 'TOTAL_PAYMENT: ERROR - Falló registro de pago total para loan_id: ' . $loan_id);
       log_message('debug', 'TOTAL_PAYMENT: insert_result: ' . ($insert_result ? 'true' : 'false') . ', payment_id: ' . $payment_id);
     } else {
       log_message('info', 'TOTAL_PAYMENT: Pago total registrado exitosamente - ID: ' . $payment_id . ', loan_id: ' . $loan_id . ', monto pagado: ' . $total_payment_amount);
     }

     // Verificar si el préstamo debe cerrarse (solo si no quedan cuotas pendientes)
     log_message('debug', 'TOTAL_PAYMENT: Verificando cierre de préstamo - loan_id: ' . $loan_id);
     $this->check_and_close_loan($loan_id, $this->input->post('customer_id'));

     log_message('info', 'TOTAL_PAYMENT: ========== PROCESAMIENTO COMPLETADO ========== - cuotas_procesadas: ' . count($processed) . ', monto_total: ' . $total_payment_amount);
     log_message('debug', 'TOTAL_PAYMENT: Array processed final: ' . json_encode($processed));

     return $processed;
   }

   /**
    * Procesa pagos personalizados NO COMPLETOS
    * Mantener solo el monto no pagado en la cuota actual, sin agregar intereses adicionales ni generar nuevas cuotas
    */
   private function process_custom_payment_incomplete($loan_id, $quota_ids, $custom_amount, $user_id, $payment_description) {
       $processed = [];
       $remaining_amount = $custom_amount;
       $payment_distribution = [];

       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== INICIANDO PAGO NO COMPLETO ==========');
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto personalizado: ' . $custom_amount . ', cuotas: ' . count($quota_ids));
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Loan ID: ' . $loan_id . ', User ID: ' . $user_id);

       // DIAGNÓSTICO: Verificar estado inicial de cuotas seleccionadas
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== DIAGNÓSTICO ESTADO INICIAL CUOTAS ==========');
       foreach ($quota_ids as $quota_id) {
           $quota_info = $this->payments_m->get_loan_item($quota_id);
           if ($quota_info) {
               $interest_pending = max(0, $quota_info->interest_amount - ($quota_info->interest_paid ?? 0));
               $capital_pending = max(0, $quota_info->capital_amount - ($quota_info->capital_paid ?? 0));
               $total_pending = $interest_pending + $capital_pending;
               log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Cuota ID ' . $quota_id . ' - status: ' . $quota_info->status . ', balance: ' . $quota_info->balance . ', interest_pending: ' . $interest_pending . ', capital_pending: ' . $capital_pending . ', total_pending: ' . $total_pending);
           } else {
               log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: ERROR - Cuota ID ' . $quota_id . ' no encontrada');
           }
       }
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== FIN DIAGNÓSTICO ESTADO INICIAL ==========');

       // Para pagos no completos, solo procesar la primera cuota seleccionada
       if (count($quota_ids) > 1) {
           log_message('warning', 'CUSTOM_PAYMENT_INCOMPLETE: Solo se procesará la primera cuota para pagos no completos - cuotas seleccionadas: ' . count($quota_ids));
       }

       $quota_id = $quota_ids[0]; // Solo primera cuota
       $quota_info = $this->payments_m->get_loan_item($quota_id);

       // Incluir cuotas con status 1 (pendiente), 3 (parcial) y 4 (no completo)
       // Excluir cuotas pagadas completamente (status = 0) y condonadas (extra_payment = 3)
       if (!$quota_info || !in_array($quota_info->status, [1, 3, 4]) || (isset($quota_info->extra_payment) && $quota_info->extra_payment == 3)) {
           log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: Cuota no válida, ya pagada o condonada - ID: ' . $quota_id . ', status: ' . ($quota_info ? $quota_info->status : 'N/A'));
           return [
               'success' => false,
               'message' => 'Cuota no válida para pago no completo',
               'payment_breakdown' => [],
               'is_partial' => false,
               'is_last_installment' => false,
               'additional_quota_generated' => false,
               'remaining_balance_distributed' => 0
           ];
       }

       // Calcular montos pendientes
       $interest_pending = max(0, $quota_info->interest_amount - ($quota_info->interest_paid ?? 0));
       $capital_pending = max(0, $quota_info->capital_amount - ($quota_info->capital_paid ?? 0));
       $total_pending = $interest_pending + $capital_pending;

       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Procesando cuota ' . $quota_id . ' - total_pending: ' . $total_pending . ', custom_amount: ' . $custom_amount);

       // DIAGNÓSTICO CRÍTICO: Verificar si el pago personalizado excede el total pendiente
       if ($custom_amount > $total_pending) {
           log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: ERROR CRÍTICO - Monto personalizado ($' . $custom_amount . ') excede total pendiente ($' . $total_pending . ') - Esto no debería suceder en pagos "no completos"');
           log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: DETALLES - interest_pending: ' . $interest_pending . ', capital_pending: ' . $capital_pending . ', total_pending: ' . $total_pending);

           // CORRECCIÓN CRÍTICA: Para pagos "no completos", si el monto excede el pendiente, aplicar solo el monto pendiente
           // Esto corrige el problema donde se aplicaba solo $992.87 de $5,000
           log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: CORRECCIÓN - Aplicando solo el monto pendiente ($' . $total_pending . ') en lugar del monto solicitado ($' . $custom_amount . ')');
           $custom_amount = $total_pending; // CORRECCIÓN: Usar el monto pendiente real
       }

       // Aplicar prioridad interés-capital
       $interest_to_pay = 0;
       $capital_to_pay = 0;

       // Primero intereses pendientes
       if ($interest_pending > 0 && $remaining_amount > 0) {
           $interest_to_pay = min($remaining_amount, $interest_pending);
           $remaining_amount -= $interest_to_pay;
           log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Aplicado a intereses: ' . $interest_to_pay . ', remaining_amount: ' . $remaining_amount);
       }

       // Luego capital pendiente
       if ($capital_pending > 0 && $remaining_amount > 0) {
           $capital_to_pay = min($remaining_amount, $capital_pending);
           $remaining_amount -= $capital_to_pay;
           log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Aplicado a capital: ' . $capital_to_pay . ', remaining_amount: ' . $remaining_amount);
       }

       $total_paid_on_quota = $interest_to_pay + $capital_to_pay;

       // DIAGNÓSTICO CRÍTICO: Para pagos "no completos", el balance debe reflejar el SALDO PENDIENTE RESTANTE
       // No el balance después del pago aplicado, sino cuánto queda por pagar de la cuota
       $remaining_balance_after_payment = $total_pending - $total_paid_on_quota;

       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== CÁLCULO DE BALANCE CRÍTICO ==========');
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: total_pending antes del pago: ' . $total_pending);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: total_paid_on_quota: ' . $total_paid_on_quota);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: remaining_balance_after_payment (saldo pendiente restante): ' . $remaining_balance_after_payment);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto restante del pago (no aplicado): ' . $remaining_amount);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== FIN CÁLCULO DE BALANCE ==========');

       // Para pago no completo, SIEMPRE marcar como incompleto (status=4), sin importar si se completó el pago
       $status = 4; // Pago no completo
       log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE_DIAGNOSIS: ========== DIAGNÓSTICO STATUS=4 ========== - Monto: $' . $custom_amount);
       log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE_DIAGNOSIS: ALERTA: Sistema debe marcar status=4 para pagos no completos');
       log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE_DIAGNOSIS: ========== FIN DIAGNÓSTICO ========== ');

       // CORRECCIÓN CRÍTICA: El balance debe ser el saldo pendiente restante de la cuota, NO el resultado de calculate_balance_after_payment
       // Para pagos "no completos", el balance representa cuánto queda por pagar de la cuota actual
       $balance = $remaining_balance_after_payment;

       // Verificación adicional: asegurar que el balance no sea negativo
       if ($balance < 0) {
           $balance = 0;
           log_message('error', 'CUSTOM_PAYMENT_INCOMPLETE: ERROR - Balance calculado negativo, ajustado a 0');
       }

       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Balance final para cuota incompleta: ' . $balance . ' (saldo pendiente restante)');

       // Actualizar cuota
       $update_data = [
           'paid_by' => $user_id,
           'pay_date' => date('Y-m-d H:i:s'),
           'interest_paid' => ($quota_info->interest_paid ?? 0) + $interest_to_pay,
           'capital_paid' => ($quota_info->capital_paid ?? 0) + $capital_to_pay,
           'balance' => $balance, // Saldo pendiente restante de la cuota
           'status' => $status // Siempre 4 para pagos no completos
       ];

       $update_result = $this->payments_m->update_quota($update_data, $quota_id);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Cuota ' . $quota_id . ' actualizada - Resultado: ' . ($update_result ? 'SUCCESS' : 'FAILED'));

       // DIAGNÓSTICO: Verificar actualización inmediatamente
       $updated_quota = $this->payments_m->get_loan_item($quota_id);
       if ($updated_quota) {
           log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: VERIFICACIÓN BD - status: ' . $updated_quota->status . ', balance: ' . $updated_quota->balance . ', interest_paid: ' . ($updated_quota->interest_paid ?? 0) . ', capital_paid: ' . ($updated_quota->capital_paid ?? 0));
       }

       // Registrar el pago en la tabla payments
       $payment_record = [
           'loan_id' => $loan_id,
           'loan_item_id' => $quota_id,
           'amount' => $total_paid_on_quota,
           'tipo_pago' => 'custom',
           'monto_pagado' => $total_paid_on_quota,
           'interest_paid' => $interest_to_pay,
           'capital_paid' => $capital_to_pay,
           'payment_date' => date('Y-m-d H:i:s'),
           'payment_user_id' => $user_id,
           'method' => 'efectivo',
           'notes' => $payment_description . ' - Tipo: INCOMPLETE'
       ];
       if ($this->db->field_exists('custom_amount', 'payments')) {
           $payment_record['custom_amount'] = $custom_amount;
       }
       if ($this->db->field_exists('custom_payment_type', 'payments')) {
           $payment_record['custom_payment_type'] = 'incomplete';
       }
       $this->db->insert('payments', $payment_record);
       $payment_id = $this->db->insert_id();
       log_message('info', 'CUSTOM_PAYMENT_INCOMPLETE: Pago registrado en BD - ID: ' . $payment_id . ', loan_id: ' . $loan_id . ', loan_item_id: ' . $quota_id . ', monto: ' . $total_paid_on_quota);

       // Actualizar balance del préstamo y estado global
       // REMOVIDO: No llamar update_loan_balance_and_status para pagos personalizados, ya que manejan el status correctamente

       // Crear array de resultados
       $processed[] = [
           'quota_id' => $quota_id,
           'amount' => $total_paid_on_quota,
           'type' => 'incomplete',
           'interest_paid' => $interest_to_pay,
           'capital_paid' => $capital_to_pay,
           'total_paid' => $total_paid_on_quota,
           'status_changed' => false, // Nunca cambia a completo para pagos no completos
           'num_quota' => $quota_info->num_quota,
           'fee_amount' => $quota_info->fee_amount,
           'interest_amount' => $quota_info->interest_amount,
           'capital_amount' => $quota_info->capital_amount,
           'balance' => $balance, // Saldo pendiente restante
           'date' => $quota_info->date,
           'status' => $status,
           'extra_payment' => $quota_info->extra_payment ?? 0,
           'payment_type' => 'incomplete'
       ];

       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Procesamiento completado - cuotas procesadas: ' . count($processed));
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto restante no aplicado: ' . $remaining_amount);

       // DIAGNÓSTICO FINAL
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== RESUMEN FINAL ==========');
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto solicitado: $' . $custom_amount);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto aplicado: $' . $total_paid_on_quota);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Monto restante (no aplicado): $' . $remaining_amount);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Saldo pendiente restante en cuota: $' . $balance);
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: Cuota marcada como status=4 (incompleto)');
       log_message('debug', 'CUSTOM_PAYMENT_INCOMPLETE: ========== FIN RESUMEN ==========');

       return [
           'success' => true,
           'payment_breakdown' => $processed,
           'is_partial' => false, // No es parcial, es incompleto
           'is_last_installment' => false,
           'additional_quota_generated' => false,
           'remaining_balance_distributed' => 0,
           'message' => 'Pago no completo procesado correctamente - saldo pendiente mantenido en cuota actual'
       ];
   }

   /**
    * CORRECCIÓN: Procesa pagos personalizados parciales correctamente
    * Cuando el pago es menor al monto de cuota, marca como "parcial" y distribuye el saldo restante en cuotas futuras
    * NUEVO: Si es la última cuota y el pago es parcial, genera nueva cuota con mora (1.5 × tasa de interés corriente)
    */
   private function process_custom_payment_partial($loan_id, $quota_ids, $custom_amount, $user_id, $payment_description) {
     $processed = [];
     $remaining_amount = $custom_amount;
     $payment_distribution = [];
     // CORRECCIÓN: Para pagos parciales, siempre establecer is_partial = true desde el inicio
     $is_partial = true; // Siempre parcial para este tipo de pago
     $remaining_balance_distributed = 0;
     $unpaid_shortfall_total = 0; // Monto NO pagado de las cuotas seleccionadas que debe distribuirse a futuro
     $additional_quota_generated = false;

     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ========== INICIANDO PROCESAMIENTO PAGO PERSONALIZADO PARCIAL ==========');
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Monto personalizado: ' . $custom_amount . ', cuotas: ' . count($quota_ids));
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Loan ID: ' . $loan_id . ', User ID: ' . $user_id);
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Quota IDs: ' . json_encode($quota_ids));
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Payment description: ' . $payment_description);
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: is_partial establecido como TRUE (pago parcial)');

     // DIAGNÓSTICO CRÍTICO: Agregar logs para verificar el problema de status=3
     log_message('error', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: ========== DIAGNÓSTICO STATUS=3 ========== - Monto: $' . $custom_amount . ', Cuotas: ' . count($quota_ids));
     log_message('error', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: ALERTA: Sistema debe marcar status=3 para pagos parciales');
     log_message('error', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: ========== FIN DIAGNÓSTICO ========== ');

     // DIAGNÓSTICO: Verificar estado inicial de cuotas seleccionadas
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ========== DIAGNÓSTICO ESTADO INICIAL CUOTAS ==========');
     foreach ($quota_ids as $quota_id) {
         $quota_info = $this->payments_m->get_loan_item($quota_id);
         if ($quota_info) {
             $interest_pending = max(0, $quota_info->interest_amount - ($quota_info->interest_paid ?? 0));
             $capital_pending = max(0, $quota_info->capital_amount - ($quota_info->capital_paid ?? 0));
             $total_pending = $interest_pending + $capital_pending;
             log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ID ' . $quota_id . ' - status: ' . $quota_info->status . ', balance: ' . $quota_info->balance . ', interest_pending: ' . $interest_pending . ', capital_pending: ' . $capital_pending . ', total_pending: ' . $total_pending);

             // DIAGNÓSTICO CRÍTICO: Verificar si el balance coincide con capital pendiente
             $expected_balance = $capital_pending;
             if ($quota_info->balance != $expected_balance) {
                 log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR CRÍTICO - Balance inconsistente! Cuota ID ' . $quota_id . ' - balance actual: ' . $quota_info->balance . ', capital_pending esperado: ' . $expected_balance);
             }
         } else {
             log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Cuota ID ' . $quota_id . ' no encontrada');
         }
     }
     log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ========== FIN DIAGNÓSTICO ESTADO INICIAL ==========');

       // Aplicar prioridad automática interés-capital para pagos personalizados
       $custom_payment_type = 'cuota';

       // NUEVO: Verificar si alguna cuota seleccionada es la última del préstamo
       $is_last_installment_payment = false;
       foreach ($quota_ids as $quota_id) {
           if ($this->loans_m->is_last_installment($loan_id, $quota_id)) {
               $is_last_installment_payment = true;
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: DETECTADA ÚLTIMA CUOTA - ID: ' . $quota_id . ', loan_id: ' . $loan_id);
               break;
           }
       }

       // CORRECCIÓN: Verificar que el pago personalizado no exceda el total de las cuotas seleccionadas
       $total_selected_amount = 0;
       foreach ($quota_ids as $quota_id) {
           $quota_info_check = $this->payments_m->get_loan_item($quota_id);
           if ($quota_info_check && $quota_info_check->status == 1) {
               $total_selected_amount += $quota_info_check->fee_amount;
           }
       }

       if ($custom_amount > $total_selected_amount) {
           log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Monto personalizado ($' . $custom_amount . ') excede el total de cuotas seleccionadas ($' . $total_selected_amount . ')');
           return [
               'success' => false,
               'message' => 'El monto del pago personalizado no puede exceder el total de las cuotas seleccionadas',
               'payment_breakdown' => [],
               'is_partial' => false,
               'is_last_installment' => false,
               'additional_quota_generated' => false,
               'remaining_balance_distributed' => 0
           ];
       }

       if (is_array($quota_ids) && !empty($quota_ids)) {
          // Procesar cuotas seleccionadas con prioridad interés-capital
           foreach ($quota_ids as $quota_id) {
               if ($remaining_amount <= 0) break;

               $quota_info = $this->payments_m->get_loan_item($quota_id);
               if (!$quota_info || $quota_info->status != 1) {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' no válida o ya pagada, saltando');
                   continue;
               }

               // CORRECCIÓN: Validar que la cuota tenga montos pendientes reales antes de procesar
               $interest_pending = max(0, $quota_info->interest_amount - ($quota_info->interest_paid ?? 0));
               $capital_pending = max(0, $quota_info->capital_amount - ($quota_info->capital_paid ?? 0));
               $total_pending = $interest_pending + $capital_pending;

               if ($total_pending <= 0) {
                   log_message('debug', 'CUSTOM_PAYMENT: Cuota ' . $quota_id . ' ya pagada completamente (total_pending: ' . $total_pending . '), saltando');
                   continue;
               }

               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Procesando cuota ' . $quota_id . ' - balance: ' . $quota_info->balance . ', remaining_amount: ' . $remaining_amount);

               // DIAGNÓSTICO: Log detallado de montos antes del procesamiento
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] Cuota ' . $quota_id . ' ANTES del procesamiento:');
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - interest_amount: ' . $quota_info->interest_amount);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - interest_paid: ' . ($quota_info->interest_paid ?? 0));
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - capital_amount: ' . $quota_info->capital_amount);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - capital_paid: ' . ($quota_info->capital_paid ?? 0));
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - balance: ' . $quota_info->balance);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - status: ' . $quota_info->status);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - fee_amount: ' . $quota_info->fee_amount);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - num_quota: ' . $quota_info->num_quota);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - date: ' . $quota_info->date);

               // CORRECCIÓN CRÍTICA: Calcular montos pendientes correctamente
               // Usar max(0, ...) para evitar valores negativos que puedan causar problemas
               $interest_pending = max(0, $quota_info->interest_amount - ($quota_info->interest_paid ?? 0));
               $capital_pending = max(0, $quota_info->capital_amount - ($quota_info->capital_paid ?? 0));
               $total_pending = $interest_pending + $capital_pending;

               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] Cálculos pendientes - interest_pending: ' . $interest_pending . ', capital_pending: ' . $capital_pending . ', total_pending: ' . $total_pending);

               // DIAGNÓSTICO: Verificar si el pago personalizado excede el total pendiente
               if ($remaining_amount > $total_pending) {
                 log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] PAGO EXCEDE TOTAL PENDIENTE - remaining_amount: ' . $remaining_amount . ' > total_pending: ' . $total_pending . ' - MARCA COMO PARCIAL');
                 $is_partial = true;
               }

               // DIAGNÓSTICO CRÍTICO: Log detallado del cálculo de distribución
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO DISTRIBUCIÓN] Cuota ID ' . $quota_id . ' - remaining_amount: ' . $remaining_amount . ', total_pending: ' . $total_pending);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO DISTRIBUCIÓN] - interest_pending: ' . $interest_pending . ', capital_pending: ' . $capital_pending);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO DISTRIBUCIÓN] - is_partial flag: ' . ($is_partial ? 'TRUE' : 'FALSE'));

               // CORRECCIÓN: Declarar e inicializar variables antes de usarlas
                       $amount_to_pay = 0;
                       $new_interest_paid = ($quota_info->interest_paid ?? 0);
                       $new_capital_paid = ($quota_info->capital_paid ?? 0);

               // Determinar cuánto pagar de esta cuota
               $amount_to_pay = min($remaining_amount, $total_pending);

               // Si el pago es menor al total pendiente, marcar como parcial
               if ($amount_to_pay < $total_pending) {
                   $is_partial = true;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Pago PARCIAL detectado - amount_to_pay: ' . $amount_to_pay . ' < total_pending: ' . $total_pending);
               }

               $quota_payment = [
                   'quota_id' => $quota_id,
                   'interest_paid' => 0,
                   'capital_paid' => 0,
                   'total_paid' => $amount_to_pay,
                   'amount' => $amount_to_pay,
                   'status_changed' => ($amount_to_pay >= $total_pending)
               ];

               // CORRECCIÓN CRÍTICA: Aplicar prioridad interés-capital correctamente
               $interest_to_pay = 0;
               $capital_to_pay = 0;

               // DIAGNÓSTICO: Verificar montos pendientes antes de aplicar
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] Antes de aplicar prioridad:');
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] - interest_pending: ' . $interest_pending);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] - capital_pending: ' . $capital_pending);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] - amount_to_pay: ' . $amount_to_pay);

               // Aplicar prioridad interés-capital: primero intereses, luego capital
               if ($interest_pending > 0 && $amount_to_pay > 0) {
                   $interest_to_pay = min($amount_to_pay, $interest_pending);
                   $amount_to_pay -= $interest_to_pay;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] Aplicado a intereses: ' . $interest_to_pay . ', amount_to_pay restante: ' . $amount_to_pay);
               }

               if ($capital_pending > 0 && $amount_to_pay > 0) {
                   $capital_to_pay = min($amount_to_pay, $capital_pending);
                   $amount_to_pay -= $capital_to_pay;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [CORRECCIÓN] Aplicado a capital: ' . $capital_to_pay . ', amount_to_pay restante: ' . $amount_to_pay);
               }

               // Actualizar variables para verificación de completitud
               $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
               $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;

               // VALIDACIÓN: Verificar que la distribución sea correcta
               $total_distributed = $interest_to_pay + $capital_to_pay;
               $expected_amount = min($remaining_amount, $total_pending);
               if ($total_distributed != $expected_amount) {
                   log_message('error', 'CUSTOM_PAYMENT_PARTIAL: [ERROR CRÍTICO] Distribución incorrecta - total_distributed: ' . $total_distributed . ', expected: ' . $expected_amount);
                   log_message('error', 'CUSTOM_PAYMENT_PARTIAL: [ERROR DETALLES] remaining_amount: ' . $remaining_amount . ', total_pending: ' . $total_pending . ', interest_to_pay: ' . $interest_to_pay . ', capital_to_pay: ' . $capital_to_pay);
               } else {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [VALIDACIÓN OK] Distribución correcta: intereses=' . $interest_to_pay . ', capital=' . $capital_to_pay . ', total=' . $total_distributed);
               }

               // DIAGNÓSTICO CRÍTICO: Verificar si el pago completo la cuota
               $will_complete_quota = ($new_interest_paid >= $quota_info->interest_amount && $new_capital_paid >= $quota_info->capital_amount);
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO COMPLETADO] Cuota ID ' . $quota_id . ' - will_complete_quota: ' . ($will_complete_quota ? 'TRUE' : 'FALSE') . ' (new_interest_paid: ' . $new_interest_paid . '/' . $quota_info->interest_amount . ', new_capital_paid: ' . $new_capital_paid . '/' . $quota_info->capital_amount . ')');

               $quota_payment['interest_paid'] = $interest_to_pay;
               $quota_payment['capital_paid'] = $capital_to_pay;

               $total_paid_on_quota = $interest_to_pay + $capital_to_pay;
               $unpaid_for_this_quota = max(0, $total_pending - $total_paid_on_quota);
               if ($unpaid_for_this_quota > 0) {
                   $unpaid_shortfall_total += $unpaid_for_this_quota;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Saldo NO pagado para cuota ' . $quota_id . ': ' . $unpaid_for_this_quota . ' (se sumará para distribuir en futuras cuotas)');
               }

               // Verificar si la cuota queda completamente pagada
               $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
               $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;

               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] Verificación de pago completo:');
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - new_interest_paid: ' . $new_interest_paid . ' >= interest_amount: ' . $quota_info->interest_amount . ' = ' . ($new_interest_paid >= $quota_info->interest_amount ? 'TRUE' : 'FALSE'));
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - new_capital_paid: ' . $new_capital_paid . ' >= capital_amount: ' . $quota_info->capital_amount . ' = ' . ($new_capital_paid >= $quota_info->capital_amount ? 'TRUE' : 'FALSE'));

               // CORRECCIÓN: Para pagos personalizados, marcar status=3 cuando el pago no completa la cuota
               if ($new_interest_paid >= $quota_info->interest_amount && $new_capital_paid >= $quota_info->capital_amount) {
                   $quota_payment['status_changed'] = true;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' queda completamente pagada - CAMBIANDO STATUS A 0');
               } else {
                   // Pago parcial: marcar como parcialmente pagado (status=3)
                   $quota_payment['status_changed'] = false;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' pago PARCIAL - CAMBIANDO STATUS A 3 (PARCIAL)');
               }

               // CORRECCIÓN: Asegurar que los montos aplicados se registren correctamente
               $quota_payment['interest_applied'] = $interest_to_pay;
               $quota_payment['capital_applied'] = $capital_to_pay;
               $quota_payment['total_paid'] = $interest_to_pay + $capital_to_pay;

               if ($total_paid_on_quota > 0) {
                   $payment_distribution[] = $quota_payment;
                   $remaining_amount -= $total_paid_on_quota;

                   // CORRECCIÓN CRÍTICA: Actualizar cuota con lógica correcta de estados
                       $new_interest_paid_total = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
                       $new_capital_paid_total = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
                       
                       // Calcular balance basado en montos pendientes reales
                       $remaining_interest = max(0, $quota_info->interest_amount - $new_interest_paid_total);
                       $remaining_capital = max(0, $quota_info->capital_amount - $new_capital_paid_total);
                       $new_balance = $remaining_interest + $remaining_capital;
                       
                       $update_data = [
                           'paid_by' => $user_id,
                           'pay_date' => date('Y-m-d H:i:s'),
                           'interest_paid' => $new_interest_paid_total,
                           'capital_paid' => $new_capital_paid_total,
                           'balance' => $new_balance
                       ];
   
                       // DIAGNÓSTICO CRÍTICO: Agregar logs detallados para rastrear asignación de status en pagos parciales
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: ========== ASIGNACIÓN DE STATUS ==========');
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: Cuota ID: ' . $quota_id);
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: quota_payment[status_changed]: ' . ($quota_payment['status_changed'] ? 'TRUE' : 'FALSE'));
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: will_complete_quota: ' . ($will_complete_quota ? 'TRUE' : 'FALSE'));
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: is_partial flag: ' . ($is_partial ? 'TRUE' : 'FALSE'));

                       // CORRECCIÓN: Forzar status correcto según especificaciones
                       // Para pagos parciales, SIEMPRE usar status=3, incluso si completa la cuota
                       // (porque es un pago parcial, no un pago completo normal)
                       if ($quota_payment['status_changed'] && !$is_partial) {
                           // Solo marcar como pagada si NO es un pago parcial
                           $update_data['status'] = 0;
                           $update_data['balance'] = 0;
                           $update_data['interest_paid'] = $quota_info->interest_amount; // Asegurar que esté completo
                           $update_data['capital_paid'] = $quota_info->capital_amount; // Asegurar que esté completo
                           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: Status asignado = 0 (completamente pagada, NO parcial)');
                       } else {
                           // Pago parcial: SIEMPRE usar status=3
                           $update_data['status'] = 3; // Forzar status parcial
                           // El balance ya fue calculado correctamente arriba
                           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: Status asignado = 3 (pago parcial), balance: ' . $new_balance . ', is_partial: ' . ($is_partial ? 'TRUE' : 'FALSE'));
                       }
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: ========== FIN ASIGNACIÓN DE STATUS ==========');
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL_DIAGNOSIS: Datos finales a guardar: ' . json_encode($update_data));
   
                   $update_result = $this->payments_m->update_quota($update_data, $quota_id);
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' actualizada con prioridad interés-capital: ' . json_encode($update_data) . ' - Resultado: ' . ($update_result ? 'SUCCESS' : 'FAILED'));
   
                   // CORRECCIÓN CRÍTICA: Verificar inmediatamente que la actualización se realizó correctamente
                   if ($update_result) {
                       $verify_quota = $this->payments_m->get_loan_item($quota_id);
                       if ($verify_quota) {
                           $expected_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
                           $expected_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;
   
                           if ($verify_quota->interest_paid != $expected_interest_paid || $verify_quota->capital_paid != $expected_capital_paid) {
                               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR CRÍTICO - Actualización no se reflejó correctamente en BD!');
                               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: Esperado - interest_paid: ' . $expected_interest_paid . ', capital_paid: ' . $expected_capital_paid);
                               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: Actual en BD - interest_paid: ' . ($verify_quota->interest_paid ?? 'NULL') . ', capital_paid: ' . ($verify_quota->capital_paid ?? 'NULL'));
                           } else {
                               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Verificación exitosa - montos actualizados correctamente en BD');
                           }
                       }
                   }

                   // DIAGNÓSTICO: Verificar actualización consultando BD inmediatamente después
                   $updated_quota = $this->payments_m->get_loan_item($quota_id);
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] Cuota ' . $quota_id . ' DESPUÉS de actualización:');
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - status: ' . ($updated_quota ? $updated_quota->status : 'NO ENCONTRADA'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - interest_paid: ' . ($updated_quota ? $updated_quota->interest_paid : 'N/A'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - capital_paid: ' . ($updated_quota ? $updated_quota->capital_paid : 'N/A'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - balance: ' . ($updated_quota ? $updated_quota->balance : 'N/A'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - fee_amount: ' . ($updated_quota ? $updated_quota->fee_amount : 'N/A'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - num_quota: ' . ($updated_quota ? $updated_quota->num_quota : 'N/A'));
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [DIAGNÓSTICO] - date: ' . ($updated_quota ? $updated_quota->date : 'N/A'));
               } else {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' no tiene montos aplicables, saltando');
               }
           }

           // CORRECCIÓN CRÍTICA: Si queda monto sin aplicar (pago parcial), distribuirlo en cuotas futuras con límites
           // NUEVO: Si es la última cuota, generar nueva cuota con mora en lugar de distribuir
           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Verificando distribución - remaining_amount: ' . $remaining_amount . ', is_partial: ' . ($is_partial ? 'TRUE' : 'FALSE') . ', is_last_installment_payment: ' . ($is_last_installment_payment ? 'TRUE' : 'FALSE'));
           
           if ($remaining_amount > 0 && $is_partial) {
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Condición cumplida para distribución - remaining_amount > 0 y is_partial = TRUE');
               if ($is_last_installment_payment) {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ÚLTIMA CUOTA PARCIAL - Generando nueva cuota con mora en lugar de distribuir');

                   // Obtener información del préstamo para calcular la mora
                   $loan = $this->loans_m->get_loan($loan_id);
                   if ($loan) {
                       // Calcular tasa de mora: 1.5 × tasa de interés corriente
                       $current_interest_rate = $loan->interest_amount / 100.0; // Convertir a decimal
                       $penalty_rate = 1.5 * $current_interest_rate;

                       // Calcular monto de mora basado en el saldo restante
                       $penalty_amount = round($remaining_amount * $penalty_rate, 2);

                       // Monto total de la nueva cuota: saldo restante + mora
                       $new_quota_total = $remaining_amount + $penalty_amount;

                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cálculo de mora - tasa corriente: ' . ($current_interest_rate * 100) . '%, tasa mora: ' . ($penalty_rate * 100) . '%, mora calculada: $' . $penalty_amount . ', nueva cuota total: $' . $new_quota_total);

                       // Generar nueva cuota con mora
                       $new_quota_id = $this->generate_additional_quota_for_remaining($loan_id, $new_quota_total, $user_id);

                       if ($new_quota_id) {
                           $additional_quota_generated = true;
                           $remaining_balance_distributed = $new_quota_total; // Para compatibilidad con el código existente
                           log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Nueva cuota con mora generada exitosamente - ID: ' . $new_quota_id . ', monto: $' . $new_quota_total . ' (saldo: $' . $remaining_amount . ' + mora: $' . $penalty_amount . ')');
                       } else {
                           log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR al generar nueva cuota con mora');
                       }
                   } else {
                       log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - No se pudo obtener información del préstamo para calcular mora');
                   }
               } else {
                   // Lógica original para cuotas que no son la última
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: CORRECCIÓN - Distribución de saldo restante en cuotas futuras: ' . $remaining_amount);

                   // Obtener cuotas futuras pendientes (no seleccionadas) con límite para evitar bucles infinitos
                   // Incluir cuotas con status 1 (pendiente), 3 (parcial) y 4 (no completo)
                   $this->db->select('*');
                   $this->db->from('loan_items');
                   $this->db->where('loan_id', $loan_id);
                   $this->db->where_in('status', [1, 3, 4]);
                   $this->db->where_not_in('id', $quota_ids);
                   $this->db->where('extra_payment !=', 3); // Excluir cuotas condonadas
                   $this->db->order_by('num_quota', 'ASC');
                   $this->db->limit(10); // LÍMITE: Máximo 10 cuotas futuras para evitar bucles infinitos
                   $future_quotas = $this->db->get()->result();

                   $remaining_to_distribute = $remaining_amount;
                   $max_increase_per_quota = $custom_amount * 0.2; // Máximo 20% del pago original por cuota
                   $total_distributed = 0;
                   $max_total_distribution = $custom_amount * 0.5; // Máximo 50% del pago original total distribuido

                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: LÍMITES aplicados - max_increase_per_quota: ' . $max_increase_per_quota . ', max_total_distribution: ' . $max_total_distribution . ', cuotas futuras encontradas: ' . count($future_quotas));

                   foreach ($future_quotas as $future_quota) {
                       if ($remaining_to_distribute <= 0 || $total_distributed >= $max_total_distribution) {
                           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Límite alcanzado - remaining_to_distribute: ' . $remaining_to_distribute . ', total_distributed: ' . $total_distributed);
                           break;
                       }

                       // Calcular cuánto aumentar esta cuota futura con límites estrictos
                       $current_fee_amount = $future_quota->fee_amount;
                       $max_for_this_quota = min($max_increase_per_quota, $current_fee_amount * 0.3); // Máximo 30% de la cuota actual
                       $increase_amount = min($remaining_to_distribute, $max_for_this_quota);

                       if ($increase_amount > 0.01) { // Solo si es mayor a 1 centavo
                           $new_fee_amount = $current_fee_amount + $increase_amount;

                           // Mantener proporciones de interés/capital
                           $interest_ratio = $future_quota->interest_amount / $current_fee_amount;
                           $capital_ratio = $future_quota->capital_amount / $current_fee_amount;

                           $new_interest_amount = round($new_fee_amount * $interest_ratio, 2);
                           $new_capital_amount = round($new_fee_amount * $capital_ratio, 2);

                           // Actualizar cuota futura
                           $update_future = [
                               'fee_amount' => $new_fee_amount,
                               'interest_amount' => $new_interest_amount,
                               'capital_amount' => $new_capital_amount,
                               'balance' => $future_quota->balance + ($new_capital_amount - $future_quota->capital_amount)
                           ];

                           $update_result_future = $this->payments_m->update_quota($update_future, $future_quota->id);

                           if ($update_result_future) {
                               $remaining_to_distribute -= $increase_amount;
                               $remaining_balance_distributed += $increase_amount;
                               $total_distributed += $increase_amount;

                               log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Cuota futura #' . $future_quota->num_quota . ' aumentada en $' . $increase_amount . ' (nuevo total: $' . $new_fee_amount . ') - Total distribuido: $' . $total_distributed);
                           } else {
                               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR al actualizar cuota futura #' . $future_quota->num_quota);
                           }
                       } else {
                           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Aumento muy pequeño para cuota #' . $future_quota->num_quota . ', saltando');
                       }
                   }

                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Distribución completada - saldo restante distribuido: $' . $remaining_balance_distributed . ', total distribuido: $' . $total_distributed . ', remaining_to_distribute final: ' . $remaining_to_distribute);
               }
           }

           // Crear array de resultados para compatibilidad
           foreach ($payment_distribution as $payment) {
               $processed[] = [
                   'quota_id' => $payment['quota_id'],
                   'amount' => $payment['total_paid'],
                   'type' => $custom_payment_type,
                   'interest_paid' => $payment['interest_applied'] ?? $payment['interest_paid'],
                   'capital_paid' => $payment['capital_applied'] ?? $payment['capital_paid'],
                   'status_changed' => $payment['status_changed']
               ];
           }

           // Recalcular balance y estado del préstamo después de la redistribución
           // REMOVIDO: No llamar update_loan_balance_and_status para pagos personalizados, ya que manejan el status correctamente

           // Verificar que todas las cuotas seleccionadas fueron procesadas
           $processed_quota_ids = array_column($processed, 'quota_id');
           $missing_quotas = array_diff($quota_ids, $processed_quota_ids);
           if (!empty($missing_quotas)) {
               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Cuotas no procesadas: ' . json_encode($missing_quotas));
           } else {
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Todas las cuotas seleccionadas fueron procesadas correctamente');
           }

           // Registrar pago personalizado en la tabla de pagos
           $total_interest_paid = array_sum(array_column($processed, 'interest_paid'));
           $total_capital_paid = array_sum(array_column($processed, 'capital_paid'));

           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [REGISTRO PAGO] Total interest_paid calculado: ' . $total_interest_paid);
           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [REGISTRO PAGO] Total capital_paid calculado: ' . $total_capital_paid);
           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [REGISTRO PAGO] Monto personalizado: ' . $custom_amount);
           log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: [REGISTRO PAGO] Suma total (interest + capital): ' . ($total_interest_paid + $total_capital_paid));

           $payment_record = [
               'loan_id' => $loan_id,
               'loan_item_id' => $processed[0]['quota_id'] ?? null,
               'amount' => $custom_amount,
               'tipo_pago' => 'custom',
               'monto_pagado' => $custom_amount,
               'interest_paid' => $total_interest_paid,
               'capital_paid' => $total_capital_paid,
               'payment_date' => date('Y-m-d H:i:s'),
               'payment_user_id' => $user_id,
               'method' => 'efectivo',
               'notes' => $payment_description . ' - Tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO') . ' - Desglose: ' . json_encode($processed)
           ];

           // Agregar campos custom_amount y custom_payment_type solo si existen en la tabla
           if ($this->db->field_exists('custom_amount', 'payments')) {
               $payment_record['custom_amount'] = $custom_amount;
           }
           if ($this->db->field_exists('custom_payment_type', 'payments')) {
               $payment_record['custom_payment_type'] = $custom_payment_type;
           }

           log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Registrando pago personalizado - loan_id: ' . $loan_id . ', tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO') . ', monto: ' . $custom_amount);

           $this->db->insert('payments', $payment_record);
           $payment_id = $this->db->insert_id();

           if (!$payment_id) {
               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Falló registro de pago personalizado para loan_id: ' . $loan_id);
           } else {
               log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Pago personalizado registrado exitosamente - ID: ' . $payment_id . ', tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO'));
           }
       }

       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Procesamiento completado - tipo: ' . $custom_payment_type . ', parcial: ' . ($is_partial ? 'SÍ' : 'NO') . ', saldo_distribuido: ' . $remaining_balance_distributed . ', nueva_cuota_generada: ' . ($additional_quota_generated ? 'SÍ' : 'NO'));

       // DIAGNÓSTICO FINAL: Resumen del procesamiento
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== RESUMEN FINAL DEL PROCESAMIENTO ==========');
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Monto personalizado solicitado: $' . $custom_amount);
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Monto restante sin aplicar: $' . $remaining_amount);
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Pago identificado como: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO'));
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Saldo distribuido en cuotas futuras: $' . $remaining_balance_distributed);
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Nueva cuota generada: ' . ($additional_quota_generated ? 'SÍ' : 'NO'));
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: Cuotas procesadas: ' . count($processed));
       log_message('debug', 'CUSTOM_PAYMENT_DIAGNOSIS: ========== FIN RESUMEN ==========');

       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ========== RESULTADO FINAL ==========');
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Monto personalizado: ' . $custom_amount);
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Monto restante sin aplicar: ' . $remaining_amount);
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Pago parcial: ' . ($is_partial ? 'SÍ' : 'NO'));
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Última cuota detectada: ' . ($is_last_installment_payment ? 'SÍ' : 'NO'));
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Nueva cuota con mora generada: ' . ($additional_quota_generated ? 'SÍ' : 'NO'));
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Saldo distribuido en cuotas futuras: ' . $remaining_balance_distributed);
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuotas procesadas: ' . count($processed));
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Total interest_paid: ' . array_sum(array_column($processed, 'interest_paid')));
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Total capital_paid: ' . array_sum(array_column($processed, 'capital_paid')));

       // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket en processed_quotas
       $payment_type = $is_partial ? 'partial' : 'paid';
       $processed_quotas = [];
       foreach ($processed as $payment) {
           $quota_info = $this->payments_m->get_loan_item($payment['quota_id']);
           if ($quota_info) {
               $processed_quotas[] = [
                   'quota_id' => $payment['quota_id'],
                   'loan_id' => $loan_id,
                   'amount' => $payment['amount'] ?? $payment['total_paid'],
                   'type' => 'custom',
                   'interest_paid' => $payment['interest_paid'] ?? 0,
                   'capital_paid' => $payment['capital_paid'] ?? 0,
                   'total_paid' => $payment['amount'] ?? $payment['total_paid'],
                   'num_quota' => $quota_info->num_quota,
                   'fee_amount' => $quota_info->fee_amount,
                   'interest_amount' => $quota_info->interest_amount,
                   'capital_amount' => $quota_info->capital_amount,
                   'balance' => $quota_info->balance - ($payment['capital_paid'] ?? 0),
                   'date' => $quota_info->date,
                   'status' => $payment['status_changed'] ? 0 : 3,
                   'extra_payment' => $quota_info->extra_payment ?? 0,
                   'payment_type' => $payment_type
               ];
           }
       }

       return [
           'success' => true,
           'payment_breakdown' => $processed_quotas, // Usar processed_quotas corregido
           'is_partial' => $is_partial,
           'is_last_installment' => $is_last_installment_payment,
           'additional_quota_generated' => $additional_quota_generated,
           'remaining_balance_distributed' => $remaining_balance_distributed,
           'message' => 'Pago personalizado procesado correctamente' . ($additional_quota_generated ? ' - Nueva cuota con mora generada' : '')
       ];
   }

   /**
    * CORRECCIÓN: Procesa pagos personalizados parciales correctamente
    * Cuando el pago es menor al monto de cuota, marca como "parcial" y distribuye el saldo restante en cuotas futuras
    */
   private function process_custom_payment($quota_ids, $custom_amount, $custom_payment_type, $user_id, $loan_id) {
       $processed = [];
       $remaining_amount = $custom_amount;
       $payment_distribution = [];
       $is_partial = false;
       $remaining_balance_distributed = 0;
       $unpaid_shortfall_total = 0; // Monto NO pagado de las cuotas seleccionadas
       $additional_quota_generated = false;
       
       // Verificar si alguna cuota seleccionada es la última del préstamo
       $is_last_installment_payment = false;
       foreach ($quota_ids as $quota_id) {
           if ($this->loans_m->is_last_installment($loan_id, $quota_id)) {
               $is_last_installment_payment = true;
               log_message('debug', 'CUSTOM_PAYMENT: DETECTADA ÚLTIMA CUOTA - ID: ' . $quota_id . ', loan_id: ' . $loan_id);
               break;
           }
       }

       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: ========== INICIANDO PROCESAMIENTO PAGO PERSONALIZADO PARCIAL ==========');
       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Monto personalizado: ' . $custom_amount . ', cuotas: ' . count($quota_ids));

       // Aplicar prioridad automática interés-capital para pagos personalizados
       if (empty($custom_payment_type)) {
           $custom_payment_type = 'cuota';
       }

       if (is_array($quota_ids) && !empty($quota_ids)) {
           // Procesar cuotas seleccionadas con prioridad interés-capital
           foreach ($quota_ids as $quota_id) {
               if ($remaining_amount <= 0) break;

               $quota_info = $this->payments_m->get_loan_item($quota_id);
               // Incluir cuotas con status 1 (pendiente), 3 (parcial) y 4 (no completo)
               // Excluir cuotas pagadas completamente (status = 0) y condonadas (extra_payment = 3)
               if (!$quota_info || !in_array($quota_info->status, [1, 3, 4]) || (isset($quota_info->extra_payment) && $quota_info->extra_payment == 3)) {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' no válida, ya pagada o condonada (status: ' . ($quota_info ? $quota_info->status : 'N/A') . '), saltando');
                   continue;
               }

               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Procesando cuota ' . $quota_id . ' - balance: ' . $quota_info->balance . ', remaining_amount: ' . $remaining_amount);

               // Calcular montos pendientes
               $interest_pending = $quota_info->interest_amount - ($quota_info->interest_paid ?? 0);
               $capital_pending = $quota_info->capital_amount - ($quota_info->capital_paid ?? 0);
               $total_pending = $interest_pending + $capital_pending;

               // Determinar cuánto pagar de esta cuota
               $amount_to_pay = min($remaining_amount, $total_pending);

               // Si el pago es menor al total pendiente, marcar como parcial
               if ($amount_to_pay < $total_pending) {
                   $is_partial = true;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Pago PARCIAL detectado - amount_to_pay: ' . $amount_to_pay . ' < total_pending: ' . $total_pending);
               }

               $quota_payment = [
                   'quota_id' => $quota_id,
                   'interest_paid' => 0,
                   'capital_paid' => 0,
                   'total_paid' => $amount_to_pay,
                   'status_changed' => ($amount_to_pay >= $total_pending)
               ];

               // Aplicar prioridad interés-capital
               $interest_to_pay = 0;
               $capital_to_pay = 0;

               // Primero intereses pendientes
               if ($interest_pending > 0 && $amount_to_pay > 0) {
                   $interest_to_pay = min($amount_to_pay, $interest_pending);
                   $amount_to_pay -= $interest_to_pay;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Aplicado a intereses: ' . $interest_to_pay . ', amount_to_pay restante: ' . $amount_to_pay);
               }

               // Luego capital pendiente
               if ($capital_pending > 0 && $amount_to_pay > 0) {
                   $capital_to_pay = min($amount_to_pay, $capital_pending);
                   $amount_to_pay -= $capital_to_pay;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Aplicado a capital: ' . $capital_to_pay . ', amount_to_pay restante: ' . $amount_to_pay);
               }

               $quota_payment['interest_paid'] = $interest_to_pay;
               $quota_payment['capital_paid'] = $capital_to_pay;

               $total_paid_on_quota = $interest_to_pay + $capital_to_pay;
               
               // Calcular monto NO pagado de esta cuota (shortfall)
               $unpaid_for_this_quota = max(0, $total_pending - $total_paid_on_quota);
               if ($unpaid_for_this_quota > 0) {
                   $unpaid_shortfall_total += $unpaid_for_this_quota;
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Saldo NO pagado para cuota ' . $quota_id . ': ' . $unpaid_for_this_quota . ' (se sumará para distribuir en futuras cuotas)');
               }

               // Verificar si la cuota queda completamente pagada
               $new_interest_paid = ($quota_info->interest_paid ?? 0) + $interest_to_pay;
               $new_capital_paid = ($quota_info->capital_paid ?? 0) + $capital_to_pay;

               // Calcular saldo restante REAL de la cuota después del pago aplicado
               $remaining_interest_after = max(0, ($quota_info->interest_amount ?? 0) - $new_interest_paid);
               $remaining_capital_after = max(0, ($quota_info->capital_amount ?? 0) - $new_capital_paid);
               $remaining_on_quota = max(0, $remaining_interest_after + $remaining_capital_after);

               if ($remaining_on_quota == 0) {
                   $quota_payment['status_changed'] = true;
                   log_message('debug', 'CUSTOM_PAYMENT: Cuota ' . $quota_id . ' queda completamente pagada');
               } else {
                   // Pago parcial: marcar como parcialmente pagado (status=3)
                   $quota_payment['status_changed'] = false;
                   log_message('debug', 'CUSTOM_PAYMENT: Cuota ' . $quota_id . ' pago PARCIAL - CAMBIANDO STATUS A 3 (PARCIAL)');
               }

               if ($total_paid_on_quota > 0) {
                   $payment_distribution[] = $quota_payment;
                   $remaining_amount -= $total_paid_on_quota;

                   // Actualizar cuota
                   $update_data = [
                       'paid_by' => $user_id,
                       'pay_date' => date('Y-m-d H:i:s'),
                       'interest_paid' => ($quota_info->interest_paid ?? 0) + $interest_to_pay,
                       'capital_paid' => ($quota_info->capital_paid ?? 0) + $capital_to_pay,
                       // El balance debe reflejar el saldo restante real (interés + capital pendientes), nunca negativo
                       'balance' => $remaining_on_quota
                   ];

                   // CORRECCIÓN: Para pagos parciales, SIEMPRE usar status=3
                   // Incluso si completa la cuota, si es un pago parcial debe mantener status=3
                   if ($quota_payment['status_changed'] && !$is_partial) {
                       // Solo marcar como pagada si NO es un pago parcial
                       $update_data['status'] = 0;
                       $update_data['balance'] = 0;
                       log_message('debug', 'CUSTOM_PAYMENT: Cuota ' . $quota_id . ' completamente pagada (NO parcial)');
                   } else {
                       // Para pagos parciales, SIEMPRE mantener status=3
                       $update_data['status'] = 3; // Mantener como parcialmente pagado
                       log_message('debug', 'CUSTOM_PAYMENT: Cuota ' . $quota_id . ' mantiene status=3 (parcialmente pagado), is_partial: ' . ($is_partial ? 'TRUE' : 'FALSE'));
                   }

                   $this->payments_m->update_quota($update_data, $quota_id);
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' actualizada con prioridad interés-capital: ' . json_encode($update_data));
               } else {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Cuota ' . $quota_id . ' no tiene montos aplicables, saltando');
               }
           }

           // CORRECCIÓN CRÍTICA: Distribuir el saldo NO pagado (shortfall) en cuotas futuras
           if ($is_partial && $unpaid_shortfall_total > 0) {
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: CORRECCIÓN - Distribución de saldo NO pagado en cuotas futuras: ' . $unpaid_shortfall_total);

               // Obtener cuotas futuras pendientes (no seleccionadas)
               // Incluir cuotas con status 1 (pendiente), 3 (parcial) y 4 (no completo)
               $this->db->select('*');
               $this->db->from('loan_items');
               $this->db->where('loan_id', $loan_id);
               $this->db->where_in('status', [1, 3, 4]);
               $this->db->where_not_in('id', $quota_ids);
               $this->db->where('extra_payment !=', 3); // Excluir cuotas condonadas
               $this->db->order_by('num_quota', 'ASC');
               $future_quotas = $this->db->get()->result();

               $remaining_to_distribute = $unpaid_shortfall_total;

               foreach ($future_quotas as $future_quota) {
                   if ($remaining_to_distribute <= 0) break;

                   // Calcular cuánto aumentar esta cuota futura
                   $current_fee_amount = $future_quota->fee_amount;
                   $increase_amount = min($remaining_to_distribute, $current_fee_amount * 0.5); // Máximo 50% de aumento

                   if ($increase_amount > 0) {
                       $new_fee_amount = $current_fee_amount + $increase_amount;

                       // Mantener proporciones de interés/capital
                       $interest_ratio = $future_quota->interest_amount / $current_fee_amount;
                       $capital_ratio = $future_quota->capital_amount / $current_fee_amount;

                       $new_interest_amount = round($new_fee_amount * $interest_ratio, 2);
                       $new_capital_amount = round($new_fee_amount * $capital_ratio, 2);

                       // Actualizar cuota futura
                       $update_future = [
                           'fee_amount' => $new_fee_amount,
                           'interest_amount' => $new_interest_amount,
                           'capital_amount' => $new_capital_amount,
                           'balance' => $future_quota->balance + ($new_capital_amount - $future_quota->capital_amount)
                       ];

                       $this->payments_m->update_quota($update_future, $future_quota->id);

                       $remaining_to_distribute -= $increase_amount;
                       $remaining_balance_distributed += $increase_amount;

                       log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Cuota futura #' . $future_quota->num_quota . ' aumentada en $' . $increase_amount . ' (nuevo total: $' . $new_fee_amount . ')');
                   }
               }

               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Distribución completada - saldo NO pagado distribuido: $' . $remaining_balance_distributed);
           }

           // CORRECCIÓN: Si es la última cuota y quedó saldo sin poder distribuir, generar nueva cuota con mora (1.5x)
           // Este bloque es un fallback para el caso donde no se ejecutó el bloque anterior
           if ($is_partial && $is_last_installment_payment && !$additional_quota_generated) {
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Verificando fallback para última cuota - remaining_amount: ' . $remaining_amount);
               $undistributed = 0;
               if (isset($remaining_to_distribute) && $remaining_to_distribute > 0) {
                   $undistributed = max(0, $remaining_to_distribute);
               } else if ($remaining_amount > 0) {
                   // Fallback: usar remaining_amount si no hay remaining_to_distribute
                   $undistributed = max(0, $remaining_amount);
               }
               if ($undistributed > 0) {
                   log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Última cuota detectada y saldo sin distribuir: $' . $undistributed . ' - generando nueva cuota con mora');
                   
                   // Obtener información del préstamo para calcular la mora
                   $loan = $this->loans_m->get_loan($loan_id);
                   if ($loan) {
                       // Calcular tasa de mora: 1.5 × tasa de interés corriente
                       $current_interest_rate = $loan->interest_amount / 100.0;
                       $penalty_rate = 1.5 * $current_interest_rate;
                       $penalty_amount = round($undistributed * $penalty_rate, 2);
                       $new_quota_total = $undistributed + $penalty_amount;
                       
                       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Fallback - Cálculo de mora: tasa=' . ($penalty_rate * 100) . '%, mora=$' . $penalty_amount . ', nueva cuota total=$' . $new_quota_total);
                       
                       $new_quota_id = $this->generate_additional_quota_for_remaining($loan_id, $new_quota_total, $user_id);
                       if ($new_quota_id) {
                           $additional_quota_generated = true;
                           $remaining_balance_distributed = $new_quota_total;
                           log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Fallback - Nueva cuota con mora generada - ID: ' . $new_quota_id);
                       }
                   }
               }
           }

           // Crear array de resultados para compatibilidad
           foreach ($payment_distribution as $payment) {
               $processed[] = [
                   'quota_id' => $payment['quota_id'],
                   'amount' => $payment['total_paid'],
                   'type' => $custom_payment_type,
                   'interest_paid' => $payment['interest_paid'],
                   'capital_paid' => $payment['capital_paid'],
                   'total_paid' => $payment['total_paid'],
                   'interest_applied' => $payment['interest_paid'],
                   'capital_applied' => $payment['capital_paid'],
                   'status_changed' => $payment['status_changed']
               ];
           }

           // Verificar que todas las cuotas seleccionadas fueron procesadas
           $processed_quota_ids = array_column($processed, 'quota_id');
           $missing_quotas = array_diff($quota_ids, $processed_quota_ids);
           if (!empty($missing_quotas)) {
               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Cuotas no procesadas: ' . json_encode($missing_quotas));
           } else {
               log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Todas las cuotas seleccionadas fueron procesadas correctamente');
           }

           // Registrar pago personalizado en la tabla de pagos
           $payment_record = [
               'loan_id' => $loan_id,
               'loan_item_id' => $processed[0]['quota_id'] ?? null,
               'amount' => $custom_amount,
               'tipo_pago' => 'custom',
               'monto_pagado' => $custom_amount,
               'interest_paid' => array_sum(array_column($processed, 'interest_paid')),
               'capital_paid' => array_sum(array_column($processed, 'capital_paid')),
               'payment_date' => date('Y-m-d H:i:s'),
               'payment_user_id' => $user_id,
               'method' => 'efectivo',
               'notes' => 'Pago personalizado - Tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO') . ' - Desglose: ' . json_encode($processed)
           ];

           // Agregar campos custom_amount y custom_payment_type solo si existen en la tabla
           if ($this->db->field_exists('custom_amount', 'payments')) {
               $payment_record['custom_amount'] = $custom_amount;
           }
           if ($this->db->field_exists('custom_payment_type', 'payments')) {
               $payment_record['custom_payment_type'] = $custom_payment_type;
           }

           log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Registrando pago personalizado - loan_id: ' . $loan_id . ', tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO') . ', monto: ' . $custom_amount);

           $this->db->insert('payments', $payment_record);
           $payment_id = $this->db->insert_id();

           if (!$payment_id) {
               log_message('error', 'CUSTOM_PAYMENT_PARTIAL: ERROR - Falló registro de pago personalizado para loan_id: ' . $loan_id);
           } else {
               log_message('info', 'CUSTOM_PAYMENT_PARTIAL: Pago personalizado registrado exitosamente - ID: ' . $payment_id . ', tipo: ' . ($is_partial ? 'PARCIAL' : 'COMPLETO'));
           }
       }

       log_message('debug', 'CUSTOM_PAYMENT_PARTIAL: Procesamiento completado - tipo: ' . $custom_payment_type . ', parcial: ' . ($is_partial ? 'SÍ' : 'NO') . ', saldo_distribuido: ' . $remaining_balance_distributed);

       return $processed;
   }

   /**
    * Libera cuotas subsiguientes después de liquidación anticipada CONDONANDO LAS DEUDAS
    */
   private function release_subsequent_quotas($loan_id, $paid_quota_num, $user_id, $waiver_type = 'liquidation') {
     log_message('debug', 'RELEASE_SUBSEQUENT_QUOTAS: Iniciando CONDONACIÓN de cuotas posteriores a ' . $paid_quota_num . ' para préstamo ' . $loan_id . ' - tipo: ' . $waiver_type);

     // Obtener todas las cuotas posteriores a la pagada
     $this->db->where('loan_id', $loan_id);
     $this->db->where('num_quota >', $paid_quota_num);
     $this->db->where('status', 1); // Solo cuotas pendientes
     $subsequent_quotas = $this->db->get('loan_items')->result();

     $total_capital_condonado = 0;
     $total_interes_condonado = 0;
     $released_count = 0;

     foreach ($subsequent_quotas as $quota) {
       // Calcular montos pendientes de esta cuota
       $capital_pendiente = $quota->capital_amount - ($quota->capital_paid ?? 0);
       $interes_pendiente = $quota->interest_amount - ($quota->interest_paid ?? 0);

       // Sumar a totales condonados
       $total_capital_condonado += $capital_pendiente;
       $total_interes_condonado += $interes_pendiente;

       // Marcar cuota como CONDONADA con los montos reales condonados
       $update_data = [
         'status' => 0, // No pendiente
         'balance' => 0, // Sin saldo
         'capital_paid' => $capital_pendiente, // Monto condonado (no pagado por el cliente)
         'interest_paid' => $interes_pendiente, // Monto condonado (no pagado por el cliente)
         'paid_by' => $user_id,
         'pay_date' => date('Y-m-d H:i:s'),
         'extra_payment' => 3 // Marcar como cuota CONDONADA
       ];

       // Agregar campo específico para condonación anticipada
       if ($waiver_type === 'early_total') {
         $update_data['extra_payment'] = 3; // Marcar como condonada anticipadamente
       }

       $this->payments_m->update_quota($update_data, $quota->id);

       log_message('debug', 'RELEASE_SUBSEQUENT_QUOTAS: Cuota ' . $quota->id . ' (num_quota: ' . $quota->num_quota . ') CONDONADA - Capital: ' . $capital_pendiente . ', Interés: ' . $interes_pendiente . ' - tipo: ' . $waiver_type);
       $released_count++;
     }

     log_message('debug', 'RELEASE_SUBSEQUENT_QUOTAS: CONDONACIÓN COMPLETADA - Total cuotas condonadas: ' . $released_count . ', Capital condonado: ' . $total_capital_condonado . ', Interés condonado: ' . $total_interes_condonado . ' - tipo: ' . $waiver_type);

     // Retornar los montos condonados para actualizar el registro de pago
     return [
       'count' => $released_count,
       'capital_condonado' => $total_capital_condonado,
       'interes_condonado' => $total_interes_condonado,
       'total_condonado' => $total_capital_condonado + $total_interes_condonado
     ];
   }

   /**
    * Procesa PAGO TOTAL ANTICIPADO CON CONDONACIÓN - REDISEÑADO
    * Nuevo enfoque unificado y claro:
    * 1. Calcula el monto que paga el cliente (balance de la cuota seleccionada)
    * 2. Calcula el monto total condonado (todas las cuotas posteriores)
    * 3. Registra el pago único del cliente
    * 4. Marca cuotas posteriores como condonadas con montos reales
    */
   private function process_early_total_payment($quota_ids, $user_id) {
     $processed = [];
     log_message('info', 'EARLY_TOTAL_PAYMENT: ========== INICIANDO PAGO TOTAL ANTICIPADO CON CONDONACIÓN (REDISEÑADO) ========== - user_id: ' . $user_id);

     // Validar entrada
     if (empty($quota_ids) || !is_array($quota_ids) || count($quota_ids) !== 1) {
       log_message('error', 'EARLY_TOTAL_PAYMENT: ERROR - Se debe seleccionar exactamente una cuota');
       return $processed;
     }

     $selected_quota_id = $quota_ids[0];
     $selected_quota = $this->payments_m->get_loan_item($selected_quota_id);
     if (!$selected_quota) {
       log_message('error', 'EARLY_TOTAL_PAYMENT: ERROR - Cuota seleccionada no encontrada: ' . $selected_quota_id);
       return $processed;
     }

     $loan_id = $selected_quota->loan_id;
     $selected_num_quota = $selected_quota->num_quota;

     log_message('info', 'EARLY_TOTAL_PAYMENT: Procesando cuota #' . $selected_num_quota . ' del préstamo ' . $loan_id);

     // ========== CÁLCULO DE MONTOS ==========

     // 1. MONTO QUE PAGA EL CLIENTE: Balance + Monto de cuota de la cuota seleccionada
     $customer_payment_amount = ($selected_quota->balance ?? 0) + ($selected_quota->fee_amount ?? 0);
     $interest_pending_selected = $selected_quota->interest_amount - ($selected_quota->interest_paid ?? 0);
     $capital_pending_selected = $selected_quota->capital_amount - ($selected_quota->capital_paid ?? 0);

     log_message('info', 'EARLY_TOTAL_PAYMENT: Monto que paga el cliente: $' . number_format($customer_payment_amount, 2, '.', ',') . ' (balance: ' . ($selected_quota->balance ?? 0) . ' + fee_amount: ' . ($selected_quota->fee_amount ?? 0) . ')');

     // 2. MONTO TOTAL CONDONADO: Todas las cuotas posteriores pendientes (suma de fee_amount)
     $this->db->select('SUM(COALESCE(fee_amount, 0)) as total_fee_amount_waived, COUNT(*) as total_quotas');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->where('num_quota >', $selected_num_quota);
     $this->db->where('status', 1); // Solo cuotas pendientes
     $waiver_calculation = $this->db->get()->row();

     $total_amount_waived = $waiver_calculation->total_fee_amount_waived ?? 0;
     $total_quotas_waived = $waiver_calculation->total_quotas ?? 0;

     // Calcular intereses y capital condonados por separado para el registro
     $this->db->select('SUM(COALESCE(interest_amount - COALESCE(interest_paid, 0), 0)) as total_interest_pending, SUM(COALESCE(capital_amount - COALESCE(capital_paid, 0), 0)) as total_capital_pending');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->where('num_quota >', $selected_num_quota);
     $this->db->where('status', 1);
     $waiver_details = $this->db->get()->row();

     $total_interest_waived = $waiver_details->total_interest_pending ?? 0;
     $total_capital_waived = $waiver_details->total_capital_pending ?? 0;

     log_message('info', 'EARLY_TOTAL_PAYMENT: Monto total condonado: $' . number_format($total_amount_waived, 2, '.', ',') . ' (' . $total_quotas_waived . ' cuotas) - Interés condonado: $' . number_format($total_interest_waived, 2, '.', ',') . ', Capital condonado: $' . number_format($total_capital_waived, 2, '.', ',') . ')');

     // ========== PROCESAMIENTO ==========

     // 1. MARCAR CUOTA SELECCIONADA COMO PAGADA
     $update_selected = [
       'status' => 0,
       'capital_paid' => ($selected_quota->capital_paid ?? 0) + $capital_pending_selected,
       'interest_paid' => ($selected_quota->interest_paid ?? 0) + $interest_pending_selected,
       'balance' => 0,
       'paid_by' => $user_id,
       'pay_date' => date('Y-m-d H:i:s'),
       'extra_payment' => 0 // Pagada normalmente, no condonada
     ];

     $this->payments_m->update_quota($update_selected, $selected_quota_id);
     log_message('info', 'EARLY_TOTAL_PAYMENT: Cuota seleccionada #' . $selected_num_quota . ' marcada como PAGADA');

     // 2. MARCAR CUOTAS POSTERIORES COMO CONDONADAS
     if ($total_quotas_waived > 0) {
       $this->db->where('loan_id', $loan_id);
       $this->db->where('num_quota >', $selected_num_quota);
       $this->db->where('status', 1);

       $update_waived = [
         'status' => 0, // Cerradas
         'balance' => 0, // Sin saldo
         'paid_by' => $user_id,
         'pay_date' => date('Y-m-d H:i:s'),
         'extra_payment' => 3 // Marcadas como condonadas
       ];

       $this->db->update('loan_items', $update_waived);
       log_message('info', 'EARLY_TOTAL_PAYMENT: ' . $total_quotas_waived . ' cuotas posteriores marcadas como CONDONADAS');
     }

     // ========== REGISTRO EN BASE DE DATOS ==========

     $payment_record = [
       'loan_id' => $loan_id,
       'loan_item_id' => $selected_quota_id,
       'amount' => $customer_payment_amount,
       'tipo_pago' => 'early_total',
       'monto_pagado' => $customer_payment_amount,
       'interest_paid' => $interest_pending_selected,
       'capital_paid' => $capital_pending_selected,
       'payment_date' => date('Y-m-d H:i:s'),
       'payment_user_id' => $user_id,
       'method' => 'efectivo',
       'notes' => 'PAGO TOTAL ANTICIPADO CON CONDONACIÓN - Cliente paga: $' . number_format($customer_payment_amount, 2, '.', ',') . ' por cuota #' . $selected_num_quota . '. Condonado: $' . number_format($total_amount_waived, 2, '.', ',') . ' en ' . $total_quotas_waived . ' cuotas posteriores.'
     ];

     // Agregar campos custom_amount y custom_payment_type solo si existen en la tabla
     if ($this->db->field_exists('custom_amount', 'payments')) {
       $payment_record['custom_amount'] = null;
     }
     if ($this->db->field_exists('custom_payment_type', 'payments')) {
       $payment_record['custom_payment_type'] = null;
     }

     $this->db->insert('payments', $payment_record);
     $payment_id = $this->db->insert_id();

     log_message('info', 'EARLY_TOTAL_PAYMENT: Pago registrado en BD - ID: ' . $payment_id);

     // ========== RESULTADO PARA TICKET ==========

     // CORRECCIÓN: Incluir TODOS los campos necesarios para el ticket
     $processed[] = [
       'quota_id' => $selected_quota_id,
       'loan_id' => $loan_id,
       'amount' => $customer_payment_amount,
       'type' => 'early_total',
       'interest_paid' => $interest_pending_selected,
       'capital_paid' => $capital_pending_selected,
       'total_paid' => $customer_payment_amount,
       'waived_amount' => $total_amount_waived,
       'capital_waived' => $total_capital_waived,
       'interest_waived' => $total_interest_waived,
       'quotas_waived' => $total_quotas_waived,
       'selected_quota_num' => $selected_num_quota,
       'num_quota' => $selected_num_quota,
       'fee_amount' => $selected_quota->fee_amount,
       'interest_amount' => $selected_quota->interest_amount,
       'capital_amount' => $selected_quota->capital_amount,
       'balance' => 0, // Después del pago
       'date' => $selected_quota->date,
       'status' => 0, // Pagada
       'extra_payment' => 0,
       'payment_type' => 'paid'
     ];

     // Agregar cuotas condonadas al processed array para el ticket
     if ($total_quotas_waived > 0) {
       // Obtener cuotas condonadas para incluir en processed_quotas
       $this->db->select('*');
       $this->db->from('loan_items');
       $this->db->where('loan_id', $loan_id);
       $this->db->where('num_quota >', $selected_num_quota);
       $this->db->where('status', 0); // Cerradas (condonadas)
       $this->db->where('extra_payment', 3); // Marcadas como condonadas
       $waived_quotas = $this->db->get()->result();

       foreach ($waived_quotas as $waived_quota) {
         $interest_pending_waived = $waived_quota->interest_amount - ($waived_quota->interest_paid ?? 0);
         $capital_pending_waived = $waived_quota->capital_amount - ($waived_quota->capital_paid ?? 0);

         $processed[] = [
           'quota_id' => $waived_quota->id,
           'loan_id' => $loan_id,
           'amount' => $waived_quota->fee_amount,
           'type' => 'early_total',
           'interest_paid' => $interest_pending_waived,
           'capital_paid' => $capital_pending_waived,
           'total_paid' => $waived_quota->fee_amount,
           'waived_amount' => $waived_quota->fee_amount,
           'capital_waived' => $capital_pending_waived,
           'interest_waived' => $interest_pending_waived,
           'quotas_waived' => 1,
           'selected_quota_num' => $selected_num_quota,
           'num_quota' => $waived_quota->num_quota,
           'fee_amount' => $waived_quota->fee_amount,
           'interest_amount' => $waived_quota->interest_amount,
           'capital_amount' => $waived_quota->capital_amount,
           'balance' => 0, // Condonada
           'date' => $waived_quota->date,
           'status' => 0, // Cerrada
           'extra_payment' => 3, // Condonada
           'payment_type' => 'waived'
         ];
       }
     }

     // Verificar cierre del préstamo
     $this->check_and_close_loan($loan_id, $this->input->post('customer_id'));

     log_message('info', 'EARLY_TOTAL_PAYMENT: ========== PROCESAMIENTO COMPLETADO ========== - Cliente paga: $' . number_format($customer_payment_amount, 2, '.', ',') . ', Condonado: $' . number_format($total_amount_waived, 2, '.', ','));

     return $processed;
   }

   /**
    * Calcula el total de montos procesados
    */
   private function calculate_total_from_processed($processed_quotas) {
     $total = 0;
     foreach ($processed_quotas as $processed) {
       $total += $processed['amount'];
     }
     return $total;
   }

   /**
    * Método de respaldo para procesar pagos cuando processed_quotas está vacío
    */
   private function process_fallback_payment($payment_data, $pending_quota_ids) {
       log_message('debug', 'FALLBACK_PAYMENT: Iniciando procesamiento de respaldo - tipo_pago: ' . $payment_data['tipo_pago']);

       $processed = [];
       $tipo_pago = $payment_data['tipo_pago'];
       $user_id = $payment_data['user_id'];
       $loan_id = $payment_data['loan_id'];

       // Procesar cada cuota pendiente según el tipo de pago
       foreach ($pending_quota_ids as $quota_id) {
           $quota_info = $this->payments_m->get_loan_item($quota_id);
           if (!$quota_info || $quota_info->status != 1) {
               continue;
           }

           // Aplicar lógica básica según tipo de pago
           switch ($tipo_pago) {
               case 'full':
                   // Pago completo
                   $update_data = [
                       'status' => 0,
                       'paid_by' => $user_id,
                       'pay_date' => date('Y-m-d H:i:s'),
                       'capital_paid' => $quota_info->capital_amount,
                       'interest_paid' => $quota_info->interest_amount,
                       'balance' => 0
                   ];
                   $this->payments_m->update_quota($update_data, $quota_id);

                   $processed[] = [
                       'quota_id' => $quota_id,
                       'loan_id' => $loan_id,
                       'amount' => $quota_info->fee_amount,
                       'type' => 'full',
                       'interest_paid' => $quota_info->interest_amount,
                       'capital_paid' => $quota_info->capital_amount,
                       'total_paid' => $quota_info->fee_amount,
                       'num_quota' => $quota_info->num_quota,
                       'fee_amount' => $quota_info->fee_amount,
                       'interest_amount' => $quota_info->interest_amount,
                       'capital_amount' => $quota_info->capital_amount,
                       'balance' => 0,
                       'date' => $quota_info->date,
                       'status' => 0,
                       'extra_payment' => 0,
                       'payment_type' => 'paid'
                   ];
                   break;

               case 'total':
                   // Pago total de una sola cuota
                   if (count($pending_quota_ids) === 1) {
                       $update_data = [
                           'status' => 0,
                           'paid_by' => $user_id,
                           'pay_date' => date('Y-m-d H:i:s'),
                           'capital_paid' => $quota_info->capital_amount,
                           'interest_paid' => $quota_info->interest_amount,
                           'balance' => 0
                       ];
                       $this->payments_m->update_quota($update_data, $quota_id);

                       $processed[] = [
                           'quota_id' => $quota_id,
                           'loan_id' => $loan_id,
                           'amount' => $quota_info->fee_amount,
                           'type' => 'total',
                           'interest_paid' => $quota_info->interest_amount,
                           'capital_paid' => $quota_info->capital_amount,
                           'total_paid' => $quota_info->fee_amount,
                           'num_quota' => $quota_info->num_quota,
                           'fee_amount' => $quota_info->fee_amount,
                           'interest_amount' => $quota_info->interest_amount,
                           'capital_amount' => $quota_info->capital_amount,
                           'balance' => 0,
                           'date' => $quota_info->date,
                           'status' => 0,
                           'extra_payment' => 0,
                           'payment_type' => 'paid'
                       ];
                   }
                   break;

               default:
                   log_message('debug', 'FALLBACK_PAYMENT: Tipo de pago no soportado en fallback: ' . $tipo_pago);
                   break;
           }
       }

       log_message('debug', 'FALLBACK_PAYMENT: Procesamiento completado - cuotas procesadas: ' . count($processed));
       return $processed;
   }

   /**
    * Método auxiliar para detectar última cuota y generar nueva cuota con mora
    */
   private function handle_remaining_balance($loan_id, $remaining_amount, $user_id, $is_partial = false, $quota_ids = []) {
       log_message('debug', 'HANDLE_REMAINING_BALANCE: Iniciando detección de última cuota - loan_id: ' . $loan_id . ', remaining_amount: ' . $remaining_amount . ', is_partial: ' . ($is_partial ? 'true' : 'false'));

       if (!$is_partial || $remaining_amount <= 0) {
           log_message('debug', 'HANDLE_REMAINING_BALANCE: Condiciones no cumplidas para generar mora - is_partial: ' . ($is_partial ? 'true' : 'false') . ', remaining_amount: ' . $remaining_amount);
           return;
       }

       // Verificar si alguna cuota procesada es la última del préstamo
       $is_last_installment = false;
       foreach ($quota_ids as $quota_id) {
           if ($this->loans_m->is_last_installment($loan_id, $quota_id)) {
               $is_last_installment = true;
               log_message('debug', 'HANDLE_REMAINING_BALANCE: DETECTADA ÚLTIMA CUOTA en pago parcial - quota_id: ' . $quota_id . ', loan_id: ' . $loan_id);
               break;
           }
       }

       if (!$is_last_installment) {
           log_message('debug', 'HANDLE_REMAINING_BALANCE: No se detectó última cuota, no se genera mora');
           return;
       }

       // Obtener información del préstamo para calcular mora
       $loan = $this->loans_m->get_loan($loan_id);
       if (!$loan) {
           log_message('error', 'HANDLE_REMAINING_BALANCE: Préstamo no encontrado para calcular mora - loan_id: ' . $loan_id);
           return;
       }

       // CORRECCIÓN: Calcular tasa de interés corriente correctamente (interés total / principal)
       $current_interest_rate = $loan->interest_amount / $loan->credit_amount; // tasa decimal
       $penalty_rate = 1.5 * $current_interest_rate; // 1.5x tasa corriente

       // Calcular monto de mora
       $penalty_amount = round($remaining_amount * $penalty_rate, 2);
       $new_quota_total = $remaining_amount + $penalty_amount;

       log_message('debug', 'HANDLE_REMAINING_BALANCE: Cálculo de mora completado - tasa corriente: ' . ($current_interest_rate * 100) . '%, tasa mora: ' . ($penalty_rate * 100) . '%, mora calculada: $' . $penalty_amount . ', nueva cuota total: $' . $new_quota_total);

       // Generar nueva cuota con mora
       $new_quota_id = $this->generate_additional_quota_for_remaining($loan_id, $new_quota_total, $user_id);

       if ($new_quota_id) {
           log_message('info', 'HANDLE_REMAINING_BALANCE: Nueva cuota con mora generada exitosamente - ID: ' . $new_quota_id . ', monto: $' . $new_quota_total . ' (saldo: $' . $remaining_amount . ' + mora: $' . $penalty_amount . ')');
       } else {
           log_message('error', 'HANDLE_REMAINING_BALANCE: ERROR al generar nueva cuota con mora para loan_id: ' . $loan_id);
       }
   }

   /**
    * Verifica y corrige cuotas con saldo negativo antes del procesamiento de pagos
    */
   private function check_and_fix_negative_balances($loan_id, $user_id) {
       log_message('debug', 'CHECK_NEGATIVE_BALANCES: ========== INICIANDO VERIFICACIÓN DE SALDOS NEGATIVOS ========== - loan_id: ' . $loan_id . ', user_id: ' . $user_id);

       // Obtener cuotas pendientes del préstamo
       $this->db->select('*');
       $this->db->from('loan_items');
       $this->db->where('loan_id', $loan_id);
       $this->db->where('status', 1); // Solo cuotas pendientes
       $this->db->order_by('num_quota', 'ASC');
       $pending_quotas = $this->db->get()->result();

       log_message('debug', 'CHECK_NEGATIVE_BALANCES: Cuotas pendientes encontradas: ' . count($pending_quotas));

       $negative_balances_fixed = 0;

       foreach ($pending_quotas as $quota) {
           $balance = $quota->balance ?? 0;
           log_message('debug', 'CHECK_NEGATIVE_BALANCES: Verificando cuota ID ' . $quota->id . ' - balance: ' . $balance);

           // Verificar si es la última cuota del préstamo
           $is_last_installment = $this->loans_m->is_last_installment($loan_id, $quota->id);

           if ($balance < 0 && $is_last_installment) {
               log_message('info', 'CHECK_NEGATIVE_BALANCES: DETECTADA CUOTA CON SALDO NEGATIVO EN ÚLTIMA CUOTA - ID: ' . $quota->id . ', balance: ' . $balance . ', loan_id: ' . $loan_id);

               // Obtener información del préstamo para calcular mora
               $loan = $this->loans_m->get_loan($loan_id);
               if (!$loan) {
                   log_message('error', 'CHECK_NEGATIVE_BALANCES: Préstamo no encontrado - loan_id: ' . $loan_id);
                   continue;
               }

               // Calcular tasa de mora: 1.5 × tasa de interés corriente
               $current_interest_rate = $loan->interest_amount / $loan->credit_amount; // tasa decimal
               $penalty_rate = 1.5 * $current_interest_rate;

               // Monto de mora: abs(balance) * penalty_rate
               $penalty_amount = round(abs($balance) * $penalty_rate, 2);

               // Nueva cuota: abs(balance) + penalty_amount
               $new_quota_total = abs($balance) + $penalty_amount;

               log_message('debug', 'CHECK_NEGATIVE_BALANCES: Cálculo de mora - tasa corriente: ' . ($current_interest_rate * 100) . '%, tasa mora: ' . ($penalty_rate * 100) . '%, mora: $' . $penalty_amount . ', nueva cuota total: $' . $new_quota_total);

               // Generar nueva cuota usando generate_additional_quota_for_remaining
               $new_quota_id = $this->generate_additional_quota_for_remaining($loan_id, $new_quota_total, $user_id);

               if ($new_quota_id) {
                   log_message('info', 'CHECK_NEGATIVE_BALANCES: Nueva cuota con mora generada exitosamente - ID: ' . $new_quota_id . ', monto: $' . $new_quota_total . ' (saldo negativo corregido: $' . abs($balance) . ' + mora: $' . $penalty_amount . ')');
                   $negative_balances_fixed++;
               } else {
                   log_message('error', 'CHECK_NEGATIVE_BALANCES: ERROR al generar nueva cuota con mora para cuota ID ' . $quota->id);
               }
           } elseif ($balance < 0 && !$is_last_installment) {
               log_message('debug', 'CHECK_NEGATIVE_BALANCES: Cuota con saldo negativo pero NO es la última - ID: ' . $quota->id . ', balance: ' . $balance . ' (se ignora)');
           } else {
               log_message('debug', 'CHECK_NEGATIVE_BALANCES: Cuota sin saldo negativo - ID: ' . $quota->id . ', balance: ' . $balance . ', is_last: ' . ($is_last_installment ? 'SÍ' : 'NO'));
           }
       }

       log_message('info', 'CHECK_NEGATIVE_BALANCES: ========== VERIFICACIÓN COMPLETADA ========== - saldos negativos corregidos: ' . $negative_balances_fixed);

       return $negative_balances_fixed;
   }

   /**
    * Genera una nueva cuota cuando el pago personalizado es inferior al requerido en la última cuota
    */
   private function generate_additional_quota_for_remaining($loan_id, $remaining_amount, $user_id)
   {
     log_message('debug', 'GENERATE_ADDITIONAL_QUOTA: Iniciando generación de cuota adicional para loan_id=' . $loan_id . ', remaining_amount=' . $remaining_amount);

     // Obtener información del préstamo
     $loan = $this->loans_m->get_loan($loan_id);
     if (!$loan) {
       log_message('error', 'GENERATE_ADDITIONAL_QUOTA: Préstamo no encontrado: ' . $loan_id);
       return false;
     }

     // Obtener la última cuota para determinar la fecha y número de la siguiente
     $this->db->select('num_quota, date, fee_amount, interest_amount, capital_amount');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->order_by('num_quota', 'DESC');
     $this->db->limit(1);
     $last_quota = $this->db->get()->row();

     if (!$last_quota) {
       log_message('error', 'GENERATE_ADDITIONAL_QUOTA: No se encontró la última cuota para loan_id=' . $loan_id);
       return false;
     }

     $next_quota_num = $last_quota->num_quota + 1;
     $last_date = new DateTime($last_quota->date);

     // Determinar frecuencia de pago
     $date_interval = $this->get_payment_date_interval($loan->payment_m);

     // Calcular fecha de la nueva cuota
     $next_date = clone $last_date;
     $next_date->add($date_interval);

     // Usar el remaining_amount como fee_amount para la nueva cuota (incluye mora)
     $fee_amount = $remaining_amount;

     // Para la nueva cuota, mantener la proporción estándar de interés/capital
     $interest_ratio = $last_quota->interest_amount / $last_quota->fee_amount;
     $capital_ratio = $last_quota->capital_amount / $last_quota->fee_amount;

     $interest_amount = round($fee_amount * $interest_ratio, 2);
     $capital_amount = round($fee_amount * $capital_ratio, 2);

     // Crear nueva cuota
     $new_quota = [
       'loan_id' => $loan_id,
       'date' => $next_date->format('Y-m-d'),
       'num_quota' => $next_quota_num,
       'fee_amount' => $fee_amount,
       'interest_amount' => $interest_amount,
       'capital_amount' => $capital_amount,
       'balance' => $capital_amount, // Balance inicial es el capital total
       'extra_payment' => 0,
       'status' => 1, // Pendiente
       'interest_paid' => 0,
       'capital_paid' => 0
     ];

     // Insertar en base de datos
     $this->db->insert('loan_items', $new_quota);
     $new_quota_id = $this->db->insert_id();

     log_message('debug', 'GENERATE_ADDITIONAL_QUOTA: Nueva cuota creada - ID: ' . $new_quota_id . ', num_quota: ' . $new_quota['num_quota'] . ', amount: ' . $fee_amount . ', fecha: ' . $new_quota['date']);

     return $new_quota_id;
   }

   /**
    * Genera cuotas adicionales cuando el pago personalizado excede el balance actual
    */
   private function generate_additional_quotas($loan_id, $amount_to_cover, $user_id, $tipo_pago, $payment_notes)
   {
     log_message('debug', 'GENERATE_ADDITIONAL_QUOTAS: Iniciando generación de cuotas adicionales para loan_id=' . $loan_id . ', amount_to_cover=' . $amount_to_cover);

     // Obtener información del préstamo
     $loan = $this->loans_m->get_loan($loan_id);
     if (!$loan) {
       log_message('error', 'GENERATE_ADDITIONAL_QUOTAS: Préstamo no encontrado: ' . $loan_id);
       return false;
     }

     // Obtener la última cuota para determinar la fecha de la siguiente
     $this->db->select('num_quota, date');
     $this->db->from('loan_items');
     $this->db->where('loan_id', $loan_id);
     $this->db->order_by('num_quota', 'DESC');
     $this->db->limit(1);
     $last_quota = $this->db->get()->row();

     if (!$last_quota) {
       log_message('error', 'GENERATE_ADDITIONAL_QUOTAS: No se encontró la última cuota para loan_id=' . $loan_id);
       return false;
     }

     $next_quota_num = $last_quota->num_quota + 1;
     $last_date = new DateTime($last_quota->date);

     // Determinar frecuencia de pago
     $date_interval = $this->get_payment_date_interval($loan->payment_m);

     // Calcular número de cuotas adicionales necesarias
     // Usamos el monto de cuota promedio del préstamo como base
     $average_quota_amount = $loan->fee_amount ?: ($loan->credit_amount / $loan->num_fee);

     // Si el monto a cubrir es mayor que el promedio, crear múltiples cuotas
     $num_additional_quotas = ceil($amount_to_cover / $average_quota_amount);

     log_message('debug', 'GENERATE_ADDITIONAL_QUOTAS: average_quota_amount=' . $average_quota_amount . ', num_additional_quotas=' . $num_additional_quotas);

     // Generar cuotas adicionales
     $remaining_amount = $amount_to_cover;
     $generated_quotas = [];

     for ($i = 0; $i < $num_additional_quotas && $remaining_amount > 0; $i++) {
       // Calcular fecha de la nueva cuota
       $current_date = clone $last_date;
       for ($j = 0; $j <= $i; $j++) {
         $current_date->add($date_interval);
       }

       // Calcular montos para esta cuota
       $quota_amount = min($average_quota_amount, $remaining_amount);

       // Para cuotas adicionales, asumimos distribución 80% capital, 20% interés (o ajustar según lógica)
       $interest_amount = $quota_amount * 0.20; // 20% interés
       $capital_amount = $quota_amount * 0.80;  // 80% capital
       $balance = $remaining_amount - $capital_amount;

       // Crear nueva cuota
       $new_quota = [
         'loan_id' => $loan_id,
         'date' => $current_date->format('Y-m-d'),
         'num_quota' => $next_quota_num + $i,
         'fee_amount' => $quota_amount,
         'interest_amount' => $interest_amount,
         'capital_amount' => $capital_amount,
         'balance' => $balance,
         'extra_payment' => 0,
         'pay_date' => date('Y-m-d H:i:s'),
         'status' => 1 // Pendiente
       ];

       // Insertar en base de datos
       $this->db->insert('loan_items', $new_quota);
       $new_quota_id = $this->db->insert_id();

       log_message('debug', 'GENERATE_ADDITIONAL_QUOTAS: Nueva cuota creada - ID: ' . $new_quota_id . ', num_quota: ' . $new_quota['num_quota'] . ', amount: ' . $quota_amount);

       $generated_quotas[] = $new_quota_id;
       $remaining_amount -= $capital_amount;

       // Si es la última cuota adicional, ajustar el balance a 0
       if ($remaining_amount <= 0 && $i == $num_additional_quotas - 1) {
         $this->db->where('id', $new_quota_id);
         $this->db->update('loan_items', ['balance' => 0]);
       }
     }

     log_message('debug', 'GENERATE_ADDITIONAL_QUOTAS: Cuotas adicionales generadas: ' . count($generated_quotas));
     return $generated_quotas;
   }

   /**
    * Obtiene el intervalo de fechas según la frecuencia de pago
    */
   private function get_payment_date_interval($payment_frequency)
   {
     switch ($payment_frequency) {
       case 'diario':
         return new DateInterval('P1D');
       case 'semanal':
         return new DateInterval('P7D');
       case 'quincenal':
         return new DateInterval('P15D');
       case 'mensual':
         return new DateInterval('P1M');
       default:
         return new DateInterval('P30D'); // Default mensual
     }
   }

   /**
    * Procesa seguimiento de cobranza - Agrega nueva cuota al préstamo
    */
   public function process_collection_followup()
   {
       log_message('debug', 'COLLECTION_FOLLOWUP: ========== INICIANDO SEGUIMIENTO DE COBRANZA ==========');

       $loan_id = $this->input->post('loan_id');
       $customer_id = $this->input->post('customer_id');
       $new_quota_status = $this->input->post('new_quota_status') ?? 'pending'; // 'pending' o 'paid'
       $user_id = $this->input->post('user_id');
       $notes = $this->input->post('notes') ?? 'Seguimiento de cobranza - Nueva cuota generada';

       try {
           // Validar datos de entrada
           if (empty($loan_id) || !is_numeric($loan_id)) {
               throw new Exception('ID del préstamo inválido');
           }

           if (empty($customer_id) || !is_numeric($customer_id)) {
               throw new Exception('ID del cliente inválido');
           }

           if (empty($user_id) || !is_numeric($user_id)) {
               throw new Exception('ID del usuario inválido');
           }

           if (!in_array($new_quota_status, ['pending', 'paid'])) {
               throw new Exception('Estado de nueva cuota inválido');
           }

           // Verificar que el préstamo existe y está activo
           $loan = $this->loans_m->get_loan($loan_id);
           if (!$loan || $loan->status != 1) {
               throw new Exception('Préstamo no encontrado o no está activo');
           }

           // Calcular saldo pendiente total del préstamo
           $this->db->select('SUM(COALESCE(balance, 0)) as total_balance');
           $this->db->from('loan_items');
           $this->db->where('loan_id', $loan_id);
           $balance_result = $this->db->get()->row();
           $remaining_balance = $balance_result->total_balance ?? 0;

           if ($remaining_balance <= 0) {
               throw new Exception('El préstamo no tiene saldo pendiente');
           }

           log_message('debug', 'COLLECTION_FOLLOWUP: Saldo pendiente calculado: $' . $remaining_balance);

           // Iniciar transacción
           $this->db->trans_begin();

           // Generar nueva cuota con el saldo restante
           $new_quota_id = $this->generate_collection_followup_quota($loan_id, $remaining_balance, $new_quota_status, $user_id, $notes);

           if (!$new_quota_id) {
               throw new Exception('Error al generar nueva cuota de seguimiento');
           }

           // Registrar seguimiento de cobranza
           $this->payments_m->create_collection_tracking($customer_id, [
               'assigned_user_id' => $user_id,
               'status' => 'active',
               'priority' => 'high',
               'notes' => $notes
           ]);

           // Registrar acción de cobranza
           $tracking = $this->payments_m->get_collection_tracking($customer_id);
           if ($tracking) {
               $this->payments_m->log_collection_action($tracking->id, [
                   'action_type' => 'followup',
                   'action_description' => 'Nueva cuota generada por seguimiento de cobranza',
                   'performed_by' => $user_id,
                   'notes' => 'Nueva cuota #' . $new_quota_id . ' generada con saldo $' . number_format($remaining_balance, 2),
                   'next_action_date' => date('Y-m-d H:i:s', strtotime('+7 days')) // Seguimiento en 7 días
               ]);
           }

           // Confirmar transacción
           if ($this->db->trans_status() === FALSE) {
               $this->db->trans_rollback();
               throw new Exception('Error en la transacción de base de datos');
           }

           $this->db->trans_commit();

           log_message('info', 'COLLECTION_FOLLOWUP: Seguimiento completado exitosamente - Nueva cuota ID: ' . $new_quota_id . ', Saldo: $' . $remaining_balance);

           echo json_encode([
               'success' => true,
               'message' => 'Seguimiento de cobranza procesado exitosamente',
               'data' => [
                   'new_quota_id' => $new_quota_id,
                   'remaining_balance' => $remaining_balance,
                   'new_quota_status' => $new_quota_status,
                   'tracking_id' => $tracking ? $tracking->id : null
               ]
           ]);

       } catch (Exception $e) {
           // Revertir transacción en caso de error
           $this->db->trans_rollback();
           log_message('error', 'COLLECTION_FOLLOWUP: Error en seguimiento de cobranza - ' . $e->getMessage());

           echo json_encode([
               'success' => false,
               'error' => $e->getMessage()
           ]);
       }
   }

   /**
    * Genera nueva cuota para seguimiento de cobranza
    */
   private function generate_collection_followup_quota($loan_id, $remaining_balance, $status, $user_id, $notes)
   {
       log_message('debug', 'GENERATE_COLLECTION_QUOTA: Generando nueva cuota para loan_id=' . $loan_id . ', balance=' . $remaining_balance);

       // Obtener información del préstamo
       $loan = $this->loans_m->get_loan($loan_id);
       if (!$loan) {
           log_message('error', 'GENERATE_COLLECTION_QUOTA: Préstamo no encontrado: ' . $loan_id);
           return false;
       }

       // Obtener la última cuota para determinar la fecha y número de la siguiente
       $this->db->select('num_quota, date, fee_amount, interest_amount, capital_amount');
       $this->db->from('loan_items');
       $this->db->where('loan_id', $loan_id);
       $this->db->order_by('num_quota', 'DESC');
       $this->db->limit(1);
       $last_quota = $this->db->get()->row();

       if (!$last_quota) {
           log_message('error', 'GENERATE_COLLECTION_QUOTA: No se encontró la última cuota para loan_id=' . $loan_id);
           return false;
       }

       $next_quota_num = $last_quota->num_quota + 1;
       $last_date = new DateTime($last_quota->date);

       // Determinar frecuencia de pago
       $date_interval = $this->get_payment_date_interval($loan->payment_m);

       // Calcular fecha de la nueva cuota
       $next_date = clone $last_date;
       $next_date->add($date_interval);

       // Para la nueva cuota de cobranza, usar el saldo restante como capital
       // Calcular interés basado en el saldo restante
       $interest_rate = $loan->interest_amount / 100; // Convertir a decimal
       $interest_amount = round($remaining_balance * $interest_rate, 2);
       $fee_amount = $remaining_balance + $interest_amount;

       // Crear nueva cuota
       $new_quota = [
           'loan_id' => $loan_id,
           'date' => $next_date->format('Y-m-d'),
           'num_quota' => $next_quota_num,
           'fee_amount' => $fee_amount,
           'interest_amount' => $interest_amount,
           'capital_amount' => $remaining_balance,
           'balance' => $remaining_balance, // Balance inicial es el capital total
           'extra_payment' => 0,
           'status' => ($status === 'paid') ? 0 : 1, // 0 = pagada, 1 = pendiente
           'interest_paid' => ($status === 'paid') ? $interest_amount : 0,
           'capital_paid' => ($status === 'paid') ? $remaining_balance : 0,
           'pay_date' => ($status === 'paid') ? date('Y-m-d H:i:s') : null,
           'paid_by' => ($status === 'paid') ? $user_id : null,
           'payment_desc' => $notes
       ];

       // Insertar en base de datos
       $this->db->insert('loan_items', $new_quota);
       $new_quota_id = $this->db->insert_id();

       if ($new_quota_id) {
           log_message('debug', 'GENERATE_COLLECTION_QUOTA: Nueva cuota creada - ID: ' . $new_quota_id . ', num_quota: ' . $new_quota['num_quota'] . ', amount: ' . $fee_amount . ', fecha: ' . $new_quota['date'] . ', status: ' . $new_quota['status']);

           // Si la cuota se marca como pagada, registrar el pago
           if ($status === 'paid') {
               $payment_data = [
                   'loan_id' => $loan_id,
                   'loan_item_id' => $new_quota_id,
                   'amount' => $fee_amount,
                   'tipo_pago' => 'collection_followup',
                   'monto_pagado' => $fee_amount,
                   'interest_paid' => $interest_amount,
                   'capital_paid' => $remaining_balance,
                   'payment_date' => date('Y-m-d H:i:s'),
                   'payment_user_id' => $user_id,
                   'method' => 'efectivo',
                   'notes' => $notes . ' - Cuota generada por seguimiento de cobranza'
               ];

               $this->db->insert('payments', $payment_data);
               log_message('debug', 'GENERATE_COLLECTION_QUOTA: Pago registrado para nueva cuota - ID: ' . $this->db->insert_id());
           }
       } else {
           log_message('error', 'GENERATE_COLLECTION_QUOTA: Error al insertar nueva cuota');
       }

       return $new_quota_id;
   }

   public function process_payment()
   {
     $quota_id = $this->input->post('quota_id');
     $loan_id = $this->input->post('loan_id');

     if (!$quota_id || !$loan_id) {
       echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
       return;
     }

     // Obtener información de la cuota
     $this->db->where('id', $quota_id);
     $quota = $this->db->get('loan_items')->row();

     if (!$quota) {
       echo json_encode(['success' => false, 'error' => 'Cuota no encontrada']);
       return;
     }

     if ($quota->status == 0) {
       echo json_encode(['success' => false, 'error' => 'Esta cuota ya está pagada']);
       return;
     }

     // Procesar el pago
     $payment_data = [
       'status' => 0, // Pagado
       'pay_date' => date('Y-m-d H:i:s'),
       'paid_by' => $this->session->userdata('user_id') ?? null
     ];

     $this->db->where('id', $quota_id);
     $update_result = $this->db->update('loan_items', $payment_data);

     if ($update_result) {
       // Log del pago procesado
       log_message('info', 'Pago procesado - Cuota ID: ' . $quota_id . ', Préstamo ID: ' . $loan_id . ', Monto: ' . $quota->fee_amount);

       echo json_encode(['success' => true, 'message' => 'Pago procesado exitosamente']);
     } else {
       echo json_encode(['success' => false, 'error' => 'Error al procesar el pago']);
     }
   }

 }

/* End of file Payments.php */
/* Location: ./application/controllers/admin/Payments.php */


