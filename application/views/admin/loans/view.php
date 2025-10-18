
<div class="modal-dialog modal-xl">
  <div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <!-- Logo y título -->
      <div class="d-flex align-items-center flex-grow-1">
        <img src="<?php echo base_url('assets/img/log.png'); ?>" alt="Logo CREDITOS VALU" style="width: 40px; height: 40px; margin-right: 15px; border-radius: 8px;">
        <div class="flex-grow-1">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h5 class="modal-title mb-1" id="staticBackdropLabel">
                <i class="fas fa-credit-card mr-2"></i>Préstamo #<?php echo $loan->id ?>
              </h5>
              <small class="text-white-50">
                <i class="fas fa-user mr-1"></i>Cliente: <?= $loan->customer_name; ?>
              </small>
            </div>
            <!-- Indicador de Estado Prominente -->
            <div class="ml-3">
              <?php echo $loan->status ?
                '<span class="badge badge-warning badge-lg px-3 py-2"><i class="fas fa-clock mr-2"></i>ACTIVO</span>' :
                '<span class="badge badge-success badge-lg px-3 py-2"><i class="fas fa-check-circle mr-2"></i>COMPLETADO</span>';
              ?>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex ml-3">
        <button type="button" class="btn btn-light btn-sm mr-2" onclick="window.print();" title="Imprimir">
          <i class="fas fa-print"></i>
        </button>
        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal" aria-hidden="true" title="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="modal-body">

      <!-- Información General del Préstamo -->
      <div class="card shadow mb-4 border-primary">
        <div class="card-header bg-primary text-white">
          <h6 class="m-0 font-weight-bold">
            <i class="fas fa-info-circle mr-2"></i>Información General del Préstamo
          </h6>
        </div>
        <div class="card-body">
          <!-- Información Principal - Agrupada por importancia -->
          <div class="row mb-4">
            <!-- Monto y Cuotas - Información crítica -->
            <div class="col-lg-6 col-md-12 mb-3">
              <div class="row">
                <div class="col-6">
                  <div class="text-center p-3 bg-success text-white rounded shadow-sm h-100">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h6 class="font-weight-bold mb-1">Monto del Crédito</h6>
                    <h4 class="font-weight-bold">$<?= number_format($loan->credit_amount, 2); ?></h4>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-3 bg-primary text-white rounded shadow-sm h-100">
                    <i class="fas fa-coins fa-2x mb-2"></i>
                    <h6 class="font-weight-bold mb-1">Cuota Mensual</h6>
                    <h4 class="font-weight-bold">$<?= number_format($loan->fee_amount, 2); ?></h4>
                  </div>
                </div>
              </div>
            </div>

            <!-- Condiciones del Préstamo -->
            <div class="col-lg-6 col-md-12 mb-3">
              <div class="row">
                <div class="col-6">
                  <div class="text-center p-3 bg-warning text-white rounded shadow-sm h-100">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                    <h6 class="font-weight-bold mb-1">Tasa de Interés</h6>
                    <h4 class="font-weight-bold"><?php echo $loan->interest_amount; ?>%</h4>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-3 bg-info text-white rounded shadow-sm h-100">
                    <i class="fas fa-hashtag fa-2x mb-2"></i>
                    <h6 class="font-weight-bold mb-1">Número de Cuotas</h6>
                    <h4 class="font-weight-bold"><?php echo $loan->num_fee; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Resumen Financiero y Detalles Operativos -->
          <div class="row">
            <!-- Resumen Financiero -->
            <div class="col-lg-6 col-md-12 mb-3">
              <div class="card border-0 bg-gradient-primary text-white shadow-sm">
                <div class="card-body">
                  <h6 class="font-weight-bold mb-3">
                    <i class="fas fa-chart-line mr-2"></i>Resumen Financiero
                  </h6>
                  <div class="row text-center">
                    <div class="col-6">
                      <div class="border-right border-white">
                        <small class="d-block">Total a Pagar</small>
                        <h5 class="font-weight-bold mb-0">$<?= number_format($loan->credit_amount + ($loan->credit_amount * $loan->interest_amount / 100), 2); ?></h5>
                      </div>
                    </div>
                    <div class="col-6">
                      <small class="d-block">Interés Total</small>
                      <h5 class="font-weight-bold mb-0">$<?= number_format($loan->credit_amount * $loan->interest_amount / 100, 2); ?></h5>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Detalles Operativos -->
            <div class="col-lg-6 col-md-12">
              <div class="row">
                <!-- Fecha y Forma de Pago -->
                <div class="col-12 mb-3">
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <div class="row text-center">
                        <div class="col-4">
                          <i class="fas fa-calendar-alt fa-2x text-secondary mb-2"></i>
                          <h6 class="font-weight-bold text-secondary mb-1">Fecha Inicio</h6>
                          <span class="badge badge-light text-dark px-2 py-1"><?php echo date('d/m/Y', strtotime($loan->date)); ?></span>
                        </div>
                        <div class="col-4">
                          <i class="fas fa-money-bill-wave fa-2x text-secondary mb-2"></i>
                          <h6 class="font-weight-bold text-secondary mb-1">Frecuencia</h6>
                          <span class="badge badge-light text-dark px-2 py-1"><?php echo $loan->payment_m; ?></span>
                        </div>
                        <div class="col-4">
                          <i class="fas fa-globe fa-2x text-secondary mb-2"></i>
                          <h6 class="font-weight-bold text-secondary mb-1">Moneda</h6>
                          <span class="badge badge-light text-dark px-2 py-1"><?php echo strtoupper($loan->short_name); ?></span>
                        </div>
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
      
        /* Estilos para los boxes de información */
        .bg-light {
          background: #f8f9fa !important;
          border: 1px solid #dee2e6 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }
      
        .bg-gradient-primary {
          background: #007bff !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
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
          height: 15px !important;
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
      
      <style>
      @media print {
        /* Expandir el modal completamente para impresión */
        .modal-dialog {
          max-width: 100% !important;
          margin: 0 !important;
          width: 100% !important;
        }
      
        .modal-content {
          border: none !important;
          box-shadow: none !important;
        }
      
        /* Ocultar elementos innecesarios durante la impresión */
        .modal-header .btn,
        .modal-header button[data-dismiss="modal"] {
          display: none !important;
        }
      
        /* Ajustar el header para impresión */
        .modal-header {
          background-color: #007bff !important;
          color: white !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }
      
        /* Asegurar que el body del modal se imprima completamente */
        .modal-body {
          max-height: none !important;
          overflow: visible !important;
        }
      
        /* Ajustar tamaños de fuente para mejor legibilidad impresa */
        body {
          font-size: 12px !important;
          line-height: 1.4 !important;
        }
      
        .card-header h6 {
          font-size: 14px !important;
          margin-bottom: 0.5rem !important;
        }
      
        .card-body h4 {
          font-size: 16px !important;
        }
      
        .card-body h5 {
          font-size: 14px !important;
        }
      
        .card-body h6 {
          font-size: 13px !important;
        }
      
        /* Ajustar espaciado para impresión */
        .card-body {
          padding: 1rem !important;
        }
      
        .row {
          margin-bottom: 0.5rem !important;
        }
      
        /* Asegurar que las tablas se impriman correctamente */
        .table {
          font-size: 11px !important;
          margin-bottom: 1rem !important;
        }
      
        .table th,
        .table td {
          padding: 0.5rem 0.25rem !important;
          border: 1px solid #dee2e6 !important;
        }
      
        .table-responsive {
          overflow: visible !important;
        }
      
        /* Ajustar badges para impresión */
        .badge {
          font-size: 10px !important;
          padding: 0.25rem 0.5rem !important;
        }
      
        /* Asegurar que los iconos se impriman */
        .fas,
        .far,
        .fab {
          font-family: 'Font Awesome 5 Free', sans-serif !important;
        }
      
        /* Ocultar elementos de navegación y controles */
        .btn,
        .dropdown,
        .navbar,
        .sidebar {
          display: none !important;
        }
      
        /* Ajustar colores para impresión en blanco y negro */
        .bg-primary {
          background-color: #f8f9fa !important;
          color: #212529 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }
      
        .bg-success {
          background-color: #f8f9fa !important;
          color: #212529 !important;
          -webkit-print-color-adjust: exact;
          color-adjust: exact;
        }
      
        .text-primary {
          color: #212529 !important;
        }
      
        .text-success {
          color: #212529 !important;
        }
      
        .text-warning {
          color: #212529 !important;
        }
      
        .text-info {
          color: #212529 !important;
        }
      
        .text-danger {
          color: #212529 !important;
        }
      
        /* Asegurar que las páginas se rompan correctamente */
        .card {
          page-break-inside: avoid !important;
          margin-bottom: 1rem !important;
        }
      
        .table {
          page-break-inside: auto !important;
        }
      
        .table tr {
          page-break-inside: avoid !important;
          page-break-after: auto !important;
        }
      
        /* Ajustar el título del modal para impresión */
        .modal-title {
          font-size: 18px !important;
          margin-bottom: 0.25rem !important;
        }
      
        /* Asegurar que el contenido se ajuste al ancho de página */
        .container,
        .container-fluid {
          max-width: 100% !important;
          padding: 0 !important;
        }
      
        /* Ocultar sombras y bordes innecesarios */
        .shadow,
        .border-primary,
        .border-success,
        .border-info,
        .border-warning {
          box-shadow: none !important;
          border: 1px solid #dee2e6 !important;
        }
      }
      </style>

      <!-- Detalle de Cuotas -->
      <div class="card shadow border-success">
        <div class="card-header bg-success text-white">
          <h6 class="m-0 font-weight-bold">
            <i class="fas fa-list-ol mr-2"></i>Detalle de Cuotas
          </h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center" width="8%"><i class="fas fa-hashtag"></i> #</th>
                  <th class="text-center" width="20%"><i class="fas fa-calendar-day mr-1"></i>Fecha Vencimiento</th>
                  <th class="text-right" width="20%"><i class="fas fa-dollar-sign mr-1"></i>Valor Cuota</th>
                  <th class="text-center" width="15%"><i class="fas fa-tasks mr-1"></i>Estado</th>
                  <th class="text-center" width="20%"><i class="fas fa-clock mr-1"></i>Estado de Pago</th>
                  <th class="text-center" width="17%"><i class="fas fa-calendar-alt mr-1"></i>Días</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($items) {
                  $i = 0;
                  $today = date('Y-m-d');
                  foreach ($items as $item) {
                    $i++;
                    $status = ($item->status) ?
                      '<span class="badge badge-warning badge-lg px-2 py-1"><i class="fas fa-clock mr-1"></i>Pendiente</span>' :
                      '<span class="badge badge-success badge-lg px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Pagada</span>';

                    $date_diff = strtotime($item->date) - strtotime($today);
                    $days_remaining = floor($date_diff / (60 * 60 * 24));

                    $payment_status = '';
                    $days_display = '';
                    if (!$item->status) { // Ya pagada
                      $payment_status = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-double mr-1"></i>Pagada</span>';
                      $days_display = '<span class="text-muted"><i class="fas fa-check mr-1"></i>-</span>';
                    } elseif ($days_remaining < 0) {
                      $payment_status = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Vencida</span>';
                      $days_display = '<span class="text-danger font-weight-bold">' . abs($days_remaining) . ' días</span>';
                    } elseif ($days_remaining == 0) {
                      $payment_status = '<span class="badge badge-warning px-2 py-1"><i class="fas fa-calendar-day mr-1"></i>Vence Hoy</span>';
                      $days_display = '<span class="text-warning font-weight-bold">0 días</span>';
                    } else {
                      $payment_status = '<span class="badge badge-info px-2 py-1"><i class="fas fa-calendar-alt mr-1"></i>Próxima</span>';
                      $days_display = '<span class="text-info font-weight-bold">' . $days_remaining . ' días</span>';
                    }

                    $row_class = '';
                    if (!$item->status) {
                      $row_class = 'table-success';
                    } elseif ($days_remaining < 0) {
                      $row_class = 'table-danger';
                    } elseif ($days_remaining == 0) {
                      $row_class = 'table-warning';
                    }

                    echo '<tr class="' . $row_class . '">';
                    echo '<td class="text-center font-weight-bold">' . $i . '</td>';
                    echo '<td class="text-center"><strong>' . date('d/m/Y', strtotime($item->date)) . '</strong></td>';
                    echo '<td class="text-right"><strong class="text-primary">$ ' . number_format($item->fee_amount, 2) . '</strong></td>';
                    echo '<td class="text-center">' . $status . '</td>';
                    echo '<td class="text-center">' . $payment_status . '</td>';
                    echo '<td class="text-center">' . $days_display . '</td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-2"></i><br><span class="text-muted">No hay cuotas disponibles</span></td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <?php if ($items && count($items) > 0): ?>
          <!-- Panel de Resumen Visual Mejorado -->
          <div class="row mt-4">
            <!-- Barra de Progreso General -->
            <div class="col-12 mb-3">
              <div class="card border-primary shadow-sm">
                <div class="card-body p-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-primary mb-0"><i class="fas fa-chart-line mr-2"></i>Progreso del Préstamo</h6>
                    <?php
                    $total_cuotas = count($items);
                    $cuotas_pagadas = count(array_filter($items, function($item) { return !$item->status; }));
                    $cuotas_pendientes = $total_cuotas - $cuotas_pagadas;
                    $cuotas_vencidas = count(array_filter($items, function($item) use ($today) {
                      return $item->status && strtotime($item->date) < strtotime($today);
                    }));
                    $porcentaje_pagado = $total_cuotas > 0 ? round(($cuotas_pagadas / $total_cuotas) * 100, 1) : 0;
                    ?>
                    <span class="badge badge-primary badge-lg"><?php echo $porcentaje_pagado; ?>% Completado</span>
                  </div>
                  <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentaje_pagado; ?>%" aria-valuenow="<?php echo $porcentaje_pagado; ?>" aria-valuemin="0" aria-valuemax="100">
                      <strong><?php echo $cuotas_pagadas; ?>/<?php echo $total_cuotas; ?> cuotas</strong>
                    </div>
                  </div>
                  <div class="row text-center">
                    <div class="col-4">
                      <div class="p-2 bg-success text-white rounded">
                        <i class="fas fa-check-circle fa-lg mb-1"></i>
                        <div class="font-weight-bold"><?php echo $cuotas_pagadas; ?></div>
                        <small>Pagadas</small>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 bg-warning text-white rounded">
                        <i class="fas fa-clock fa-lg mb-1"></i>
                        <div class="font-weight-bold"><?php echo $cuotas_pendientes - $cuotas_vencidas; ?></div>
                        <small>Pendientes</small>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 bg-danger text-white rounded">
                        <i class="fas fa-exclamation-triangle fa-lg mb-1"></i>
                        <div class="font-weight-bold"><?php echo $cuotas_vencidas; ?></div>
                        <small>Vencidas</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Próximo Pago y Alertas -->
            <div class="col-md-6">
              <div class="card border-info shadow-sm">
                <div class="card-body p-3">
                  <h6 class="text-info mb-3"><i class="fas fa-calendar-check mr-2"></i>Próximo Pago</h6>
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
                    <div class="d-flex align-items-center mb-2">
                      <div class="flex-grow-1">
                        <strong class="text-primary">Cuota #<?php echo array_search($proxima_cuota, $items) + 1; ?></strong>
                        <br><small class="text-muted">Vence: <?php echo date('d/m/Y', strtotime($proxima_cuota->date)); ?></small>
                      </div>
                      <div class="text-right">
                        <div class="font-weight-bold text-primary">$<?php echo number_format($proxima_cuota->fee_amount, 2); ?></div>
                      </div>
                    </div>
                    <div class="text-center">
                      <?php if ($dias_restantes < 0): ?>
                        <span class="badge badge-danger badge-lg px-3 py-2"><i class="fas fa-exclamation-triangle mr-2"></i>Vencida hace <?php echo abs($dias_restantes); ?> días</span>
                      <?php elseif ($dias_restantes == 0): ?>
                        <span class="badge badge-warning badge-lg px-3 py-2"><i class="fas fa-calendar-day mr-2"></i>¡Vence HOY!</span>
                      <?php else: ?>
                        <span class="badge badge-info badge-lg px-3 py-2"><i class="fas fa-calendar-alt mr-2"></i><?php echo $dias_restantes; ?> días restantes</span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-center py-3">
                      <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                      <h5 class="text-success font-weight-bold">¡Préstamo Completado!</h5>
                      <p class="text-muted mb-0">Todas las cuotas han sido pagadas</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Resumen Financiero -->
            <div class="col-md-6">
              <div class="card border-success shadow-sm">
                <div class="card-body p-3">
                  <h6 class="text-success mb-3"><i class="fas fa-dollar-sign mr-2"></i>Estado Financiero</h6>
                  <div class="row">
                    <div class="col-6 text-center">
                      <div class="p-2 bg-light rounded mb-2">
                        <i class="fas fa-coins fa-2x text-success mb-1"></i>
                        <div class="font-weight-bold text-success">$<?php echo number_format($cuotas_pagadas * $loan->fee_amount, 2); ?></div>
                        <small class="text-muted">Pagado</small>
                      </div>
                    </div>
                    <div class="col-6 text-center">
                      <div class="p-2 bg-light rounded mb-2">
                        <i class="fas fa-clock fa-2x text-warning mb-1"></i>
                        <div class="font-weight-bold text-warning">$<?php echo number_format($cuotas_pendientes * $loan->fee_amount, 2); ?></div>
                        <small class="text-muted">Pendiente</small>
                      </div>
                    </div>
                  </div>
                  <hr class="my-2">
                  <div class="text-center">
                    <small class="text-muted">Total del Préstamo</small>
                    <div class="font-weight-bold text-primary h5">$<?php echo number_format($loan->credit_amount + ($loan->credit_amount * $loan->interest_amount / 100), 2); ?></div>
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
