<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controlador para APIs públicas que no requieren autenticación
 * Este controlador NO hereda de MY_Controller para evitar middleware de autenticación
 */
class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // NO cargar modelos ni librerías que requieran autenticación
        // Este controlador es completamente independiente

        // Cargar librería para manejo de errores HTTP 413
        $this->load->library('HttpErrorHandler');
    }

    /**
     * API para enviar comisión del 40% (para cobradores) - SIN AUTENTICACIÓN
     */
    public function send_commission()
    {
        // COMPLETAMENTE INDEPENDIENTE - NO USAR CODEIGNITER PARA EVITAR REDIRECCIONES
        // Detener cualquier procesamiento de CI
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            if (isset($CI->output)) {
                $CI->output->_display();
                exit;
            }
        }

        // Limpiar buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Headers JSON estrictos
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        try {
            // Obtener parámetros directamente
            $collector_id = isset($_POST['collector_id']) ? trim($_POST['collector_id']) : null;
            $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
            $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
            $selected_commissions = isset($_POST['selected_commissions']) ? trim($_POST['selected_commissions']) : null;

            if (!$collector_id) {
                echo json_encode(['success' => false, 'message' => 'ID de cobrador requerido']);
                exit;
            }

            // Validar tamaño de datos para prevenir error 413
            $request_data = $_POST;
            $this->load->library('HttpErrorHandler');
            $validation = $this->httperrorhandler->validate_data_size($request_data, 5); // 5MB límite
            if (!$validation['valid']) {
                echo json_encode(['success' => false, 'message' => $validation['error']]);
                exit;
            }

            // Conexión directa a MySQL
            $host = 'localhost';
            $user = 'root';
            $pass = '';
            $db = 'prestamo';

            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                throw new Exception('Error de conexión: ' . $conn->connect_error);
            }

            $conn->set_charset('utf8mb4');

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

            $total_commission = 0;

            // Si hay comisiones seleccionadas específicas, procesar solo esas
            if ($selected_commissions) {
                $selected_data = json_decode($selected_commissions, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'message' => 'Datos de comisiones seleccionadas inválidos']);
                    exit;
                }

                foreach ($selected_data as $commission) {
                    // Verificar si ya existe registro para esta combinación específica
                    $sql_check = "SELECT id FROM collector_commissions WHERE user_id = ? AND loan_id = ? AND client_id = ?";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->bind_param('iii', $collector_id, $commission['loan_id'], $commission['client_id']);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    if ($result_check->num_rows > 0) {
                        // Actualizar registro existente
                        $row = $result_check->fetch_assoc();
                        $sql_update = "UPDATE collector_commissions SET
                                      total_interest = ?,
                                      commission_40 = ?,
                                      status = 'enviado',
                                      sent_at = NOW(),
                                      period_start = ?,
                                      period_end = ?
                                      WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $period_start = $validated_dates ? $validated_dates['start'] : null;
                        $period_end = $validated_dates ? $validated_dates['end'] : null;
                        $stmt_update->bind_param('ddsssi', $commission['interest'], $commission['commission'], $period_start, $period_end, $row['id']);
                        $stmt_update->execute();
                    } else {
                        // Crear nuevo registro específico
                        $sql_insert = "INSERT INTO collector_commissions
                                      (user_id, loan_id, client_id, total_interest, commission_40, status, sent_at, period_start, period_end)
                                      VALUES (?, ?, ?, ?, ?, 'enviado', NOW(), ?, ?)";
                        $stmt_insert = $conn->prepare($sql_insert);
                        $period_start = $validated_dates ? $validated_dates['start'] : null;
                        $period_end = $validated_dates ? $validated_dates['end'] : null;
                        $stmt_insert->bind_param('iiiddss', $collector_id, $commission['loan_id'], $commission['client_id'], $commission['interest'], $commission['commission'], $period_start, $period_end);
                        $stmt_insert->execute();
                    }

                    $total_commission += $commission['commission'];
                }
            } else {
                // Lógica anterior para envío general (sin selección específica)
                // Obtener totales de intereses y comisión
                $where_date = '';
                if ($validated_dates) {
                    $where_date = " AND li.pay_date >= '{$validated_dates['start']} 00:00:00' AND li.pay_date <= '{$validated_dates['end']} 23:59:59'";
                }

                $sql_totals = "SELECT
                              COALESCE(SUM(li.interest_paid), 0) as total_interest,
                              COALESCE(SUM(li.interest_paid) * 0.4, 0) as total_commission
                              FROM loan_items li
                              WHERE li.paid_by = ? AND li.status = 0 AND li.interest_paid > 0{$where_date}";

                $stmt_totals = $conn->prepare($sql_totals);
                $stmt_totals->bind_param('i', $collector_id);
                $stmt_totals->execute();
                $result_totals = $stmt_totals->get_result();
                $totals = $result_totals->fetch_assoc();

                $total_interest = $totals['total_interest'];
                $total_commission = $totals['total_commission'];

                if ($total_commission <= 0) {
                    echo json_encode(['success' => false, 'message' => 'No hay comisiones pendientes para enviar']);
                    exit;
                }

                // Verificar si ya existe un registro pendiente para este período
                $sql_check_period = "SELECT id FROM collector_commissions WHERE user_id = ? AND period_start = ? AND period_end = ? AND status = 'pendiente'";
                $stmt_check_period = $conn->prepare($sql_check_period);
                $period_start = $validated_dates ? $validated_dates['start'] : null;
                $period_end = $validated_dates ? $validated_dates['end'] : null;
                $stmt_check_period->bind_param('iss', $collector_id, $period_start, $period_end);
                $stmt_check_period->execute();
                $result_check_period = $stmt_check_period->get_result();

                if ($result_check_period->num_rows > 0) {
                    // Actualizar registro existente
                    $row = $result_check_period->fetch_assoc();
                    $sql_update_period = "UPDATE collector_commissions SET
                                        total_interest = ?,
                                        commission_40 = ?,
                                        status = 'enviado',
                                        sent_at = NOW()
                                        WHERE id = ?";
                    $stmt_update_period = $conn->prepare($sql_update_period);
                    $stmt_update_period->bind_param('ddi', $total_interest, $total_commission, $row['id']);
                    $stmt_update_period->execute();
                } else {
                    // Crear nuevo registro
                    $sql_insert_period = "INSERT INTO collector_commissions
                                        (user_id, total_interest, commission_40, status, sent_at, period_start, period_end)
                                        VALUES (?, ?, ?, 'enviado', NOW(), ?, ?)";
                    $stmt_insert_period = $conn->prepare($sql_insert_period);
                    $stmt_insert_period->bind_param('iddss', $collector_id, $total_interest, $total_commission, $period_start, $period_end);
                    $stmt_insert_period->execute();
                }
            }

            $conn->close();

            echo json_encode([
                'success' => true,
                'message' => 'Comisión enviada exitosamente al administrador',
                'commission_amount' => $total_commission
            ]);

        } catch (Exception $e) {
            // Registrar error con manejo mejorado
            log_message('error', '[API Commission] Error procesando solicitud: ' . $e->getMessage());

            // Intentar notificar sobre el error si es relacionado con tamaño de datos
            if (strpos($e->getMessage(), '413') !== false || strpos($e->getMessage(), 'Entity Too Large') !== false) {
                log_message('error', '[API Commission] Error 413 detectado - posible problema de tamaño de datos');
            }

            echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
        }

        exit;
    }
}

/* End of file Api.php */
/* Location: ./application/controllers/Api.php */