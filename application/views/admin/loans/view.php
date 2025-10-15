
<div class="modal-dialog modal-xl">
  <div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <!-- Logo y título -->
      <div class="d-flex align-items-center">
        <img src="<?php echo base_url('assets/img/log.png'); ?>" alt="Logo CREDITOS VALU" style="width: 40px; height: 40px; margin-right: 15px; border-radius: 8px;">
        <div>
          <h5 class="modal-title mb-1" id="staticBackdropLabel">
            <i class="fas fa-credit-card mr-2"></i>Préstamo #<?php echo $loan->id ?>
          </h5>
          <small class="text-white-50">
            <i class="fas fa-user mr-1"></i>Cliente: <?= $loan->customer_name; ?>
          </small>
        </div>
      </div>
      <div class="d-flex">
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
          <div class="row">
            <!-- Columna Izquierda -->
            <div class="col-lg-6 col-md-12">
              <div class="row mb-3">
                <div class="col-6">
                  <div class="text-center p-3 bg-light rounded">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <h6 class="font-weight-bold text-success mb-1">Monto del Crédito</h6>
                    <h4 class="text-success font-weight-bold">$<?= number_format($loan->credit_amount, 2); ?></h4>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-3 bg-light rounded">
                    <i class="fas fa-percentage fa-2x text-warning mb-2"></i>
                    <h6 class="font-weight-bold text-warning mb-1">Tasa de Interés</h6>
                    <h4 class="text-warning font-weight-bold"><?php echo $loan->interest_amount; ?>%</h4>
                  </div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-6">
                  <div class="text-center p-3 bg-light rounded">
                    <i class="fas fa-hashtag fa-2x text-info mb-2"></i>
                    <h6 class="font-weight-bold text-info mb-1">Número de Cuotas</h6>
                    <h4 class="text-info font-weight-bold"><?php echo $loan->num_fee; ?></h4>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center p-3 bg-light rounded">
                    <i class="fas fa-coins fa-2x text-primary mb-2"></i>
                    <h6 class="font-weight-bold text-primary mb-1">Monto por Cuota</h6>
                    <h4 class="text-primary font-weight-bold">$<?= number_format($loan->fee_amount, 2); ?></h4>
                  </div>
                </div>
              </div>
            </div>

            <!-- Columna Derecha -->
            <div class="col-lg-6 col-md-12">
              <div class="card border-0 bg-gradient-primary text-white mb-3">
                <div class="card-body">
                  <h6 class="font-weight-bold mb-3">
                    <i class="fas fa-chart-line mr-2"></i>Resumen Financiero
                  </h6>
                  <div class="row">
                    <div class="col-6">
                      <small>Monto Total a Pagar:</small>
                      <h5 class="font-weight-bold">$<?= number_format($loan->credit_amount + ($loan->credit_amount * $loan->interest_amount / 100), 2); ?></h5>
                    </div>
                    <div class="col-6">
                      <small>Interés Total:</small>
                      <h5 class="font-weight-bold">$<?= number_format($loan->credit_amount * $loan->interest_amount / 100, 2); ?></h5>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-6">
                  <strong><i class="fas fa-calendar-alt mr-1"></i>Fecha del Crédito:</strong><br>
                  <span class="badge badge-light text-dark"><?php echo date('d/m/Y', strtotime($loan->date)); ?></span>
                </div>
                <div class="col-6">
                  <strong><i class="fas fa-money-bill-wave mr-1"></i>Forma de Pago:</strong><br>
                  <span class="badge badge-light text-dark"><?php echo $loan->payment_m; ?></span>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-6">
                  <strong><i class="fas fa-globe mr-1"></i>Moneda:</strong><br>
                  <span class="badge badge-light text-dark"><?php echo strtoupper($loan->short_name); ?></span>
                </div>
                <div class="col-6">
                  <strong><i class="fas fa-info-circle mr-1"></i>Estado:</strong><br>
                  <?php echo $loan->status ?
                    '<span class="badge badge-danger badge-lg"><i class="fas fa-clock mr-1"></i>Pendiente</span>' :
                    '<span class="badge badge-success badge-lg"><i class="fas fa-check-circle mr-1"></i>Pagado</span>';
                  ?>
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
        .col-lg-6, .col-md-12 {
          flex: 0 0 50% !important;
          max-width: 50% !important;
        }
      
        .col-lg-3, .col-md-6 {
          flex: 0 0 25% !important;
          max-width: 25% !important;
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
                  <th class="text-center" width="8%"><i class="fas fa-hashtag"></i></th>
                  <th class="text-center" width="25%"><i class="fas fa-calendar-day mr-1"></i>Fecha de Pago</th>
                  <th class="text-right" width="25%"><i class="fas fa-dollar-sign mr-1"></i>Monto a Pagar</th>
                  <th class="text-center" width="20%"><i class="fas fa-info-circle mr-1"></i>Estado</th>
                  <th class="text-center" width="22%"><i class="fas fa-clock mr-1"></i>Días Restantes</th>
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
                      '<span class="badge badge-warning badge-lg"><i class="fas fa-clock mr-1"></i>Pendiente</span>' :
                      '<span class="badge badge-success badge-lg"><i class="fas fa-check-circle mr-1"></i>Cancelado</span>';

                    $date_diff = strtotime($item->date) - strtotime($today);
                    $days_remaining = floor($date_diff / (60 * 60 * 24));

                    $days_display = '';
                    if ($item->status) { // Solo mostrar días si está pendiente
                      if ($days_remaining < 0) {
                        $days_display = '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Vencido (' . abs($days_remaining) . ' días)</span>';
                      } elseif ($days_remaining == 0) {
                        $days_display = '<span class="badge badge-warning"><i class="fas fa-calendar-day mr-1"></i>Hoy</span>';
                      } else {
                        $days_display = '<span class="badge badge-info"><i class="fas fa-calendar-alt mr-1"></i>' . $days_remaining . ' días</span>';
                      }
                    } else {
                      $days_display = '<span class="badge badge-secondary"><i class="fas fa-check mr-1"></i>Pagado</span>';
                    }

                    $row_class = '';
                    if ($item->status && $days_remaining < 0) {
                      $row_class = 'table-danger';
                    } elseif ($item->status && $days_remaining == 0) {
                      $row_class = 'table-warning';
                    }

                    echo '<tr class="' . $row_class . '">';
                    echo '<td class="text-center font-weight-bold">' . $i . '</td>';
                    echo '<td class="text-center">' . date('d/m/Y', strtotime($item->date)) . '</td>';
                    echo '<td class="text-right"><strong class="text-primary">$ ' . number_format($item->fee_amount, 2) . '</strong></td>';
                    echo '<td class="text-center">' . $status . '</td>';
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
          <div class="row mt-3">
            <div class="col-md-6">
              <div class="card border-info">
                <div class="card-body p-3">
                  <h6 class="text-info mb-2"><i class="fas fa-chart-bar mr-1"></i>Resumen de Pagos</h6>
                  <?php
                  $total_cuotas = count($items);
                  $cuotas_pagadas = count(array_filter($items, function($item) { return !$item->status; }));
                  $cuotas_pendientes = $total_cuotas - $cuotas_pagadas;
                  $porcentaje_pagado = $total_cuotas > 0 ? round(($cuotas_pagadas / $total_cuotas) * 100, 1) : 0;
                  ?>
                  <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentaje_pagado; ?>%" aria-valuenow="<?php echo $porcentaje_pagado; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo $porcentaje_pagado; ?>%
                    </div>
                  </div>
                  <small class="text-muted">
                    <i class="fas fa-check-circle text-success mr-1"></i><?php echo $cuotas_pagadas; ?> pagadas
                    <i class="fas fa-clock text-warning ml-3 mr-1"></i><?php echo $cuotas_pendientes; ?> pendientes
                  </small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-warning">
                <div class="card-body p-3">
                  <h6 class="text-warning mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Próximo Pago</h6>
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
                    <p class="mb-1"><strong>Cuota #<?php echo array_search($proxima_cuota, $items) + 1; ?></strong></p>
                    <p class="mb-1">Fecha: <strong><?php echo date('d/m/Y', strtotime($proxima_cuota->date)); ?></strong></p>
                    <p class="mb-1">Monto: <strong class="text-primary">$<?php echo number_format($proxima_cuota->fee_amount, 2); ?></strong></p>
                    <p class="mb-0">
                      <?php if ($dias_restantes < 0): ?>
                        <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Vencida (<?php echo abs($dias_restantes); ?> días)</span>
                      <?php elseif ($dias_restantes == 0): ?>
                        <span class="badge badge-warning"><i class="fas fa-calendar-day mr-1"></i>Vence hoy</span>
                      <?php else: ?>
                        <span class="badge badge-info"><i class="fas fa-calendar-alt mr-1"></i><?php echo $dias_restantes; ?> días restantes</span>
                      <?php endif; ?>
                    </p>
                  <?php else: ?>
                    <p class="text-success mb-0"><i class="fas fa-check-circle mr-1"></i>Todas las cuotas han sido pagadas</p>
                  <?php endif; ?>
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
