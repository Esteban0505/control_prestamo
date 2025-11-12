<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments_m extends CI_Model {

  /**
   * Asegura columnas de auditoría/descripcion en loan_items
   */
  public function ensure_aux_columns()
  {
    log_message('debug', 'Verificando columnas auxiliares en loan_items');
    if (!$this->db->field_exists('payment_desc', 'loan_items')) {
      log_message('debug', 'Agregando columna payment_desc a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN payment_desc VARCHAR(255) NULL AFTER extra_payment");
    } else {
      log_message('debug', 'Columna payment_desc ya existe en loan_items');
    }
    if (!$this->db->field_exists('paid_by', 'loan_items')) {
      log_message('debug', 'Agregando columna paid_by a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN paid_by INT NULL AFTER status");
    } else {
      log_message('debug', 'Columna paid_by ya existe en loan_items');
    }
    if (!$this->db->field_exists('pay_date', 'loan_items')) {
      log_message('debug', 'Agregando columna pay_date a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN pay_date DATETIME NULL AFTER date");
    } else {
      log_message('debug', 'Columna pay_date ya existe en loan_items');
    }
    if (!$this->db->field_exists('interest_paid', 'loan_items')) {
      log_message('debug', 'Agregando columna interest_paid a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN interest_paid DECIMAL(10,2) NULL DEFAULT 0 AFTER interest_amount");
    } else {
      log_message('debug', 'Columna interest_paid ya existe en loan_items');
    }
    if (!$this->db->field_exists('capital_paid', 'loan_items')) {
      log_message('debug', 'Agregando columna capital_paid a loan_items');
      $this->db->query("ALTER TABLE loan_items ADD COLUMN capital_paid DECIMAL(10,2) NULL DEFAULT 0 AFTER capital_amount");
    } else {
      log_message('debug', 'Columna capital_paid ya existe en loan_items');
    }
  }

  /**
   * Asegura la tabla collector_commissions
   */
  private function ensure_commissions_table()
  {
    if (!$this->db->table_exists('collector_commissions')) {
      log_message('debug', 'Creando tabla collector_commissions');
      $this->db->query(
        "CREATE TABLE collector_commissions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NULL,
          loan_id INT NULL,
          loan_item_id INT NULL,
          client_name VARCHAR(255) NULL,
          client_cedula VARCHAR(100) NULL,
          amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
      );
    } else {
      log_message('debug', 'Tabla collector_commissions ya existe');
      // Agregar columnas si no existen
      if (!$this->db->field_exists('loan_id', 'collector_commissions')) {
        $this->db->query("ALTER TABLE collector_commissions ADD COLUMN loan_id INT NULL AFTER user_id");
      }
      if (!$this->db->field_exists('client_name', 'collector_commissions')) {
        $this->db->query("ALTER TABLE collector_commissions ADD COLUMN client_name VARCHAR(255) NULL AFTER loan_item_id");
      }
      if (!$this->db->field_exists('client_cedula', 'collector_commissions')) {
        $this->db->query("ALTER TABLE collector_commissions ADD COLUMN client_cedula VARCHAR(100) NULL AFTER client_name");
      }
    }
  }

  /**
   * Asegura la tabla payments
   */
  private function ensure_payments_table()
  {
    if (!$this->db->table_exists('payments')) {
      log_message('debug', 'Creando tabla payments');
      $this->db->query(
        "CREATE TABLE payments (
          id INT AUTO_INCREMENT PRIMARY KEY,
          loan_id INT NOT NULL,
          loan_item_id INT NULL,
          amount DECIMAL(15,2) NOT NULL,
          tipo_pago VARCHAR(50) NULL,
          monto_pagado DECIMAL(15,2) NOT NULL,
          interest_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          capital_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          payment_date DATETIME NOT NULL,
          payment_user_id INT NULL,
          method VARCHAR(50) NULL,
          notes TEXT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
      );
    } else {
      log_message('debug', 'Tabla payments ya existe');
      // Verificar si tiene las columnas tipo_pago y monto_pagado
      if (!$this->db->field_exists('tipo_pago', 'payments')) {
        log_message('debug', 'Agregando columna tipo_pago a payments');
        $this->db->query("ALTER TABLE payments ADD COLUMN tipo_pago VARCHAR(50) NULL AFTER amount");
      }
      if (!$this->db->field_exists('monto_pagado', 'payments')) {
        log_message('debug', 'Agregando columna monto_pagado a payments');
        $this->db->query("ALTER TABLE payments ADD COLUMN monto_pagado DECIMAL(15,2) NOT NULL AFTER tipo_pago");
      }
    }
  }

  /**
   * Obtiene pagos con paginación optimizada
   */
  public function get_payments($limit = 50, $offset = 0)
  {
    $this->db->select("li.id, c.dni, concat(c.first_name,' ',c.last_name) AS name_cst, l.id AS loan_id, li.num_quota, li.fee_amount, li.pay_date, li.interest_paid, li.capital_paid, li.balance, li.status");
    $this->db->from('loan_items li');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->where('li.pay_date IS NOT NULL');
    $this->db->order_by('li.pay_date', 'desc');
    $this->db->limit($limit, $offset);

    return $this->db->get()->result();
  }

  /**
   * Obtiene pagos paginados con filtros optimizados
   */
  public function get_payments_paginated($per_page, $offset, $search = null, $date_from = null, $date_to = null)
  {
    // Consulta optimizada con filtros
    $this->db->select("li.id, c.dni, concat(c.first_name,' ',c.last_name) AS name_cst, l.id AS loan_id, li.num_quota, li.fee_amount, li.pay_date, li.interest_paid, li.capital_paid, li.balance, li.status");
    $this->db->from('loan_items li');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->where('li.pay_date IS NOT NULL');

    // Aplicar filtros
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->or_like('l.id', $search);
      $this->db->group_end();
    }

    if (!empty($date_from)) {
      $this->db->where('li.pay_date >=', $date_from . ' 00:00:00');
    }

    if (!empty($date_to)) {
      $this->db->where('li.pay_date <=', $date_to . ' 23:59:59');
    }

    $this->db->order_by('li.pay_date', 'desc');
    $this->db->limit($per_page, $offset);

    $payments = $this->db->get()->result();

    // Contar total de registros con los mismos filtros
    $this->db->select('COUNT(*) as total');
    $this->db->from('loan_items li');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->where('li.pay_date IS NOT NULL');

    // Aplicar los mismos filtros para el conteo
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->or_like('l.id', $search);
      $this->db->group_end();
    }

    if (!empty($date_from)) {
      $this->db->where('li.pay_date >=', $date_from . ' 00:00:00');
    }

    if (!empty($date_to)) {
      $this->db->where('li.pay_date <=', $date_to . ' 23:59:59');
    }

    $total_result = $this->db->get()->row();
    $total = $total_result ? $total_result->total : 0;

    return [
      'payments' => $payments,
      'total' => $total
    ];
  }

  /**
   * Cuenta total de pagos para paginación (versión antigua - mantener por compatibilidad)
   */
  public function count_total_payments_old()
  {
    $this->db->where('pay_date IS NOT NULL');
    return $this->db->count_all_results('loan_items');
  }

  /**
   * Obtiene estadísticas de pagos optimizadas con SQL
   */
  public function get_payments_stats()
  {
    // Consulta optimizada para estadísticas
    $query = "
      SELECT
        COUNT(*) as total_payments,
        COALESCE(SUM(li.fee_amount), 0) as total_amount,
        COALESCE(AVG(li.fee_amount), 0) as avg_amount,
        MAX(li.pay_date) as latest_payment_date
      FROM loan_items li
      WHERE li.pay_date IS NOT NULL
    ";

    $result = $this->db->query($query)->row();

    return [
      'total_payments' => $result->total_payments ?? 0,
      'total_amount' => $result->total_amount ?? 0,
      'avg_amount' => $result->avg_amount ?? 0,
      'latest_payment_date' => $result->latest_payment_date
    ];
  }

  /**
   * Contar clientes por nivel de riesgo
   */
  public function count_clients_by_risk($risk_level)
  {
    $where_condition = '';
    switch ($risk_level) {
      case 'high':
        $where_condition = 'sub.max_dias_atraso >= 60';
        break;
      case 'medium':
        $where_condition = 'sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60';
        break;
      case 'low':
        $where_condition = 'sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30';
        break;
      default:
        return 0;
    }

    $query = "
      SELECT COUNT(DISTINCT c.id) as count
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
      WHERE {$where_condition}
    ";

    $result = $this->db->query($query)->row();
    return $result ? $result->count : 0;
  }

  /**
   * Obtener información de mora de un cliente
   */
  public function get_customer_overdue_info($customer_id)
  {
    $query = $this->db->query("
      SELECT
        COUNT(*) as cuotas_vencidas,
        SUM(COALESCE(li.fee_amount, 0)) as total_adeudado,
        SUM(COALESCE(li.interest_amount - COALESCE(li.interest_paid, 0), 0)) as intereses_pendientes,
        SUM(COALESCE(li.capital_amount - COALESCE(li.capital_paid, 0), 0)) as capital_pendiente,
        MAX(DATEDIFF(NOW(), li.date)) as dias_max_atraso,
        MIN(li.date) as fecha_primer_vencimiento
      FROM loan_items li
      JOIN loans l ON l.id = li.loan_id
      WHERE l.customer_id = ?
        AND li.status = 1
        AND li.date < NOW()
    ", [$customer_id]);

    return $query->row();
  }

  /**
   * Aplicar penalización por intereses
   */
  public function apply_interest_penalty($customer_id)
  {
    // Aumentar intereses pendientes en 5%
    $this->db->query("
      UPDATE loan_items li
      JOIN loans l ON l.id = li.loan_id
      SET li.interest_amount = li.interest_amount * 1.05
      WHERE l.customer_id = ?
        AND li.status = 1
        AND li.date < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ", [$customer_id]);

    return $this->db->affected_rows() > 0;
  }

  /**
   * Aplicar multa por mora
   */
  public function apply_late_fee_penalty($customer_id)
  {
    // Agregar multa del 2% del capital pendiente
    $this->db->query("
      UPDATE loan_items li
      JOIN loans l ON l.id = li.loan_id
      SET li.fee_amount = li.fee_amount * 1.02
      WHERE l.customer_id = ?
        AND li.status = 1
        AND li.date < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ", [$customer_id]);

    return $this->db->affected_rows() > 0;
  }

  /**
   * Aplicar penalizaciones automáticas a clientes con mora >60 días
   */
  public function apply_automatic_penalties()
  {
    $penalties_applied = 0;

    // Penalización automática: aumentar intereses en 10% para clientes con mora >60 días
    $this->db->query("
      UPDATE loan_items li
      JOIN loans l ON l.id = li.loan_id
      SET li.interest_amount = li.interest_amount * 1.10
      WHERE li.status = 1
        AND li.date < DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND li.interest_paid > 0
    ");

    $penalties_applied += $this->db->affected_rows();

    // Bloquear automáticamente clientes con mora >90 días
    $this->db->query("
      INSERT IGNORE INTO customer_blacklist (customer_id, reason, notes, blocked_by)
      SELECT DISTINCT
        l.customer_id,
        'overdue_payments' as reason,
        CONCAT('Bloqueo automático por mora >90 días - ', COUNT(*), ' cuotas vencidas') as notes,
        1 as blocked_by
      FROM loan_items li
      JOIN loans l ON l.id = li.loan_id
      WHERE li.status = 1
        AND li.date < DATE_SUB(NOW(), INTERVAL 90 DAY)
        AND li.interest_paid > 0
      GROUP BY l.customer_id
      HAVING COUNT(*) >= 2
    ");

    $penalties_applied += $this->db->affected_rows();

    return $penalties_applied;
  }

  /**
   * Cuenta total de pagos para paginación con filtros
   */
  public function count_total_payments($search = null, $date_from = null, $date_to = null)
  {
    $this->db->select('COUNT(*) as total');
    $this->db->from('loan_items li');
    $this->db->join('loans l', 'l.id = li.loan_id', 'left');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->where('li.pay_date IS NOT NULL');

    // Aplicar filtros
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->or_like('l.id', $search);
      $this->db->group_end();
    }

    if (!empty($date_from)) {
      $this->db->where('li.pay_date >=', $date_from . ' 00:00:00');
    }

    if (!empty($date_to)) {
      $this->db->where('li.pay_date <=', $date_to . ' 23:59:59');
    }

    $result = $this->db->get()->row();
    return $result ? $result->total : 0;
  }

  public function get_searchCst($dni, $suggest = false)
      {
        log_message('debug', 'Buscando cliente con dni/nombre: ' . $dni . ', suggest: ' . ($suggest ? 'true' : 'false'));
        $this->db->select("l.id as loan_id, l.customer_id, c.dni, concat(c.first_name, ' ', c.last_name) AS cst_name, l.credit_amount, l.payment_m, co.name as coin_name, l.status as loan_status, concat(u.first_name, ' ', u.last_name) AS asesor_name, c.user_id as assigned_user_id, SUM(COALESCE(li.balance, 0)) as total_balance, COUNT(CASE WHEN li.status = 1 THEN 1 END) as pending_quotas");
        $this->db->from('customers c');
        $this->db->join('loans l', 'l.customer_id = c.id', 'inner');
        $this->db->join('loan_items li', 'li.loan_id = l.id', 'left');
        $this->db->join('coins co', 'co.id = l.coin_id', 'left');
        $this->db->join('users u', 'u.id = l.assigned_user_id', 'left');
        $this->db->group_start();
        $this->db->or_like('c.dni', $dni);
        $this->db->or_like("CONCAT(c.first_name, ' ', c.last_name)", $dni);
        $this->db->group_end();
        $this->db->where('l.status', 1); // Solo préstamos activos
        $this->db->where('li.extra_payment !=', 3); // Excluir cuotas condonadas
        $this->db->group_by('l.id');
        $this->db->having('COUNT(CASE WHEN li.status = 1 THEN 1 END) > 0'); // Solo préstamos con cuotas pendientes
        $this->db->order_by('l.date', 'desc'); // Ordenar por fecha más reciente
        if ($suggest) {
            $this->db->limit(10);
        } else {
            $this->db->limit(1);
        }

        $query = $this->db->get();
        log_message('debug', 'Query ejecutado: ' . $this->db->last_query());
        log_message('debug', 'Resultado de búsqueda: ' . $query->num_rows() . ' filas encontradas');

        if ($suggest) {
          $result = $query->result();
          log_message('debug', 'Resultados suggest: ' . json_encode($result));
          return $result;
        } else {
          $result = $query->row();
          if ($result) {
            log_message('debug', 'cliente_id: ' . $result->customer_id . ', estado: ' . $result->loan_status . ', balance: ' . $result->total_balance);
          } else {
            log_message('debug', 'No se encontró cliente con balance > 0, verificando si existe préstamo activo sin balance pendiente');
            // Verificar si hay préstamo activo sin balance
            $this->db->select("l.id as loan_id, l.customer_id, c.dni, concat(c.first_name, ' ', c.last_name) AS cst_name, l.credit_amount, l.payment_m, co.name as coin_name, l.status as loan_status, concat(u.first_name, ' ', u.last_name) AS asesor_name, c.user_id as assigned_user_id, 0 as total_balance");
            $this->db->from('customers c');
            $this->db->join('loans l', 'l.customer_id = c.id', 'inner');
            $this->db->join('coins co', 'co.id = l.coin_id', 'left');
            $this->db->join('users u', 'u.id = l.assigned_user_id', 'left');
            $this->db->group_start();
            $this->db->or_like('c.dni', $dni);
            $this->db->or_like("CONCAT(c.first_name, ' ', c.last_name)", $dni);
            $this->db->group_end();
            $this->db->where('l.status', 1); // Solo préstamos activos
            $this->db->where('li.extra_payment !=', 3); // Excluir cuotas condonadas
            $this->db->group_by('l.id');
            $this->db->order_by('l.date', 'desc');
            $this->db->limit(1);
            $fallback_query = $this->db->get();
            log_message('debug', 'Query fallback: ' . $this->db->last_query());
            log_message('debug', 'Resultado fallback: ' . $fallback_query->num_rows() . ' filas encontradas');
            if ($fallback_query->num_rows() > 0) {
              $result = $fallback_query->row();
              log_message('debug', 'Cliente encontrado en fallback: ' . json_encode($result));
            }
          }
          log_message('debug', 'Resultado único: ' . json_encode($result));
          return $result;
        }
      }

  public function get_quotasCst($loan_id)
    {
      $loan_id = (int)$loan_id;
      log_message('debug', 'Obteniendo cuotas para loan_id: ' . $loan_id);
      $this->db->select('id, loan_id, date as fecha_pago, num_quota as n_cuota, fee_amount as monto_cuota, interest_amount, capital_amount, balance, status as estado, COALESCE(interest_paid, 0) as interest_paid, COALESCE(capital_paid, 0) as capital_paid');
      $this->db->where('loan_id', $loan_id);
      // Incluir cuotas pendientes (status=1), parciales (status=3) y no completas (status=4)
      // Excluir cuotas pagadas completamente (status=0) y condonadas (extra_payment=3)
      $this->db->where_in('status', [1, 3, 4]);
      $this->db->where('extra_payment !=', 3); // Excluir cuotas condonadas
      $this->db->order_by('num_quota', 'asc');

      $query = $this->db->get('loan_items');
      log_message('debug', 'Query cuotas: ' . $this->db->last_query());
      log_message('debug', 'Número de cuotas encontradas: ' . $query->num_rows());
      $quotas = [];

      foreach ($query->result() as $row) {
        $quotas[] = [
          'id' => $row->id,
          'loan_id' => $row->loan_id,
          'date' => $row->fecha_pago,
          'num_quota' => $row->n_cuota,
          'fee_amount' => $row->monto_cuota,
          'interest_amount' => $row->interest_amount,
          'capital_amount' => $row->capital_amount,
          'balance' => $row->balance,
          'status' => $row->estado,
          'interest_paid' => $row->interest_paid,
          'capital_paid' => $row->capital_paid
        ];
      }
      log_message('debug', 'Cuotas procesadas: ' . json_encode($quotas));

      return $quotas;
    }

  public function update_quota($data, $id)
  {
    // LOG DIAGNÓSTICO: Verificar datos antes de actualizar
    log_message('debug', 'UPDATE_QUOTA: Actualizando cuota ID ' . $id . ' con datos: ' . json_encode($data));

    // Obtener estado actual antes de actualizar
    $current_quota = $this->get_loan_item($id);
    if ($current_quota) {
      log_message('debug', 'UPDATE_QUOTA: Estado ANTES de actualizar - status: ' . $current_quota->status . ', balance: ' . $current_quota->balance . ', interest_paid: ' . ($current_quota->interest_paid ?? 0) . ', capital_paid: ' . ($current_quota->capital_paid ?? 0));
    } else {
      log_message('error', 'UPDATE_QUOTA: ERROR - Cuota ID ' . $id . ' no encontrada antes de actualizar');
      return false;
    }

    // CORRECCIÓN CRÍTICA: Asegurar que los valores numéricos sean correctos
    if (isset($data['status'])) {
        $data['status'] = (int)$data['status'];
    }
    if (isset($data['balance'])) {
        $data['balance'] = (float)$data['balance'];
    }
    if (isset($data['interest_paid'])) {
        $data['interest_paid'] = (float)$data['interest_paid'];
    }
    if (isset($data['capital_paid'])) {
        $data['capital_paid'] = (float)$data['capital_paid'];
    }

    // CORRECCIÓN CRÍTICA: Asegurar que status se guarde como entero explícitamente
    if (isset($data['status'])) {
        $data['status'] = (int)$data['status'];
        log_message('debug', 'UPDATE_QUOTA: Status convertido a entero: ' . $data['status']);
    }
    
    $this->db->where('id', $id);
    $result = $this->db->update('loan_items', $data);
    
    log_message('debug', 'UPDATE_QUOTA: Query ejecutada: ' . $this->db->last_query());
    log_message('debug', 'UPDATE_QUOTA: Filas afectadas: ' . $this->db->affected_rows());

    if ($result) {
      // CORRECCIÓN CRÍTICA: Forzar flush de la conexión para asegurar que se guarde
      $this->db->flush_cache();
      
      // CORRECCIÓN: Esperar un momento para que la BD procese la actualización
      usleep(100000); // 100ms
      
      // Verificar estado después de actualizar
      $updated_quota = $this->get_loan_item($id);
      if ($updated_quota) {
        log_message('debug', 'UPDATE_QUOTA: Estado DESPUÉS de actualizar - status: ' . $updated_quota->status . ', balance: ' . $updated_quota->balance . ', interest_paid: ' . ($updated_quota->interest_paid ?? 0) . ', capital_paid: ' . ($updated_quota->capital_paid ?? 0));
        
        // CORRECCIÓN: Verificar que los cambios se aplicaron correctamente
        if (isset($data['status']) && (int)$updated_quota->status !== (int)$data['status']) {
            log_message('error', 'UPDATE_QUOTA: ERROR CRÍTICO - Status NO se actualizó! Esperado: ' . $data['status'] . ', Actual: ' . $updated_quota->status);
            // Reintentar actualización con SET explícito
            $this->db->where('id', $id);
            $this->db->set('status', (int)$data['status'], false);
            $this->db->update('loan_items');
            log_message('error', 'UPDATE_QUOTA: Reintentando actualizar status con SET explícito - Query: ' . $this->db->last_query());
            
            // Verificar nuevamente
            $updated_quota = $this->get_loan_item($id);
            if ($updated_quota && (int)$updated_quota->status !== (int)$data['status']) {
                log_message('error', 'UPDATE_QUOTA: ERROR PERSISTENTE - Status aún no actualizado después del reintento!');
            }
        }
        if (isset($data['interest_paid']) && abs((float)$updated_quota->interest_paid - (float)$data['interest_paid']) > 0.01) {
            log_message('error', 'UPDATE_QUOTA: ERROR CRÍTICO - interest_paid NO se actualizó! Esperado: ' . $data['interest_paid'] . ', Actual: ' . $updated_quota->interest_paid);
        }
        if (isset($data['capital_paid']) && abs((float)$updated_quota->capital_paid - (float)$data['capital_paid']) > 0.01) {
            log_message('error', 'UPDATE_QUOTA: ERROR CRÍTICO - capital_paid NO se actualizó! Esperado: ' . $data['capital_paid'] . ', Actual: ' . $updated_quota->capital_paid);
        }
      }
      log_message('debug', 'UPDATE_QUOTA: Actualización exitosa para cuota ID ' . $id);
    } else {
      log_message('error', 'UPDATE_QUOTA: ERROR - Falló actualización de cuota ID ' . $id . ' - Query: ' . $this->db->last_query());
      log_message('error', 'UPDATE_QUOTA: Error DB: ' . $this->db->error()['message'] ?? 'Sin error específico');
    }

    return $result;
  }

  public function check_cstLoan($loan_id)
  {
    $this->db->where('loan_id', $loan_id);

    $query = $this->db->get('loan_items'); 

    $check = false;

    foreach ($query->result() as $row) {
      if ($row->status == 1) {
        $check = true;
        break;
      } 
    }

    return $check;
  }

  public function update_cstLoan($loan_id, $customer_id)
  {
    $this->db->where('id', $loan_id);
    $this->db->update('loans', ['status' => 0]);

    $this->db->where('id', $customer_id);
    $this->db->update('customers', ['loan_status' => 0]); 
  }

  public function get_quotasPaid($data, $loan_id = null)
     {
       log_message('debug', 'get_quotasPaid: Parámetros recibidos - data: ' . json_encode($data) . ', loan_id: ' . $loan_id);
       log_message('debug', 'get_quotasPaid: Tipo de data: ' . gettype($data) . ', es array: ' . (is_array($data) ? 'sí' : 'no'));

       // Solo filtrar por IDs específicos - no por loan_id para evitar incluir todas las cuotas
       if (!is_array($data) || empty($data)) {
         log_message('error', 'get_quotasPaid: data no es array válido o está vacío');
         return [];
       }

       log_message('debug', 'get_quotasPaid: Array original recibido: ' . json_encode($data));

       // Filtrar IDs inválidos (como "0" que no existen en la BD)
       $valid_ids = array_filter($data, function($id) {
         $is_valid = is_numeric($id) && $id > 0;
         log_message('debug', 'get_quotasPaid: Filtrando ID ' . json_encode($id) . ' - is_numeric: ' . (is_numeric($id) ? 'sí' : 'no') . ', >0: ' . ($id > 0 ? 'sí' : 'no') . ', válido: ' . ($is_valid ? 'sí' : 'no'));
         return $is_valid;
       });

       log_message('debug', 'get_quotasPaid: IDs válidos después del filtro: ' . json_encode($valid_ids));

       if (empty($valid_ids)) {
         log_message('error', 'get_quotasPaid: No hay IDs válidos en el array - buscando cuotas pagadas recientemente');
         if ($loan_id) {
           // Buscar cuotas pagadas en los últimos 10 minutos
           $recent_time = date('Y-m-d H:i:s', strtotime('-10 minutes'));
           $this->db->where('loan_id', $loan_id);
           $this->db->where('status', 0);
           $this->db->where('pay_date >=', $recent_time);
           $this->db->order_by('num_quota', 'asc');
           $result = $this->db->get('loan_items')->result();
           log_message('debug', 'get_quotasPaid: Fallback reciente - Query: ' . $this->db->last_query() . ' - Resultados: ' . count($result));
           if (!empty($result)) {
             return $result;
           }
           // Si no hay recientes, obtener todas las pagadas
           $this->db->where('loan_id', $loan_id);
           $this->db->where('status', 0);
           $this->db->order_by('num_quota', 'asc');
           $result = $this->db->get('loan_items')->result();
           log_message('debug', 'get_quotasPaid: Fallback todas pagadas - Query: ' . $this->db->last_query() . ' - Resultados: ' . count($result));
           return $result;
         }
         return [];
       }

       // Verificar existencia de cada ID en la base de datos antes de la consulta
       foreach ($valid_ids as $id) {
         $exists = $this->db->where('id', $id)->count_all_results('loan_items');
         log_message('debug', 'get_quotasPaid: Verificando existencia de ID ' . $id . ' en loan_items: ' . ($exists > 0 ? 'EXISTE' : 'NO EXISTE'));
       }

       $this->db->where_in('id', $valid_ids);
       $this->db->order_by('num_quota', 'asc');
       $result = $this->db->get('loan_items')->result();
       log_message('debug', 'get_quotasPaid: Query: ' . $this->db->last_query() . ' - Resultados: ' . count($result) . ' - IDs válidos solicitados: ' . json_encode($valid_ids));

       // LOG DIAGNÓSTICO: Verificar campos importantes en los resultados
       if (!empty($result)) {
         log_message('debug', 'get_quotasPaid: DIAGNÓSTICO - Primer resultado completo: ' . json_encode($result[0]));
         log_message('debug', 'get_quotasPaid: DIAGNÓSTICO - Campos disponibles: ' . json_encode(array_keys(get_object_vars($result[0]))));
         foreach ($result as $quota) {
           log_message('debug', 'get_quotasPaid: DIAGNÓSTICO - Cuota ID ' . $quota->id . ' - fee_amount: ' . (isset($quota->fee_amount) ? $quota->fee_amount : 'NO EXISTE') . ', status: ' . (isset($quota->status) ? $quota->status : 'NO EXISTE'));
         }
         log_message('debug', 'get_quotasPaid: DIAGNÓSTICO - Todos los IDs retornados: ' . json_encode(array_column($result, 'id')));
       } else {
         log_message('error', 'get_quotasPaid: Query retornó array vacío');
       }

       // Validaciones
       $ids = array_column($result, 'id');
       $unique_ids = array_unique($ids);
       if (count($ids) != count($unique_ids)) {
         log_message('error', 'get_quotasPaid: Duplicaciones en IDs: ' . json_encode($ids));
       }
       $missing = array_diff($valid_ids, $ids);
       if (!empty($missing)) {
         log_message('error', 'get_quotasPaid: IDs faltantes: ' . json_encode($missing));
       }

       log_message('debug', 'get_quotasPaid: Retornando ' . count($result) . ' cuotas');
       return $result;
     }

  /**
   * Obtiene una cuota específica
   */
  public function get_loan_item($id)
  {
    $this->db->where('id', $id);
    return $this->db->get('loan_items')->row();
  }

  /**
   * Procesa pago manual sobre una cuota
   * - amount: monto positivo
   * - description: texto opcional guardado en payment_description
   * - payment_type: 'interest', 'capital', 'both', 'full'
   */
  public function process_manual_payment($loan_item_id, $amount, $description, $payment_type, $user_id = null)
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ((int)$loan_item->status === 0) {
      return ['success' => false, 'error' => 'La cuota ya está pagada'];
    }

    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a 0'];
    }

    switch ($payment_type) {
      case 'interest':
        $result = $this->pay_interest_only($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'capital':
        $result = $this->pay_capital_only($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'both':
        $result = $this->pay_both($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      case 'full':
        $result = $this->pay_full_quota($loan_item_id, $amount, $user_id, 'efectivo', $description, $payment_type);
        break;

      default:
        $result = ['success' => false, 'error' => 'Tipo de pago no válido'];
    }

    return $result;
  }

  /**
   * Procesa un pago según el tipo especificado
   */
  public function process_payment($data)
  {
    log_message('debug', 'Iniciando process_payment con datos: ' . json_encode($data));

    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $this->db->trans_start();

    try {
      $loan_id = $data['loan_id'];
      $loan_item_id = $data['loan_item_id'];
      $amount = $data['amount'];
      $payment_type = $data['payment_type'];
      $payment_user_id = $data['payment_user_id'];
      $method = $data['method'] ?? 'efectivo';
      $notes = $data['notes'] ?? '';
      $loan = $data['loan'];
      $loan_item = $data['loan_item'];

      $result = ['success' => false, 'data' => null, 'error' => ''];

      switch ($payment_type) {
        case 'interest':
          $result = $this->process_interest_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'capital':
          $result = $this->process_capital_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'both':
          $result = $this->process_both_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        case 'full':
          $result = $this->process_full_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type);
          break;

        default:
          throw new Exception('Tipo de pago no válido');
      }

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de solo intereses
   */
  private function process_interest_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'interest')
  {
    log_message('debug', 'Procesando pago de intereses: loan_item_id=' . $loan_item_id . ', amount=' . $amount);
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar intereses'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // Verificar existencia de columna interest_paid
    if (!$this->db->field_exists('interest_paid', 'loan_items')) {
      log_message('error', 'Columna interest_paid no existe en loan_items');
      return ['success' => false, 'error' => 'Columna interest_paid no existe en loan_items'];
    }

    // VALIDACIÓN PREVIA: Calcular montos pendientes reales
    $interest_pending = max(0, $loan_item->interest_amount - ($loan_item->interest_paid ?? 0));
    $capital_pending = max(0, $loan_item->capital_amount - ($loan_item->capital_paid ?? 0));
    $total_pending = $interest_pending + $capital_pending;

    log_message('debug', 'VALIDACIÓN PREVIA - Intereses pendientes: ' . $interest_pending . ', Capital pendiente: ' . $capital_pending . ', Total pendiente: ' . $total_pending);

    // Verificar que el monto no exceda los intereses pendientes
    if ($amount > $interest_pending) {
      return ['success' => false, 'error' => 'El monto excede los intereses pendientes (' . number_format($interest_pending, 2) . ')'];
    }

    // Verificar que el monto sea positivo
    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a cero'];
    }

    // Actualizar intereses pagados en la cuota
    $new_interest_paid = ($loan_item->interest_paid ?? 0) + $amount;

    // Usar el nuevo método centralizado para calcular el balance
    $balance_calculation = $this->calculate_balance_after_payment($loan_item_id, $amount, $payment_type);
    if ($balance_calculation['success']) {
      $new_balance = $balance_calculation['data']['nuevo_saldo'];
      log_message('debug', 'PAYMENT_INTEREST_DEBUG - Balance calculado correctamente: ' . $new_balance);
    } else {
      $new_balance = $loan_item->balance;
      log_message('error', 'PAYMENT_INTEREST_DEBUG - Error calculando balance: ' . $balance_calculation['error']);
    }

    // Determinar si la cuota queda parcialmente pagada
    $capital_pending = $loan_item->capital_amount - ($loan_item->capital_paid ?? 0);
    $is_partial_payment = ($new_interest_paid < $loan_item->interest_amount) || ($capital_pending > 0);

    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'interest_paid' => $new_interest_paid,
      'balance' => $new_balance,
      'pay_date' => $is_partial_payment ? null : date('Y-m-d H:i:s'), // Solo marcar fecha si está completamente pagada
      'paid_by' => $is_partial_payment ? null : $payment_user_id // Solo asignar usuario si está completamente pagada
    ]);
    log_message('debug', 'Actualizado interest_paid a: ' . $new_interest_paid . ', balance a: ' . $new_balance . ', is_partial: ' . ($is_partial_payment ? 'true' : 'false'));

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $amount,
      'capital_paid' => 0,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago de intereses: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    // Corrección automática de balance y status
    $this->update_loan_balance_and_status($loan_id);

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $interest_pending - $amount,
        'remaining_capital' => $capital_pending,
        'is_partial' => $is_partial_payment
      ]
    ];
  }

  /**
   * Procesa pago de solo capital
   */
  private function process_capital_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'capital')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar capital'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // VALIDACIÓN PREVIA: Calcular montos pendientes reales
    $interest_pending = max(0, $loan_item->interest_amount - ($loan_item->interest_paid ?? 0));
    $capital_pending = max(0, $loan_item->capital_amount - ($loan_item->capital_paid ?? 0));
    $total_pending = $interest_pending + $capital_pending;

    log_message('debug', 'VALIDACIÓN PREVIA CAPITAL - Intereses pendientes: ' . $interest_pending . ', Capital pendiente: ' . $capital_pending . ', Total pendiente: ' . $total_pending);

    // Verificar que el monto no exceda el capital pendiente
    if ($amount > $capital_pending) {
      return ['success' => false, 'error' => 'El monto excede el capital pendiente (' . number_format($capital_pending, 2) . ')'];
    }

    // Verificar que el monto sea positivo
    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a cero'];
    }

    // Usar el nuevo método centralizado para calcular el balance
    $balance_calculation = $this->calculate_balance_after_payment($loan_item_id, $amount, $payment_type);
    if ($balance_calculation['success']) {
      $new_balance = $balance_calculation['data']['nuevo_saldo'];
      $new_capital_paid = ($loan_item->capital_paid ?? 0) + $balance_calculation['data']['capital'];
      log_message('debug', 'PAYMENT_CAPITAL_DEBUG - Balance calculado correctamente: ' . $new_balance . ', capital_paid: ' . $new_capital_paid);
    } else {
      $new_balance = $loan_item->balance - $amount;
      $new_capital_paid = ($loan_item->capital_paid ?? 0) + $amount;
      log_message('error', 'PAYMENT_CAPITAL_DEBUG - Error calculando balance: ' . $balance_calculation['error'] . ', usando cálculo simple');
    }

    // Determinar si la cuota queda parcialmente pagada
    $interest_pending = $loan_item->interest_amount - ($loan_item->interest_paid ?? 0);
    $is_partial_payment = ($new_capital_paid < $loan_item->capital_amount) || ($interest_pending > 0);

    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'capital_paid' => $new_capital_paid,
      'balance' => $new_balance,
      'pay_date' => $is_partial_payment ? null : date('Y-m-d H:i:s'), // Solo marcar fecha si está completamente pagada
      'paid_by' => $is_partial_payment ? null : $payment_user_id // Solo asignar usuario si está completamente pagada
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => 0,
      'capital_paid' => $amount,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago de capital: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    // Corrección automática de balance y status
    $this->update_loan_balance_and_status($loan_id);

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $interest_pending,
        'remaining_capital' => $capital_pending - $amount,
        'is_partial' => $is_partial_payment
      ]
    ];
  }

  /**
   * Procesa pago de capital e intereses
   */
  private function process_both_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'both')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // VALIDACIÓN PREVIA: Calcular montos pendientes reales
    $interest_pending = max(0, $loan_item->interest_amount - ($loan_item->interest_paid ?? 0));
    $capital_pending = max(0, $loan_item->capital_amount - ($loan_item->capital_paid ?? 0));
    $total_pending = $interest_pending + $capital_pending;

    log_message('debug', 'VALIDACIÓN PREVIA BOTH - Intereses pendientes: ' . $interest_pending . ', Capital pendiente: ' . $capital_pending . ', Total pendiente: ' . $total_pending);

    if ($amount > $total_pending) {
      return ['success' => false, 'error' => 'El monto excede el total pendiente de la cuota (' . number_format($total_pending, 2) . ')'];
    }

    // Verificar que el monto sea positivo
    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a cero'];
    }

    // Distribuir el pago: primero intereses, luego capital
    $interest_paid = min($amount, $interest_pending);
    $capital_paid = $amount - $interest_paid;

    // Usar el nuevo método centralizado para calcular el balance
    $balance_calculation = $this->calculate_balance_after_payment($loan_item_id, $amount, $payment_type);
    if ($balance_calculation['success']) {
      $new_balance = $balance_calculation['data']['nuevo_saldo'];
      $new_interest_paid = ($loan_item->interest_paid ?? 0) + $balance_calculation['data']['interes'];
      $new_capital_paid = ($loan_item->capital_paid ?? 0) + $balance_calculation['data']['capital'];
      log_message('debug', 'PAYMENT_BOTH_DEBUG - Balance calculado correctamente: ' . $new_balance . ', interes_paid: ' . $new_interest_paid . ', capital_paid: ' . $new_capital_paid);
    } else {
      // Fallback al cálculo anterior
      $new_interest_paid = ($loan_item->interest_paid ?? 0) + $interest_paid;
      $new_capital_paid = ($loan_item->capital_paid ?? 0) + $capital_paid;
      $new_balance = $loan_item->balance - $capital_paid;
      log_message('error', 'PAYMENT_BOTH_DEBUG - Error calculando balance: ' . $balance_calculation['error'] . ', usando cálculo simple');
    }

    // Determinar si la cuota queda parcialmente pagada
    $is_partial_payment = ($new_interest_paid < $loan_item->interest_amount) || ($new_capital_paid < $loan_item->capital_amount);

    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'interest_paid' => $new_interest_paid,
      'capital_paid' => $new_capital_paid,
      'balance' => $new_balance,
      'pay_date' => $is_partial_payment ? null : date('Y-m-d H:i:s'), // Solo marcar fecha si está completamente pagada
      'paid_by' => $is_partial_payment ? null : $payment_user_id // Solo asignar usuario si está completamente pagada
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $interest_paid,
      'capital_paid' => $capital_paid,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago mixto: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    // Corrección automática de balance y status
    $this->update_loan_balance_and_status($loan_id);

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => $interest_pending - $interest_paid,
        'remaining_capital' => $capital_pending - $capital_paid,
        'is_partial' => $is_partial_payment
      ]
    ];
  }

  /**
   * Procesa pago total de una cuota
   */
  private function process_full_payment($loan_id, $loan_item_id, $amount, $payment_user_id, $method, $notes, $payment_type = 'full')
  {
    if (!$loan_item_id) {
      return ['success' => false, 'error' => 'Debe especificar una cuota para pagar'];
    }

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if ($loan_item->status == 0) {
      return ['success' => false, 'error' => 'Esta cuota ya está pagada'];
    }

    // VALIDACIÓN PREVIA: Calcular montos pendientes reales
    $interest_pending = max(0, $loan_item->interest_amount - ($loan_item->interest_paid ?? 0));
    $capital_pending = max(0, $loan_item->capital_amount - ($loan_item->capital_paid ?? 0));
    $total_pending = $interest_pending + $capital_pending;

    log_message('debug', 'VALIDACIÓN PREVIA FULL - Intereses pendientes: ' . $interest_pending . ', Capital pendiente: ' . $capital_pending . ', Total pendiente: ' . $total_pending);

    if ($amount < $total_pending) {
      return ['success' => false, 'error' => 'El monto es menor al total pendiente de la cuota (' . number_format($total_pending, 2) . ')'];
    }

    // Verificar que el monto sea positivo
    if ($amount <= 0) {
      return ['success' => false, 'error' => 'El monto debe ser mayor a cero'];
    }

    // Marcar la cuota como pagada
    $this->db->where('id', $loan_item_id);
    $this->db->update('loan_items', [
      'status' => 0,
      'interest_paid' => $loan_item->interest_amount,
      'capital_paid' => $loan_item->capital_amount,
      'balance' => 0, // Asegurar que el balance sea 0 para cuotas pagadas
      'pay_date' => date('Y-m-d H:i:s')
    ]);

    // Registrar el pago
    $payment_data = [
      'loan_id' => $loan_id,
      'loan_item_id' => $loan_item_id,
      'amount' => $amount,
      'tipo_pago' => $payment_type,
      'monto_pagado' => $amount,
      'interest_paid' => $interest_pending,
      'capital_paid' => $capital_pending,
      'payment_date' => date('Y-m-d H:i:s'),
      'payment_user_id' => $payment_user_id,
      'method' => $method,
      'notes' => $notes
    ];

    log_message('debug', 'Insertando pago completo: ' . json_encode($payment_data));
    $this->db->insert('payments', $payment_data);
    log_message('debug', 'Pago insertado con ID: ' . $this->db->insert_id());

    // Corrección automática de balance y status
    $this->update_loan_balance_and_status($loan_id);

    // Verificar si el préstamo está completamente pagado
    $this->check_and_close_loan($loan_id);

    // Calcular comisión si hay usuario asignado
    if ($payment_user_id) {
      // Obtener datos del cliente
      $this->db->select('c.first_name, c.last_name, c.dni');
      $this->db->from('customers c');
      $this->db->join('loans l', 'l.customer_id = c.id', 'left');
      $this->db->where('l.id', $loan_id);
      $client = $this->db->get()->row();

      $client_name = $client ? trim($client->first_name . ' ' . $client->last_name) : '';
      $client_cedula = $client ? $client->dni : '';

      $commission = $amount * 0.4; // 40% del monto total pagado
      $this->db->insert('collector_commissions', [
        'user_id' => $payment_user_id,
        'loan_id' => $loan_id,
        'loan_item_id' => $loan_item_id,
        'client_name' => $client_name,
        'client_cedula' => $client_cedula,
        'amount' => $amount,
        'commission' => $commission
      ]);
    }

    return [
      'success' => true,
      'data' => [
        'payment_id' => $this->db->insert_id(),
        'remaining_interest' => 0,
        'remaining_capital' => 0,
        'quota_paid' => true
      ]
    ];
  }

  /**
   * Verifica si el préstamo está completamente pagado y lo cierra
   */
  private function check_and_close_loan($loan_id)
  {
    $this->db->select('SUM(COALESCE(balance, 0)) as total_balance');
    $this->db->from('loan_items');
    $this->db->where('loan_id', $loan_id);
    $total_balance = $this->db->get()->row()->total_balance;
    error_log("CHECK_CLOSE_LOAN: loan_id=$loan_id, total_balance=$total_balance");

    if ($total_balance == 0) {
      // Cerrar el préstamo
      $this->db->where('id', $loan_id);
      $this->db->update('loans', ['status' => 0]);
      error_log("LOAN_CLOSED: loan_id=$loan_id");

      // Obtener el customer_id para actualizar su estado
      $loan = $this->db->where('id', $loan_id)->get('loans')->row();
      if ($loan) {
        $this->db->where('id', $loan->customer_id);
        $this->db->update('customers', ['loan_status' => 0]);
        error_log("CUSTOMER_LOAN_STATUS_UPDATED: customer_id=" . $loan->customer_id);
      }
    }
  }
  
  /**
   * Método auxiliar para calcular el balance después de un pago según el tipo de amortización
   */
  public function calculate_balance_after_payment($loan_item_id, $amount, $payment_type, $loan_id = null)
  {
    // LOG DIAGNÓSTICO: Inicio del cálculo
    log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: ========== INICIANDO CÁLCULO ========== - loan_item_id=' . $loan_item_id . ', amount=' . $amount . ', payment_type=' . $payment_type . ', loan_id=' . $loan_id);

    // Obtener información de la cuota y el préstamo
    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      log_message('error', 'CALCULATE_BALANCE_AFTER_PAYMENT: ERROR - Cuota no encontrada - loan_item_id=' . $loan_item_id);
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    if (!$loan_id) {
      $loan_id = $loan_item->loan_id;
    }

    // Obtener información del préstamo para determinar el tipo de amortización
    $this->load->model('Loans_m');
    $loan = $this->Loans_m->get_loan($loan_id);
    if (!$loan) {
      log_message('error', 'CALCULATE_BALANCE_AFTER_PAYMENT: ERROR - Préstamo no encontrado - loan_id=' . $loan_id);
      return ['success' => false, 'error' => 'Préstamo no encontrado'];
    }

    $amortization_type = $loan->amortization_type;
    $saldo_anterior = $loan_item->balance;
    $capital_inicial = $loan->credit_amount;
    $tasa = $loan->interest_amount / 100; // Convertir a decimal
    $periodo = $loan_item->num_quota;

    log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Datos básicos - amortization_type=' . $amortization_type . ', saldo_anterior=' . $saldo_anterior . ', tasa=' . $tasa . ', periodo=' . $periodo);

    $interes = 0;
    $capital = 0;
    $nuevo_saldo = $saldo_anterior;

    // LOG DIAGNÓSTICO: Estado inicial de la cuota
    $interest_pending = max(0, $loan_item->interest_amount - ($loan_item->interest_paid ?? 0));
    $capital_pending = max(0, $loan_item->capital_amount - ($loan_item->capital_paid ?? 0));
    log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Estado inicial cuota - interest_pending=' . $interest_pending . ', capital_pending=' . $capital_pending . ', total_pending=' . ($interest_pending + $capital_pending));

    switch ($amortization_type) {
      case 'francesa':
        // Sistema francés: cuota fija, interés decreciente, capital creciente
        $interes = $saldo_anterior * $tasa;
        $capital = $amount - $interes;
        $nuevo_saldo = $saldo_anterior - $capital;
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema FRANCÉS - interes_calculado=' . $interes . ', capital_calculado=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        break;

      case 'americana':
        // Sistema americano: solo intereses hasta el final, capital completo al final
        $total_cuotas = $loan->num_fee;
        $es_ultimo_pago = ($periodo >= $total_cuotas);

        if ($es_ultimo_pago) {
          $interes = $saldo_anterior * $tasa;
          $capital = $saldo_anterior;
          $nuevo_saldo = 0;
          log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema AMERICANO - ÚLTIMO PAGO - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        } else {
          $interes = $saldo_anterior * $tasa;
          $capital = 0;
          $nuevo_saldo = $saldo_anterior;
          log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema AMERICANO - PAGO INTERMEDIO - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        }
        break;

      case 'europea':
        // Sistema europeo: capital creciente, interés fijo hasta el final
        $total_cuotas = $loan->num_fee;
        $es_ultimo_pago = ($periodo >= $total_cuotas);

        $interes = $saldo_anterior * $tasa;
        if ($es_ultimo_pago) {
          $capital = $saldo_anterior;
          $nuevo_saldo = 0;
          log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema EUROPEO - ÚLTIMO PAGO - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        } else {
          $capital = 0;
          $nuevo_saldo = $saldo_anterior;
          log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema EUROPEO - PAGO INTERMEDIO - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        }
        break;

      default:
        // Sistema francés por defecto
        $interes = $saldo_anterior * $tasa;
        $capital = $amount - $interes;
        $nuevo_saldo = $saldo_anterior - $capital;
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: Sistema POR DEFECTO (Francés) - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        break;
    }

    // Ajustar según el tipo de pago personalizado
    switch ($payment_type) {
      case 'interest':
        // Solo intereses: no cambiar capital ni balance
        $capital = 0;
        $nuevo_saldo = $saldo_anterior;
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: AJUSTE - Solo intereses - capital=0, nuevo_saldo=' . $nuevo_saldo);
        break;

      case 'capital':
        // Solo capital: no hay intereses en este pago
        $interes = 0;
        $capital = $amount;
        $nuevo_saldo = $saldo_anterior - $capital;
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: AJUSTE - Solo capital - interes=0, capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo);
        break;

      case 'both':
        // Intereses y capital: usar cálculo normal
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: AJUSTE - Ambos (interés+capital) - usando cálculo normal');
        break;

      case 'full':
        // Pago completo: marcar como pagado
        $nuevo_saldo = 0;
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: AJUSTE - Pago completo - nuevo_saldo=0');
        break;

      case 'custom':
        // Para pagos personalizados, usar max(0) para evitar balances negativos
        $nuevo_saldo = max(0, $nuevo_saldo);
        log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: AJUSTE - Pago personalizado - aplicando max(0) al balance: ' . $nuevo_saldo);
        break;
    }

    // Protección: nunca permitir balances negativos
    if ($nuevo_saldo < 0) {
      $nuevo_saldo = 0;
      log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: PROTECCIÓN - Balance ajustado a 0 para evitar negativo - valor original: ' . ($saldo_anterior - $capital));
    }

    // LOG DIAGNÓSTICO: Resultado final
    log_message('debug', 'CALCULATE_BALANCE_AFTER_PAYMENT: ========== RESULTADO FINAL ========== - interes=' . $interes . ', capital=' . $capital . ', nuevo_saldo=' . $nuevo_saldo . ', amortization_type=' . $amortization_type);

    return [
      'success' => true,
      'data' => [
        'interes' => $interes,
        'capital' => $capital,
        'nuevo_saldo' => $nuevo_saldo,
        'amortization_type' => $amortization_type
      ]
    ];
  }

  /**
   * Actualiza el balance y status del préstamo basado en pagos totales
   */
  public function update_loan_balance_and_status($loan_id)
  {
    // Asegurar que la columna balance existe en loans
    if (!$this->db->field_exists('balance', 'loans')) {
      log_message('debug', 'Agregando columna balance a loans');
      $this->db->query("ALTER TABLE loans ADD COLUMN balance DECIMAL(15,2) NULL DEFAULT 0 AFTER credit_amount");
    }

    // Calcular balance total de loan_items
    $this->db->select('SUM(COALESCE(balance, 0)) as total_balance');
    $this->db->from('loan_items');
    $this->db->where('loan_id', $loan_id);
    $query = $this->db->get();
    $total_balance = $query->row()->total_balance ?? 0;

    // Logs
    log_message('debug', 'Corrección automática - loan_id: ' . $loan_id . ', total_balance: ' . $total_balance);
    error_log("UPDATE_LOAN_BALANCE: loan_id=$loan_id, total_balance=$total_balance");

    // Actualizar balance en loans
    $update_data = ['balance' => $total_balance];

    // Si total_balance <= 0, marcar como pagado
    if ($total_balance <= 0) {
      $this->db->set('status', 0);
      $this->db->where('id', $loan_id);
      $this->db->update('loans');
      $update_data['status'] = 0;
      $update_data['balance'] = 0;
      log_message('debug', 'Préstamo marcado como pagado - loan_id: ' . $loan_id);
      error_log("LOAN_MARKED_PAID: loan_id=$loan_id");
    }

    $this->db->where('id', $loan_id);
    $this->db->update('loans', $update_data);
  }
  /**
   * Procesa pago de solo intereses
   */
  public function pay_interest_only($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'interest')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_interest_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de solo capital SIN recálculo de amortización para pagos parciales
   */
  public function pay_capital_only($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'capital')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_capital_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        // NO recalcular amortización para pagos parciales personalizados
        // Solo recalcular si es un pago completo de capital (no personalizado)
        if ($payment_type !== 'custom') {
          $this->recalculate_amortization($loan_id, $loan_item_id);
        }

        // Para pagos personalizados, actualizar el balance de la cuota
        if ($payment_type === 'custom') {
          log_message('debug', 'PAY_CAPITAL_ONLY: Actualizando balance para pago personalizado - loan_item_id: ' . $loan_item_id . ', amount: ' . $amount);
          $new_balance = $loan_item->balance - $amount;
          $this->db->where('id', $loan_item_id);
          $this->db->update('loan_items', ['balance' => $new_balance]);
          log_message('debug', 'PAY_CAPITAL_ONLY: Balance actualizado de ' . $loan_item->balance . ' a ' . $new_balance);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago de intereses y capital
   */
  public function pay_both($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'both')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_both_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Procesa pago completo de una cuota
   */
  public function pay_full_quota($loan_item_id, $amount, $user_id = null, $method = 'efectivo', $notes = '', $payment_type = 'full')
  {
    $this->ensure_aux_columns();
    $this->ensure_commissions_table();
    $this->ensure_payments_table();

    $loan_item = $this->get_loan_item($loan_item_id);
    if (!$loan_item) {
      return ['success' => false, 'error' => 'Cuota no encontrada'];
    }

    $loan_id = $loan_item->loan_id;

    $this->db->trans_start();

    try {
      $result = $this->process_full_payment($loan_id, $loan_item_id, $amount, $user_id, $method, $notes, $payment_type);

      if ($result['success']) {
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
          $result = ['success' => false, 'error' => 'Error en la transacción'];
        }
      } else {
        $this->db->trans_rollback();
      }

      return $result;

    } catch (Exception $e) {
      $this->db->trans_rollback();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtiene clientes con pagos vencidos (agrupado por cliente) - Optimizado
   */
  public function get_overdue_clients($search = null, $risk_level = null, $min_amount = null, $max_amount = null)
  {
    // Usar consulta optimizada con subquery para mejor rendimiento
    $subquery = "
      SELECT
        li.loan_id,
        COUNT(*) as cuotas_vencidas,
        SUM(li.fee_amount) as total_adeudado,
        MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso,
        GROUP_CONCAT(DISTINCT li.num_quota) as cuotas_nums
      FROM loan_items li
      WHERE li.status = 1
        AND li.date < CURDATE()
      GROUP BY li.loan_id
    ";

    // Verificar si la columna status existe antes de usarla
    $status_column = '1 as customer_status';
    try {
      $check_status = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_status->num_rows() > 0) {
        $status_column = 'COALESCE(c.status, 1) as customer_status';
      }
    } catch (Exception $e) {
      // Si hay error, usar valor por defecto
    }

    $this->db->select("
      c.id as customer_id,
      CONCAT(c.first_name, ' ', c.last_name) as client_name,
      c.dni as client_cedula,
      " . $status_column . ",
      sub.cuotas_vencidas,
      sub.total_adeudado,
      sub.max_dias_atraso,
      GROUP_CONCAT(DISTINCT l.id) as loan_ids,
      sub.cuotas_nums
    ");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id');
    $this->db->join("($subquery) sub", 'sub.loan_id = l.id', 'inner');
    $this->db->where('l.status', 1); // Solo préstamos activos

    // Aplicar filtros
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->group_end();
    }

    if (!empty($risk_level)) {
      switch ($risk_level) {
        case 'low':
          $this->db->where('sub.max_dias_atraso >=', 1);
          $this->db->where('sub.max_dias_atraso <', 30);
          break;
        case 'medium':
          $this->db->where('sub.max_dias_atraso >=', 30);
          $this->db->where('sub.max_dias_atraso <', 60);
          break;
        case 'high':
          $this->db->where('sub.max_dias_atraso >=', 60);
          break;
      }
    }

    if (!empty($min_amount)) {
      $this->db->where('sub.total_adeudado >=', $min_amount);
    }

    if (!empty($max_amount)) {
      $this->db->where('sub.total_adeudado <=', $max_amount);
    }

    $this->db->group_by('c.id');
    $this->db->order_by('sub.max_dias_atraso', 'DESC');

    return $this->db->get()->result();
  }

  /**
   * Aplica penalización a un cliente específico - Optimizado
   */
  public function apply_penalty_to_customer($customer_id)
  {
    // Verificar si ya existe penalización para este cliente usando consulta optimizada
    $existing_penalty = $this->db->where('customer_id', $customer_id)->count_all_results('loans_penalties');
    if ($existing_penalty > 0) {
      return false; // Ya tiene penalización
    }

    // Obtener préstamos activos del cliente con mora >60 días usando subquery optimizada
    $subquery = "
      SELECT li.loan_id, MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
      FROM loan_items li
      WHERE li.status = 1 AND li.date < CURDATE()
      GROUP BY li.loan_id
      HAVING MAX(DATEDIFF(NOW(), li.date)) > 60
    ";

    $this->db->select('l.id as loan_id');
    $this->db->from('loans l');
    $this->db->join("($subquery) sub", 'sub.loan_id = l.id', 'inner');
    $this->db->where('l.customer_id', $customer_id);
    $this->db->where('l.status', 1);
    $overdue_loans = $this->db->get()->result();

    if (empty($overdue_loans)) {
      return false; // No cumple criterios para penalización
    }

    // Aplicar penalización al primer préstamo encontrado
    $loan = $overdue_loans[0];

    $this->db->insert('loans_penalties', [
      'loan_id' => $loan->loan_id,
      'customer_id' => $customer_id,
      'penalty_reason' => 'Aplicación manual de penalización - Mora >60 días'
    ]);

    // Cambiar status del loan a "Castigado"
    $this->db->where('id', $loan->loan_id);
    $this->db->update('loans', ['status' => 2]);

    return true;
  }

  /**
   * Obtiene estadísticas de riesgo para el dashboard - Optimizado
   */
  public function get_overdue_statistics()
  {
    // Consulta optimizada para obtener estadísticas en una sola query
    $query = "
      SELECT
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 60 THEN c.id END) as high_risk_count,
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60 THEN c.id END) as medium_risk_count,
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30 THEN c.id END) as low_risk_count,
        COALESCE(SUM(sub.total_adeudado), 0) as total_amount
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
    ";

    $result = $this->db->query($query)->row();

    return [
      'high_risk_count' => $result ? $result->high_risk_count : 0,
      'medium_risk_count' => $result ? $result->medium_risk_count : 0,
      'low_risk_count' => $result ? $result->low_risk_count : 0,
      'total_amount' => $result ? $result->total_amount : 0
    ];
  }

  /**
   * Obtiene clientes por nivel de riesgo para notificaciones masivas
   */
  public function get_clients_by_risk_level($risk_level)
  {
    $where_condition = '';
    switch ($risk_level) {
      case 'high':
        $where_condition = 'sub.max_dias_atraso >= 60';
        break;
      case 'medium':
        $where_condition = 'sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60';
        break;
      case 'low':
        $where_condition = 'sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30';
        break;
      default:
        return [];
    }

    $query = "
      SELECT
        c.id as customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as client_name,
        c.dni as client_cedula,
        c.mobile,
        sub.total_adeudado,
        sub.max_dias_atraso
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
      WHERE {$where_condition}
      GROUP BY c.id
      ORDER BY sub.max_dias_atraso DESC
    ";

    return $this->db->query($query)->result();
  }

  /**
   * Cuenta clientes por nivel de riesgo (versión anterior - mantener por compatibilidad)
   */
  public function count_clients_by_risk_old($risk_level)
  {
    $where_condition = '';
    switch ($risk_level) {
      case 'high':
        $where_condition = 'sub.max_dias_atraso >= 60';
        break;
      case 'medium':
        $where_condition = 'sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60';
        break;
      case 'low':
        $where_condition = 'sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30';
        break;
      default:
        return 0;
    }

    $query = "
      SELECT COUNT(DISTINCT c.id) as count
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
      WHERE {$where_condition}
    ";

    $result = $this->db->query($query)->row();
    return $result ? $result->count : 0;
  }

  /**
   * Obtiene estadísticas de riesgo mejoradas
   */
  public function get_risk_statistics()
  {
    $query = "
      SELECT
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 60 THEN c.id END) as high_risk_count,
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60 THEN c.id END) as medium_risk_count,
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30 THEN c.id END) as low_risk_count,
        COUNT(DISTINCT CASE WHEN sub.max_dias_atraso >= 90 THEN c.id END) as critical_risk_count,
        COALESCE(SUM(CASE WHEN sub.max_dias_atraso >= 60 THEN sub.total_adeudado END), 0) as high_risk_amount,
        COALESCE(SUM(CASE WHEN sub.max_dias_atraso >= 30 AND sub.max_dias_atraso < 60 THEN sub.total_adeudado END), 0) as medium_risk_amount,
        COALESCE(SUM(CASE WHEN sub.max_dias_atraso >= 1 AND sub.max_dias_atraso < 30 THEN sub.total_adeudado END), 0) as low_risk_amount,
        COALESCE(SUM(sub.total_adeudado), 0) as total_overdue_amount,
        AVG(sub.max_dias_atraso) as avg_days_overdue
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
    ";

    $result = $this->db->query($query)->row();

    return [
      'high_risk_count' => $result ? $result->high_risk_count : 0,
      'medium_risk_count' => $result ? $result->medium_risk_count : 0,
      'low_risk_count' => $result ? $result->low_risk_count : 0,
      'critical_risk_count' => $result ? $result->critical_risk_count : 0,
      'high_risk_amount' => $result ? $result->high_risk_amount : 0,
      'medium_risk_amount' => $result ? $result->medium_risk_amount : 0,
      'low_risk_amount' => $result ? $result->low_risk_amount : 0,
      'total_overdue_amount' => $result ? $result->total_overdue_amount : 0,
      'avg_days_overdue' => $result ? $result->avg_days_overdue : 0
    ];
  }

  /**
   * Cuenta nuevos clientes morosos hoy
   */
  public function count_new_overdue_today()
  {
    // Clientes que se volvieron morosos hoy
    $query = "
      SELECT COUNT(DISTINCT c.id) as count
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN loan_items li ON li.loan_id = l.id
      WHERE li.status = 1
        AND li.date < CURDATE()
        AND DATEDIFF(NOW(), li.date) = 1
    ";

    $result = $this->db->query($query)->row();
    return $result ? $result->count : 0;
  }

  /**
   * Obtiene el monto total adeudado
   */
  public function get_total_overdue_amount()
  {
    $query = "
      SELECT COALESCE(SUM(sub.total_adeudado), 0) as total_amount
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
    ";

    $result = $this->db->query($query)->row();
    return $result ? $result->total_amount : 0;
  }

  /**
   * Obtiene tendencias de mora por mes
   */
  public function get_overdue_trends_by_month($months = 12)
  {
    $query = "
      SELECT
        DATE_FORMAT(li.date, '%Y-%m') as month,
        COUNT(DISTINCT c.id) as clients_count,
        SUM(li.fee_amount) as total_amount
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id
      INNER JOIN loan_items li ON li.loan_id = l.id
      WHERE li.status = 1
        AND li.date < CURDATE()
        AND li.date >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
      GROUP BY DATE_FORMAT(li.date, '%Y-%m')
      ORDER BY month DESC
    ";

    return $this->db->query($query)->result();
  }

  /**
   * Obtiene distribución por nivel de riesgo
   */
  public function get_risk_distribution()
  {
    $query = "
      SELECT
        CASE
          WHEN sub.max_dias_atraso >= 60 THEN 'Alto Riesgo (60+ días)'
          WHEN sub.max_dias_atraso >= 30 THEN 'Medio Riesgo (30-59 días)'
          ELSE 'Bajo Riesgo (1-29 días)'
        END as risk_level,
        COUNT(DISTINCT c.id) as clients_count,
        SUM(sub.total_adeudado) as total_amount,
        (COUNT(DISTINCT c.id) / (SELECT COUNT(DISTINCT c2.id)
                                 FROM customers c2
                                 INNER JOIN loans l2 ON l2.customer_id = c2.id AND l2.status = 1
                                 INNER JOIN loan_items li2 ON li2.loan_id = l2.id
                                 WHERE li2.status = 1 AND li2.date < CURDATE())) * 100 as percentage
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
      GROUP BY
        CASE
          WHEN sub.max_dias_atraso >= 60 THEN 'Alto Riesgo (60+ días)'
          WHEN sub.max_dias_atraso >= 30 THEN 'Medio Riesgo (30-59 días)'
          ELSE 'Bajo Riesgo (1-29 días)'
        END
      ORDER BY clients_count DESC
    ";

    return $this->db->query($query)->result();
  }

  /**
   * Obtiene los clientes con mayor mora
   */
  public function get_top_overdue_clients($limit = 10)
  {
    $query = "
      SELECT
        c.id as customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as client_name,
        c.dni as client_cedula,
        sub.total_adeudado,
        sub.max_dias_atraso,
        sub.cuotas_vencidas
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso,
          COUNT(*) as cuotas_vencidas
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE()
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
      ORDER BY sub.total_adeudado DESC
      LIMIT {$limit}
    ";

    return $this->db->query($query)->result();
  }

  /**
   * Calcula la tasa de recuperación
   */
  public function get_recovery_rate()
  {
    // Total pagado en los últimos 30 días vs total adeudado
    $query = "
      SELECT
        COALESCE(SUM(p.amount), 0) as total_recovered,
        (SELECT COALESCE(SUM(sub.total_adeudado), 0)
         FROM customers c
         INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
         INNER JOIN (
           SELECT li.loan_id, SUM(li.fee_amount) as total_adeudado
           FROM loan_items li
           WHERE li.status = 1 AND li.date < CURDATE()
           GROUP BY li.loan_id
         ) sub ON sub.loan_id = l.id) as total_overdue
      FROM payments p
      WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ";

    $result = $this->db->query($query)->row();

    if ($result && $result->total_overdue > 0) {
      return ($result->total_recovered / $result->total_overdue) * 100;
    }

    return 0;
  }

  /**
   * Obtiene resumen de mora para reportes
   */
  public function get_overdue_summary($start_date = null, $end_date = null)
  {
    $where_clause = "";
    if ($start_date && $end_date) {
      $where_clause = "AND li.date BETWEEN '{$start_date}' AND '{$end_date}'";
    }

    $query = "
      SELECT
        COUNT(DISTINCT c.id) as total_clients,
        COALESCE(SUM(sub.total_adeudado), 0) as total_amount,
        AVG(sub.max_dias_atraso) as avg_days_overdue
      FROM customers c
      INNER JOIN loans l ON l.customer_id = c.id AND l.status = 1
      INNER JOIN (
        SELECT
          li.loan_id,
          SUM(li.fee_amount) as total_adeudado,
          MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
        FROM loan_items li
        WHERE li.status = 1 AND li.date < CURDATE() {$where_clause}
        GROUP BY li.loan_id
      ) sub ON sub.loan_id = l.id
    ";

    $result = $this->db->query($query)->row();

    return [
      'total_clients' => $result ? $result->total_clients : 0,
      'total_amount' => $result ? $result->total_amount : 0,
      'avg_days_overdue' => $result ? $result->avg_days_overdue : 0,
      'recovery_rate' => $this->get_recovery_rate()
    ];
  }

  /**
   * Gestión de Seguimiento de Cobranza
   */

  /**
   * Crea o actualiza seguimiento de cobranza para un cliente
   */
  public function create_collection_tracking($customer_id, $data = [])
  {
    // Verificar si ya existe seguimiento
    $existing = $this->db->where('customer_id', $customer_id)->get('collection_tracking')->row();

    if ($existing) {
      // Actualizar existente
      $update_data = array_merge([
        'updated_at' => date('Y-m-d H:i:s')
      ], $data);

      $this->db->where('id', $existing->id);
      $this->db->update('collection_tracking', $update_data);
      return $existing->id;
    } else {
      // Crear nuevo seguimiento
      $insert_data = array_merge([
        'customer_id' => $customer_id,
        'status' => 'active',
        'priority' => 'medium',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
      ], $data);

      $this->db->insert('collection_tracking', $insert_data);
      return $this->db->insert_id();
    }
  }

  /**
   * Registra una acción de cobranza
   */
  public function log_collection_action($tracking_id, $action_data)
  {
    $data = array_merge($action_data, [
      'collection_tracking_id' => $tracking_id,
      'created_at' => date('Y-m-d H:i:s')
    ]);

    $this->db->insert('collection_actions', $data);

    // Actualizar fecha de último contacto y próxima acción
    $update_data = ['last_contact_date' => date('Y-m-d H:i:s')];
    if (isset($action_data['next_action_date'])) {
      $update_data['next_followup_date'] = $action_data['next_action_date'];
    }

    $this->db->where('id', $tracking_id);
    $this->db->update('collection_tracking', $update_data);

    return $this->db->insert_id();
  }

  /**
   * Obtiene seguimiento de cobranza con acciones
   */
  public function get_collection_tracking($customer_id)
  {
    $this->db->select('ct.*, u.first_name as assigned_user_name, u.last_name as assigned_user_lastname');
    $this->db->from('collection_tracking ct');
    $this->db->join('users u', 'u.id = ct.assigned_user_id', 'left');
    $this->db->where('ct.customer_id', $customer_id);
    $tracking = $this->db->get()->row();

    if ($tracking) {
      // Obtener acciones relacionadas
      $this->db->select('ca.*, u.first_name as performed_by_name, u.last_name as performed_by_lastname');
      $this->db->from('collection_actions ca');
      $this->db->join('users u', 'u.id = ca.performed_by', 'left');
      $this->db->where('ca.collection_tracking_id', $tracking->id);
      $this->db->order_by('ca.created_at', 'DESC');
      $tracking->actions = $this->db->get()->result();
    }

    return $tracking;
  }

  /**
   * Obtiene casos pendientes de seguimiento
   */
  public function get_pending_followups($user_id = null, $limit = 50)
  {
    $this->db->select('ct.*, c.first_name, c.last_name, c.dni, c.mobile');
    $this->db->from('collection_tracking ct');
    $this->db->join('customers c', 'c.id = ct.customer_id');
    $this->db->where('ct.status', 'active');
    $this->db->where('ct.next_followup_date <=', date('Y-m-d H:i:s'));
    $this->db->order_by('ct.priority', 'DESC');
    $this->db->order_by('ct.next_followup_date', 'ASC');

    if ($user_id) {
      $this->db->where('ct.assigned_user_id', $user_id);
    }

    $this->db->limit($limit);
    return $this->db->get()->result();
  }

  /**
   * Asigna cobrador a un caso
   */
  public function assign_collector($customer_id, $user_id)
  {
    $tracking_id = $this->create_collection_tracking($customer_id, [
      'assigned_user_id' => $user_id,
      'status' => 'active'
    ]);

    // Registrar acción de asignación
    $this->log_collection_action($tracking_id, [
      'action_type' => 'escalation',
      'action_description' => 'Asignado cobrador para seguimiento',
      'performed_by' => $this->session->userdata('user_id') ?? null,
      'notes' => 'Caso asignado a cobrador'
    ]);

    return $tracking_id;
  }

  /**
   * Actualiza estado de cobranza
   */
  public function update_collection_status($customer_id, $status, $notes = '')
  {
    $this->db->where('customer_id', $customer_id);
    $this->db->update('collection_tracking', [
      'status' => $status,
      'notes' => $notes,
      'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Registrar acción
    $tracking = $this->db->where('customer_id', $customer_id)->get('collection_tracking')->row();
    if ($tracking) {
      $this->log_collection_action($tracking->id, [
        'action_type' => 'escalation',
        'action_description' => 'Estado actualizado a: ' . $status,
        'performed_by' => $this->session->userdata('user_id') ?? null,
        'notes' => $notes
      ]);
    }
  }

  /**
   * Cuenta total de clientes con pagos vencidos (para paginación) - Optimizado
   */
  public function count_overdue_clients($search = null, $risk_level = null, $min_amount = null, $max_amount = null)
  {
    // Consulta optimizada usando subquery
    $subquery = "
      SELECT
        li.loan_id,
        COUNT(*) as cuotas_vencidas,
        SUM(li.fee_amount) as total_adeudado,
        MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso
      FROM loan_items li
      WHERE li.status = 1
        AND li.date < CURDATE()
      GROUP BY li.loan_id
    ";

    $this->db->select('COUNT(DISTINCT c.id) as total');
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id');
    $this->db->join("($subquery) sub", 'sub.loan_id = l.id', 'inner');
    $this->db->where('l.status', 1); // Solo préstamos activos

    // Aplicar filtros
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->group_end();
    }

    if (!empty($risk_level)) {
      switch ($risk_level) {
        case 'low':
          $this->db->where('sub.max_dias_atraso >=', 1);
          $this->db->where('sub.max_dias_atraso <', 30);
          break;
        case 'medium':
          $this->db->where('sub.max_dias_atraso >=', 30);
          $this->db->where('sub.max_dias_atraso <', 60);
          break;
        case 'high':
          $this->db->where('sub.max_dias_atraso >=', 60);
          break;
      }
    }

    if (!empty($min_amount)) {
      $this->db->where('sub.total_adeudado >=', $min_amount);
    }

    if (!empty($max_amount)) {
      $this->db->where('sub.total_adeudado <=', $max_amount);
    }

    $result = $this->db->get()->row();
    return $result ? $result->total : 0;
  }

  /**
   * Obtiene clientes con pagos vencidos con paginación - Optimizado
   */
  public function get_overdue_clients_paginated($search = null, $risk_level = null, $min_amount = null, $max_amount = null, $limit = 25, $offset = 0)
  {
    // Usar consulta optimizada con subquery para mejor rendimiento
    $subquery = "
      SELECT
        li.loan_id,
        COUNT(*) as cuotas_vencidas,
        SUM(li.fee_amount) as total_adeudado,
        MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso,
        GROUP_CONCAT(DISTINCT li.num_quota) as cuotas_nums
      FROM loan_items li
      WHERE li.status = 1
        AND li.date < CURDATE()
      GROUP BY li.loan_id
    ";

    // Verificar si la columna status existe antes de usarla
    $status_column = '1 as customer_status';
    try {
      $check_status = $this->db->query("SHOW COLUMNS FROM customers LIKE 'status'");
      if ($check_status->num_rows() > 0) {
        $status_column = 'COALESCE(c.status, 1) as customer_status';
      }
    } catch (Exception $e) {
      // Si hay error, usar valor por defecto
    }

    $this->db->select("
      c.id as customer_id,
      CONCAT(c.first_name, ' ', c.last_name) as client_name,
      c.dni as client_cedula,
      " . $status_column . ",
      sub.cuotas_vencidas,
      sub.total_adeudado,
      sub.max_dias_atraso,
      GROUP_CONCAT(DISTINCT l.id) as loan_ids,
      sub.cuotas_nums
    ");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id');
    $this->db->join("($subquery) sub", 'sub.loan_id = l.id', 'inner');
    $this->db->where('l.status', 1); // Solo préstamos activos

    // Aplicar filtros
    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like("CONCAT(c.first_name, ' ', c.last_name)", $search);
      $this->db->or_like('c.dni', $search);
      $this->db->group_end();
    }

    if (!empty($risk_level)) {
      switch ($risk_level) {
        case 'low':
          $this->db->where('sub.max_dias_atraso >=', 1);
          $this->db->where('sub.max_dias_atraso <', 30);
          break;
        case 'medium':
          $this->db->where('sub.max_dias_atraso >=', 30);
          $this->db->where('sub.max_dias_atraso <', 60);
          break;
        case 'high':
          $this->db->where('sub.max_dias_atraso >=', 60);
          break;
      }
    }

    if (!empty($min_amount)) {
      $this->db->where('sub.total_adeudado >=', $min_amount);
    }

    if (!empty($max_amount)) {
      $this->db->where('sub.total_adeudado <=', $max_amount);
    }

    $this->db->group_by('c.id');
    $this->db->order_by('sub.max_dias_atraso', 'DESC');
    $this->db->limit($limit, $offset);

    return $this->db->get()->result();
  }

  /**
   * Aplica castigo automático a clientes con mora >60 días (versión anterior - mantener por compatibilidad)
   */
  public function apply_automatic_penalties_old()
  {
    // Obtener clientes con mora >60 días
    $this->db->select("c.id as customer_id, l.id as loan_id, MAX(DATEDIFF(NOW(), li.date)) as max_dias_atraso");
    $this->db->from('customers c');
    $this->db->join('loans l', 'l.customer_id = c.id');
    $this->db->join('loan_items li', 'li.loan_id = l.id');
    $this->db->where('li.status', 1); // No pagado
    $this->db->where('li.date <', date('Y-m-d')); // Fecha vencida
    $this->db->group_by('c.id, l.id');
    $this->db->having('MAX(DATEDIFF(NOW(), li.date)) >', 60);

    $overdue_loans = $this->db->get()->result();

    $penalties_applied = 0;

    foreach ($overdue_loans as $loan) {
      // Verificar si ya existe penalty para este loan
      $existing_penalty = $this->db->where('loan_id', $loan->loan_id)->get('loans_penalties')->row();

      if (!$existing_penalty) {
        // Aplicar penalty
        $this->db->insert('loans_penalties', [
          'loan_id' => $loan->loan_id,
          'customer_id' => $loan->customer_id,
          'penalty_reason' => 'Más de 60 días de mora'
        ]);

        // Cambiar status del loan a "Castigado" (podría ser un status especial, por ahora usamos status=2)
        $this->db->where('id', $loan->loan_id)->update('loans', ['status' => 2]);

        $penalties_applied++;
      }
    }

    return $penalties_applied;
  }

  /**
   * Recalcula la amortización para las cuotas futuras después de un pago a capital
   */
  private function recalculate_amortization($loan_id, $current_loan_item_id)
  {
    // Cargar modelos necesarios
    $this->load->model('Loans_m');
    $this->load->library('Amortization');

    // Obtener datos del préstamo
    $loan = $this->Loans_m->get_loan($loan_id);
    if (!$loan) {
      throw new Exception('Préstamo no encontrado');
    }

    // Obtener todas las cuotas del préstamo
    $loan_items = $this->Loans_m->get_loanItems($loan_id);
    if (!$loan_items) {
      throw new Exception('No se encontraron cuotas para el préstamo');
    }

    // Encontrar la cuota actual
    $current_item = null;
    $future_items = [];
    foreach ($loan_items as $item) {
      if ($item->id == $current_loan_item_id) {
        $current_item = $item;
        break;
      }
    }

    if (!$current_item) {
      throw new Exception('Cuota actual no encontrada');
    }

    // Filtrar cuotas futuras
    $future_items = array_filter($loan_items, function($item) use ($current_item) {
      return $item->num_quota > $current_item->num_quota;
    });

    if (empty($future_items)) {
      // No hay cuotas futuras
      return;
    }

    // Calcular el nuevo balance después del pago
    $new_balance = $current_item->balance;

    // Número de cuotas restantes
    $remaining_periods = count($future_items);

    // Calcular frecuencia de pago
    $payment_frequency = $loan->payment_m; // mensual, quincenal, etc.

    // Calcular períodos por año
    switch ($payment_frequency) {
      case 'mensual':
        $periods_per_year = 12;
        break;
      case 'quincenal':
        $periods_per_year = 24;
        break;
      case 'semanal':
        $periods_per_year = 52;
        break;
      default:
        $periods_per_year = 12;
    }

    // Calcular tasa periódica (no se usa directamente aquí)
    // $periodic_rate = $this->Loans_m->get_period_rate($loan->interest_amount, $periods_per_year);

    // Recalcular amortización para las cuotas restantes
    $start_date = !empty($future_items) ? $future_items[0]->date : $current_item->date;
    $amortization_result = $this->amortization->calculate_french_amortization(
      $new_balance,
      $loan->interest_amount,
      $remaining_periods,
      $start_date, // fecha de la primera cuota futura como inicio
      $loan->amortization_type,
      $payment_frequency
    );

    if (!isset($amortization_result['table']) || empty($amortization_result['table'])) {
      throw new Exception('Error al recalcular amortización');
    }

    $new_table = $amortization_result['table'];

    // Actualizar las cuotas futuras
    $future_items_array = array_values($future_items); // reindex
    for ($i = 0; $i < $remaining_periods; $i++) {
      $future_item = $future_items_array[$i];
      $new_data = $new_table[$i];

      $update_data = [
        'interest_amount' => $new_data['interes'],
        'capital_amount' => $new_data['capital'],
        'fee_amount' => $new_data['cuota'],
        'balance' => $new_data['saldo']
      ];

      $this->db->where('id', $future_item->id);
      $this->db->update('loan_items', $update_data);
    }
  }

}

/* End of file Payments_m.php */
/* Location: ./application/models/Payments_m.php */
