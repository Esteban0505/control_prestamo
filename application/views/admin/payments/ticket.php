<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="utf-8">
 <title>Ticket de Pago - Préstamo <?php echo $loan_id; ?></title>
 <link href="<?php echo site_url(); ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
 <link rel="icon" href="<?php echo base_url('assets/img/log.png'); ?>" type="image/x-icon" />
  <style type="text/css" media="all">
    body {
      color: #000;
      font-family: 'Nunito', sans-serif;
      background: linear-gradient(135deg, #071e3d, #1b2a49);
      margin: 0;
      padding: 20px;
    }
    #wrapper {
      max-width: 900px;
      margin: 0 auto;
      padding: 30px;
    }
    .ticket-container {
      background-color: #ffffff;
      border: 4px solid #f2c94c;
      border-radius: 20px;
      padding: 50px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.4);
      position: relative;
      overflow: hidden;
    }
    .ticket-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 12px;
      background: linear-gradient(90deg, #f2c94c, #d4af37, #f2c94c);
    }
    .ticket-container::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 12px;
      background: linear-gradient(90deg, #f2c94c, #d4af37, #f2c94c);
    }
    .ticket-header {
      text-align: center;
      margin-bottom: 25px;
      border-bottom: 2px solid #007bff;
      padding-bottom: 15px;
    }
    .company-header {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      padding: 15px;
      background: linear-gradient(135deg, #071e3d, #1b2a49);
      border-radius: 10px;
      color: white;
    }
    .company-logo {
      width: 100px;
      height: 100px;
      margin-right: 25px;
      border-radius: 50%;
      border: 4px solid #f2c94c;
      box-shadow: 0 4px 15px rgba(242, 201, 76, 0.3);
    }
    .company-info {
      text-align: left;
    }
    .company-name {
      font-size: 32px;
      color: #f2c94c;
      margin: 0;
      letter-spacing: 3px;
      font-weight: bold;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .company-tagline {
      font-size: 12px;
      color: #ccc;
      margin: 5px 0 0 0;
      letter-spacing: 1px;
    }
    .ticket-status h2 {
      color: #28a745;
      margin: 10px 0 5px 0;
      font-size: 22px;
    }
    .ticket-status p {
      color: #666;
      margin: 0;
      font-size: 16px;
    }
    .payment-info {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border: 1px solid #dee2e6;
    }
    .payment-info p {
      margin: 5px 0;
      font-size: 14px;
    }
    .table {
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 20px;
    }
    .table th {
      background-color: #007bff;
      color: white;
      border: none;
      font-weight: bold;
      text-align: center;
    }
    .table td {
      text-align: center;
      vertical-align: middle;
      border: 1px solid #dee2e6;
    }
    .table tfoot th {
      background-color: #f8f9fa;
      border-top: 2px solid #007bff;
      font-weight: bold;
    }
    .payment-method {
      background-color: #e9ecef;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .summary-section {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      margin-bottom: 20px;
    }
    .summary-section h4 {
      color: #495057;
      margin-bottom: 15px;
      text-align: center;
    }
    .summary-section p {
      margin: 8px 0;
      font-size: 14px;
    }
    .btn {
      margin-bottom: 10px;
      font-weight: bold;
      text-transform: uppercase;
    }
    .no-print {
      margin-top: 20px;
    }
    @media print {
      .no-print {
        display: none;
      }
      body {
        background-color: white;
      }
      #wrapper {
        max-width: 100%;
        padding: 0;
      }
      .ticket-container {
        border: none;
        box-shadow: none;
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div id="wrapper">
    <div class="ticket-container">
      <!-- Encabezado del Ticket -->
      <div class="ticket-header">
        <div class="company-header">
          <img src="<?php echo base_url('assets/img/log.png'); ?>" alt="Logo Creditos Valu" class="company-logo">
          <div class="company-info">
            <h1 class="company-name">CREDITOS VALU</h1>
            <p class="company-tagline">PRESTAMOS A TU MEDIDA, SOLUCIONES A TU ALCANCE</p>
          </div>
        </div>
        <div class="ticket-status">
          <h2>✓ Pago Realizado Exitosamente</h2>
          <p>Ticket de Pago - Préstamo N° <?php echo $loan_id; ?></p>
        </div>
      </div>

      <!-- Información del Pago -->
      <div class="payment-info">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Fecha/Hora del Pago:</strong> <?php
              date_default_timezone_set('America/Bogota');
              echo date('d/m/Y h:i A');
            ?></p>
            <p><strong>N° Préstamo:</strong> <?php echo $loan_id; ?></p>
            <p><strong>Número de Identidad:</strong> <?php echo $dni; ?></p>
          </div>
          <div class="col-md-6">
            <p><strong>Tipo de Moneda:</strong> COP</p>
            <p><strong>Método de Pago:</strong> Efectivo</p>
          </div>
        </div>
      </div>

      <!-- Tabla de Cuotas Procesadas -->
      <div class="quotas-table">
        <h4>Detalle de Cuotas Procesadas</h4>
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead class="thead-dark">
              <tr>
                <th class="text-center">N° Cuota</th>
                <th class="text-center">Fecha Vencimiento</th>
                <th class="text-right">Monto Cuota</th>
                <th class="text-right">Interés</th>
                <th class="text-right">Capital</th>
                <th class="text-right">Saldo Restante</th>
                <th class="text-center">Estado</th>
                <?php if ($show_payment_distribution): ?>
                <th class="text-center">Interés Pagado</th>
                <th class="text-center">Capital Pagado</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php
              $total_cuota = 0;
              $total_interes = 0;
              $total_capital = 0;
              $total_saldo = 0;

              // Usar processed_quotas si está disponible, sino quotasPaid
              $quotas_to_show = !empty($processed_quotas) ? $processed_quotas : (!empty($quotasPaid) ? $quotasPaid : []);

              // DEBUG: Log para verificar qué datos están disponibles
              error_log('TICKET DEBUG - processed_quotas: ' . json_encode($processed_quotas));
              error_log('TICKET DEBUG - quotasPaid: ' . json_encode($quotasPaid));
              error_log('TICKET DEBUG - quotas_to_show: ' . json_encode($quotas_to_show));
              error_log('TICKET DEBUG - count(quotas_to_show): ' . count($quotas_to_show));
              error_log('TICKET DEBUG - tipo_pago: ' . (isset($tipo_pago) ? $tipo_pago : 'NO SET'));

              // Usar variables pasadas desde el controlador (si existen) o inicializar con valores por defecto
              $show_payment_distribution = isset($show_payment_distribution) ? $show_payment_distribution : false;
              $is_liquidation = isset($is_liquidation) ? $is_liquidation : false;

              if (!empty($quotas_to_show)):
                foreach ($quotas_to_show as $quota):
                  // Verificar si es objeto o array
                  $num_quota = is_object($quota) ? $quota->num_quota : (isset($quota['num_quota']) ? $quota['num_quota'] : '');
                  $date = is_object($quota) ? $quota->date : (isset($quota['date']) ? $quota['date'] : '');
                  $fee_amount = is_object($quota) ? $quota->fee_amount : (isset($quota['fee_amount']) ? $quota['fee_amount'] : 0);
                  $interest_amount = is_object($quota) ? $quota->interest_amount : (isset($quota['interest_amount']) ? $quota['interest_amount'] : 0);
                  $capital_amount = is_object($quota) ? $quota->capital_amount : (isset($quota['capital_amount']) ? $quota['capital_amount'] : 0);
                  $balance = is_object($quota) ? $quota->balance : (isset($quota['balance']) ? $quota['balance'] : 0);
                  $status = is_object($quota) ? $quota->status : (isset($quota['status']) ? $quota['status'] : 1);

                  $total_cuota += $fee_amount;
                  $total_interes += $interest_amount;
                  $total_capital += $capital_amount;
                  $total_saldo += $balance;
              ?>
                <tr>
                  <td class="text-center"><?php echo $num_quota; ?></td>
                  <td class="text-center"><?php echo $date ? date('d/m/Y', strtotime($date)) : 'Sin fecha'; ?></td>
                  <td class="text-right"><?php echo number_format($fee_amount, 2, ',', '.'); ?></td>
                  <td class="text-right"><?php echo number_format($interest_amount, 2, ',', '.'); ?></td>
                  <td class="text-right"><?php echo number_format($capital_amount, 2, ',', '.'); ?></td>
                  <td class="text-right"><?php echo number_format($balance, 2, ',', '.'); ?></td>
                  <td class="text-center">
                    <span class="badge <?php echo $status == 1 ? 'badge-warning' : 'badge-success'; ?>">
                      <?php echo $status == 1 ? 'Pendiente' : 'Pagado'; ?>
                    </span>
                  </td>
                  <?php if ($show_payment_distribution): ?>
                  <td class="text-right">
                    <?php
                    $interest_paid = is_object($quota) ? (isset($quota->interest_paid) ? $quota->interest_paid : 0) : (isset($quota['interest_paid']) ? $quota['interest_paid'] : 0);
                    echo number_format($interest_paid, 2, ',', '.');
                    ?>
                  </td>
                  <td class="text-right">
                    <?php
                    $capital_paid = is_object($quota) ? (isset($quota->capital_paid) ? $quota->capital_paid : 0) : (isset($quota['capital_paid']) ? $quota['capital_paid'] : 0);
                    echo number_format($capital_paid, 2, ',', '.');
                    ?>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">No hay cuotas procesadas para mostrar</td>
                </tr>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr class="table-success font-weight-bold">
                <th colspan="2" class="text-right">RESUMEN DEL PAGO</th>
                <th class="text-right"><?php echo number_format($total_cuota, 2, ',', '.'); ?> COP</th>
                <th class="text-right"><?php echo number_format($total_interes, 2, ',', '.'); ?> COP</th>
                <th class="text-right"><?php echo number_format($total_capital, 2, ',', '.'); ?> COP</th>
                <th class="text-right"><?php echo number_format($total_saldo, 2, ',', '.'); ?> COP</th>
                <th class="text-center">
                  <?php echo count($quotasPaid); ?> cuota(s) procesada(s)
                </th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Detalles del Pago -->
      <div class="payment-method">
        <div class="row">
          <div class="col-md-12">
            <strong>Método de Pago:</strong> Efectivo
          </div>
        </div>
      </div>

      <!-- Resumen del Pago -->
      <div class="summary-section">
        <h4>Resumen del Pago</h4>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Cliente:</strong> <?php echo $name_cst; ?></p>
            <p><strong>Cobrador:</strong> <?php echo $user_name; ?></p>
            <p><strong>Total Cuotas Procesadas:</strong> <?php echo count($quotasPaid); ?></p>
            <p><strong>Fecha de Pago:</strong> <?php
              date_default_timezone_set('America/Bogota');
              echo date('d/m/Y h:i A');
            ?></p>
          </div>
          <div class="col-md-6">
            <p><strong>Total Pagado:</strong> <span class="text-success font-weight-bold"><?php echo number_format($total_amount ?? $total_cuota, 2, ',', '.') . ' COP'; ?></span></p>
            <?php if (isset($tipo_pago) && in_array($tipo_pago, ['interest', 'capital', 'both', 'custom'])): ?>
            <p><strong>Tipo de Pago:</strong>
              <?php
              $tipo_descripcion = [
                'interest' => 'Solo Interés',
                'capital' => 'Pago a Capital',
                'both' => 'Interés y Capital',
                'custom' => 'Monto Personalizado con Prioridad (Interés → Capital)'
              ];
              echo $tipo_descripcion[$tipo_pago] ?? $tipo_pago;
              ?>
            </p>
            <?php endif; ?>

            <?php if (isset($custom_payment_type) && $custom_payment_type === 'liquidation'): ?>
            <div class="alert alert-success mt-3">
              <h6><i class="fas fa-check-circle"></i> Liquidación Anticipada Completada</h6>
              <p class="mb-1">El préstamo ha sido liquidado anticipadamente. Todas las cuotas posteriores han sido liberadas.</p>
              <small>El cliente ya no tiene obligaciones pendientes con este préstamo.</small>
            </div>
            <?php endif; ?>

            <?php if ($show_payment_distribution): ?>
            <div class="alert alert-info mt-3">
              <h6><i class="fas fa-info-circle"></i> Distribución del Pago Personalizado</h6>
              <p class="mb-1">El pago se aplicó siguiendo la prioridad: <strong>Interés completo primero, luego Capital</strong></p>
              <small>Esta distribución asegura que los intereses se paguen completamente antes de reducir el capital adeudado.</small>
            </div>
            <?php endif; ?>
            <p><strong>Total Intereses:</strong> <?php echo number_format($total_interes, 2, ',', '.') . ' COP'; ?></p>
            <p><strong>Total Capital:</strong> <?php echo number_format($total_capital, 2, ',', '.') . ' COP'; ?></p>
            <p><strong>Saldo Restante:</strong> <?php echo number_format($total_saldo, 2, ',', '.') . ' COP'; ?></p>
            <?php if (isset($tipo_pago) && $tipo_pago === 'custom' && isset($custom_amount) && $custom_amount > 0): ?>
            <p><strong>Monto Personalizado:</strong> <?php echo number_format($custom_amount, 2, ',', '.') . ' COP'; ?></p>
            <?php endif; ?>
            <?php if (isset($payment_description) && !empty($payment_description)): ?>
            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($payment_description); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="no-print">
        <button onclick="window.print();" class="btn btn-primary btn-block">Imprimir Ticket</button>
        <a class="btn btn-success btn-block" href="<?php echo site_url('admin/payments/'); ?>">Listar Pagos</a>
      </div>
    </div>
  </div>
</body>
</html>
