<?php

require_once 'bootstrap.php';

class PaymentsModelTest extends PHPUnit_Framework_TestCase {

    protected $CI;
    protected $model;

    protected function setUp() {
        $this->CI = init_codeigniter();
        setup_test_database();

        $this->model = $this->CI->payments_m;
    }

    protected function tearDown() {
        teardown_test_database();
    }

    /**
     * Test para get_payments
     */
    public function testGetPayments() {
        // Preparar datos de prueba
        $test_customer = [
            'id' => 1,
            'dni' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez'
        ];
        $test_loan = ['id' => 1, 'customer_id' => 1];
        $test_payment = [
            'id' => 1,
            'loan_id' => 1,
            'num_quota' => 1,
            'fee_amount' => 1000,
            'pay_date' => '2023-01-01 10:00:00'
        ];

        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_payment);

        $result = $this->model->get_payments();

        $this->assertNotEmpty($result);
        $this->assertEquals('12345678', $result[0]->dni);
    }

    /**
     * Test para get_searchCst con DNI válido
     */
    public function testGetSearchCstValidDni() {
        // Preparar datos de prueba
        $test_customer = [
            'id' => 1,
            'dni' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'user_id' => 1
        ];
        $test_loan = [
            'id' => 1,
            'customer_id' => 1,
            'credit_amount' => 10000,
            'status' => 1
        ];
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'balance' => 1000
        ];

        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->get_searchCst('12345678', false);

        $this->assertNotNull($result);
        $this->assertEquals('12345678', $result->dni);
        $this->assertEquals(10000, $result->credit_amount);
    }

    /**
     * Test para get_searchCst con DNI no encontrado
     */
    public function testGetSearchCstNotFound() {
        $result = $this->model->get_searchCst('99999999', false);

        $this->assertNull($result);
    }

    /**
     * Test para get_quotasCst
     */
    public function testGetQuotasCst() {
        // Preparar datos de prueba
        $test_loan = ['id' => 1];
        $test_quotas = [
            [
                'id' => 1,
                'loan_id' => 1,
                'num_quota' => 1,
                'fee_amount' => 1000,
                'interest_amount' => 200,
                'capital_amount' => 800,
                'balance' => 1000,
                'status' => 1
            ],
            [
                'id' => 2,
                'loan_id' => 1,
                'num_quota' => 2,
                'fee_amount' => 1000,
                'interest_amount' => 180,
                'capital_amount' => 820,
                'balance' => 1000,
                'status' => 1
            ]
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quotas[0]);
        $this->CI->db->insert('loan_items', $test_quotas[1]);

        $result = $this->model->get_quotasCst(1);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
    }

    /**
     * Test para update_quota
     */
    public function testUpdateQuota() {
        // Preparar datos de prueba
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'balance' => 1000
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $update_data = [
            'status' => 0,
            'balance' => 0,
            'paid_by' => 1,
            'pay_date' => '2023-01-01 10:00:00'
        ];

        $result = $this->model->update_quota($update_data, 1);

        $this->assertTrue($result);

        // Verificar actualización
        $updated_quota = $this->CI->db->where('id', 1)->get('loan_items')->row();
        $this->assertEquals(0, $updated_quota->status);
        $this->assertEquals(0, $updated_quota->balance);
    }

    /**
     * Test para check_cstLoan
     */
    public function testCheckCstLoan() {
        // Preparar datos de prueba
        $test_loan = ['id' => 1];
        $test_quotas = [
            ['id' => 1, 'loan_id' => 1, 'status' => 1], // Pendiente
            ['id' => 2, 'loan_id' => 1, 'status' => 0]  // Pagada
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quotas[0]);
        $this->CI->db->insert('loan_items', $test_quotas[1]);

        $result = $this->model->check_cstLoan(1);

        $this->assertTrue($result); // Hay cuotas pendientes
    }

    /**
     * Test para update_cstLoan
     */
    public function testUpdateCstLoan() {
        // Preparar datos de prueba
        $test_loan = ['id' => 1, 'status' => 1];
        $test_customer = ['id' => 1, 'loan_status' => 1];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('customers', $test_customer);

        $this->model->update_cstLoan(1, 1);

        // Verificar actualización
        $updated_loan = $this->CI->db->where('id', 1)->get('loans')->row();
        $updated_customer = $this->CI->db->where('id', 1)->get('customers')->row();

        $this->assertEquals(0, $updated_loan->status);
        $this->assertEquals(0, $updated_customer->loan_status);
    }

    /**
     * Test para get_quotasPaid
     */
    public function testGetQuotasPaid() {
        // Preparar datos de prueba
        $test_quotas = [
            ['id' => 1, 'loan_id' => 1, 'num_quota' => 1, 'fee_amount' => 1000],
            ['id' => 2, 'loan_id' => 1, 'num_quota' => 2, 'fee_amount' => 1000]
        ];

        $this->CI->db->insert('loan_items', $test_quotas[0]);
        $this->CI->db->insert('loan_items', $test_quotas[1]);

        $result = $this->model->get_quotasPaid(['1', '2'], 1);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(2, $result[1]->id);
    }

    /**
     * Test para get_loan_item
     */
    public function testGetLoanItem() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'fee_amount' => 1000,
            'status' => 1
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->get_loan_item(1);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(1000, $result->fee_amount);
    }

    /**
     * Test para process_manual_payment con tipo 'full'
     */
    public function testProcessManualPaymentFull() {
        // Preparar datos de prueba
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->process_manual_payment(1, 1000, 'Pago completo', 'full', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(1000, $result['data']['remaining_interest'] + $result['data']['remaining_capital']);
    }

    /**
     * Test para process_manual_payment con tipo 'interest'
     */
    public function testProcessManualPaymentInterest() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200,
            'interest_paid' => 0
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->process_manual_payment(1, 200, 'Pago intereses', 'interest', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['remaining_interest']);
    }

    /**
     * Test para process_manual_payment con tipo 'capital'
     */
    public function testProcessManualPaymentCapital() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200,
            'capital_paid' => 0
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->process_manual_payment(1, 800, 'Pago capital', 'capital', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['remaining_capital']);
    }

    /**
     * Test para process_manual_payment con monto inválido
     */
    public function testProcessManualPaymentInvalidAmount() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->process_manual_payment(1, 0, 'Pago inválido', 'full', 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('El monto debe ser mayor a 0', $result['error']);
    }

    /**
     * Test para process_manual_payment con cuota ya pagada
     */
    public function testProcessManualPaymentAlreadyPaid() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 0, // Ya pagada
            'fee_amount' => 1000
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->process_manual_payment(1, 1000, 'Pago duplicado', 'full', 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('La cuota ya está pagada', $result['error']);
    }

    /**
     * Test para calculate_balance_after_payment - sistema francés
     */
    public function testCalculateBalanceAfterPaymentFrench() {
        // Preparar datos de prueba
        $test_loan = [
            'id' => 1,
            'credit_amount' => 10000,
            'interest_amount' => 12, // 12%
            'amortization_type' => 'francesa'
        ];
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'balance' => 10000,
            'num_quota' => 1
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->model->calculate_balance_after_payment(1, 1000, 'both', 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('interes', $result['data']);
        $this->assertArrayHasKey('capital', $result['data']);
        $this->assertArrayHasKey('nuevo_saldo', $result['data']);
    }

    /**
     * Test para update_loan_balance_and_status
     */
    public function testUpdateLoanBalanceAndStatus() {
        // Preparar datos de prueba
        $test_loan = ['id' => 1, 'status' => 1];
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'balance' => 0 // Saldo pagado
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota);

        $this->model->update_loan_balance_and_status(1);

        // Verificar que el préstamo se cerró
        $updated_loan = $this->CI->db->where('id', 1)->get('loans')->row();
        $this->assertEquals(0, $updated_loan->status);
    }

    /**
     * Test para get_overdue_clients
     */
    public function testGetOverdueClients() {
        // Preparar datos de prueba
        $test_customer = [
            'id' => 1,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dni' => '12345678'
        ];
        $test_loan = ['id' => 1, 'customer_id' => 1, 'status' => 1];
        $test_quota_overdue = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'date' => '2023-01-01', // Fecha vencida
            'fee_amount' => 1000
        ];

        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota_overdue);

        $result = $this->model->get_overdue_clients();

        $this->assertNotEmpty($result);
        $this->assertEquals('Juan Pérez', $result[0]->client_name);
    }

    /**
     * Test para get_overdue_statistics
     */
    public function testGetOverdueStatistics() {
        // Preparar datos de prueba similares al test anterior
        $test_customer = [
            'id' => 1,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dni' => '12345678'
        ];
        $test_loan = ['id' => 1, 'customer_id' => 1, 'status' => 1];
        $test_quota_overdue = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'date' => '2023-01-01',
            'fee_amount' => 1000
        ];

        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota_overdue);

        $result = $this->model->get_overdue_statistics();

        $this->assertArrayHasKey('high_risk_count', $result);
        $this->assertArrayHasKey('medium_risk_count', $result);
        $this->assertArrayHasKey('low_risk_count', $result);
        $this->assertArrayHasKey('total_amount', $result);
    }

    /**
     * Test para get_recovery_rate
     */
    public function testGetRecoveryRate() {
        // Preparar datos de prueba - pagos recientes
        $test_payment = [
            'id' => 1,
            'loan_id' => 1,
            'amount' => 1000,
            'payment_date' => date('Y-m-d H:i:s') // Pago reciente
        ];

        $this->CI->db->insert('payments', $test_payment);

        $result = $this->model->get_recovery_rate();

        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * Test para get_top_overdue_clients
     */
    public function testGetTopOverdueClients() {
        // Preparar datos de prueba
        $test_customer = [
            'id' => 1,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dni' => '12345678'
        ];
        $test_loan = ['id' => 1, 'customer_id' => 1, 'status' => 1];
        $test_quota_overdue = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'date' => '2023-01-01',
            'fee_amount' => 2000 // Monto alto para estar en top
        ];

        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota_overdue);

        $result = $this->model->get_top_overdue_clients(5);

        $this->assertNotEmpty($result);
        $this->assertEquals('Juan Pérez', $result[0]->client_name);
        $this->assertEquals(2000, $result[0]->total_adeudado);
    }
}