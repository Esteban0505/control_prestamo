<?php
/**
 * Script de prueba para ejecutar pruebas completas de pago con el usuario 1152675687
 * Simula el flujo completo de pagos desde la búsqueda del cliente hasta la generación del ticket
 */

// Simular sesión de usuario
$_SESSION['loggedin'] = TRUE;
$_SESSION['user_id'] = 1; // Usuario administrador

echo "=== INICIANDO PRUEBAS COMPLETAS DE PAGO ===\n";
echo "Usuario: 1152675687 (DNI existente en BD)\n";
echo "URL: http://localhost/prestamo-1/admin/payments/edit\n\n";

// Paso 1: Buscar cliente por DNI
$dni_to_test = isset($argv[1]) ? $argv[1] : '122333333';
echo "PASO 1: Buscando cliente con DNI $dni_to_test...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/prestamo-1/admin/payments/ajax_searchCst');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'dni=' . $dni_to_test . '&suggest=0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Respuesta HTTP: $http_code\n";
$customer_data = json_decode($response, true);

if ($customer_data && isset($customer_data['data'])) {
    echo "✓ Cliente encontrado:\n";
    echo "  - Nombre: " . ($customer_data['data']['cst']['name'] ?? 'N/A') . "\n";
    echo "  - Loan ID: " . ($customer_data['data']['cst']['loan_id'] ?? 'N/A') . "\n";
    echo "  - Customer ID: " . ($customer_data['data']['cst']['id'] ?? 'N/A') . "\n";

    $loan_id = $customer_data['data']['cst']['loan_id'];
    $customer_id = $customer_data['data']['cst']['id'];

    // Paso 2: Obtener cuotas del cliente
    echo "\nPASO 2: Obteniendo cuotas del cliente...\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/prestamo-1/admin/payments/ajax_get_quotas');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'loan_id=' . $loan_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest'
    ]);

    $quotas_response = curl_exec($ch);
    $quotas_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Respuesta HTTP cuotas: $quotas_http_code\n";
    $quotas_data = json_decode($quotas_response, true);

    if ($quotas_data && isset($quotas_data['data']['quotas'])) {
        $quotas = $quotas_data['data']['quotas'];
        echo "✓ Cuotas encontradas: " . count($quotas) . "\n";

        // Mostrar primeras 3 cuotas como ejemplo
        for ($i = 0; $i < min(3, count($quotas)); $i++) {
            $quota = $quotas[$i];
            echo "  - Cuota #" . ($quota['num_quota'] ?? 'N/A') . ": " . number_format($quota['fee_amount'] ?? 0, 2, ',', '.') . " COP\n";
        }

        // Paso 3: Probar cada tipo de pago
        $payment_types = [
            'full' => 'Cuota completa',
            'interest' => 'Solo interés',
            'capital' => 'Pago a capital',
            'both' => 'Interés y capital',
            'total_condonacion' => 'Pago Total Anticipado',
            'custom' => 'Monto personalizado'
        ];

        foreach ($payment_types as $tipo_pago => $descripcion) {
            echo "\n=== PROBANDO TIPO DE PAGO: $descripcion ($tipo_pago) ===\n";

            // Preparar datos del pago
            $post_data = [
                'name_cst' => $customer_data['data']['cst']['name'] ?? '',
                'coin' => 'COP',
                'loan_id' => $loan_id,
                'user_id' => 1,
                'customer_id' => $customer_id,
                'tipo_pago' => $tipo_pago,
                'payment_description' => "Prueba automática - $descripcion"
            ];

            // Seleccionar cuotas según el tipo de pago
            if ($tipo_pago === 'total_condonacion') {
                // Solo la primera cuota pendiente
                $pending_quotas = array_filter($quotas, function($q) {
                    return ($q['status'] ?? 1) == 1;
                });
                if (!empty($pending_quotas)) {
                    $first_quota = reset($pending_quotas);
                    $post_data['quota_id'] = [$first_quota['id']];
                }
            } elseif ($tipo_pago === 'custom') {
                // Primeras 2 cuotas para pago personalizado
                $pending_quotas = array_filter($quotas, function($q) {
                    return ($q['status'] ?? 1) == 1;
                });
                $selected_quotas = array_slice(array_values($pending_quotas), 0, 2);
                $post_data['quota_id'] = array_column($selected_quotas, 'id');
                $post_data['custom_amount'] = 50000; // Monto personalizado de prueba
                $post_data['custom_payment_type'] = 'cuota';
            } else {
                // Primeras 2 cuotas para otros tipos
                $pending_quotas = array_filter($quotas, function($q) {
                    return ($q['status'] ?? 1) == 1;
                });
                $selected_quotas = array_slice(array_values($pending_quotas), 0, 2);
                $post_data['quota_id'] = array_column($selected_quotas, 'id');
            }

            // Convertir arrays a formato POST
            $post_fields = [];
            foreach ($post_data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $post_fields[] = $key . '[]=' . urlencode($v);
                    }
                } else {
                    $post_fields[] = $key . '=' . urlencode($value);
                }
            }
            $post_string = implode('&', $post_fields);

            echo "Enviando datos: " . substr($post_string, 0, 200) . "...\n";

            // Ejecutar el pago
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/prestamo-1/admin/payments/ticket');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $ticket_response = curl_exec($ch);
            $ticket_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo "Respuesta HTTP ticket: $ticket_http_code\n";

            if ($ticket_http_code == 200) {
                echo "✓ Ticket generado exitosamente\n";

                // Verificar elementos clave en el ticket
                if (strpos($ticket_response, 'Pago Realizado Exitosamente') !== false) {
                    echo "  ✓ Estado de pago correcto\n";
                } else {
                    echo "  ✗ Estado de pago no encontrado\n";
                }

                if (strpos($ticket_response, 'Sin fecha') === false) {
                    echo "  ✓ Fechas correctas en ticket\n";
                } else {
                    echo "  ✗ Fechas incorrectas encontradas\n";
                }

                // Verificar totales no cero
                if (preg_match('/Total Pagado:.*?([0-9,]+\.[0-9]{2})/', $ticket_response, $matches)) {
                    $total_pagado = str_replace(',', '', $matches[1]);
                    if ($total_pagado > 0) {
                        echo "  ✓ Total pagado correcto: $total_pagado COP\n";
                    } else {
                        echo "  ✗ Total pagado es cero\n";
                    }
                }

                // Verificar estados de cuotas
                $status_matches = [];
                preg_match_all('/badge[^>]*>([^<]*)/', $ticket_response, $status_matches);
                if (!empty($status_matches[1])) {
                    echo "  ✓ Estados de cuotas encontrados: " . implode(', ', array_unique($status_matches[1])) . "\n";
                }

            } else {
                echo "✗ Error al generar ticket - Código: $ticket_http_code\n";
                echo "Respuesta: " . substr($ticket_response, 0, 500) . "\n";
            }

            // Pequeña pausa entre pruebas
            sleep(1);
        }

    } else {
        echo "✗ Error al obtener cuotas\n";
        echo "Respuesta: $quotas_response\n";
    }

} else {
    echo "✗ Cliente no encontrado\n";
    echo "Respuesta: $response\n";
}

echo "\n=== FIN DE PRUEBAS ===\n";
?>