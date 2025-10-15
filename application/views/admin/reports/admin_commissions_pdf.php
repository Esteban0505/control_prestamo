<!-- Vista PDF para reportes administrativos de comisiones -->
<style>
    body { font-family: Arial, sans-serif; }
    .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
    .title { font-size: 24px; font-weight: bold; margin: 0; }
    .subtitle { font-size: 14px; margin: 5px 0 0 0; }
    .filters { background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #dc3545; }
    .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .table th { background-color: #343a40; color: white; font-weight: bold; }
    .table .text-right { text-align: right; }
    .table .text-center { text-align: center; }
    .total-row { background-color: #dc3545; color: white; font-weight: bold; }
    .summary { background-color: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
    .footer { text-align: center; font-size: 12px; color: #6c757d; margin-top: 30px; }
</style>

<div class="header">
    <div class="title">REPORTE ADMINISTRATIVO DE COMISIONES</div>
    <div class="subtitle">Sistema de Comisiones del 40% de Intereses</div>
</div>

<div class="filters">
    <strong>Filtros Aplicados:</strong><br>
    <strong>Fecha Inicio:</strong> <?php echo $start_date ?: 'Sin límite'; ?><br>
    <strong>Fecha Fin:</strong> <?php echo $end_date ?: 'Sin límite'; ?><br>
    <strong>Cobrador:</strong> <?php echo $collector_id ? 'ID: ' . $collector_id : 'Todos los cobradores'; ?><br>
    <strong>Fecha de Generación:</strong> <?php echo date('d/m/Y H:i:s'); ?>
</div>

<?php if (!empty($commissions)): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Cobrador</th>
                <th class="text-right">Pagos Realizados</th>
                <th class="text-right">Interés Total Pagado</th>
                <th class="text-right">Comisión 40%</th>
                <th class="text-right">Monto Total Cobrado</th>
                <th class="text-right">Clientes Atendidos</th>
                <th class="text-right">Préstamos Gestionados</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_payments = 0;
            $total_interest = 0;
            $total_commission = 0;
            $total_collected = 0;
            $total_customers = 0;
            $total_loans = 0;

            foreach ($commissions as $commission):
                $total_payments += $commission->total_payments ?? 0;
                $total_interest += $commission->total_interest_paid ?? 0;
                $total_commission += $commission->interest_commission_40 ?? 0;
                $total_collected += $commission->total_amount_collected ?? 0;
                $total_customers += $commission->customers_handled ?? 0;
                $total_loans += $commission->loans_handled ?? 0;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($commission->user_name); ?></td>
                <td class="text-right"><?php echo number_format($commission->total_payments, 0, ',', '.'); ?></td>
                <td class="text-right">$<?php echo number_format($commission->total_interest_paid, 0, ',', '.'); ?></td>
                <td class="text-right">$<?php echo number_format($commission->interest_commission_40, 0, ',', '.'); ?></td>
                <td class="text-right">$<?php echo number_format($commission->total_amount_collected, 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($commission->customers_handled, 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($commission->loans_handled, 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- Fila de totales -->
            <tr class="total-row">
                <td><strong>TOTALES</strong></td>
                <td class="text-right"><strong><?php echo number_format($total_payments, 0, ',', '.'); ?></strong></td>
                <td class="text-right"><strong>$<?php echo number_format($total_interest, 0, ',', '.'); ?></strong></td>
                <td class="text-right"><strong>$<?php echo number_format($total_commission, 0, ',', '.'); ?></strong></td>
                <td class="text-right"><strong>$<?php echo number_format($total_collected, 0, ',', '.'); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($total_customers, 0, ',', '.'); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($total_loans, 0, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <h4>Resumen Ejecutivo</h4>
        <p>Este reporte administrativo muestra el detalle completo de las comisiones del 40% calculadas sobre los intereses pagados por los clientes. Los datos incluyen información consolidada por cobrador con métricas detalladas de rendimiento.</p>

        <strong>Métricas Principales:</strong>
        <ul>
            <li><strong>Total de Cobradores Activos:</strong> <?php echo count($commissions); ?></li>
            <li><strong>Comisión Total a Pagar:</strong> $<?php echo number_format($total_commission, 0, ',', '.'); ?></li>
            <li><strong>Clientes Gestionados:</strong> <?php echo number_format($total_customers, 0, ',', '.'); ?></li>
            <li><strong>Préstamos Activos:</strong> <?php echo number_format($total_loans, 0, ',', '.'); ?></li>
        </ul>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 50px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
        <h4>No hay datos disponibles</h4>
        <p>No se encontraron registros de comisiones para los filtros seleccionados.</p>
        <p><strong>Filtros aplicados:</strong></p>
        <p>Fecha inicio: <?php echo $start_date ?: 'Sin límite'; ?></p>
        <p>Fecha fin: <?php echo $end_date ?: 'Sin límite'; ?></p>
        <p>Cobrador: <?php echo $collector_id ? 'ID: ' . $collector_id : 'Todos'; ?></p>
    </div>
<?php endif; ?>

<div class="footer">
    <p>Reporte generado por el Sistema de Gestión de Préstamos</p>
    <p>Fecha: <?php echo date('d/m/Y H:i:s'); ?> | Usuario: Administrador</p>
</div>