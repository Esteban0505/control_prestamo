<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-user"></i> Detalles del Cliente
          </h3>
          <div class="card-tools">
            <a href="<?php echo site_url('admin/customers/edit/' . $customer->id); ?>" class="btn btn-primary btn-sm">
              <i class="fas fa-edit"></i> Editar Cliente
            </a>
            <a href="<?php echo site_url('admin/customers'); ?>" class="btn btn-secondary btn-sm">
              <i class="fas fa-arrow-left"></i> Volver
            </a>
          </div>
        </div>
        <div class="card-body">
          <!-- Información del Cliente -->
          <div class="row">
            <div class="col-md-6">
              <h5><i class="fas fa-id-card"></i> Información Personal</h5>
              <table class="table table-borderless">
                <tr>
                  <th width="150">Nombre:</th>
                  <td><?php echo htmlspecialchars($customer->first_name . ' ' . $customer->last_name); ?></td>
                </tr>
                <tr>
                  <th>Cédula:</th>
                  <td><?php echo htmlspecialchars($customer->dni); ?></td>
                </tr>
                <tr>
                  <th>Género:</th>
                  <td><?php echo ucfirst($customer->gender); ?></td>
                </tr>
                <tr>
                  <th>Tipo Cliente:</th>
                  <td>
                    <span class="badge <?php echo $customer->tipo_cliente == 'especial' ? 'badge-success' : 'badge-info'; ?>">
                      <?php echo ucfirst($customer->tipo_cliente); ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Asesor:</th>
                  <td><?php echo htmlspecialchars($customer->user_name ?: 'No asignado'); ?></td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <h5><i class="fas fa-map-marker-alt"></i> Información de Contacto</h5>
              <table class="table table-borderless">
                <tr>
                  <th width="150">Teléfono:</th>
                  <td><?php echo htmlspecialchars($customer->mobile ?: 'No registrado'); ?></td>
                </tr>
                <tr>
                  <th>Tel. Fijo:</th>
                  <td><?php echo htmlspecialchars($customer->phone_fixed ?: 'No registrado'); ?></td>
                </tr>
                <tr>
                  <th>Email:</th>
                  <td><?php echo htmlspecialchars($customer->phone ?: 'No registrado'); ?></td>
                </tr>
                <tr>
                  <th>Dirección:</th>
                  <td><?php echo htmlspecialchars($customer->address ?: 'No registrada'); ?></td>
                </tr>
                <tr>
                  <th>Observaciones:</th>
                  <td><?php echo htmlspecialchars($customer->company ?: 'No registrada'); ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Información Financiera -->
          <div class="row mt-4">
            <div class="col-md-6">
              <h5><i class="fas fa-chart-line"></i> Información Financiera</h5>
              <table class="table table-borderless">
                <tr>
                  <th width="200">Límite de Crédito:</th>
                  <td>$<?php echo number_format($customer->quota, 0, ',', '.'); ?></td>
                </tr>
                <tr>
                  <th>Total Préstamos:</th>
                  <td><?php echo $customer->loan_count; ?></td>
                </tr>
                <tr>
                  <th>Estado Lista Negra:</th>
                  <td>
                    <?php if ($customer->is_blacklisted): ?>
                      <span class="badge badge-danger">En Lista Negra</span>
                    <?php else: ?>
                      <span class="badge badge-success">Cliente Regular</span>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <h5><i class="fas fa-exclamation-triangle"></i> Estado de Mora</h5>
              <?php if (isset($overdue_info) && $overdue_info): ?>
                <table class="table table-borderless">
                  <tr>
                    <th width="200">Cuotas Vencidas:</th>
                    <td><?php echo $overdue_info->cuotas_vencidas; ?></td>
                  </tr>
                  <tr>
                    <th>Total Adeudado:</th>
                    <td>$<?php echo number_format($overdue_info->total_adeudado, 0, ',', '.'); ?></td>
                  </tr>
                  <tr>
                    <th>Máx. Días Atraso:</th>
                    <td><?php echo $overdue_info->max_dias_atraso; ?> días</td>
                  </tr>
                  <tr>
                    <th>Nivel de Riesgo:</th>
                    <td>
                      <?php
                      $risk = $overdue_info->max_dias_atraso >= 60 ? 'Alto' : ($overdue_info->max_dias_atraso >= 30 ? 'Medio' : 'Bajo');
                      $badge_class = $risk == 'Alto' ? 'badge-danger' : ($risk == 'Medio' ? 'badge-warning' : 'badge-success');
                      ?>
                      <span class="badge <?php echo $badge_class; ?>"><?php echo $risk; ?></span>
                    </td>
                  </tr>
                </table>
              <?php else: ?>
                <p class="text-success"><i class="fas fa-check-circle"></i> Sin pagos vencidos</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Resumen de Préstamos -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-chart-bar"></i> Resumen de Préstamos
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#loanSummaryModal">
              <i class="fas fa-eye"></i> Ver Detalles
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Total Préstamos</span>
                  <span class="info-box-number"><?php echo $loan_summary['total_loans']; ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-play"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Activos</span>
                  <span class="info-box-number"><?php echo $loan_summary['active_loans']; ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Pagados</span>
                  <span class="info-box-number"><?php echo $loan_summary['paid_loans']; ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Castigados</span>
                  <span class="info-box-number"><?php echo $loan_summary['penalized_loans']; ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Total Prestado</span>
                  <span class="info-box-number">$<?php echo number_format($loan_summary['total_amount'], 0, ',', '.'); ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="info-box">
                <span class="info-box-icon bg-secondary"><i class="fas fa-balance-scale"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Saldo Pendiente</span>
                  <span class="info-box-number">$<?php echo number_format($loan_summary['total_balance'], 0, ',', '.'); ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Resumen de Préstamos -->
  <div class="modal fade" id="loanSummaryModal" tabindex="-1" role="dialog" aria-labelledby="loanSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loanSummaryModalLabel"><i class="fas fa-chart-bar"></i> Detalles del Resumen de Préstamos</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <h6>Estadísticas Generales</h6>
              <table class="table table-borderless">
                <tr>
                  <th>Total de Préstamos:</th>
                  <td><?php echo $loan_summary['total_loans']; ?></td>
                </tr>
                <tr>
                  <th>Préstamos Activos:</th>
                  <td><?php echo $loan_summary['active_loans']; ?></td>
                </tr>
                <tr>
                  <th>Préstamos Pagados:</th>
                  <td><?php echo $loan_summary['paid_loans']; ?></td>
                </tr>
                <tr>
                  <th>Préstamos Castigados:</th>
                  <td><?php echo $loan_summary['penalized_loans']; ?></td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6>Información Financiera</h6>
              <table class="table table-borderless">
                <tr>
                  <th>Total Prestado:</th>
                  <td>$<?php echo number_format($loan_summary['total_amount'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                  <th>Saldo Pendiente Total:</th>
                  <td>$<?php echo number_format($loan_summary['total_balance'], 0, ',', '.'); ?></td>
                </tr>
                <tr>
                  <th>Porcentaje Pagado:</th>
                  <td>
                    <?php
                    $percentage = $loan_summary['total_amount'] > 0 ? (($loan_summary['total_amount'] - $loan_summary['total_balance']) / $loan_summary['total_amount']) * 100 : 0;
                    echo number_format($percentage, 2) . '%';
                    ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>
          <hr>
          <h6>Historial de Préstamos</h6>
          <?php if (!empty($loans)): ?>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Moneda</th>
                    <th>Asesor</th>
                    <th>Estado</th>
                    <th>Saldo Actual</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($loans as $loan): ?>
                    <tr>
                      <td><?php echo $loan->id; ?></td>
                      <td><?php echo date('d/m/Y', strtotime($loan->date)); ?></td>
                      <td>$<?php echo number_format($loan->credit_amount, 0, ',', '.'); ?></td>
                      <td><?php echo htmlspecialchars($loan->coin_short); ?></td>
                      <td><?php echo htmlspecialchars($loan->asesor_name ?: 'No asignado'); ?></td>
                      <td>
                        <?php
                        $status_class = '';
                        switch ($loan->status) {
                          case 0: $status_class = 'badge-success'; break;
                          case 1: $status_class = 'badge-warning'; break;
                          case 2: $status_class = 'badge-danger'; break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $loan->status_text; ?></span>
                      </td>
                      <td>$<?php echo number_format($loan->current_balance, 0, ',', '.'); ?></td>
                      <td>
                        <a href="<?php echo site_url('admin/loans/view/' . $loan->id); ?>" class="btn btn-info btn-sm" title="Ver Detalles">
                          <i class="fas fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> Este cliente no tiene préstamos registrados.
            </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

</div>

<style>
.info-box {
  box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
  border-radius: .25rem;
  margin-bottom: 1rem;
  background-color: #fff;
  display: flex;
  align-items: center;
  padding: .5rem;
}

.info-box-icon {
  border-top-left-radius: .25rem;
  border-bottom-left-radius: .25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.875rem;
  width: 70px;
  height: 70px;
}

.info-box-content {
  padding: 5px 10px;
  margin-left: 10px;
  display: flex;
  flex-direction: column;
}

.info-box-text {
  text-transform: uppercase;
  font-weight: 700;
  font-size: .6875rem;
  color: #6c757d;
}

.info-box-number {
  font-size: 1.125rem;
  font-weight: 700;
  color: #000;
}
</style>