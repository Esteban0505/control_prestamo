<div class="card shadow mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-3">
    <h6 class="m-0 font-weight-bold text-primary">Resumen General de Prestamos</h6>
    <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="#" onclick="imp_credits(imp1);"><i class="fas fa-print fa-sm"></i> Imprimir</a>
  </div>
  <div class="card-body">

    <div class="form-row">
      <div class="form-group col-4">

        <label class="small mb-1" for="exampleFormControlSelect2">Tipo de moneda</label>
        <select class="form-control" id="coin_type" name="coin_type">
          <option value="0"> Seleccionar moneda</option>
          <?php foreach ($coins as $c): ?>
            <option value="<?php echo $c->id ?>" data-symbol="<?php echo $c->short_name ?>"><?php echo $c->name.' ('.strtoupper($c->short_name).')' ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="table-responsive" id="imp1">
      <table class="table" width="100%" cellspacing="0">
        <thead class="thead-dark">
          <tr>
            <th>Descripción</th>
            <th class="text-right">Cantidad</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Total Credito</td>
            <td class="text-right" id="cr">0</td>
          </tr>
          <tr>
            <td>Total Credito con Interes</td>
            <td class="text-right" id="cr_interest">0</td>
          </tr>
          <tr>
            <td>Total Credito cancelado con interes</td>
            <td class="text-right" id="cr_interestPaid">0</td>
          </tr>
          <tr>
            <td>Total Credito por cobrar con interes</td>
            <td class="text-right" id="cr_interestPay">0</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reporte: Pagos por Cliente -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Pagos por Cliente</h6>
      <div class="table-responsive mt-2">
        <table class="table table-striped table-bordered" id="tbl_payments_by_customer">
          <thead class="thead-dark">
            <tr>
              <th>Cliente</th>
              <th class="text-right"># Pagos</th>
              <th class="text-right">Total Pagado</th>
              <th>Último Pago</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($per_customer_payments)) { foreach ($per_customer_payments as $row) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row->customer_name); ?></td>
              <td class="text-right"><?php echo (int)$row->payments_count; ?></td>
              <td class="text-right"><?php echo number_format((float)$row->total_paid, 2, ',', '.'); ?></td>
              <td><?php echo $row->last_payment ? $row->last_payment : '-'; ?></td>
            </tr>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reporte: Usuario con más cobranzas -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Usuarios con más Cobranzas</h6>
      <div class="table-responsive mt-2">
        <table class="table table-striped table-bordered" id="tbl_top_collectors">
          <thead class="thead-dark">
            <tr>
              <th>Usuario</th>
              <th class="text-right"># Cobranzas</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($top_collectors)) { foreach ($top_collectors as $row) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row->user_name); ?></td>
              <td class="text-right"><?php echo (int)$row->payments_count; ?></td>
            </tr>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reporte: Cliente con mayor racha de pagos -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Cliente con Mayor Racha de Pagos</h6>
      <div class="table-responsive mt-2">
        <table class="table table-striped table-bordered" id="tbl_longest_streak">
          <thead class="thead-dark">
            <tr>
              <th>Cliente</th>
              <th class="text-right">Racha Máxima (cuotas seguidas)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($longest_streak)) { ?>
            <tr>
              <td><?php echo htmlspecialchars($longest_streak['customer_name']); ?></td>
              <td class="text-right"><?php echo (int)$longest_streak['max_streak']; ?></td>
            </tr>
            <?php } else { ?>
            <tr>
              <td colspan="2">Sin datos de pagos registrados.</td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reporte: Comisiones por Cobrador -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Reporte de Cobranzas y Comisiones</h6>
      <div class="table-responsive mt-2">
        <table class="table table-striped table-bordered" id="tbl_commissions">
          <thead class="thead-dark">
            <tr>
              <th>Usuario</th>
              <th>Rol</th>
              <th class="text-right">Total Cobranzas</th>
              <th class="text-right">Total Ganado (40%)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($collector_commissions)) { foreach ($collector_commissions as $r) { ?>
            <tr>
              <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?></td>
              <td><?php echo htmlspecialchars($r->role); ?></td>
              <td class="text-right"><?php echo (int)$r->total_cobranzas; ?></td>
              <td class="text-right">$<?php echo number_format((float)$r->total_commission, 2, ',', '.'); ?></td>
            </tr>
            <?php } } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>