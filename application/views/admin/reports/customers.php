<div class="card shadow mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-3">
    <h6 class="m-0 font-weight-bold text-primary">Reporte global por clientes</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Dni</th>
            <th>Nombre completo</th>
            <th>Total préstamos</th>
            <th>Monto total prestado</th>
            <th>Monto total pagado</th>
            <th>Fecha último préstamo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($customers)): foreach($customers as $ct): ?>
            <tr>
              <td><?php echo $ct->dni ?></td>
              <td><?php echo $ct->nombre_cliente ?></td>
              <td><?php echo $ct->total_prestamos ?></td>
              <td><?php echo number_format($ct->total_prestado, 0, ',', '.') ?></td>
              <td><?php echo number_format($ct->total_pagado, 0, ',', '.') ?></td>
              <td><?php echo $ct->ultimo_prestamo ?></td>
              <td>
                <a href="<?php echo site_url('admin/reports/customer_pdf/'.$ct->id); ?>" target="_blank" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-eye fa-sm"></i> Ver préstamos</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center">No existen Clientes con préstamo</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>