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
    $data['users'] = $this->user_m->get_active_users();
    $data['subview'] = 'admin/payments/edit';
    $this->load->view('admin/_main_layout', $data);
  }

  function ajax_searchCst()
  {
    log_message('debug', 'Iniciando ajax_searchCst');
    $dni = $this->input->post('dni');
    $suggest = $this->input->post('suggest') == '1';
    log_message('debug', 'DNI recibido: ' . $dni . ', suggest: ' . ($suggest ? 'true' : 'false'));

    try {
      $cst = $this->payments_m->get_searchCst($dni, $suggest);
      log_message('debug', 'Cliente encontrado: ' . json_encode($cst));

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
        }
        $search_data = ['cst' => $cst, 'quotas' => $quota_data, 'current_user' => $current_user];
      }

      log_message('debug', 'Datos de respuesta: ' . json_encode($search_data));

      echo json_encode($search_data);
    } catch (Exception $e) {
      log_message('error', 'Error en ajax_searchCst: ' . $e->getMessage());
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  function ajax_get_quotas()
  {
    $loan_id = $this->input->post('loan_id');
    log_message('debug', 'Obteniendo cuotas para loan_id: ' . $loan_id);

    try {
      $quotas = $this->payments_m->get_quotasCst($loan_id);
      log_message('debug', 'Cuotas obtenidas: ' . json_encode($quotas));

      echo json_encode(['quotas' => $quotas]);
    } catch (Exception $e) {
      log_message('error', 'Error en ajax_get_quotas: ' . $e->getMessage());
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  function ticket()
    {
      log_message('debug', 'TICKET: ========== INICIO PROCESAMIENTO TICKET ==========');
      $data['name_cst'] = $this->input->post('name_cst');
     $data['coin'] = $this->input->post('coin');
     $data['loan_id'] = $this->input->post('loan_id');
     $user_id = $this->input->post('user_id');
     $tipo_pago = $this->input->post('tipo_pago');
     $custom_amount = $this->input->post('custom_amount');
     $custom_payment_type = $this->input->post('custom_payment_type');
     $payment_description = $this->input->post('payment_description');

     // Agregar datos nuevos a la data del ticket
     $data['tipo_pago'] = $tipo_pago;
     $data['custom_amount'] = $custom_amount;
     $data['custom_payment_type'] = $custom_payment_type;
     $data['payment_description'] = $payment_description;

     log_message('debug', 'TICKET: Datos básicos - name_cst: ' . $data['name_cst'] . ', coin: ' . $data['coin'] . ', loan_id: ' . $data['loan_id'] . ', user_id: ' . $user_id . ', tipo_pago: ' . $tipo_pago . ', custom_amount: ' . $custom_amount . ', custom_payment_type: ' . $custom_payment_type . ', payment_description: ' . $payment_description);

     // Validación del campo tipo_pago
     $valid_payment_types = ['full', 'interest', 'capital', 'both', 'custom'];
     log_message('debug', 'TICKET: ========== INICIO VALIDACIÓN ==========');
     log_message('debug', 'TICKET: Validando tipo_pago - recibido: ' . $tipo_pago . ', tipos válidos: ' . json_encode($valid_payment_types));
     log_message('debug', 'TICKET: custom_payment_type: ' . $custom_payment_type);
     log_message('debug', 'TICKET: custom_amount: ' . $custom_amount);
     if (!in_array($tipo_pago, $valid_payment_types)) {
       log_message('error', 'TICKET: Tipo de pago inválido: ' . $tipo_pago . ' - tipos válidos: ' . json_encode($valid_payment_types));
       show_error('Tipo de pago inválido. Debe ser: full, interest, capital, both o custom.', 400);
       return;
     }
     log_message('debug', 'TICKET: ========== VALIDACIÓN PASADA ==========');

     // DIAGNÓSTICO: Verificar todos los datos POST
     log_message('debug', 'TICKET: DIAGNÓSTICO - Todos los datos POST: ' . json_encode($this->input->post()));
     log_message('debug', 'TICKET: DIAGNÓSTICO - Campo tipo_pago específicamente: ' . (isset($_POST['tipo_pago']) ? $_POST['tipo_pago'] : 'NO EXISTE'));
     log_message('debug', 'TICKET: DIAGNÓSTICO - Campos del formulario: ' . json_encode(array_keys($this->input->post())));

     // Ensure aux columns exist
     $this->payments_m->ensure_aux_columns();

     $quota_ids = $this->input->post('quota_id');
     $amount = $this->input->post('amount'); // Monto del pago
     log_message('debug', 'TICKET: POST data completo: ' . json_encode($this->input->post()));
     log_message('debug', 'TICKET: quota_ids recibidos: ' . json_encode($quota_ids));
     log_message('debug', 'TICKET: Número de cuotas seleccionadas: ' . (is_array($quota_ids) ? count($quota_ids) : 0));
     log_message('debug', 'TICKET: Tipo de quota_ids: ' . gettype($quota_ids));
     log_message('debug', 'TICKET: Monto del pago: ' . $amount);
     log_message('debug', 'TICKET: Tipo de pago seleccionado: ' . $tipo_pago);

     // Filtrar solo cuotas pendientes (status=1) antes de procesar
     log_message('debug', 'TICKET: ========== INICIANDO FILTRO DE CUOTAS PENDIENTES ==========');
     $pending_quota_ids = [];
     if (is_array($quota_ids) && !empty($quota_ids)) {
       log_message('debug', 'TICKET: Procesando ' . count($quota_ids) . ' cuotas del POST');
       foreach ($quota_ids as $q) {
         log_message('debug', 'TICKET: Verificando cuota ID ' . $q . ' - obteniendo info de BD');
         $quota_info = $this->payments_m->get_loan_item($q);
         log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Info obtenida: ' . json_encode($quota_info));
         if ($quota_info && $quota_info->status == 1) {
           $pending_quota_ids[] = $q;
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Estado: PENDIENTE (se procesará)');
         } else {
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Estado: ' . ($quota_info ? $quota_info->status : 'NO ENCONTRADA') . ' (se ignora)');
         }
       }
     } else {
       log_message('debug', 'TICKET: quota_ids no es array válido o está vacío - no se procesarán cuotas');
     }

     log_message('debug', 'TICKET: Cuotas pendientes filtradas: ' . json_encode($pending_quota_ids));
     log_message('debug', 'TICKET: Número de cuotas pendientes: ' . count($pending_quota_ids));

     // Para pagos personalizados, NO marcar las cuotas como pagadas completamente
     // Solo actualizar si NO es pago personalizado
     if (!($tipo_pago === 'custom' && !empty($custom_amount))) {
       // LOG ADICIONAL: Verificar estado de cuotas antes de actualizar
       if (!empty($pending_quota_ids)) {
         log_message('debug', 'TICKET: ========== ACTUALIZANDO CUOTAS A PAGADAS ==========');
         log_message('debug', 'TICKET: Verificando estado de cuotas ANTES de actualizar:');
         foreach ($pending_quota_ids as $q) {
           $quota_info = $this->payments_m->get_loan_item($q);
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Estado actual: ' . ($quota_info ? $quota_info->status : 'NO ENCONTRADA'));
         }

         log_message('debug', 'TICKET: Iniciando actualización de cuotas...');
         foreach ($pending_quota_ids as $q) {
           log_message('debug', 'TICKET: Actualizando cuota ID: ' . $q . ' con status=0, paid_by=' . $user_id . ', pay_date=' . date('Y-m-d H:i:s'));
           $this->payments_m->update_quota(['status' => 0, 'paid_by' => $user_id, 'pay_date' => date('Y-m-d H:i:s')], $q);
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' actualizada exitosamente');
         }

         // LOG ADICIONAL: Verificar estado de cuotas DESPUÉS de actualizar
         log_message('debug', 'TICKET: Verificando estado de cuotas DESPUÉS de actualizar:');
         foreach ($pending_quota_ids as $q) {
           $quota_info = $this->payments_m->get_loan_item($q);
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' - Estado después: ' . ($quota_info ? $quota_info->status : 'NO ENCONTRADA'));
         }
       } else {
         log_message('debug', 'TICKET: No hay cuotas pendientes para actualizar');
       }
     } else {
       log_message('debug', 'TICKET: Pago personalizado - NO se marcan cuotas como pagadas completamente, solo se procesa el monto personalizado');
       // Para pagos personalizados, NO marcar las cuotas como pagadas completamente
       // El procesamiento del pago personalizado se hace más abajo
     }

     if (!empty($pending_quota_ids)) {
       log_message('debug', 'TICKET: ========== PROCESANDO PAGOS SEGÚN TIPO_PAGO ==========');
       log_message('debug', 'TICKET: Tipo de pago: ' . $tipo_pago . ', Monto: ' . $amount . ', Custom Amount: ' . $custom_amount . ', Cuotas pendientes: ' . json_encode($pending_quota_ids));

       // Procesar pago según tipo_pago
       $payment_results = [];
       if ($tipo_pago === 'custom' && !empty($custom_amount) && count($pending_quota_ids) > 0) {
         // Para pago personalizado: distribuir el monto entre las cuotas seleccionadas según el tipo especificado
         log_message('debug', 'TICKET: Procesando pago personalizado - monto: ' . $custom_amount . ', tipo: ' . $custom_payment_type . ', cuotas: ' . count($pending_quota_ids));

         // VALIDACIÓN: Verificar que custom_payment_type esté presente
         if (empty($custom_payment_type)) {
           log_message('error', 'TICKET: custom_payment_type está vacío o no definido');
           show_error('Debe seleccionar el tipo de aplicación del pago personalizado.', 400);
           return;
         }

         // DEBUG: Agregar logs detallados para depuración
         log_message('debug', 'TICKET: Iniciando procesamiento de pago personalizado');
         log_message('debug', 'TICKET: custom_payment_type: ' . $custom_payment_type);
         log_message('debug', 'TICKET: custom_amount: ' . $custom_amount);
         log_message('debug', 'TICKET: pending_quota_ids: ' . json_encode($pending_quota_ids));

         $remaining_amount = $custom_amount;
         $payment_notes = !empty($payment_description) ? $payment_description : 'Pago personalizado desde ticket';

         // Verificar si es la última cuota y el pago es menor al monto adeudado
         $is_last_quota = false;
         if (count($pending_quota_ids) == 1) {
           $last_quota_info = $this->payments_m->get_loan_item($pending_quota_ids[0]);
           if ($last_quota_info && $remaining_amount < $last_quota_info->balance) {
             $is_last_quota = true;
             log_message('debug', 'TICKET: Es la última cuota y el pago (' . $remaining_amount . ') es menor al balance (' . $last_quota_info->balance . ') - PERMITIR PAGO PARCIAL, NO generar cuotas adicionales');
           }
         }

         foreach ($pending_quota_ids as $index => $quota_id) {
           if ($remaining_amount <= 0) break;

           // Obtener información de la cuota
           $quota_info = $this->payments_m->get_loan_item($quota_id);
           if (!$quota_info) continue;

           // Calcular cuánto pagar de esta cuota según el tipo de pago personalizado
           $amount_to_pay = 0;
           log_message('debug', 'TICKET: Procesando cuota ' . $quota_id . ' - custom_payment_type: ' . $custom_payment_type . ', remaining_amount: ' . $remaining_amount);

           switch ($custom_payment_type) {
             case 'cuota':
               // Aplicar a cuota completa (intereses + capital)
               $quota_balance = $quota_info->balance;
               $amount_to_pay = min($remaining_amount, $quota_balance);
               log_message('debug', 'TICKET: Caso cuota - quota_balance: ' . $quota_balance . ', amount_to_pay: ' . $amount_to_pay);
               break;

             case 'interes':
               // Aplicar solo a intereses
               $interest_pending = $quota_info->interest_amount - ($quota_info->interest_paid ?? 0);
               $amount_to_pay = min($remaining_amount, $interest_pending);
               log_message('debug', 'TICKET: Caso interes - interest_pending: ' . $interest_pending . ', amount_to_pay: ' . $amount_to_pay);
               break;

             case 'capital':
               // Aplicar solo a capital
               $capital_pending = $quota_info->capital_amount - ($quota_info->capital_paid ?? 0);
               $amount_to_pay = min($remaining_amount, $capital_pending);
               log_message('debug', 'TICKET: Caso capital - capital_pending: ' . $capital_pending . ', amount_to_pay: ' . $amount_to_pay);
               break;

             default:
               log_message('error', 'TICKET: Tipo de pago personalizado inválido: ' . $custom_payment_type);
               continue 2; // Saltar al siguiente ciclo del foreach principal
           }

           log_message('debug', 'TICKET: Cuota ' . $quota_id . ' - tipo: ' . $custom_payment_type . ', amount_to_pay: ' . $amount_to_pay . ', remaining: ' . $remaining_amount);

           // Solo procesar si hay algo que pagar
           if ($amount_to_pay > 0) {
             // Usar el método apropiado según el tipo de pago personalizado
             switch ($custom_payment_type) {
               case 'cuota':
                 $result = $this->payments_m->pay_capital_only($quota_id, $amount_to_pay, $user_id, 'efectivo', $payment_notes, 'custom');
                 break;

               case 'interes':
                 $result = $this->payments_m->pay_interest_only($quota_id, $amount_to_pay, $user_id, 'efectivo', $payment_notes, 'custom');
                 break;

               case 'capital':
                 $result = $this->payments_m->pay_capital_only($quota_id, $amount_to_pay, $user_id, 'efectivo', $payment_notes, 'custom');
                 break;
             }
           } else {
             log_message('debug', 'TICKET: Cuota ' . $quota_id . ' no tiene monto pendiente para el tipo ' . $custom_payment_type . ', saltando');
             continue;
           }

           $payment_results[$quota_id] = $result;
           $remaining_amount -= $amount_to_pay;

           log_message('debug', 'TICKET: Resultado pago personalizado para cuota ' . $quota_id . ': ' . json_encode($result));

           if (!$result['success']) {
             log_message('error', 'TICKET: Error procesando pago personalizado para cuota ' . $quota_id . ': ' . $result['error']);
           }

           // Si es la última cuota y el pago fue menor al balance, NO generar cuotas adicionales
           // Solo permitir pagos parciales en la última cuota sin extender el préstamo
           if ($is_last_quota) {
             log_message('debug', 'TICKET: Última cuota pagada parcialmente - Balance actualizado correctamente, NO se generan cuotas adicionales');
           }
         }

         // Si queda monto restante después de pagar todas las cuotas seleccionadas, aplicar al balance general del préstamo
         if ($remaining_amount > 0) {
           log_message('debug', 'TICKET: Monto restante después de pagar cuotas seleccionadas: ' . $remaining_amount . ' - Aplicando al balance general del préstamo');

           // Obtener información del préstamo
           $loan_id = $this->input->post('loan_id');
           $loan = $this->loans_m->get_loan($loan_id);

           if ($loan) {
             // Calcular el balance total actual del préstamo
             $this->db->select('SUM(COALESCE(balance, 0)) as total_balance');
             $this->db->from('loan_items');
             $this->db->where('loan_id', $loan_id);
             $total_balance_result = $this->db->get()->row();
             $current_total_balance = $total_balance_result ? $total_balance_result->total_balance : 0;

             log_message('debug', 'TICKET: Balance total actual del préstamo: ' . $current_total_balance);

             // Si el balance total es mayor que el monto restante, aplicar reducción proporcional
             if ($current_total_balance > $remaining_amount) {
               // Aplicar el monto restante al balance general (reducirlo)
               $new_total_balance = max(0, $current_total_balance - $remaining_amount);

               // Actualizar el balance de las cuotas restantes proporcionalmente
               if ($current_total_balance > 0) {
                 $reduction_ratio = $remaining_amount / $current_total_balance;

                 // Obtener todas las cuotas pendientes
                 $pending_quotas = $this->db->where('loan_id', $loan_id)->where('status', 1)->where('balance >', 0)->get('loan_items')->result();

                 foreach ($pending_quotas as $quota) {
                   $balance_reduction = $quota->balance * $reduction_ratio;
                   $new_balance = max(0, $quota->balance - $balance_reduction);

                   $this->db->where('id', $quota->id);
                   $this->db->update('loan_items', ['balance' => $new_balance]);

                   log_message('debug', 'TICKET: Cuota ' . $quota->id . ' - balance reducido de ' . $quota->balance . ' a ' . $new_balance);
                 }
               }
               // Registrar el pago adicional en la tabla de pagos
               $payment_data = [
                 'loan_id' => $loan_id,
                 'loan_item_id' => null, // No específico a una cuota
                 'amount' => $remaining_amount,
                 'tipo_pago' => $tipo_pago,
                 'monto_pagado' => $remaining_amount,
                 'interest_paid' => 0,
                 'capital_paid' => $remaining_amount,
                 'payment_date' => date('Y-m-d H:i:s'),
                 'payment_user_id' => $user_id,
                 'method' => 'efectivo',
                 'notes' => $payment_notes . ' - Aplicado al balance general del préstamo'
               ];

               $this->db->insert('payments', $payment_data);
               log_message('debug', 'TICKET: Pago adicional registrado para balance general: ' . $this->db->insert_id());

               // Actualizar el balance del préstamo en la tabla loans
               $this->payments_m->update_loan_balance_and_status($loan_id);
               log_message('debug', 'TICKET: Balance del préstamo actualizado después de pago personalizado');
             } else {
               // Si el balance total es menor o igual al monto restante, generar cuotas adicionales
               log_message('debug', 'TICKET: Balance total (' . $current_total_balance . ') <= monto restante (' . $remaining_amount . ') - Generando cuotas adicionales');

               // Calcular el monto que queda por cubrir después de pagar el balance actual
               $amount_to_cover = $remaining_amount - $current_total_balance;

               // Pagar completamente todas las cuotas pendientes restantes
               $pending_quotas = $this->db->where('loan_id', $loan_id)->where('status', 1)->where('balance >', 0)->get('loan_items')->result();
               foreach ($pending_quotas as $quota) {
                 $this->db->where('id', $quota->id);
                 $this->db->update('loan_items', [
                   'status' => 0,
                   'balance' => 0,
                   'capital_paid' => $quota->capital_amount,
                   'interest_paid' => $quota->interest_amount,
                   'pay_date' => date('Y-m-d H:i:s')
                 ]);
                 log_message('debug', 'TICKET: Cuota adicional ' . $quota->id . ' pagada completamente');
               }

               // Generar cuotas adicionales para cubrir el monto restante
               $this->generate_additional_quotas($loan_id, $amount_to_cover, $user_id, $tipo_pago, $payment_notes);

               // Registrar el pago adicional en la tabla de pagos
               $payment_data = [
                 'loan_id' => $loan_id,
                 'loan_item_id' => null, // No específico a una cuota
                 'amount' => $remaining_amount,
                 'tipo_pago' => $tipo_pago,
                 'monto_pagado' => $remaining_amount,
                 'interest_paid' => 0,
                 'capital_paid' => $remaining_amount,
                 'payment_date' => date('Y-m-d H:i:s'),
                 'payment_user_id' => $user_id,
                 'method' => 'efectivo',
                 'notes' => $payment_notes . ' - Generadas cuotas adicionales para cubrir monto restante'
               ];

               $this->db->insert('payments', $payment_data);
               log_message('debug', 'TICKET: Pago adicional registrado para cuotas adicionales: ' . $this->db->insert_id());
             }
           }
         }
       }

       log_message('debug', 'TICKET: ========== PROCESANDO DATOS PARA TICKET ==========');
       if (!$this->payments_m->check_cstLoan($this->input->post('loan_id'))) {
         $this->payments_m->update_cstLoan($this->input->post('loan_id'), $this->input->post('customer_id'));
       }

       // Para pagos personalizados, obtener todas las cuotas del préstamo ya que pueden estar parcialmente pagadas
       if ($tipo_pago === 'custom' && !empty($custom_amount)) {
         log_message('debug', 'TICKET: Pago personalizado - obteniendo TODAS las cuotas del préstamo (incluyendo procesadas)');

         // Para pagos personalizados, necesitamos obtener TODAS las cuotas, no solo las pendientes
         // porque las cuotas procesadas pueden seguir teniendo status=1 pero con balances actualizados
         $this->db->select('id, loan_id, date as fecha_pago, num_quota as n_cuota, fee_amount as monto_cuota, interest_amount, capital_amount, balance, status as estado, interest_paid, capital_paid, pay_date');
         $this->db->where('loan_id', $this->input->post('loan_id'));
         $this->db->order_by('num_quota', 'asc');
         $all_quotas = $this->db->get('loan_items')->result();

         // Convertir al formato esperado
         $data['quotasPaid'] = [];
         foreach ($all_quotas as $quota) {
           $data['quotasPaid'][] = [
             'id' => $quota->id,
             'loan_id' => $quota->loan_id,
             'date' => $quota->fecha_pago,
             'num_quota' => $quota->n_cuota,
             'fee_amount' => $quota->monto_cuota,
             'interest_amount' => $quota->interest_amount,
             'capital_amount' => $quota->capital_amount,
             'balance' => $quota->balance,
             'status' => $quota->estado,
             'interest_paid' => $quota->interest_paid,
             'capital_paid' => $quota->capital_paid,
             'pay_date' => $quota->pay_date
           ];
         }

         log_message('debug', 'TICKET: TODAS las cuotas del préstamo obtenidas: ' . count($data['quotasPaid']));

         // Para pagos personalizados, marcar la cuota como pagada parcialmente (status permanece 1)
         // pero actualizar pay_date para registro
         if (!empty($pending_quota_ids)) {
           foreach ($pending_quota_ids as $quota_id) {
             $this->payments_m->update_quota([
               'paid_by' => $user_id,
               'pay_date' => date('Y-m-d H:i:s')
             ], $quota_id);
             log_message('debug', 'TICKET: Cuota ' . $quota_id . ' marcada con pay_date para pago personalizado');
           }
         }
       } else {
         // Para pagos normales, verificar existencia de cuotas antes de llamar get_quotasPaid
         log_message('debug', 'TICKET: Pago normal - Verificando existencia de cuotas antes de get_quotasPaid');
         foreach ($quota_ids as $q) {
           $exists = $this->db->where('id', $q)->count_all_results('loan_items');
           log_message('debug', 'TICKET: Cuota ID ' . $q . ' existe en BD: ' . ($exists > 0 ? 'SÍ' : 'NO'));
           if ($exists == 0) {
             log_message('error', 'TICKET: Cuota ID ' . $q . ' NO EXISTE en la base de datos');
           }
         }

         log_message('debug', 'TICKET: Llamando get_quotasPaid con pending_quota_ids: ' . json_encode($pending_quota_ids) . ', loan_id: ' . $this->input->post('loan_id'));
         $data['quotasPaid'] = $this->payments_m->get_quotasPaid($pending_quota_ids, $this->input->post('loan_id'));
         log_message('debug', 'TICKET: quotasPaid obtenidas: ' . count($data['quotasPaid']) . ' - Detalles: ' . json_encode($data['quotasPaid']));
       }

       // LOG ADICIONAL: Comparar IDs solicitados vs IDs retornados
       $returned_ids = array_column($data['quotasPaid'], 'id');
       log_message('debug', 'TICKET: IDs solicitados (originales): ' . json_encode($quota_ids));
       log_message('debug', 'TICKET: IDs pendientes filtrados: ' . json_encode($pending_quota_ids));
       log_message('debug', 'TICKET: IDs retornados por get_quotasPaid: ' . json_encode($returned_ids));
       log_message('debug', 'TICKET: Resultados de pagos procesados: ' . json_encode($payment_results));
       $extra_ids = array_diff($returned_ids, $pending_quota_ids);
       if (!empty($extra_ids)) {
         log_message('error', 'TICKET: IDs EXTRA retornados que NO estaban en la solicitud: ' . json_encode($extra_ids));
       }
       $missing_ids = array_diff($pending_quota_ids, $returned_ids);
       if (!empty($missing_ids)) {
         log_message('error', 'TICKET: IDs FALTANTES que deberían haber sido retornados: ' . json_encode($missing_ids));
       }
     } else {
       $data['quotasPaid'] = [];
       log_message('debug', 'TICKET: No hay cuotas pendientes, quotasPaid vacío');
     }

     // LOG DIAGNÓSTICO: Verificar contenido de quotasPaid antes del cálculo
     log_message('debug', 'TICKET: DIAGNÓSTICO - quotasPaid es array: ' . (is_array($data['quotasPaid']) ? 'SÍ' : 'NO'));
     log_message('debug', 'TICKET: DIAGNÓSTICO - count(quotasPaid): ' . count($data['quotasPaid']));
     if (!empty($data['quotasPaid'])) {
       log_message('debug', 'TICKET: DIAGNÓSTICO - Primera cuota: ' . json_encode($data['quotasPaid'][0]));
       if (is_object($data['quotasPaid'][0])) {
         log_message('debug', 'TICKET: DIAGNÓSTICO - Campos disponibles: ' . json_encode(array_keys(get_object_vars($data['quotasPaid'][0]))));
       } elseif (is_array($data['quotasPaid'][0])) {
         log_message('debug', 'TICKET: DIAGNÓSTICO - Campos disponibles (array): ' . json_encode(array_keys($data['quotasPaid'][0])));
       }
       $fee_amounts = array_column($data['quotasPaid'], 'fee_amount');
       log_message('debug', 'TICKET: DIAGNÓSTICO - fee_amounts extraídos: ' . json_encode($fee_amounts));
       foreach ($data['quotasPaid'] as $quota) {
         $quota_id = is_object($quota) ? $quota->id : (isset($quota['id']) ? $quota['id'] : 'UNKNOWN');
         $fee_amount = is_object($quota) ? (isset($quota->fee_amount) ? $quota->fee_amount : 'NO EXISTE') : (isset($quota['fee_amount']) ? $quota['fee_amount'] : 'NO EXISTE');
         log_message('debug', 'TICKET: DIAGNÓSTICO - Cuota ID ' . $quota_id . ' - fee_amount: ' . $fee_amount);
       }
     }

     // Obtener datos adicionales para el resumen del ticket
     $customer_id = $this->input->post('customer_id');
     $customer = $this->customers_m->get($customer_id);
     $data['dni'] = $customer ? $customer->dni : '';

     $user = $this->user_m->get_user($user_id);
     $data['user_name'] = $user ? ($user->first_name . ' ' . $user->last_name) : '';

     $coin_id = $this->input->post('coin');
     $coin = $this->coins_m->get($coin_id);
     $data['coin_name'] = $coin ? $coin->name : '';

     // Calcular total_amount según el tipo de pago
     log_message('debug', 'TICKET: ========== CÁLCULO DE TOTAL_AMOUNT ==========');

     if ($tipo_pago === 'custom' && !empty($custom_amount)) {
       // Para pagos personalizados, usar el monto personalizado total
       $data['total_amount'] = $custom_amount;
       log_message('debug', 'TICKET: Pago personalizado - total_amount: ' . $custom_amount);
     } else {
       // Para pagos normales, calcular como suma de fee_amount de las cuotas pagadas
       $fee_amounts_array = array_column($data['quotasPaid'], 'fee_amount');
       $data['total_amount'] = array_sum($fee_amounts_array);
       log_message('debug', 'TICKET: Pago normal - fee_amounts_array: ' . json_encode($fee_amounts_array));
       log_message('debug', 'TICKET: Pago normal - total_amount calculado: ' . $data['total_amount']);
     }

     log_message('debug', 'TICKET: CÁLCULO - data[total_amount] final: ' . $data['total_amount']);

     // CORRECCIÓN: Si total_amount es 0, usar el monto del pago original como fallback
     if ($data['total_amount'] == 0) {
         $data['total_amount'] = $amount;
         log_message('debug', 'TICKET: CORRECCIÓN - total_amount era 0, usando monto del pago original: ' . $amount);
     }

     // Verificar si no quedan cuotas pendientes y cerrar el préstamo
     $loan_id = $this->input->post('loan_id');
     $customer_id = $this->input->post('customer_id');
     $pending_quotas = $this->db->where('loan_id', $loan_id)->where('status', 1)->count_all_results('loan_items');
     if ($pending_quotas == 0) {
       $this->loans_m->update_loan($loan_id, ['status' => 0]);
       $this->customers_m->save(['loan_status' => 0], $customer_id);
       log_message('debug', 'Préstamo cerrado automáticamente: loan_id=' . $loan_id . ', customer_id=' . $customer_id);
     }

     log_message('debug', 'TICKET: ========== FINAL - CARGANDO VISTA ==========');
     log_message('debug', 'TICKET: Datos finales para vista: total_amount=' . $data['total_amount'] . ', quotasPaid count=' . count($data['quotasPaid']));
     $this->load->view('admin/payments/ticket', $data);
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
