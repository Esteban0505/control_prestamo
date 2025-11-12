<?php

require_once 'bootstrap.php';

class PaymentsControllerTest extends PHPUnit_Framework_TestCase {

    protected $CI;
    protected $controller;

    protected function setUp() {
        $this->CI = init_codeigniter();
        setup_test_database();

        // Crear instancia del controlador
        $this->controller = new Payments();
        $this->controller->load = $this->CI->load;
        $this->controller->input = $this->CI->input;
        $this->controller->session = $this->CI->session;
        $this->controller->db = $this->CI->db;
        $this->controller->payments_m = $this->CI->payments_m;
        $this->controller->loans_m = $this->CI->loans_m;
        $this->controller->customers_m = $this->CI->customers_m;
        $this->controller->coins_m = $this->CI->coins_m;
        $this->controller->user_m = $this->CI->user_m;
    }

    protected function tearDown() {
        teardown_test_database();
    }

    /**
     * Test para ajax_searchCst con DNI válido
     */
    public function testAjaxSearchCstValidDni() {
        // Preparar datos de prueba
        $test_customer = [
            'id' => 1,
            'dni' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez'
        ];
        $test_loan = [
            'id' => 1,
            'customer_id' => 1,
            'credit_amount' => 10000,
            'status' => 1
        ];

        // Insertar datos de prueba
        $this->CI->db->insert('customers', $test_customer);
        $this->CI->db->insert('loans', $test_loan);

        // Simular POST request
        $_POST['dni'] = '12345678';
        $_POST['suggest'] = '0';

        // Ejecutar método
        $this->controller->ajax_searchCst();

        // Verificar que se haya enviado respuesta JSON
        $this->expectOutputRegex('/{"success":true,"data":{"cst":/');
    }

    /**
     * Test para ajax_searchCst con DNI vacío
     */
    public function testAjaxSearchCstEmptyDni() {
        $_POST['dni'] = '';
        $_POST['suggest'] = '0';

        $this->controller->ajax_searchCst();

        $this->expectOutputRegex('/{"success":false,"error":/');
    }

    /**
     * Test para ajax_get_quotas con loan_id válido
     */
    public function testAjaxGetQuotasValidLoanId() {
        // Preparar datos de prueba
        $test_loan = ['id' => 1, 'customer_id' => 1];
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'num_quota' => 1,
            'fee_amount' => 1000,
            'status' => 1
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota);

        $_POST['loan_id'] = '1';

        $this->controller->ajax_get_quotas();

