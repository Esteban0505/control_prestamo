<?php
/**
 * Script de diagnóstico para identificar inconsistencias en cálculos de préstamos
 * Ejecutar desde el navegador: http://localhost/prestamo-1/diagnostic_calculations.php
 */

// Configuración básica para acceder a CodeIgniter
define('BASEPATH', dirname(__FILE__) . '/system/');
define('APPPATH', dirname(__FILE__) . '/application/');
define('ENVIRONMENT', 'development');

// Incluir archivos necesarios
require_once 'index.php';

class DiagnosticCalculations {

    private $CI;

    public function __construct() {
        // Obtener instancia de CodeIgniter
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('url');
    }

    public function run_diagnostic() {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Diagnóstico de Cálculos - Sistema de Préstamos</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #007bff; color: white; padding: 20px; border-radius: 5px; }
                .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
                .error { background: #f8d7da; border-color: #f5c6cb; }
                .warning { background: #fff3cd; border-color: #ffeaa7; }
                .success { background: #d4edda; border-color: #c3e6cb; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
                th { background: #f8f9fa; }
                .critical { color: #dc3545; font-weight: bold; }
                .high { color: #fd7e14; font-weight: bold; }
                .medium { color: #ffc107; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🔍 Diagnóstico de Inconsistencias en Cálculos</h1>
                <p>Sistema de Préstamos - Análisis de errores de cálculo</p>
                <p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>";

        try {
            $this->check_database_connection();
            $this->analyze_loan_consistency();
            $this->analyze_payment_consistency();
            $this->analyze_negative_balances();
            $this->generate_recommendations();

        } catch (Exception $e) {
            echo "<div class='section error'>";
            echo "<h3>❌ Error en el diagnóstico</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }

        echo "</body></html>";
    }

    private function check_database_connection() {
        echo "<div class='section success'>";
        echo "<h3>✅ Conexión a Base de Datos</h3>";
        echo "<p>Estado: Conectado correctamente</p>";
        echo "<p>Base de datos: " . $this->CI->db->database . "</p>";
        echo "</div>";
    }

    private function analyze_loan_consistency() {
        echo "<div class='section'>";
        echo "<h3>📊 Análisis de Consistencia de Préstamos</h3>";

        // Consulta para detectar inconsistencias en préstamos
        $query = "
            SELECT
                l.id, l.credit_amount, l.interest_amount,
                COALESCE(SUM(li.fee_amount), 0) as total_fees,
                COALESCE(SUM(li.balance), 0) as total_balance,
                COALESCE(SUM(li.interest_paid + li.capital_paid), 0) as total_paid,
                COUNT(li.id) as num_installments,
                l.status as loan_status
            FROM loans l
            LEFT JOIN loan_items li ON li.loan_id = l.id
            GROUP BY l.id, l.credit_amount, l.interest_amount, l.status
            HAVING
                ABS(l.credit_amount + l.interest_amount - total_fees) > 0.01
                OR total_balance < -0.01
                OR total_balance > l.credit_amount + l.interest_amount + 1000
        ";

        $inconsistencies = $this->CI->db->query($query)->result();

        echo "<h4>Préstamos con Inconsistencias: " . count($inconsistencies) . "</h4>";

        if (!empty($inconsistencies)) {
            echo "<table>";
            echo "<tr>
                    <th>ID Préstamo</th>
                    <th>Monto Original</th>
                    <th>Interés</th>
                    <th>Total Esperado</th>
                    <th>Total Cuotas</th>
                    <th>Diferencia</th>
                    <th>Balance Actual</th>
                    <th>Estado</th>
                    <th>Severidad</th>
                  </tr>";

            foreach ($inconsistencies as $loan) {
                $expected_total = $loan->credit_amount + $loan->interest_amount;
                $difference = abs($expected_total - $loan->total_fees);
                $severity = $this->calculate_severity($loan, $difference);

                echo "<tr>";
                echo "<td>{$loan->id}</td>";
                echo "<td>$" . number_format($loan->credit_amount, 0, ',', '.') . "</td>";
                echo "<td>$" . number_format($loan->interest_amount, 0, ',', '.') . "</td>";
                echo "<td>$" . number_format($expected_total, 0, ',', '.') . "</td>";
                echo "<td>$" . number_format($loan->total_fees, 0, ',', '.') . "</td>";
                echo "<td class='$severity'>$" . number_format($difference, 2, ',', '.') . "</td>";
                echo "<td>$" . number_format($loan->total_balance, 0, ',', '.') . "</td>";
                echo "<td>" . ($loan->loan_status == 0 ? 'Pagado' : 'Pendiente') . "</td>";
                echo "<td class='$severity'>" . ucfirst($severity) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='success'>✅ No se encontraron inconsistencias graves en préstamos.</p>";
        }
        echo "</div>";
    }

    private function analyze_payment_consistency() {
        echo "<div class='section'>";
        echo "<h3>💰 Análisis de Consistencia de Pagos</h3>";

        // Verificar pagos sin cuotas correspondientes
        $orphan_payments = $this->CI->db->query("
            SELECT p.id, p.loan_id, p.amount, p.payment_date
            FROM payments p
            LEFT JOIN loan_items li ON li.id = p.loan_item_id
            WHERE p.loan_item_id IS NOT NULL AND li.id IS NULL
        ")->result();

        echo "<h4>Pagos huérfanos (sin cuota correspondiente): " . count($orphan_payments) . "</h4>";

        if (!empty($orphan_payments)) {
            echo "<table>";
            echo "<tr><th>ID Pago</th><th>ID Préstamo</th><th>Monto</th><th>Fecha</th></tr>";
            foreach ($orphan_payments as $payment) {
                echo "<tr>";
                echo "<td>{$payment->id}</td>";
                echo "<td>{$payment->loan_id}</td>";
                echo "<td>$" . number_format($payment->amount, 0, ',', '.') . "</td>";
                echo "<td>{$payment->payment_date}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='success'>✅ No se encontraron pagos huérfanos.</p>";
        }

        echo "</div>";
    }

    private function analyze_negative_balances() {
        echo "<div class='section'>";
        echo "<h3>⚠️ Análisis de Saldos Negativos</h3>";

        $negative_balances = $this->CI->db->query("
            SELECT l.id, l.credit_amount,
                   SUM(COALESCE(li.balance, 0)) as total_balance,
                   SUM(COALESCE(li.interest_paid + li.capital_paid, 0)) as total_paid,
                   COUNT(li.id) as num_installments
            FROM loans l
            JOIN loan_items li ON li.loan_id = l.id
            GROUP BY l.id, l.credit_amount
            HAVING total_balance < -0.01
            ORDER BY total_balance ASC
        ")->result();

        echo "<h4>Préstamos con saldo negativo: " . count($negative_balances) . "</h4>";

        if (!empty($negative_balances)) {
            echo "<table>";
            echo "<tr>
                    <th>ID Préstamo</th>
                    <th>Monto Original</th>
                    <th>Saldo Actual</th>
                    <th>Total Pagado</th>
                    <th>Sobrepago</th>
                    <th>Cuotas</th>
                  </tr>";

            foreach ($negative_balances as $loan) {
                $overpayment = abs($loan->total_balance);
                echo "<tr>";
                echo "<td>{$loan->id}</td>";
                echo "<td>$" . number_format($loan->credit_amount, 0, ',', '.') . "</td>";
                echo "<td class='critical'>$" . number_format($loan->total_balance, 0, ',', '.') . "</td>";
                echo "<td>$" . number_format($loan->total_paid, 0, ',', '.') . "</td>";
                echo "<td class='high'>$" . number_format($overpayment, 0, ',', '.') . "</td>";
                echo "<td>{$loan->num_installments}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='success'>✅ No se encontraron saldos negativos.</p>";
        }

        echo "</div>";
    }

    private function generate_recommendations() {
        echo "<div class='section'>";
        echo "<h3>📋 Recomendaciones de Corrección</h3>";

        echo "<h4>🔧 Correcciones Prioritarias:</h4>";
        echo "<ol>";
        echo "<li><strong>Implementar BCMath:</strong> Reemplazar cálculos con punto flotante por operaciones de precisión arbitraria</li>";
        echo "<li><strong>Simplificar consultas:</strong> Reemplazar subqueries complejas por cálculos directos</li>";
        echo "<li><strong>Agregar validaciones:</strong> Verificar integridad de datos antes de cada operación</li>";
        echo "<li><strong>Implementar auditoría:</strong> Log detallado de todas las operaciones financieras</li>";
        echo "<li><strong>Corregir datos existentes:</strong> Ajustar manualmente préstamos inconsistentes</li>";
        echo "</ol>";

        echo "<h4>🛡️ Medidas Preventivas:</h4>";
        echo "<ul>";
        echo "<li>Usar transacciones de base de datos para operaciones críticas</li>";
        echo "<li>Implementar bloqueo optimista para concurrencia</li>";
        echo "<li>Agregar pruebas unitarias para cálculos financieros</li>";
        echo "<li>Implementar validación de entrada robusta</li>";
        echo "</ul>";

        echo "</div>";
    }

    private function calculate_severity($loan, $difference) {
        $balance_abs = abs($loan->total_balance);

        if ($balance_abs > 10000 || $difference > 5000) {
            return 'critical';
        } elseif ($balance_abs > 1000 || $difference > 1000) {
            return 'high';
        } else {
            return 'medium';
        }
    }
}

// Ejecutar diagnóstico
$diagnostic = new DiagnosticCalculations();
$diagnostic->run_diagnostic();