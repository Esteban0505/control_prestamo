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

      <!-- Sección Especial para Pago Anticipado con Condonación -->
      <?php
      // CORRECCIÓN DEFINITIVA: Usar $processed_quotas directamente para todos los cálculos
      // processed_quotas contiene los datos frescos del pago procesado con montos correctos
      $quotas_to_show = !empty($processed_quotas) ? $processed_quotas : (!empty($quotasPaid) ? $quotasPaid : []);
      ?>
      <?php if (isset($tipo_pago) && ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total')): ?>
      <div class="summary-section" style="border: 3px solid #28a745; background: linear-gradient(135deg, #f8fff8, #e8f5e8);">
        <h4 style="color: #28a745; text-align: center;"><i class="fas fa-hand-holding-usd"></i> LIQUIDACIÓN ANTICIPADA CON CONDONACIÓN</h4>
        <div class="row">
          <div class="col-md-6">
            <div class="alert alert-success" style="border-radius: 10px;">
              <h6><i class="fas fa-check-circle"></i> Información del Pago</h6>
              <p class="mb-2"><strong>Monto Pagado por Cliente:</strong></p>
              <p class="text-success font-weight-bold" style="font-size: 18px;">
                <?php
                // Usar la información de condonación del controlador si está disponible
                $monto_pagado_cliente = 0;
                if (isset($waiver_info) && isset($waiver_info['customer_payment'])) {
                    $monto_pagado_cliente = $waiver_info['customer_payment'];
                } elseif (isset($_POST['quota_id'])) {
                    // Fallback: buscar en las cuotas procesadas
                    $selected_quota_id = is_array($_POST['quota_id']) ? $_POST['quota_id'][0] : $_POST['quota_id'];
                    foreach ($quotas_to_show as $quota) {
                        if (is_array($quota) && isset($quota['id']) && $quota['id'] == $selected_quota_id && isset($quota['payment_type']) && $quota['payment_type'] === 'paid') {
                            $monto_pagado_cliente = $quota['balance'] ?? 0;
                            break;
                        }
                    }
                }
                echo number_format($monto_pagado_cliente, 2, ',', '.') . ' COP';
                ?>
              </p>
              <small class="text-muted">Corresponde al saldo pendiente de la cuota seleccionada para liquidación</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="alert alert-info" style="border-radius: 10px;">
              <h6><i class="fas fa-info-circle"></i> Cuotas Condonadas</h6>
              <?php
              $cuotas_condonadas = 0;
              $monto_condonado = 0;
              if (isset($waiver_info) && isset($waiver_info['quotas_waived'])) {
                  $cuotas_condonadas = $waiver_info['quotas_waived'];
                  $monto_condonado = $waiver_info['total_waived'];
              } else {
                  // Fallback: contar cuotas condonadas
                  foreach ($quotas_to_show as $quota) {
                      if (is_array($quota) && isset($quota['extra_payment']) && $quota['extra_payment'] == 3) {
                          $cuotas_condonadas++;
                          $monto_condonado += ($quota['fee_amount'] ?? 0);
                      }
                  }
              }
              ?>
              <p class="mb-1"><strong>Número de Cuotas Condonadas:</strong> <?php echo $cuotas_condonadas; ?></p>
              <p class="mb-1"><strong>Monto Total Condonado:</strong> <?php echo number_format($monto_condonado, 2, ',', '.') . ' COP'; ?></p>
              <small class="text-muted">Las cuotas posteriores a la seleccionada han sido condonadas completamente</small>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="alert alert-warning" style="border-radius: 10px; border-color: #ffc107;">
              <h6><i class="fas fa-exclamation-triangle"></i> Nota Importante - Liquidación Anticipada</h6>
              <ul class="mb-0">
                <li>El cliente ha pagado únicamente el saldo pendiente de la cuota seleccionada</li>
                <li>Todas las cuotas posteriores han sido condonadas por completo</li>
                <li>El préstamo queda completamente liquidado y sin obligaciones pendientes</li>
                <li>Esta transacción representa un descuento especial por pago anticipado</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tabla de Cuotas Procesadas -->
      <div class="quotas-table">
        <h4>Detalle de Cuotas</h4>
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead class="thead-dark">
              <tr>
                <th class="text-center">N° Cuota</th>
                <th class="text-center">Fecha Vencimiento</th>
                <th class="text-right">Monto Cuota</th>
                <?php if (isset($tipo_pago) && $tipo_pago === 'custom'): ?>
                <th class="text-center" colspan="2">-</th>
                <?php else: ?>
                <th class="text-right">Interés</th>
                <th class="text-right">Capital</th>
                <?php endif; ?>
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
              $total_interes_pagado = 0;
              $total_capital_pagado = 0;

              // CORRECCIÓN DEFINITIVA: Usar $processed_quotas directamente para todos los cálculos
              // processed_quotas contiene los datos frescos del pago procesado con montos correctos
              $quotas_to_show = !empty($processed_quotas) ? $processed_quotas : (!empty($quotasPaid) ? $quotasPaid : []);

              // CORRECCIÓN: Para pagos personalizados, asegurar que las fechas se muestren correctamente
              if (!empty($quotas_to_show)) {
                  foreach ($quotas_to_show as &$quota) {
                      if (is_array($quota) && isset($quota['date']) && !empty($quota['date'])) {
                          // Si la fecha viene como string, formatearla
                          if (is_string($quota['date'])) {
                              $quota['date'] = date('d/m/Y', strtotime($quota['date']));
                          }
                      }
                  }
              }

              // Para pagos con condonación, asegurar que se muestren todas las cuotas (pagadas y condonadas)
              if (isset($tipo_pago) && ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total') && !empty($quotas_to_show)) {
                  // Las cuotas ya están incluidas en processed_quotas desde el controlador
                  // No necesitamos hacer cambios adicionales aquí
              }

              // Determinar si mostrar distribución de pagos
              // Para pagos con condonación, NO mostrar las columnas de Interés Pagado y Capital Pagado
              $show_payment_distribution = isset($tipo_pago) && in_array($tipo_pago, ['interest', 'capital', 'both', 'custom']) && !in_array($tipo_pago, ['early_total', 'total_condonacion']);

              if (!empty($quotas_to_show)):
                foreach ($quotas_to_show as $quota):
                  // Verificar si es objeto o array y manejar null/undefined
                  $num_quota = '';
                  $date = '';
                  $fee_amount = 0;
                  $interest_amount = 0;
                  $capital_amount = 0;
                  $balance = 0;
                  $status = 1;
                  $extra_payment = 0;
                  $payment_type = '';

                  if (is_object($quota) && $quota !== null) {
                      $num_quota = isset($quota->num_quota) ? $quota->num_quota : '';
                      $date = isset($quota->date) ? $quota->date : '';
                      $fee_amount = isset($quota->fee_amount) ? $quota->fee_amount : 0;
                      $interest_amount = isset($quota->interest_amount) ? $quota->interest_amount : 0;
                      $capital_amount = isset($quota->capital_amount) ? $quota->capital_amount : 0;
                      $balance = isset($quota->balance) ? $quota->balance : 0;
                      $status = isset($quota->status) ? $quota->status : 1;
                      $extra_payment = isset($quota->extra_payment) ? $quota->extra_payment : 0;
                      $payment_type = isset($quota->payment_type) ? $quota->payment_type : '';
                  } elseif (is_array($quota) && !empty($quota)) {
                      $num_quota = isset($quota['num_quota']) ? $quota['num_quota'] : '';
                      $date = isset($quota['date']) ? $quota['date'] : '';
                      $fee_amount = isset($quota['fee_amount']) ? $quota['fee_amount'] : 0;
                      $interest_amount = isset($quota['interest_amount']) ? $quota['interest_amount'] : 0;
                      $capital_amount = isset($quota['capital_amount']) ? $quota['capital_amount'] : 0;
                      $balance = isset($quota['balance']) ? $quota['balance'] : 0;
                      $status = isset($quota['status']) ? $quota['status'] : 1;
                      $extra_payment = isset($quota['extra_payment']) ? $quota['extra_payment'] : 0;
                      $payment_type = isset($quota['payment_type']) ? $quota['payment_type'] : '';
                  }

                  // CORRECCIÓN DEFINITIVA: Definir interest_paid y capital_paid para evitar PHP Notice
                  $interest_paid = isset($quota['interest_paid']) ? $quota['interest_paid'] : 0;
                  $capital_paid = isset($quota['capital_paid']) ? $quota['capital_paid'] : 0;

                  // CORRECCIÓN: Para condonaciones, mostrar montos originales pero marcar como condonada
                  if ($extra_payment == 3 || $payment_type === 'waived') {
                      $status_text = 'Condonado';
                      $status_class = 'badge-info';
                      // Los montos condonados se muestran pero no se suman a totales pagados
                      // Para pago total con condonación, mostrar los montos originales en la tabla
                      $total_cuota += $fee_amount;
                      $total_interes += $interest_amount;
                      $total_capital += $capital_amount;
                      $total_saldo += $balance;
                  } elseif ($payment_type === 'paid') {
                      $status_text = 'Pagado';
                      $status_class = 'badge-success';
                      // CORRECCIÓN DEFINITIVA: Usar los montos realmente pagados desde processed_quotas
                      // processed_quotas contiene los valores correctos del pago procesado
                      $total_cuota += ($interest_paid + $capital_paid);
                      $total_interes += $interest_paid;
                      $total_capital += $capital_paid;
                      $total_saldo += $balance;
                  } else {
                      // CORRECCIÓN: Mejorar lógica de estados para pagos personalizados
                      if ($status == 4) {
                          $status_text = 'Pago no completo';
                          $status_class = 'badge-warning';
                      } elseif ($status == 3) {
                          $status_text = 'Pago parcial';
                          $status_class = 'badge-info';
                      } elseif ($status == 0) {
                          $status_text = 'Pagado';
                          $status_class = 'badge-success';
                      } else {
                          $status_text = 'Pendiente';
                          $status_class = 'badge-warning';
                      }
                      // CORRECCIÓN DEFINITIVA: Usar los montos realmente pagados desde processed_quotas para pagos normales
                      // processed_quotas contiene los valores correctos del pago procesado
                      $total_cuota += ($interest_paid + $capital_paid);
                      $total_interes += $interest_paid;
                      $total_capital += $capital_paid;
                      $total_saldo += $balance;

                      // LOG DIAGNÓSTICO DEFINITIVO: Verificar estado de cuota en ticket
                      error_log("TICKET_DIAG: Cuota ID {$num_quota} - status: {$status}, status_text: {$status_text}, payment_type: " . ($payment_type ?? 'N/A') . ", interest_paid: " . ($interest_paid ?? 'N/A') . ", capital_paid: " . ($capital_paid ?? 'N/A') . ", processed_quotas: " . (!empty($processed_quotas) ? 'SÍ' : 'NO'));
                  }

                  // CORRECCIÓN DEFINITIVA: Sumar pagos realizados desde processed_quotas (solo para cuotas pagadas, no condonadas)
                  // processed_quotas contiene los valores correctos de interest_paid y capital_paid
                  if ($extra_payment != 3 && $payment_type !== 'waived') {
                      $total_interes_pagado += $interest_paid;
                      $total_capital_pagado += $capital_paid;
                  }
              ?>
                <tr>
                  <td class="text-center"><?php echo $num_quota; ?></td>
                  <td class="text-center"><?php echo $date ? date('d/m/Y', strtotime($date)) : 'Sin fecha'; ?></td>
                  <td class="text-right"><?php echo number_format($fee_amount, 2, ',', '.'); ?></td>
                  <?php if (isset($tipo_pago) && $tipo_pago === 'custom'): ?>
                  <td class="text-center" colspan="2">-</td>
                  <?php else: ?>
                  <td class="text-right"><?php echo number_format($interest_amount, 2, ',', '.'); ?></td>
                  <td class="text-right"><?php echo number_format($capital_amount, 2, ',', '.'); ?></td>
                  <?php endif; ?>
                  <td class="text-right"><?php echo number_format($balance, 2, ',', '.'); ?></td>
                  <td class="text-center">
                    <span class="badge <?php echo $status_class; ?>" style="font-size: 12px; padding: 6px 10px;">
                      <?php if ($extra_payment == 3 || $payment_type === 'waived'): ?>
                        <i class="fas fa-hand-holding-usd"></i> <?php echo $status_text; ?>
                      <?php elseif ($status == 0 || $payment_type === 'paid'): ?>
                        <i class="fas fa-check"></i> <?php echo $status_text; ?>
                      <?php elseif ($status == 4): ?>
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $status_text; ?> (saldo pendiente)
                      <?php elseif ($status == 3): ?>
                        <i class="fas fa-balance-scale"></i> <?php echo $status_text; ?> (saldo distribuido)
                      <?php else: ?>
                        <?php echo $status_text; ?>
                      <?php endif; ?>
                    </span>
                  </td>
                  <?php if ($show_payment_distribution): ?>
                  <td class="text-center"><?php echo number_format($interest_paid, 2, ',', '.'); ?></td>
                  <td class="text-center"><?php echo number_format($capital_paid, 2, ',', '.'); ?></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="<?php echo $show_payment_distribution ? '10' : '8'; ?>" class="text-center text-muted">
                    <strong>No hay cuotas procesadas para mostrar</strong><br>
                    <small>Verifique que el pago se haya procesado correctamente</small>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr style="background-color: #f8f9fa !important; color: black !important;" class="font-weight-bold">
                <th colspan="2" class="text-right" style="color: black !important;">RESUMEN DEL PAGO</th>
                <th class="text-right" style="color: black !important;"><?php echo number_format($total_cuota, 2, ',', '.'); ?> COP</th>
                <?php if (isset($tipo_pago) && $tipo_pago === 'custom'): ?>
                <th class="text-center" colspan="2" style="color: black !important;">-</th>
                <?php else: ?>
                <th class="text-right" style="color: black !important;"><?php echo number_format($total_interes, 2, ',', '.'); ?> COP</th>
                <th class="text-right" style="color: black !important;"><?php echo number_format($total_capital, 2, ',', '.'); ?> COP</th>
                <?php endif; ?>
                <th class="text-right" style="color: black !important;"><?php echo number_format($total_saldo, 2, ',', '.'); ?> COP</th>
                <th class="text-center" style="color: black !important;">
                  <?php echo count($quotas_to_show); ?> cuota(s) procesada(s)
                </th>
                <?php if ($show_payment_distribution): ?>
                <th class="text-center" style="color: black !important;"><?php echo number_format($total_interes_pagado, 2, ',', '.'); ?> COP</th>
                <th class="text-center" style="color: black !important;"><?php echo number_format($total_capital_pagado, 2, ',', '.'); ?> COP</th>
                <?php endif; ?>
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
            <p><strong>Total Cuotas Procesadas:</strong> <?php echo count($quotas_to_show); ?></p>
            <p><strong>Fecha de Pago:</strong> <?php
              date_default_timezone_set('America/Bogota');
              echo date('d/m/Y h:i A');
            ?></p>
          </div>
          <div class="col-md-6">
            <p><strong>Total Pagado:</strong> <span class="text-success font-weight-bold"><?php
              // Para pagos con condonación, usar la información del waiver_info
              if (isset($tipo_pago) && ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total') && isset($waiver_info) && isset($waiver_info['customer_payment'])) {
                  echo number_format($waiver_info['customer_payment'], 2, ',', '.') . ' COP';
              } elseif (isset($tipo_pago) && $tipo_pago === 'total_condonacion') {
                  $monto_real_pagado = 0;
                  if (isset($_POST['quota_id'])) {
                      $selected_quota_id = is_array($_POST['quota_id']) ? $_POST['quota_id'][0] : $_POST['quota_id'];
                      foreach ($quotas_to_show as $quota) {
                          if (is_array($quota) && isset($quota['id']) && $quota['id'] == $selected_quota_id && isset($quota['payment_type']) && $quota['payment_type'] === 'paid') {
                              $monto_real_pagado = $quota['balance'] ?? 0;
                              break;
                          }
                      }
                  }
                  echo number_format($monto_real_pagado, 2, ',', '.') . ' COP';
              } elseif (isset($total_amount) && $total_amount > 0) {
                  echo number_format($total_amount, 2, ',', '.') . ' COP';
              } else {
                  echo number_format($total_cuota, 2, ',', '.') . ' COP';
              }
            ?></span></p>
            <?php if (isset($tipo_pago) && $tipo_pago === 'total_condonacion'): ?>
            <div class="alert alert-info mt-2">
              <small><i class="fas fa-info-circle"></i> <strong>Liquidación Anticipada:</strong> Monto total pagado por liquidación completa del saldo pendiente.</small>
            </div>
            <?php elseif (isset($total_amount) && $total_amount == 0 && !empty($quotas_to_show)): ?>
            <div class="alert alert-warning mt-2">
              <small><i class="fas fa-exclamation-triangle"></i> <strong>Nota:</strong> El total pagado es 0. Verifique que los datos de pago se hayan procesado correctamente.</small>
            </div>
            <?php endif; ?>
            <?php if (isset($tipo_pago) && in_array($tipo_pago, ['interest', 'capital', 'both', 'custom'])): ?>
            <p><strong>Tipo de Pago:</strong>
              <?php
              $tipo_descripcion = [
                'interest' => 'Solo Interés',
                'capital' => 'Pago a Capital',
                'both' => 'Interés y Capital',
                'custom' => 'Monto Personalizado Parcial'
              ];
              echo $tipo_descripcion[$tipo_pago] ?? $tipo_pago;
              ?>
            </p>
            <?php endif; ?>

            <?php if (isset($tipo_pago) && ($tipo_pago === 'total_condonacion' || $tipo_pago === 'early_total')): ?>
            <div class="alert alert-success mt-3" style="border-radius: 10px;">
              <h6><i class="fas fa-check-circle"></i> Liquidación Anticipada con Condonación Completada</h6>
              <p class="mb-1">El préstamo ha sido liquidado anticipadamente con descuento especial.</p>
              <p class="mb-1"><strong>Monto Pagado por Cliente:</strong>
                <?php
                $monto_real_pagado = 0;
                if (isset($waiver_info) && isset($waiver_info['customer_payment'])) {
                    $monto_real_pagado = $waiver_info['customer_payment'];
                } elseif (isset($_POST['quota_id'])) {
                    $selected_quota_id = is_array($_POST['quota_id']) ? $_POST['quota_id'][0] : $_POST['quota_id'];
                    foreach ($quotas_to_show as $quota) {
                        if (is_array($quota) && isset($quota['id']) && $quota['id'] == $selected_quota_id && isset($quota['payment_type']) && $quota['payment_type'] === 'paid') {
                            $monto_real_pagado = $quota['balance'] ?? 0;
                            break;
                        }
                    }
                }
                echo number_format($monto_real_pagado, 2, ',', '.') . ' COP';
                ?>
              </p>
              <?php
              $cuotas_condonadas = 0;
              $monto_condonado = 0;
              if (isset($waiver_info) && isset($waiver_info['quotas_waived'])) {
                  $cuotas_condonadas = $waiver_info['quotas_waived'];
                  $monto_condonado = $waiver_info['total_waived'];
              } else {
                  foreach ($quotas_to_show as $quota) {
                      if (is_array($quota) && isset($quota['extra_payment']) && $quota['extra_payment'] == 3) {
                          $cuotas_condonadas++;
                          $monto_condonado += ($quota['fee_amount'] ?? 0);
                      }
                  }
              }
              ?>
              <p class="mb-1"><strong>Cuotas Condonadas:</strong> <?php echo $cuotas_condonadas; ?> cuota(s)</p>
              <p class="mb-1"><strong>Monto Total Condonado:</strong> <?php echo number_format($monto_condonado, 2, ',', '.') . ' COP'; ?></p>
              <small class="text-success font-weight-bold">✓ El cliente ya no tiene obligaciones pendientes con este préstamo.</small>
            </div>
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
              <p class="mb-1">El pago se aplicó siguiendo la prioridad: <strong>Interés → Capital (proporcional)</strong></p>
              <small>Esta distribución asegura que los intereses se paguen primero y luego el capital proporcionalmente.</small>
            </div>
            <?php endif; ?>
            <p><strong>Total Intereses:</strong> <?php echo number_format($total_interes, 2, ',', '.') . ' COP'; ?></p>
            <p><strong>Total Capital:</strong> <?php echo number_format($total_capital, 2, ',', '.') . ' COP'; ?></p>
            <p><strong>Saldo Restante:</strong> <?php echo number_format($total_saldo, 2, ',', '.') . ' COP'; ?></p>
            <?php if (isset($tipo_pago) && $tipo_pago === 'custom' && isset($custom_amount) && $custom_amount > 0): ?>
            <p><strong>Monto Personalizado:</strong> <?php echo number_format($custom_amount, 2, ',', '.') . ' COP'; ?></p>
            <?php endif; ?>

            <?php if (isset($tipo_pago) && $tipo_pago === 'custom'): ?>
            <div class="alert alert-warning mt-3" style="border-radius: 10px;">
              <h6><i class="fas fa-balance-scale"></i> Redistribución Automática de Saldos</h6>
              <p class="mb-2"><strong>Tipo de Pago:</strong> Monto Personalizado Parcial</p>
              <p class="mb-1"><strong>Distribución:</strong> Interés → Capital (proporcional)</p>
              <?php
              // Determinar estado dinámicamente basado en la cuota procesada
              $estado_cuota = 'Pago no completo';
              if (!empty($quotas_to_show)) {
                  $main_quota = $quotas_to_show[0];
                  $status = '';
                  if (is_array($main_quota) && isset($main_quota['status'])) {
                      $status = $main_quota['status'];
                  } elseif (is_object($main_quota) && isset($main_quota->status)) {
                      $status = $main_quota->status;
                  }
                  if ($status == 4) {
                      $estado_cuota = 'Pago no completo';
                  } elseif ($status == 3) {
                      $estado_cuota = 'Pago parcial';
                  } elseif ($status == 0) {
                      $estado_cuota = 'Pagado';
                  } else {
                      $estado_cuota = 'Pendiente';
                  }
              }
              ?>
              <p class="mb-1"><strong>Estado de Cuota:</strong> <?php echo $estado_cuota; ?></p>
              <?php if (isset($redistribution_log) && is_array($redistribution_log)): ?>
              <div class="mt-2">
                <strong>Historial de Redistribuciones:</strong>
                <ul class="mb-1">
                  <?php foreach ($redistribution_log as $log_entry): ?>
                  <li><?php echo htmlspecialchars($log_entry); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php endif; ?>
              <small class="text-warning font-weight-bold">
                <?php
                // Determinar el mensaje según si se creó nueva cuota o no
                $has_new_installment = false;
                if (isset($redistribution_log) && is_array($redistribution_log)) {
                  foreach ($redistribution_log as $log) {
                    if (strpos($log, 'nueva cuota adicional') !== false) {
                      $has_new_installment = true;
                      break;
                    }
                  }
                }
                if ($has_new_installment) {
                  echo 'Saldo restante trasladado a nueva cuota adicional (última cuota del préstamo).';
                } else {
                  echo 'No se generaron cuotas nuevas. Los montos de las cuotas pendientes fueron actualizados proporcionalmente.';
                }
                ?>
              </small>
            </div>
            <?php endif; ?>

            <?php if (isset($tipo_pago) && $tipo_pago === 'early_total' && isset($processed_quotas[0]['waived_amount']) && $processed_quotas[0]['waived_amount'] > 0): ?>
            <div class="alert alert-info mt-3" style="border-radius: 10px;">
              <h6><i class="fas fa-hand-holding-usd"></i> CONDONACIÓN REALIZADA</h6>
              <p class="mb-1"><strong>Monto Condonado:</strong> <?php echo number_format($processed_quotas[0]['waived_amount'], 2, ',', '.') . ' COP'; ?></p>
              <p class="mb-1"><strong>Capital Condonado:</strong> <?php echo number_format($processed_quotas[0]['capital_waived'] ?? 0, 2, ',', '.') . ' COP'; ?></p>
              <p class="mb-1"><strong>Interés Condonado:</strong> <?php echo number_format($processed_quotas[0]['interest_waived'] ?? 0, 2, ',', '.') . ' COP'; ?></p>
              <small class="text-info font-weight-bold">Las cuotas futuras han sido condonadas completamente. El cliente solo pagó por la cuota seleccionada.</small>
            </div>
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

  <!-- Script de Debugging para Consola -->
  <script type="text/javascript">
    console.log('=== DEBUGGING TICKET DE PAGO ===');
    console.log('Fecha/Hora de ejecución:', new Date().toISOString());

    // Variables PHP disponibles
    console.log('=== VARIABLES PHP DISPONIBLES ===');
    console.log('loan_id:', <?php echo json_encode($loan_id); ?>);
    console.log('dni:', <?php echo json_encode($dni); ?>);
    console.log('name_cst:', <?php echo json_encode($name_cst); ?>);
    console.log('user_name:', <?php echo json_encode($user_name); ?>);
    console.log('tipo_pago:', <?php echo json_encode(isset($tipo_pago) ? $tipo_pago : null); ?>);
    console.log('total_amount:', <?php echo json_encode(isset($total_amount) ? $total_amount : null); ?>);
    console.log('custom_amount:', <?php echo json_encode(isset($custom_amount) ? $custom_amount : null); ?>);
    console.log('custom_payment_type:', <?php echo json_encode(isset($custom_payment_type) ? $custom_payment_type : null); ?>);
    console.log('payment_description:', <?php echo json_encode(isset($payment_description) ? $payment_description : null); ?>);
    console.log('show_payment_distribution:', <?php echo json_encode(isset($show_payment_distribution) ? $show_payment_distribution : null); ?>);

    // Datos de processed_quotas
    console.log('=== DATOS DE PROCESSED_QUOTAS ===');
    console.log('processed_quotas:', <?php echo json_encode(isset($processed_quotas) ? $processed_quotas : null); ?>);
    console.log('quotasPaid:', <?php echo json_encode(isset($quotasPaid) ? $quotasPaid : null); ?>);
    console.log('quotas_to_show:', <?php echo json_encode(isset($quotas_to_show) ? $quotas_to_show : null); ?>);

    // Información de waiver_info
    console.log('=== INFORMACIÓN DE WAIVER_INFO ===');
    console.log('waiver_info:', <?php echo json_encode(isset($waiver_info) ? $waiver_info : null); ?>);

    // Totales calculados
    console.log('=== TOTALES CALCULADOS ===');
    console.log('total_cuota:', <?php echo json_encode(isset($total_cuota) ? $total_cuota : null); ?>);
    console.log('total_interes:', <?php echo json_encode(isset($total_interes) ? $total_interes : null); ?>);
    console.log('total_capital:', <?php echo json_encode(isset($total_capital) ? $total_capital : null); ?>);
    console.log('total_saldo:', <?php echo json_encode(isset($total_saldo) ? $total_saldo : null); ?>);
    console.log('total_interes_pagado:', <?php echo json_encode(isset($total_interes_pagado) ? $total_interes_pagado : null); ?>);
    console.log('total_capital_pagado:', <?php echo json_encode(isset($total_capital_pagado) ? $total_capital_pagado : null); ?>);

    // Información adicional de configuración
    console.log('=== CONFIGURACIÓN Y DATOS ADICIONALES ===');
    console.log('redistribution_log:', <?php echo json_encode(isset($redistribution_log) ? $redistribution_log : null); ?>);
    console.log('POST data (quota_id):', <?php echo json_encode(isset($_POST['quota_id']) ? $_POST['quota_id'] : null); ?>);
    console.log('Número de cuotas procesadas:', <?php echo json_encode(isset($quotas_to_show) ? count($quotas_to_show) : 0); ?>);

    // CORRECCIÓN DEFINITIVA: Logs adicionales para debugging de valores en cero
    console.log('=== DIAGNÓSTICO DE VALORES EN CERO ===');
    console.log('¿processed_quotas está vacío?', <?php echo json_encode(empty($processed_quotas)); ?>);
    console.log('¿quotas_to_show está vacío?', <?php echo json_encode(empty($quotas_to_show)); ?>);
    console.log('¿total_cuota es cero?', <?php echo json_encode(isset($total_cuota) && $total_cuota == 0); ?>);
    console.log('¿total_interes es cero?', <?php echo json_encode(isset($total_interes) && $total_interes == 0); ?>);
    console.log('¿total_capital es cero?', <?php echo json_encode(isset($total_capital) && $total_capital == 0); ?>);
    console.log('¿total_interes_pagado es cero?', <?php echo json_encode(isset($total_interes_pagado) && $total_interes_pagado == 0); ?>);
    console.log('¿total_capital_pagado es cero?', <?php echo json_encode(isset($total_capital_pagado) && $total_capital_pagado == 0); ?>);

    // Análisis detallado de cuotas
    if (typeof <?php echo json_encode(isset($quotas_to_show) ? $quotas_to_show : null); ?> !== 'undefined' && <?php echo json_encode(isset($quotas_to_show) ? $quotas_to_show : null); ?> !== null) {
      console.log('=== ANÁLISIS DETALLADO DE CUOTAS ===');
      <?php
      if (isset($quotas_to_show) && is_array($quotas_to_show)) {
        foreach ($quotas_to_show as $index => $quota) {
          echo "console.log('Cuota " . $index . ":', " . json_encode($quota) . ");\n";
        }
      }
      ?>
    }

    console.log('=== FIN DEL DEBUGGING ===');
  </script>
</body>
</html>
