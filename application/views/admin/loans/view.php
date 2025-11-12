
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <!-- Header del Préstamo -->
      <div class="card mb-4 shadow">
        <div class="card-header bg-gradient-primary text-white">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="d-flex align-items-center">
                <img src="<?php echo base_url('assets/img/log.png'); ?>" alt="Logo CREDITOS VALU" class="rounded-circle mr-3 shadow" style="width: 60px; height: 60px; border: 3px solid rgba(255,255,255,0.3);">
                <div>
                  <h3 class="mb-1 font-weight-bold"><i class="fas fa-credit-card mr-2"></i>Préstamo #<?php echo $loan->id ?></h3>
                  <h6 class="mb-0 opacity-75"><i class="fas fa-user mr-1"></i>Cliente: <?= $loan->customer_name; ?></h6>
                </div>
              </div>
            </div>
            <div class="col-md-4 text-right">
              <div class="mb-2">
                <?php echo $loan->status ?
                  '<span class="badge badge-warning badge-lg px-4 py-2 shadow"><i class="fas fa-clock mr-2"></i>ACTIVO</span>' :
                  '<span class="badge badge-success badge-lg px-4 py-2 shadow"><i class="fas fa-check-circle mr-2"></i>COMPLETADO</span>';
                ?>
              </div>
              <div class="text-white-50 small">
                <i class="fas fa-calendar-alt mr-1"></i>Generado: <?php echo date('d/m/Y H:i', strtotime('now')); ?>
              </div>
              <div>
                <button class="btn btn-light btn-sm mr-2 shadow-sm" onclick="window.print();">
                  <i class="fas fa-print mr-1"></i>Imprimir
                </button>
                <button class="btn btn-light btn-sm shadow-sm" onclick="window.history.back();">
                  <i class="fas fa-arrow-left mr-1"></i>Volver
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Información General del Préstamo -->
      <div class="card mb-4 shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0 font-weight-bold"><i class="fas fa-info-circle mr-2"></i>Información General del Préstamo</h4>
        </div>
        <div class="card-body">
          <!-- Información Principal -->
          <div class="row mb-4">
            <div class="col-lg-6 mb-3">
              <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-8">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Monto del Crédito</div>
                      <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format($loan->credit_amount, 2); ?></div>
                      <div class="text-xs text-muted">Capital principal</div>
                    </div>
                    <div class="col-4 text-right">
                      <i class="fas fa-dollar-sign fa-2x text-primary opacity-75"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 mb-3">
              <div class="card border-left-success shadow h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-8">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cuota Mensual</div>
                      <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format($loan->fee_amount, 2); ?></div>
                      <div class="text-xs text-muted">Pago mensual</div>
                    </div>
                    <div class="col-4 text-right">
                      <i class="fas fa-coins fa-2x text-success opacity-75"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Detalles del Préstamo -->
          <div class="row mb-4">
            <div class="col-md-6 mb-3">
              <div class="card shadow h-100">
                <div class="card-body">
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <i class="fas fa-percentage fa-2x text-warning mb-2"></i>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Tasa de Interés</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($loan->interest_amount, 2); ?>%</div>
                        <div class="text-xs text-muted">Calculada</div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <i class="fas fa-hashtag fa-2x text-info mb-2"></i>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">N° de Cuotas</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $loan->num_fee; ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card shadow h-100">
                <div class="card-body">
                  <div class="row">
                    <div class="col-6">
                      <div class="text-center">
                        <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Fecha Inicio</div>
                        <div class="text-sm font-weight-bold"><?php echo date('d/m/Y', strtotime($loan->date)); ?></div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-center">
                        <i class="fas fa-money-bill-wave fa-2x text-secondary mb-2"></i>
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Frecuencia</div>
                        <div class="text-sm font-weight-bold"><?php echo ucfirst($loan->payment_m); ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resumen Financiero -->
          <div class="row">
            <div class="col-12">
              <div class="card border-left-info shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-chart-line mr-2"></i>Resumen Financiero</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="text-center">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total a Pagar</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format($loan->credit_amount + ($loan->credit_amount * $loan->interest_amount / 100), 2); ?></div>
                        <div class="text-xs text-muted">Capital + Intereses</div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="text-center">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Interés Total</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format($loan->credit_amount * $loan->interest_amount / 100, 2); ?></div>
                        <div class="text-xs text-muted">Solo intereses</div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="text-center">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Moneda</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo strtoupper($loan->short_name); ?></div>
                        <div class="text-xs text-muted">Tipo de moneda</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
          
      
      <style>
      /* Estilos para impresión */
      @media print {
        /* Expandir modal a página completa */
        .modal-dialog {
          max-width: 100% !important;
          margin: 0 !important;
          width: 100% !important;
        }

        .modal-content {
          border: none !important;
          box-shadow: none !important;
          border-radius: 0 !important;
        }

        /* Ocultar elementos innecesarios */
        .modal-header .d-flex,
        .btn,
        .close {
          display: none !important;
        }

        /* Ajustar header para impresión */
        .modal-header {
          background: #343a40 !important;
          color: white !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
          padding: 15px !important;
          border-bottom: 2px solid #000 !important;
        }

        .modal-title {
          font-size: 18px !important;
          font-weight: bold !important;
          margin: 0 !important;
        }

        /* Logo en impresión */
        .modal-header img {
          width: 30px !important;
          height: 30px !important;
          margin-right: 10px !important;
          border-radius: 4px !important;
        }

        /* Ajustar body del modal */
        .modal-body {
          padding: 20px !important;
        }

        /* Estilos para las tarjetas */
        .card {
          border: 1px solid #000 !important;
          margin-bottom: 20px !important;
          page-break-inside: avoid !important;
        }

        .card-header {
          background: #f8f9fa !important;
          border-bottom: 1px solid #000 !important;
          padding: 10px 15px !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        .card-header h6 {
          font-size: 14px !important;
          font-weight: bold !important;
          margin: 0 !important;
        }

        .card-body {
          padding: 15px !important;
        }

        /* Tabla de cuotas */
        .table {
          font-size: 11px !important;
          margin-bottom: 0 !important;
        }

        .table th {
          background: #343a40 !important;
          color: white !important;
          border: 1px solid #000 !important;
          padding: 8px 4px !important;
          font-size: 10px !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        .table td {
          border: 1px solid #000 !important;
          padding: 6px 4px !important;
          font-size: 10px !important;
        }

        .table-hover tbody tr:hover {
          background: transparent !important;
        }

        /* Badges */
        .badge {
          font-size: 9px !important;
          padding: 3px 6px !important;
          border: 1px solid #000 !important;
        }

        .badge-lg {
          font-size: 10px !important;
          padding: 4px 8px !important;
        }

        /* Progress bar */
        .progress {
          height: 20px !important;
          background: #e9ecef !important;
          border: 1px solid #000 !important;
        }

        .progress-bar {
          background: #28a745 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        /* Iconos */
        .fas {
          font-size: 12px !important;
        }

        /* Texto y fuentes */
        body {
          font-size: 12px !important;
          line-height: 1.4 !important;
        }

        h4, h5, h6 {
          font-size: 14px !important;
          margin-bottom: 8px !important;
        }

        h4 {
          font-size: 16px !important;
        }

        .font-weight-bold {
          font-weight: bold !important;
        }

        /* Espaciado */
        .mb-1 { margin-bottom: 0.25rem !important; }
        .mb-2 { margin-bottom: 0.5rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }

        .mt-3 { margin-top: 1rem !important; }

        .p-3 { padding: 1rem !important; }

        /* Evitar cortes de página */
        .row {
          page-break-inside: avoid !important;
        }

        .card {
          page-break-inside: avoid !important;
        }

        .table tr {
          page-break-inside: avoid !important;
        }

        /* Asegurar que el contenido se imprima completamente */
        * {
          -webkit-print-color-adjust: exact !important;
          color-adjust: exact !important;
        }

        /* Ocultar elementos de Bootstrap que no se necesitan */
        .modal-backdrop {
          display: none !important;
        }

        /* Ajustar anchos de columnas para impresión */
        .col-lg-6, .col-md-12, .col-12, .col-md-6 {
          flex: 0 0 50% !important;
          max-width: 50% !important;
        }

        .col-lg-3, .col-md-6, .col-6 {
          flex: 0 0 25% !important;
          max-width: 25% !important;
        }

        .col-4 {
          flex: 0 0 33.333333% !important;
          max-width: 33.333333% !important;
        }

        /* Ajustar elementos del panel de progreso para impresión */
        .progress {
          height: 20px !important;
          background: #e9ecef !important;
          border: 1px solid #000 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        .progress-bar {
          background: #28a745 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        /* Ajustar cards del resumen visual */
        .bg-success, .bg-warning, .bg-danger {
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        /* Ajustar badges del estado */
        .badge-success, .badge-warning, .badge-danger, .badge-info, .badge-primary {
          border: 1px solid #000 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }

        /* Ajustar números formateados */
        .text-success, .text-primary, .text-warning, .text-info {
          color: #000 !important;
        }
      }
      </style>

      <!-- Detalle de Cuotas -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="fas fa-list-ol mr-2"></i>Detalle de Cuotas</h5>
        </div>
        <div class="card-body">
         <div class="table-responsive">
           <table class="table table-striped table-hover">
             <thead class="thead-dark">
               <tr>
                 <th class="text-center">N°</th>
                 <th class="text-center">Fecha Vencimiento</th>
                 <th class="text-right">Valor Cuota</th>
                 <th class="text-center">Estado</th>
                 <th class="text-center">Estado de Pago</th>
                 <th class="text-center">Días Restantes</th>
               </tr>
             </thead>
              <tbody>
                <?php
                if ($items) {
                  $i = 0;
                  $today = date('Y-m-d');
                  foreach ($items as $item) {
                    $i++;
                    $date_diff = strtotime($item->date) - strtotime($today);
                    $days_remaining = floor($date_diff / (60 * 60 * 24));

                    // Determinar estado y colores
                    $row_class = '';
                    $status_badge = '';
                    $payment_status_badge = '';
                    $days_display = '';

                    // Verificar si es una cuota condonada
                    $is_waived = isset($item->extra_payment) && $item->extra_payment == 3;

                    // Obtener valores de pagos con valores por defecto
                    $interest_paid = isset($item->interest_paid) ? (float)$item->interest_paid : 0;
                    $capital_paid = isset($item->capital_paid) ? (float)$item->capital_paid : 0;
                    $item_status = isset($item->status) ? (int)$item->status : 1;

                    if ($is_waived) {
                      // Cuota condonada
                      $row_class = 'table-info';
                      $status_badge = '<span class="badge badge-info"><i class="fas fa-hand-holding-usd mr-1"></i>Condonado</span>';
                      $payment_status_badge = '<span class="badge badge-info"><i class="fas fa-hand-holding-usd mr-1"></i>Condonado</span>';
                      $days_display = '<span class="text-info font-weight-bold">Liberado</span>';
                    } elseif ($item_status === 0) {
                      // Ya pagada completamente (status = 0)
                      $row_class = 'table-success';
                      $status_badge = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Pagada</span>';
                      $payment_status_badge = '<span class="badge badge-success"><i class="fas fa-check-double mr-1"></i>Pagada</span>';
                      $days_display = '<span class="text-muted">-</span>';
                    } elseif ($item_status === 3) {
                      // Pago parcial (status = 3)
                      $row_class = 'table-warning';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-adjust mr-1"></i>Pago Parcial</span>';
                      $payment_status_badge = '<span class="badge badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Pago Parcial</span>';
                      $days_display = '<span class="text-warning font-weight-bold">' . ($days_remaining >= 0 ? $days_remaining . ' días' : abs($days_remaining) . ' días atraso') . '</span>';
                    } elseif ($item_status === 4) {
                      // Pago no completo explícito (status = 4)
                      $row_class = 'table-warning';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Pago no completo</span>';
                      $payment_status_badge = '<span class="badge badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Pago no completo</span>';
                      $days_display = '<span class="text-warning font-weight-bold">' . ($days_remaining >= 0 ? $days_remaining . ' días' : abs($days_remaining) . ' días atraso') . '</span>';
                    } elseif ($item_status > 0 && ($interest_paid > 0 || $capital_paid > 0)) {
                      // Pago parcial: status pendiente pero con pagos realizados (fallback para casos donde status no está actualizado)
                      $row_class = 'table-warning';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-adjust mr-1"></i>Pago Parcial</span>';
                      $payment_status_badge = '<span class="badge badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Pago Parcial</span>';
                      $days_display = '<span class="text-warning font-weight-bold">' . ($days_remaining >= 0 ? $days_remaining . ' días' : abs($days_remaining) . ' días atraso') . '</span>';
                    } elseif ($days_remaining < 0) {
                      $row_class = 'table-danger';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pendiente</span>';
                      $payment_status_badge = '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Vencida</span>';
                      $days_display = '<span class="text-danger font-weight-bold">' . abs($days_remaining) . ' días atraso</span>';
                    } elseif ($days_remaining == 0) {
                      $row_class = 'table-warning';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pendiente</span>';
                      $payment_status_badge = '<span class="badge badge-warning"><i class="fas fa-calendar-day mr-1"></i>Vence Hoy</span>';
                      $days_display = '<span class="text-warning font-weight-bold">Vence hoy</span>';
                    } else {
                      $row_class = 'table-light';
                      $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pendiente</span>';
                      $payment_status_badge = '<span class="badge badge-info"><i class="fas fa-calendar-alt mr-1"></i>Próxima</span>';
                      $days_display = '<span class="text-info font-weight-bold">' . $days_remaining . ' días</span>';
                    }

                    echo '<tr class="' . $row_class . '">';
                    echo '<td class="text-center font-weight-bold">' . $i . '</td>';
                    echo '<td class="text-center"><strong>' . date('d/m/Y', strtotime($item->date)) . '</strong></td>';
                    echo '<td class="text-right"><strong class="text-primary">$ ' . number_format($item->fee_amount, 2) . '</strong></td>';
                    echo '<td class="text-center">' . $status_badge . '</td>';
                    echo '<td class="text-center">' . $payment_status_badge . '</td>';
                    echo '<td class="text-center">' . $days_display . '</td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="6" class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-2"></i><br><span class="text-muted">No hay cuotas disponibles</span></td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

         <?php if ($items && count($items) > 0): ?>
         <!-- Panel de Resumen -->
         <div class="row mt-4">
           <!-- Barra de Progreso -->
           <div class="col-12 mb-4">
             <div class="card">
               <div class="card-header">
                 <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Progreso del Préstamo</h5>
               </div>
               <div class="card-body">
                 <?php
                 $total_cuotas = count($items);
                 $cuotas_pagadas = count(array_filter($items, function($item) { return !$item->status && (!isset($item->extra_payment) || $item->extra_payment != 3); }));
                 $cuotas_condonadas = count(array_filter($items, function($item) { return isset($item->extra_payment) && $item->extra_payment == 3; }));
                 $cuotas_pendientes = $total_cuotas - $cuotas_pagadas - $cuotas_condonadas;
                 $cuotas_vencidas = count(array_filter($items, function($item) use ($today) {
                   return $item->status && strtotime($item->date) < strtotime($today) && (!isset($item->extra_payment) || $item->extra_payment != 3);
                 }));
                 $porcentaje_pagado = $total_cuotas > 0 ? round((($cuotas_pagadas + $cuotas_condonadas) / $total_cuotas) * 100, 1) : 0;
                 ?>
                 <div class="d-flex justify-content-between align-items-center mb-3">
                   <span class="h6 mb-0">Completado: <?php echo $porcentaje_pagado; ?>%</span>
                   <span class="badge badge-primary"><?php echo $cuotas_pagadas + $cuotas_condonadas; ?>/<?php echo $total_cuotas; ?> cuotas</span>
                 </div>
                 <div class="progress mb-4">
                   <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentaje_pagado; ?>%" aria-valuenow="<?php echo $porcentaje_pagado; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                 </div>
                 <div class="row text-center">
                   <div class="col-md-3 mb-3">
                     <div class="p-3 bg-success text-white rounded">
                       <i class="fas fa-check-circle fa-2x mb-2"></i>
                       <div class="h4 font-weight-bold"><?php echo $cuotas_pagadas; ?></div>
                       <small>Pagadas</small>
                     </div>
                   </div>
                   <div class="col-md-3 mb-3">
                     <div class="p-3 bg-info text-white rounded">
                       <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                       <div class="h4 font-weight-bold"><?php echo $cuotas_condonadas; ?></div>
                       <small>Condonadas</small>
                     </div>
                   </div>
                   <div class="col-md-3 mb-3">
                     <div class="p-3 bg-warning text-white rounded">
                       <i class="fas fa-clock fa-2x mb-2"></i>
                       <div class="h4 font-weight-bold"><?php echo $cuotas_pendientes - $cuotas_vencidas; ?></div>
                       <small>Pendientes</small>
                     </div>
                   </div>
                   <div class="col-md-3 mb-3">
                     <div class="p-3 bg-danger text-white rounded">
                       <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                       <div class="h4 font-weight-bold"><?php echo $cuotas_vencidas; ?></div>
                       <small>Vencidas</small>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
           </div>

           <!-- Próximo Pago -->
           <div class="col-md-6 mb-4">
             <div class="card h-100">
               <div class="card-header bg-info text-white">
                 <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Próximo Pago</h5>
               </div>
               <div class="card-body">
                 <?php
                 $proxima_cuota = null;
                 foreach ($items as $item) {
                   if ($item->status) {
                     $proxima_cuota = $item;
                     break;
                   }
                 }
                 if ($proxima_cuota):
                   $dias_restantes = floor((strtotime($proxima_cuota->date) - strtotime($today)) / (60 * 60 * 24));
                 ?>
                   <div class="d-flex justify-content-between align-items-center mb-3">
                     <div>
                       <h6 class="mb-1">Cuota #<?php echo array_search($proxima_cuota, $items) + 1; ?></h6>
                       <small class="text-muted">Vence: <?php echo date('d/m/Y', strtotime($proxima_cuota->date)); ?></small>
                     </div>
                     <div class="text-right">
                       <h4 class="text-primary font-weight-bold">$<?php echo number_format($proxima_cuota->fee_amount, 2); ?></h4>
                     </div>
                   </div>
                   <div class="text-center">
                     <?php if ($dias_restantes < 0): ?>
                       <span class="badge badge-danger px-3 py-2"><i class="fas fa-exclamation-triangle mr-1"></i>Vencida hace <?php echo abs($dias_restantes); ?> días</span>
                     <?php elseif ($dias_restantes == 0): ?>
                       <span class="badge badge-warning px-3 py-2"><i class="fas fa-calendar-day mr-1"></i>¡Vence HOY!</span>
                     <?php else: ?>
                       <span class="badge badge-info px-3 py-2"><i class="fas fa-calendar-alt mr-1"></i><?php echo $dias_restantes; ?> días restantes</span>
                     <?php endif; ?>
                   </div>
                 <?php else: ?>
                   <div class="text-center py-4">
                     <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                     <h5 class="text-success font-weight-bold">¡Préstamo Completado!</h5>
                     <p class="text-muted">Todas las cuotas han sido pagadas</p>
                   </div>
                 <?php endif; ?>
               </div>
             </div>
           </div>

           <!-- Estado Financiero -->
           <div class="col-md-6 mb-4">
             <div class="card h-100">
               <div class="card-header bg-success text-white">
                 <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i>Estado Financiero</h5>
               </div>
               <div class="card-body">
                 <div class="row">
                   <div class="col-4 text-center mb-3">
                     <div class="p-3 bg-light rounded">
                       <i class="fas fa-coins fa-2x text-success mb-2"></i>
                       <div class="h5 font-weight-bold text-success">$<?php echo number_format($cuotas_pagadas * $loan->fee_amount, 2); ?></div>
                       <small class="text-muted">Pagado</small>
                     </div>
                   </div>
                   <div class="col-4 text-center mb-3">
                     <div class="p-3 bg-light rounded">
                       <i class="fas fa-hand-holding-usd fa-2x text-info mb-2"></i>
                       <div class="h5 font-weight-bold text-info">$<?php echo number_format($cuotas_condonadas * $loan->fee_amount, 2); ?></div>
                       <small class="text-muted">Condonado</small>
                     </div>
                   </div>
                   <div class="col-4 text-center mb-3">
                     <div class="p-3 bg-light rounded">
                       <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                       <div class="h5 font-weight-bold text-warning">$<?php echo number_format($cuotas_pendientes * $loan->fee_amount, 2); ?></div>
                       <small class="text-muted">Pendiente</small>
                     </div>
                   </div>
                 </div>
                 <hr>
                 <div class="text-center">
                   <small class="text-muted">Total del Préstamo</small>
                   <div class="h4 font-weight-bold text-primary">$<?php echo number_format($loan->credit_amount + ($loan->credit_amount * $loan->interest_amount / 100), 2); ?></div>
                   <?php if ($cuotas_condonadas > 0): ?>
                   <div class="mt-2">
                     <small class="text-info"><i class="fas fa-info-circle mr-1"></i>Este préstamo incluye <?php echo $cuotas_condonadas; ?> cuota(s) condonada(s) por liquidación anticipada</small>
                   </div>
                   <?php endif; ?>
                 </div>
               </div>
             </div>
           </div>
         </div>
         <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
