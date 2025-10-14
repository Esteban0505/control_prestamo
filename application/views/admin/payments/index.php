<!-- Dashboard de Estadísticas de Pagos -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pagos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($payments); ?></div>
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
                            <?php
                            $total = 0;
                            foreach($payments as $py) {
                                $total += $py->fee_amount;
                            }
                            echo "$ " . number_format($total, 2);
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
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pago Promedio</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $avg = count($payments) > 0 ? $total / count($payments) : 0;
                            echo "$ " . number_format($avg, 2);
                            ?>
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
                            <?php
                            $latest = !empty($payments) ? max(array_column($payments, 'pay_date')) : 'N/A';
                            echo $latest != 'N/A' ? date('d/m/Y', strtotime($latest)) : 'N/A';
                            ?>
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
                      $ <?php echo number_format($py->fee_amount, 2); ?>
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

    <!-- Tabla para desktop -->
    <div class="d-none d-md-block">
      <div class="table-responsive">
        <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
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
                  $ <?php echo number_format($py->fee_amount, 2); ?>
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
    </div>
  </div>
</div>