        $this->expectOutputRegex('/{"success":true,"data":{"quotas":/');
    }

    /**
     * Test para ajax_get_quotas con loan_id inválido
     */
    public function testAjaxGetQuotasInvalidLoanId() {
        $_POST['loan_id'] = 'invalid';

        $this->controller->ajax_get_quotas();

        $this->expectOutputRegex('/{"success":false,"error":/');
    }

    /**
     * Test para validación de datos de pago
     */
    public function testPrepareAndValidatePaymentDataValid() {
        // Preparar datos POST
        $_POST = [
            'name_cst' => 'Juan Pérez',
            'coin' => '1',
            'loan_id' => '1',
            'user_id' => '1',
            'tipo_pago' => 'full',
            'quota_id' => ['1'],
            'customer_id' => '1',
            'amount' => '1000'
        ];

        $result = $this->controller->prepare_and_validate_payment_data();

        $this->assertArrayHasKey('tipo_pago', $result);
        $this->assertEquals('full', $result['tipo_pago']);
    }

    /**
     * Test para filtrado de cuotas pendientes
     */
    public function testFilterPendingQuotas() {
        // Preparar datos de prueba
        $test_quota_pending = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'balance' => 1000
        ];
        $test_quota_paid = [
            'id' => 2,
            'loan_id' => 1,
            'status' => 0,
            'balance' => 0
        ];

        $this->CI->db->insert('loan_items', $test_quota_pending);
        $this->CI->db->insert('loan_items', $test_quota_paid);

        $quota_ids = ['1', '2'];
        $result = $this->controller->filter_pending_quotas($quota_ids, 1, 1);

        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]);
    }

    /**
     * Test para procesamiento de pago completo
     */
    public function testProcessFullPayment() {
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

        $result = $this->controller->process_full_payment(['1'], 1);

        $this->assertCount(1, $result);
        $this->assertEquals('full', $result[0]['type']);
        $this->assertEquals(1000, $result[0]['amount']);
    }

    /**
     * Test para procesamiento de pago solo intereses
     */
    public function testProcessInterestOnlyPayment() {
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

        $result = $this->controller->process_interest_only_payment(['1'], 1);

        $this->assertCount(1, $result);
        $this->assertEquals('interest', $result[0]['type']);
        $this->assertEquals(200, $result[0]['amount']);
    }

    /**
     * Test para procesamiento de pago solo capital
     */
    public function testProcessCapitalOnlyPayment() {
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

        $result = $this->controller->process_capital_only_payment(['1'], 1);

        $this->assertCount(1, $result);
        $this->assertEquals('capital', $result[0]['type']);
        $this->assertEquals(800, $result[0]['amount']);
    }

    /**
     * Test para procesamiento de pago interés y capital
     */
    public function testProcessInterestCapitalPayment() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->controller->process_interest_capital_payment(['1'], 1);

        $this->assertCount(1, $result);
        $this->assertEquals('both', $result[0]['type']);
        $this->assertEquals(1000, $result[0]['amount']);
    }

    /**
     * Test para procesamiento de pago total
     */
    public function testProcessTotalPayment() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200,
            'num_quota' => 1
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->controller->process_total_payment(['1'], 1);

        $this->assertCount(1, $result);
        $this->assertEquals('total', $result[0]['type']);
        $this->assertEquals(1000, $result[0]['amount']);
    }

    /**
     * Test para procesamiento de pago personalizado
     */
    public function testProcessCustomPayment() {
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000,
            'capital_amount' => 800,
            'interest_amount' => 200,
            'balance' => 1000
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $result = $this->controller->process_custom_payment(['1'], 500, 'interest_capital', 1);

        $this->assertNotEmpty($result);
        $this->assertEquals('custom_priority', $result[0]['type']);
    }

    /**
     * Test para cálculo de total del ticket
     */
    public function testCalculateTicketTotalAmount() {
        $payment_data = [
            'tipo_pago' => 'full',
            'custom_amount' => 0,
            'amount' => 1000
        ];
        $processed_quotas = [['amount' => 1000]];
        $data = ['quotasPaid' => [['fee_amount' => 1000]]];

        $result = $this->controller->calculate_ticket_total_amount($payment_data, $processed_quotas, $data);

        $this->assertEquals(1000, $result);
    }

    /**
     * Test para verificación de cierre de préstamo
     */
    public function testCheckAndCloseLoan() {
        // Preparar datos de prueba - préstamo con cuotas pagadas
        $test_loan = ['id' => 1, 'customer_id' => 1];
        $test_quota_paid = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 0 // Pagada
        ];

        $this->CI->db->insert('loans', $test_loan);
        $this->CI->db->insert('loan_items', $test_quota_paid);

        $this->controller->check_and_close_loan(1, 1);

        // Verificar que el préstamo se cerró
        $loan = $this->CI->db->where('id', 1)->get('loans')->row();
        $this->assertEquals(0, $loan->status);
    }

    /**
     * Test para método process_payment del controlador
     */
    public function testProcessPayment() {
        // Preparar datos POST
        $_POST = [
            'quota_id' => '1',
            'loan_id' => '1'
        ];

        // Preparar datos de prueba
        $test_quota = [
            'id' => 1,
            'loan_id' => 1,
            'status' => 1,
            'fee_amount' => 1000
        ];
        $this->CI->db->insert('loan_items', $test_quota);

        $this->controller->process_payment();

        $this->expectOutputRegex('/{"success":true,"message":"Pago procesado exitosamente"}/');
    }
}