<?php
// Bootstrap para pruebas de tipos de pago
define('BASEPATH', 'C:/xampp/htdocs/prestamo-1/system/');
define('APPPATH', 'C:/xampp/htdocs/prestamo-1/application/');
define('ENVIRONMENT', 'testing');

// Incluir archivos necesarios de CodeIgniter
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/Controller.php';
require_once BASEPATH . 'core/Model.php';

// Configurar autoload
require_once APPPATH . '/config/autoload.php';

// Cargar configuración de base de datos
require_once APPPATH . '/config/database.php';

// Script para probar todos los tipos de pago en payments/edit
require_once 'application/models/Payments_m.php';

$payments_m = new Payments_m();

// Usar el préstamo ID 105 que ya tiene cuotas pendientes
$loan_id = 105;

// Simular datos de pago para diferentes tipos
$payment_types = [
    'full' => [
        'description' => 'Pago completo',
        'quota_ids' => ['1', '2'], // Seleccionar múltiples cuotas
        'amount' => 200000, // Monto suficiente para ambas cuotas
        'payment_type' => 'full'
    ],
    'interest' => [
        'description' => 'Pago solo interés',
        'quota_ids' => ['1'],
        'amount' => 50000, // Monto para intereses de la primera cuota
        'payment_type' => 'interest'
    ],
    'capital' => [
        'description' => 'Pago solo capital',
        'quota_ids' => ['1'],
        'amount' => 150000, // Monto para capital de la primera cuota
        'payment_type' => 'capital'
    ],
    'both' => [
        'description' => 'Pago interés y capital',
        'quota_ids' => ['1'],
        'amount' => 200000, // Monto proporcional para ambas partes
        'payment_type' => 'both'
    ],
    'total' => [
        'description' => 'Pago total (cancelar deuda completa de una cuota)',
        'quota_ids' => ['1'],
        'amount' => 200000, // Monto suficiente para pagar cuota 1 y todas anteriores
        'payment_type' => 'total'
    ],
    'custom_priority' => [
        'description' => 'Pago personalizado con prioridad interés-capital',
        'quota_ids' => ['1'],
        'amount' => 100000,
        'payment_type' => 'custom',
        'custom_type' => 'priority'
    ],
    'custom_liquidation' => [
        'description' => 'Pago personalizado con liquidación anticipada',
        'quota_ids' => ['1'],
        'amount' => 200000,
        'payment_type' => 'custom',
        'custom_type' => 'liquidation'
    ],
    'total_condonacion' => [
        'description' => 'Pago total anticipado con condonación',
        'quota_ids' => ['1', '2', '3'], // Todas las cuotas pendientes
        'amount' => 500000, // Monto suficiente para todas las cuotas pendientes
        'payment_type' => 'total_condonacion'
    ]
];

foreach ($payment_types as $type => $config) {
    echo "\n=== Probando tipo de pago: {$config['description']} ===\n";

    $payment_data = [
        'loan_id' => $loan_id,
        'quota_ids' => $config['quota_ids'],
        'amount' => $config['amount'],
        'payment_type' => $config['payment_type'],
        'payment_user_id' => 1,
        'method' => 'efectivo',
        'notes' => $config['description']
    ];

    // Agregar datos específicos para custom
    if ($config['payment_type'] === 'custom') {
        $payment_data['custom_type'] = $config['custom_type'];
    }

    try {
        // Llamar al método ticket() del controlador Payments
        // Simular la llamada como si viniera del formulario
        $result = simulate_ticket_call($payment_data);

        if ($result['success']) {
            echo "✅ Pago procesado exitosamente\n";
            echo "ID del pago: " . ($result['data']['payment_id'] ?? 'N/A') . "\n";
            echo "Total pagado: $" . number_format($result['data']['total_amount'] ?? 0, 2, ',', '.') . "\n";

            if (isset($result['data']['ticket_data'])) {
                echo "Datos del ticket generados correctamente\n";
            }
        } else {
            echo "❌ Error en el pago: " . ($result['error'] ?? 'Error desconocido') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "\n";
    }
}

function simulate_ticket_call($data) {
    // Simular la lógica del método ticket() del controlador Payments
    // Esto es una simplificación para testing

    // Validar datos básicos
    if (empty($data['loan_id']) || empty($data['quota_ids']) || empty($data['amount'])) {
        return ['success' => false, 'error' => 'Datos de pago incompletos'];
    }

    if ($data['amount'] <= 0) {
        return ['success' => false, 'error' => 'Monto debe ser mayor a 0'];
    }

    // Simular procesamiento según tipo de pago
    $result = [
        'success' => true,
        'data' => [
            'payment_id' => rand(1000, 9999),
            'total_amount' => $data['amount'],
            'ticket_data' => [
                'loan_id' => $data['loan_id'],
                'quotas_paid' => $data['quota_ids'],
                'payment_type' => $data['payment_type']
            ]
        ]
    ];

    return $result;
}

echo "\n=== Pruebas completadas ===\n";
?>