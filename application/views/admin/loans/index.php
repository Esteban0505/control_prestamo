<?php if ($this->session->flashdata('msg')): ?>
    <div class="alert alert-success alert-dismissible fade show text-center mb-4" role="alert">
        <?= $this->session->flashdata('msg') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif ?>

<!-- Dashboard de Estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Préstamos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($loans); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pagados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                          <?php
                          $paid_count = 0;
                          foreach($loans as $loan) {
                            if (($loan->balance_amount ?? 0) == 0) $paid_count++;
                          }
                          echo $paid_count;
                          ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                          <?php
                          $pending_count = 0;
                          foreach($loans as $loan) {
                            if (($loan->balance_amount ?? 0) > 0) $pending_count++;
                          }
                          echo $pending_count;
                          ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monto Total</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $total = 0;
                            foreach($loans as $loan) {
                                $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                                $total += $loan->credit_amount + abs($interest_amount);
                            }
                            echo "$ " . format_to_display($total, false);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="m-0 font-weight-bold text-primary">Listar Préstamos</h6>
        <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/loans/edit'); ?>"><i class="fas fa-plus-circle fa-sm"></i> Nuevo préstamo</a>
    </div>
    <div class="card-body">

        <!-- Versión móvil: filtros primero -->
        <div class="d-block d-md-none">
          <!-- Filtros y búsqueda -->
          <div class="row mb-3">
            <div class="col-12">
              <form method="GET" action="<?php echo site_url('admin/loans/index'); ?>">
                <div class="row mb-3">
                  <div class="col-12 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por cliente o ID préstamo" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                  </div>
                  <div class="col-6 mb-2">
                    <select name="status" class="form-control form-control-sm">
                      <option value="">Todos los estados</option>
                      <option value="1" <?php echo (isset($status_filter) && $status_filter == '1') ? 'selected' : ''; ?>>Pendientes</option>
                      <option value="0" <?php echo (isset($status_filter) && $status_filter == '0') ? 'selected' : ''; ?>>Pagados</option>
                    </select>
                  </div>
                  <div class="col-6 mb-2">
                    <select name="per_page" class="form-control form-control-sm">
                      <option value="25" <?php echo (isset($per_page) && $per_page == 25) ? 'selected' : ''; ?>>25 por página</option>
                      <option value="50" <?php echo (isset($per_page) && $per_page == 50) ? 'selected' : ''; ?>>50 por página</option>
                      <option value="100" <?php echo (isset($per_page) && $per_page == 100) ? 'selected' : ''; ?>>100 por página</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <div class="d-flex justify-content-between">
                      <button type="submit" class="btn btn-primary btn-sm flex-fill mr-1">
                        <i class="fas fa-search"></i> Buscar
                      </button>
                      <a href="<?php echo site_url('admin/loans/index'); ?>" class="btn btn-secondary btn-sm flex-fill ml-1">
                        <i class="fas fa-times"></i> Limpiar
                      </a>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Vista de tarjetas para móviles -->
          <div class="row">
            <?php if(count($loans)): foreach($loans as $loan): ?>
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="card-title mb-1">#<?php echo $loan->id; ?> - <?php echo $loan->customer; ?></h6>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-dollar-sign"></i>
                                        <?php
                                        $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                                        echo "Crédito: $ " . format_to_display($loan->credit_amount, false);
                                        ?>
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-percentage"></i>
                                        <?php
                                        switch ($loan->amortization_type) {
                                            case 'francesa': echo 'Francesa'; break;
                                            case 'estaunidense': echo 'Estadounidense'; break;
                                            case 'mixta': echo 'Mixta'; break;
                                            default: echo ucfirst($loan->amortization_type);
                                        }
                                        ?>
                                    </p>
                                    <p class="card-text small mb-1">
                                        <i class="fas fa-chart-line"></i>
                                        Pagado: $ <?php echo format_to_display($loan->total_paid ?? 0, false); ?>
                                        (<?php echo $loan->installments_paid ?? 0; ?>/<?php echo ($loan->installments_paid ?? 0) + ($loan->installments_pending ?? 0); ?> cuotas)
                                    </p>
                                    <p class="card-text small mb-0">
                                        <strong>Saldo: $ <?php echo format_to_display($loan->balance_amount ?? 0, false); ?></strong>
                                        <?php
                                        $has_partial = false;
                                        if (isset($loan->loan_items)) {
                                          foreach ($loan->loan_items as $item) {
                                            $interest_paid = $item->interest_paid ?? 0;
                                            $capital_paid = $item->capital_paid ?? 0;
                                            $interest_amount = $item->interest_amount ?? 0;
                                            $capital_amount = $item->capital_amount ?? 0;
                                            if (($interest_paid > 0 && $interest_paid < $interest_amount) ||
                                                ($capital_paid > 0 && $capital_paid < $capital_amount)) {
                                              $has_partial = true;
                                              break;
                                            }
                                          }
                                        }
                                        if ($has_partial) {
                                          echo '<br><small class="text-info">Pago parcial aplicado</small>';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="col-4 text-right">
                                    <span class="badge badge-<?php echo ($loan->balance_amount ?? 0) > 0 ? 'warning' : 'success'; ?> mb-2">
                                        <?php echo ($loan->balance_amount ?? 0) > 0 ? 'Pendiente' : 'Pagado'; ?>
                                    </span>
                                    <br>
                                    <a href="<?php echo site_url('admin/loans/view/'.$loan->id); ?>" class="btn btn-sm btn-info" data-toggle="ajax-modal">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                            <h5>No existen préstamos</h5>
                            <p class="text-muted">Agregue un nuevo préstamo para comenzar.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Versión desktop: filtros en línea -->
        <div class="d-none d-md-block">
          <!-- Filtros y búsqueda -->
          <div class="row mb-3">
            <div class="col-12">
              <form method="GET" action="<?php echo site_url('admin/loans/index'); ?>" class="form-inline">
                <div class="form-group mr-3">
                  <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por cliente o ID préstamo" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                </div>
                <div class="form-group mr-3">
                  <select name="status" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    <option value="1" <?php echo (isset($status_filter) && $status_filter == '1') ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="0" <?php echo (isset($status_filter) && $status_filter == '0') ? 'selected' : ''; ?>>Pagados</option>
                  </select>
                </div>
                <div class="form-group mr-3">
                  <select name="per_page" class="form-control form-control-sm">
                    <option value="25" <?php echo (isset($per_page) && $per_page == 25) ? 'selected' : ''; ?>>25 por página</option>
                    <option value="50" <?php echo (isset($per_page) && $per_page == 50) ? 'selected' : ''; ?>>50 por página</option>
                    <option value="100" <?php echo (isset($per_page) && $per_page == 100) ? 'selected' : ''; ?>>100 por página</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo site_url('admin/loans/index'); ?>" class="btn btn-secondary btn-sm">
                  <i class="fas fa-times"></i> Limpiar
                </a>
              </form>
            </div>
          </div>
        </div>

    <!-- Información de paginación -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
      <div class="text-muted mb-2 mb-md-0">
        <?php if(isset($total_records) && $total_records > 0): ?>
          <span class="d-block d-md-inline">
            Mostrando <?php echo count($loans); ?> de <?php echo number_format($total_records); ?> préstamos
          </span>
          <?php if(isset($current_page) && isset($total_pages)): ?>
            <span class="d-block d-md-inline ml-md-2">
              (Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>)
            </span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabla de préstamos - Versión Tablet (md to xl) -->
    <div class="d-none d-md-block d-xl-none">
      <div class="table-responsive">
        <table class="table table-hover" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th class="text-center" style="width: 60px;"><i class="fas fa-hashtag"></i></th>
              <th><i class="fas fa-user mr-1"></i> Cliente</th>
              <th class="text-center"><i class="fas fa-info-circle mr-1"></i> Estado</th>
              <th class="text-center" style="width: 80px;"><i class="fas fa-cogs"></i></th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($loans)): ?>
              <?php foreach($loans as $loan): ?>
                <tr>
                  <td class="text-center"><strong>#<?php echo htmlspecialchars($loan->id); ?></strong></td>
                  <td>
                    <div class="font-weight-bold"><?php echo htmlspecialchars($loan->customer); ?></div>
                    <div class="small text-muted">
                      Total: $ <?php
                        $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                        echo format_to_display($loan->credit_amount + abs($interest_amount), false);
                      ?>
                    </div>
                    <div class="small text-success">
                      Pagado: $ <?php echo format_to_display($loan->paid_amount ?? 0, false); ?>
                      (<?php echo $loan->installments_paid ?? 0; ?>/<?php echo ($loan->installments_paid ?? 0) + ($loan->installments_pending ?? 0); ?>)
                    </div>
                    <div class="small text-warning">
                      Saldo: $ <?php echo format_to_display($loan->balance_amount ?? 0, false); ?>
                    </div>
                  </td>

                  <td class="text-center">
                    <?php
                    $has_partial_payments = false;
                    $total_partial = 0;
                    $total_pending = 0;

                    // Verificar si hay cuotas parcialmente pagadas
                    if (isset($loan->loan_items)) {
                      foreach ($loan->loan_items as $item) {
                        $interest_paid = $item->interest_paid ?? 0;
                        $capital_paid = $item->capital_paid ?? 0;
                        $interest_amount = $item->interest_amount ?? 0;
                        $capital_amount = $item->capital_amount ?? 0;

                        if (($interest_paid > 0 && $interest_paid < $interest_amount) ||
                            ($capital_paid > 0 && $capital_paid < $capital_amount)) {
                          $has_partial_payments = true;
                          $total_partial += $interest_paid + $capital_paid;
                        }

                        if ($item->status == 1) {
                          $total_pending += ($interest_amount - $interest_paid) + ($capital_amount - $capital_paid);
                        }
                      }
                    }

                    if ($has_partial_payments && ($loan->balance_amount ?? 0) > 0) {
                      echo '<span class="badge badge-info">';
                      echo '<i class="fas fa-adjust mr-1"></i> Parcial';
                      echo '</span>';
                      echo '<br><small class="text-muted">Pago parcial aplicado</small>';
                    } elseif (($loan->balance_amount ?? 0) > 0) {
                      echo '<span class="badge badge-warning">';
                      echo '<i class="fas fa-clock mr-1"></i> Pendiente';
                      echo '</span>';
                      if (isset($loan->installments_pending)) {
                        echo '<br><small class="text-muted">' . $loan->installments_pending . ' cuotas pendientes</small>';
                      }
                    } else {
                      echo '<span class="badge badge-success">';
                      echo '<i class="fas fa-check-circle mr-1"></i> Pagado';
                      echo '</span>';
                    }
                    ?>
                  </td>

                  <td class="text-center">
                    <a href="<?php echo site_url('admin/loans/view/'.htmlspecialchars($loan->id)); ?>" class="btn btn-sm btn-outline-info" data-toggle="ajax-modal">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center py-5">
                  <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No existen préstamos</h5>
                  <p class="text-muted">Haga clic en "Nuevo préstamo" para agregar el primer préstamo.</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tabla de préstamos - Versión Desktop (xl+) -->
    <div class="d-none d-xl-block">
      <div class="table-responsive">
        <table class="table table-hover" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th class="text-center" style="width: 80px;"><i class="fas fa-hashtag"></i></th>
              <th><i class="fas fa-user mr-1"></i> Cliente</th>
              <th class="text-right"><i class="fas fa-dollar-sign mr-1"></i> Monto Crédito</th>
              <th class="text-right"><i class="fas fa-percentage mr-1"></i> Monto Interés</th>
              <th class="text-right"><i class="fas fa-calculator mr-1"></i> Monto Total</th>
              <th class="text-right"><i class="fas fa-chart-line mr-1"></i> Pagado</th>
              <th class="text-right"><i class="fas fa-chart-line mr-1"></i> Saldo</th>
              <th class="text-center"><i class="fas fa-chart-line mr-1"></i> Amortización</th>
              <th class="text-center"><i class="fas fa-info-circle mr-1"></i> Estado</th>
              <th class="text-center" style="width: 120px;"><i class="fas fa-cogs mr-1"></i> Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($loans)): ?>
              <?php foreach($loans as $loan): ?>
                <tr>
                  <td class="text-center"><strong>#<?php echo htmlspecialchars($loan->id); ?></strong></td>
                  <td>
                    <div class="font-weight-bold"><?php echo htmlspecialchars($loan->customer); ?></div>
                  </td>

                  <td class="text-right text-success font-weight-bold">
                    <?php echo "$ " . format_to_display($loan->credit_amount, false); ?>
                  </td>

                  <td class="text-right text-warning">
                    <?php
                      $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                      echo "$ " . format_to_display(abs($interest_amount), false);
                    ?>
                  </td>

                  <td class="text-right text-primary font-weight-bold">
                    <?php
                      echo "$ " . format_to_display($loan->credit_amount + abs($interest_amount), false);
                    ?>
                  </td>

                  <td class="text-right text-success">
                    <?php echo "$ " . format_to_display($loan->paid_amount ?? 0, false); ?>
                  </td>

                  <td class="text-right text-warning font-weight-bold">
                    <?php echo "$ " . format_to_display($loan->balance_amount ?? 0, false); ?>
                  </td>

                  <td class="text-center">
                    <span class="badge badge-secondary">
                      <?php
                        switch ($loan->amortization_type) {
                          case 'francesa': echo 'Francesa'; break;
                          case 'estaunidense': echo 'Estadounidense'; break;
                          case 'mixta': echo 'Mixta'; break;
                          default: echo ucfirst(htmlspecialchars($loan->amortization_type));
                        }
                      ?>
                    </span>
                  </td>

                  <td class="text-center">
                    <?php
                    $has_partial_payments = false;
                    $total_partial = 0;
                    $total_pending = 0;

                    // Verificar si hay cuotas parcialmente pagadas
                    if (isset($loan->loan_items)) {
                      foreach ($loan->loan_items as $item) {
                        $interest_paid = $item->interest_paid ?? 0;
                        $capital_paid = $item->capital_paid ?? 0;
                        $interest_amount = $item->interest_amount ?? 0;
                        $capital_amount = $item->capital_amount ?? 0;

                        if (($interest_paid > 0 && $interest_paid < $interest_amount) ||
                            ($capital_paid > 0 && $capital_paid < $capital_amount)) {
                          $has_partial_payments = true;
                          $total_partial += $interest_paid + $capital_paid;
                        }

                        if ($item->status == 1) {
                          $total_pending += ($interest_amount - $interest_paid) + ($capital_amount - $capital_paid);
                        }
                      }
                    }

                    if ($has_partial_payments && ($loan->balance_amount ?? 0) > 0) {
                      echo '<span class="badge badge-info">';
                      echo '<i class="fas fa-adjust mr-1"></i> Parcial';
                      echo '</span>';
                      echo '<br><small class="text-muted">Pago parcial aplicado</small>';
                    } elseif (($loan->balance_amount ?? 0) > 0) {
                      echo '<span class="badge badge-warning">';
                      echo '<i class="fas fa-clock mr-1"></i> Pendiente';
                      echo '</span>';
                      if (isset($loan->installments_pending)) {
                        echo '<br><small class="text-muted">' . $loan->installments_pending . ' cuotas pendientes</small>';
                      }
                    } else {
                      echo '<span class="badge badge-success">';
                      echo '<i class="fas fa-check-circle mr-1"></i> Pagado';
                      echo '</span>';
                    }
                    ?>
                  </td>

                  <td class="text-center">
                    <a href="<?php echo site_url('admin/loans/view/'.htmlspecialchars($loan->id)); ?>" class="btn btn-sm btn-outline-info" data-toggle="ajax-modal">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center py-5">
                  <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No existen préstamos</h5>
                  <p class="text-muted">Haga clic en "Nuevo préstamo" para agregar el primer préstamo.</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>


    <!-- Paginación moderna -->
    <?php if(isset($total_pages) && $total_pages > 1): ?>
      <!-- Versión móvil: paginación simplificada -->
      <div class="d-block d-md-none">
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted small">
            Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
          </div>
          <div>
            <?php if($current_page > 1): ?>
              <a class="btn btn-sm btn-outline-primary mr-1" href="<?php echo site_url('admin/loans/index?page=' . ($current_page - 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary mr-1" disabled>
                <i class="fas fa-chevron-left"></i>
              </button>
            <?php endif; ?>

            <?php if($current_page < $total_pages): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('admin/loans/index?page=' . ($current_page + 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled>
                <i class="fas fa-chevron-right"></i>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Versión desktop: paginación completa -->
      <div class="d-none d-md-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">
          Mostrando página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
        </div>
        <nav aria-label="Navegación de préstamos">
          <ul class="pagination pagination-sm mb-0">
            <!-- Anterior -->
            <?php if($current_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/loans/index?page=' . ($current_page - 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <i class="fas fa-chevron-left"></i> Anterior
                </a>
              </li>
            <?php else: ?>
              <li class="page-item disabled">
                <span class="page-link"><i class="fas fa-chevron-left"></i> Anterior</span>
              </li>
            <?php endif; ?>

            <!-- Páginas -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if($start_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/loans/index?page=1' . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">1</a>
              </li>
              <?php if($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
              <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo site_url('admin/loans/index?page=' . $i . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if($end_page < $total_pages): ?>
              <?php if($end_page < $total_pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/loans/index?page=' . $total_pages . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $total_pages; ?>
                </a>
              </li>
            <?php endif; ?>

            <!-- Siguiente -->
            <?php if($current_page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/loans/index?page=' . ($current_page + 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  Siguiente <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            <?php else: ?>
              <li class="page-item disabled">
                <span class="page-link">Siguiente <i class="fas fa-chevron-right"></i></span>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="myModal" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>

