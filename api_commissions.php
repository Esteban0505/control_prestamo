<?php
// API independiente para comisiones - sin CodeIgniter
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Obtener parámetros directamente
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

if (!$user_id) {
    echo json_encode(['error' => 'ID de cobrador requerido']);
    exit;
}

try {
    // Conexión directa a MySQL
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'prestamobd';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    // Validar fechas
    $validated_dates = null;
    if ($start_date && $end_date) {
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        if ($start_timestamp && $end_timestamp && $start_timestamp <= $end_timestamp) {
            $validated_dates = [
                'start' => date('Y-m-d', $start_timestamp),
                'end' => date('Y-m-d', $end_timestamp)
            ];
        }
    }

    // Obtener detalles de intereses por cliente
    $clients = [];
    $where_date = '';
    if ($validated_dates) {
        $where_date = " AND li.pay_date >= '{$validated_dates['start']} 00:00:00' AND li.pay_date <= '{$validated_dates['end']} 23:59:59'";
    }

    $sql = "SELECT
        c.id as customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.dni,
        l.id as loan_id,
        l.credit_amount,
        l.num_fee as total_quotas,
        COUNT(CASE WHEN li.status = 0 THEN 1 END) as payments_made,
        COUNT(li.id) as total_quotas_with_data,
        SUM(CASE WHEN li.status = 0 THEN li.interest_paid ELSE 0 END) as total_interest_paid,
        SUM(CASE WHEN li.status = 0 THEN li.interest_paid ELSE 0 END) * 0.4 as interest_commission_40,
        SUM(CASE WHEN li.status = 0 THEN li.fee_amount ELSE 0 END) as total_collected,
        MAX(CASE WHEN li.status = 0 THEN li.pay_date END) as last_payment_date,
        CONCAT(
            COUNT(CASE WHEN li.status = 0 THEN 1 END),
            '/',
            l.num_fee
        ) as progress
    FROM customers c
    LEFT JOIN loans l ON l.customer_id = c.id
    LEFT JOIN loan_items li ON li.loan_id = l.id AND li.paid_by = ?
    WHERE li.id IS NOT NULL{$where_date}
    GROUP BY c.id, l.id
    HAVING payments_made > 0
    ORDER BY total_interest_paid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error en la preparación de la consulta: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_object()) {
        $clients[] = $row;
    }

    // Calcular totales
    $total_interest = 0;
    $total_commission = 0;
    foreach ($clients as $client) {
        $total_interest += $client->total_interest_paid ?? 0;
        $total_commission += $client->interest_commission_40 ?? 0;
    }

    // Verificar estado de envío de comisión por cada cliente/préstamo
    foreach ($clients as &$client) {
        $client->commission_status = 'pendiente';
        $client->commission_sent_at = null;

        // La tabla collector_commissions no tiene campos status ni sent_at
        // Por ahora, marcar todos como pendientes ya que no hay tracking de envío
    }

    // Estado general (para compatibilidad con código existente)
    $send_status = 'pendiente';

    $conn->close();

    // Asegurar que la respuesta JSON sea válida
    $response = [
        'clients' => $clients,
        'total_interest' => $total_interest,
        'total_commission' => $total_commission,
        'send_status' => $send_status
    ];

    // Verificar que no haya datos corruptos antes de enviar
    $json_output = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json_output === false) {
        echo json_encode(['error' => 'Error al generar respuesta JSON']);
    } else {
        echo $json_output;
    }

} catch (Exception $e) {
    $error_response = ['error' => 'Error interno del servidor: ' . $e->getMessage()];
    echo json_encode($error_response);
}

exit;