<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports_m extends CI_Model {

  public function get_reportLoan($coin_id)
  {
    $this->db->select('c.short_name, sum(l.credit_amount) as sum_credit');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where('l.coin_id', $coin_id);
    $cr = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interest');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where('l.coin_id', $coin_id);
    $cr_interest = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interestPaid');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where(['l.coin_id' => $coin_id, 'l.status' => 0]);
    $cr_interestPaid = $this->db->get('loans l')->row();

    $this->db->select('c.short_name, sum(TRUNCATE(l.credit_amount*(l.interest_amount/100) + l.credit_amount,2)) AS cr_interestPay');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');
    $this->db->where(['l.coin_id' => $coin_id, 'l.status' => 1]);
    $cr_interestPay = $this->db->get('loans l')->row();

    $credits = [$cr, $cr_interest, $cr_interestPaid, $cr_interestPay];

    return $credits;
  }

  public function get_reportCoin($coin_id)
  {
    $this->db->where('id', $coin_id);

    return $this->db->get('coins')->row(); 
  }

  public function get_reportDates($coin_id, $start_date, $end_date)
  {
    $this->db->select("id, date, credit_amount, interest_amount, num_fee, payment_m,
     (num_fee*fee_amount) AS total_int, status");
    $this->db->from('loans');
    $this->db->where('coin_id', $coin_id);
    $this->db->where("date BETWEEN '{$start_date}' AND '{$end_date}'");

    return $this->db->get()->result(); 
  }

  public function get_reportCsts()
  {
    $this->db->select("id, dni, CONCAT(first_name, ' ',last_name) AS customer");
    $this->db->from('customers');
    $this->db->where('loan_status', 1);

    return $this->db->get()->result(); 
  }

  public function get_reportLC($customer_id)
  {
    $this->db->select("l.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, co.short_name, co.name");
    $this->db->from('loans l');
    $this->db->join('customers c', 'c.id = l.customer_id', 'left');
    $this->db->join('coins co', 'co.id = l.coin_id', 'left');
    $this->db->where('l.customer_id', $customer_id);

    return $this->db->get()->result(); 
  }

  public function get_reportLI($loan_id)
  {
    $this->db->where('loan_id', $loan_id);

    return $this->db->get('loan_items')->result(); 
  }

  /**
   * Resumen de pagos por cliente
   * Retorna: customer_id, customer_name, payments_count, total_paid, last_payment
   */
  public function get_payments_by_customer()
  {
    // Basado en loan_items (status=0 pagado)
    $this->db->select("c.id AS customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name, COUNT(li.id) AS payments_count, SUM(li.fee_amount) AS total_paid, MAX(li.pay_date) AS last_payment")
             ->from('loan_items li')
             ->join('loans l', 'l.id = li.loan_id', 'left')
             ->join('customers c', 'c.id = l.customer_id', 'left')
             ->where('li.status', 0)
             ->group_by('c.id')
             ->order_by('payments_count', 'DESC');

    return $this->db->get()->result();
  }

  /**
   * Top usuarios cobradores por cantidad de cobranzas
   * Retorna: user_id, user_name, payments_count
   */
  public function get_top_collectors()
  {
    // Si existe columna 'paid_by' en loan_items, se usa para atribuir la cobranza
    if ($this->db->field_exists('paid_by', 'loan_items')) {
      $this->db->select("u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name, COUNT(li.id) AS payments_count")
               ->from('loan_items li')
               ->join('users u', 'u.id = li.paid_by', 'left')
               ->where('li.status', 0)
               ->where('li.paid_by IS NOT NULL', null, false)
               ->group_by('u.id')
               ->order_by('payments_count', 'DESC');
      return $this->db->get()->result();
    }

    // Sin columna de auditoría no se puede atribuir al usuario
    return [];
  }

  /**
   * Cliente con mayor racha (consecutivos) de pagos realizados por número de cuota dentro de cada préstamo
   * Calcula en PHP: por cada cliente y préstamo, cuenta secuencias consecutivas de loan_items pagados (status=0)
   * Retorna array con: customer_id, customer_name, max_streak, details (opcional)
   */
  public function get_customer_with_longest_paid_streak()
  {
    // Traer cuotas pagadas con su cliente y préstamo
    $this->db->select('c.id AS customer_id, c.first_name, c.last_name, l.id AS loan_id, li.num_quota, li.status')
             ->from('loan_items li')
             ->join('loans l', 'l.id = li.loan_id', 'left')
             ->join('customers c', 'c.id = l.customer_id', 'left')
             ->where('li.status', 0)
             ->order_by('c.id ASC, l.id ASC, li.num_quota ASC');

    $rows = $this->db->get()->result();
    if (empty($rows)) {
      return null;
    }

    $maxByCustomer = [];
    $currentCustomer = null;
    $currentLoan = null;
    $prevQuota = null;
    $currentStreak = 0;

    foreach ($rows as $row) {
      $customerKey = $row->customer_id;
      $loanKey = $row->loan_id;

      if ($currentCustomer !== $customerKey || $currentLoan !== $loanKey) {
        // Reiniciar para nuevo préstamo o cliente
        $currentCustomer = $customerKey;
        $currentLoan = $loanKey;
        $prevQuota = null;
        $currentStreak = 0;
      }

      if ($prevQuota !== null && ((int)$row->num_quota === (int)$prevQuota + 1)) {
        $currentStreak += 1;
      } else {
        // Inicia una nueva racha de al menos 1
        $currentStreak = 1;
      }

      $prevQuota = (int)$row->num_quota;

      if (!isset($maxByCustomer[$customerKey])) {
        $maxByCustomer[$customerKey] = [
          'customer_id' => $row->customer_id,
          'customer_name' => trim($row->first_name.' '.$row->last_name),
          'max_streak' => $currentStreak,
        ];
      } else if ($currentStreak > $maxByCustomer[$customerKey]['max_streak']) {
        $maxByCustomer[$customerKey]['max_streak'] = $currentStreak;
      }
    }

    // Encontrar el máximo global
    $top = null;
    foreach ($maxByCustomer as $entry) {
      if ($top === null || $entry['max_streak'] > $top['max_streak']) {
        $top = $entry;
      }
    }

    return $top; // null o ['customer_id'=>, 'customer_name'=>, 'max_streak'=>]
  }

  /**
   * Estadísticas para dashboard: Total de préstamos por tipo de moneda
   */
  public function get_currency_loans($start_date = null, $end_date = null)
  {
    $this->db->select('c.name, SUM(l.credit_amount) AS total_amount');
    $this->db->from('loans l');
    $this->db->join('coins c', 'c.id = l.coin_id', 'left');

    if ($start_date && $end_date) {
      $this->db->where('l.date >=', $start_date);
      $this->db->where('l.date <=', $end_date);
    }

    $this->db->group_by('c.name');
    return $this->db->get()->result();
  }

  /**
   * Estadísticas para dashboard: Monto total prestado por mes
   */
  public function get_loan_amounts_by_month($start_date = null, $end_date = null)
  {
    $this->db->select('MONTH(date) AS month, YEAR(date) AS year, SUM(credit_amount) AS total_amount');
    $this->db->from('loans');

    if ($start_date && $end_date) {
      $this->db->where('date >=', $start_date);
      $this->db->where('date <=', $end_date);
    }

    $this->db->group_by('YEAR(date), MONTH(date)');
    $this->db->order_by('YEAR(date) ASC, MONTH(date) ASC');
    return $this->db->get()->result();
  }

  /**
   * Estadísticas para dashboard: Pagos recibidos por mes
   */
  public function get_received_payments_by_month($start_date = null, $end_date = null)
  {
    $this->db->select('MONTH(pay_date) AS month, YEAR(pay_date) AS year, SUM(fee_amount) AS total_payments');
    $this->db->from('loan_items');
    $this->db->where('status', 0); // Pagado

    if ($start_date && $end_date) {
      $this->db->where('pay_date >=', $start_date);
      $this->db->where('pay_date <=', $end_date);
    }

    $this->db->group_by('YEAR(pay_date), MONTH(pay_date)');
    $this->db->order_by('YEAR(pay_date) ASC, MONTH(pay_date) ASC');
    return $this->db->get()->result();
  }

  /**
   * Estadísticas para dashboard: Pagos esperados por mes
   */
  public function get_expected_payments_by_month($start_date = null, $end_date = null)
  {
    $this->db->select('MONTH(date) AS month, YEAR(date) AS year, SUM(fee_amount) AS total_expected');
    $this->db->from('loan_items');

    if ($start_date && $end_date) {
      $this->db->where('date >=', $start_date);
      $this->db->where('date <=', $end_date);
    }

    $this->db->group_by('YEAR(date), MONTH(date)');
    $this->db->order_by('YEAR(date) ASC, MONTH(date) ASC');
    return $this->db->get()->result();
  }

}

/* End of file Reports_m.php */
/* Location: ./application/models/Reports_m.php */