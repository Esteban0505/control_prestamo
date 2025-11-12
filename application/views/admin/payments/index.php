<!-- Dashboard de Estadísticas de Pagos -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pagos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_payments']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-receipt fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monto Total</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            $ <?php echo number_format($stats['total_amount'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pago Promedio</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            $ <?php echo number_format($stats['avg_amount'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Último Pago</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['latest_payment_date'] ? date('d/m/Y', strtotime($stats['latest_payment_date'])) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-3">
    <h6 class="m-0 font-weight-bold text-primary">Listar Pagos</h6>
    <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/payments/edit'); ?>"><i class="fas fa-plus-circle fa-sm"></i> Realizar Pago</a>
  </div>
  <div class="card-body">

    <!-- Versión móvil: filtros en columnas -->
    <div class="d-block d-md-none">
      <div class="row mb-3">
        <div class="col-12">
          <form method="GET" action="<?php echo site_url('admin/payments/index'); ?>">
            <div class="row mb-3">
              <div class="col-12 mb-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, DNI o ID préstamo" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
              </div>
              <div class="col-6 mb-2">
                <input type="date" name="date_from" class="form-control form-control-sm" placeholder="dd/mm/aaaa" value="<?php echo isset($date_from) ? $date_from : ''; ?>">
              </div>
              <div class="col-6 mb-2">
                <input type="date" name="date_to" class="form-control form-control-sm" placeholder="dd/mm/aaaa" value="<?php echo isset($date_to) ? $date_to : ''; ?>">
              </div>
              <div class="col-12 mb-2">
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
                  <a href="<?php echo site_url('admin/payments/index'); ?>" class="btn btn-secondary btn-sm flex-fill ml-1">
                    <i class="fas fa-times"></i> Limpiar
                  </a>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Versión desktop: filtros en línea -->
    <div class="d-none d-md-block">
      <div class="row mb-3">
        <div class="col-md-12">
          <form method="GET" action="<?php echo site_url('admin/payments/index'); ?>" class="form-inline">
            <div class="form-group mr-3">
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, DNI o ID préstamo" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
            </div>
            <div class="form-group mr-3">
              <input type="date" name="date_from" class="form-control form-control-sm" placeholder="Desde" value="<?php echo isset($date_from) ? $date_from : ''; ?>">
            </div>
            <div class="form-group mr-3">
              <input type="date" name="date_to" class="form-control form-control-sm" placeholder="Hasta" value="<?php echo isset($date_to) ? $date_to : ''; ?>">
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
            <a href="<?php echo site_url('admin/payments/index'); ?>" class="btn btn-secondary btn-sm">
              <i class="fas fa-times"></i> Limpiar
            </a>
          </form>
        </div>
      </div>
    </div>

    <!-- Información de paginación -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="text-muted">
        <?php if(isset($total_records) && $total_records > 0): ?>
          Mostrando <?php echo count($payments); ?> de <?php echo number_format($total_records); ?> pagos
          <?php if(isset($current_page) && isset($total_pages)): ?>
            (Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>)
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Vista de tarjetas para móviles -->
    <div class="d-block d-md-none">
      <div class="row">
        <?php if(count($payments)): foreach($payments as $py): ?>
          <div class="col-12 mb-3">
            <div class="card shadow-sm border-left-success">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-8">
                    <h6 class="card-title mb-1"><?php echo $py->name_cst; ?></h6>
                    <p class="card-text small text-muted mb-1">
                      <i class="fas fa-id-card"></i> <?php echo $py->dni; ?>
                    </p>
                    <p class="card-text small text-muted mb-1">
                      <i class="fas fa-credit-card"></i> Préstamo #<?php echo $py->loan_id; ?> - Cuota <?php echo $py->num_quota; ?>
                    </p>
                    <p class="card-text small mb-0">
                      <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($py->pay_date)); ?>
                    </p>
                  </div>
                  <div class="col-4 text-right">
                    <div class="h5 text-success font-weight-bold mb-0">
                      $ <?php echo number_format($py->fee_amount, 2, ',', '.'); ?>
                    </div>
                    <small class="text-muted">Pagado</small>
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
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <h5>No existen pagos</h5>
                <p class="text-muted">Realice el primer pago para comenzar.</p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabla de pagos -->
    <div class="table-responsive">
      <table class="table table-hover" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th><i class="fas fa-id-card mr-1"></i> N. Cédula</th>
              <th><i class="fas fa-user mr-1"></i> Cliente</th>
              <th><i class="fas fa-hashtag mr-1"></i> N° Préstamo</th>
              <th><i class="fas fa-list-ol mr-1"></i> N° Cuota</th>
              <th><i class="fas fa-dollar-sign mr-1"></i> Monto Pagado</th>
              <th><i class="fas fa-calendar-alt mr-1"></i> Fecha de Pago</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($payments)): foreach($payments as $py): ?>
              <tr>
                <td><strong><?php echo $py->dni; ?></strong></td>
                <td>
                  <div class="d-flex align-items-center">
                    <div>
                      <div class="font-weight-bold"><?php echo $py->name_cst; ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-primary">
                    <i class="fas fa-credit-card mr-1"></i>
                    #<?php echo $py->loan_id; ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-info">
                    <i class="fas fa-list-ol mr-1"></i>
                    Cuota <?php echo $py->num_quota; ?>
                  </span>
                </td>
                <td class="text-success font-weight-bold h6">
                  $ <?php echo number_format($py->fee_amount, 2, ',', '.'); ?>
                </td>
                <td>
                  <i class="fas fa-calendar-check text-success mr-1"></i>
                  <?php echo date('d/m/Y H:i', strtotime($py->pay_date)); ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-5">
                  <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                  <h5 class="text-muted">No existen pagos registrados</h5>
                  <p class="text-muted">Los pagos realizados aparecerán aquí.</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
    </div>


    <!-- Paginación moderna -->
    <?php if(isset($total_pages) && $total_pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">
          Mostrando página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
        </div>
        <nav aria-label="Navegación de pagos">
          <ul class="pagination pagination-sm mb-0">
            <!-- Anterior -->
            <?php if($current_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/payments/index?page=' . ($current_page - 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($date_from) && $date_from ? '&date_from=' . $date_from : '') . (isset($date_to) && $date_to ? '&date_to=' . $date_to : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
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
                <a class="page-link" href="<?php echo site_url('admin/payments/index?page=1' . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($date_from) && $date_from ? '&date_from=' . $date_from : '') . (isset($date_to) && $date_to ? '&date_to=' . $date_to : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">1</a>
              </li>
              <?php if($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
              <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo site_url('admin/payments/index?page=' . $i . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($date_from) && $date_from ? '&date_from=' . $date_from : '') . (isset($date_to) && $date_to ? '&date_to=' . $date_to : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if($end_page < $total_pages): ?>
              <?php if($end_page < $total_pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/payments/index?page=' . $total_pages . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($date_from) && $date_from ? '&date_from=' . $date_from : '') . (isset($date_to) && $date_to ? '&date_to=' . $date_to : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $total_pages; ?>
                </a>
              </li>
            <?php endif; ?>

            <!-- Siguiente -->
            <?php if($current_page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/payments/index?page=' . ($current_page + 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($date_from) && $date_from ? '&date_from=' . $date_from : '') . (isset($date_to) && $date_to ? '&date_to=' . $date_to : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
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